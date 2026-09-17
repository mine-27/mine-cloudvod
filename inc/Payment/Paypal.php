<?php
namespace MineCloudvod\Payment;
use MineCloudvod\Libs\QRcode;

class Paypal extends Base {
    private $is_wechat, $murl, $payment = 'paypal', $error = false;
    public function __construct( ) {

        add_filter( 'mcv_payment_methods', [ $this, 'admin_options' ] );

        add_action('rest_api_init', [$this, 'register_routes']);

        add_filter( 'mcv_order_payment_methods', [ $this, 'add_payment_methods' ] );

    }

    public function add_payment_methods( $methods ){
        if( MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['status']??false ){
            $methods[$this->payment] = __('PayPal', 'mine-cloudvod');
        }
        return $methods;
    }

    public function register_routes(){
        /**
         * Alipay支付回调
         */
        register_rest_route('mine-cloudvod/v1', '/paypal_notify', [
                'methods'             => \WP_REST_Server::ALLMETHODS,
                'callback'            => [$this, 'mcv_paypal_notify'],
                'permission_callback' => '__return_true',
                'args'                => [
                ]
        ]);
    }
    /**
     * 支付回调
     */
    public function mcv_paypal_notify( \WP_REST_Request $request ){
        $tradeno = $request['orderid'];
        $tnTmp = explode( '_', $tradeno );
        $tradeno = $tnTmp[0];

        $mcv_order = get_post( $tradeno );
        if( !$mcv_order )return 'order not found';

        $transaction_id = get_post_meta( $tradeno, '_mcv_order_transaction_id', true );

        $paypal_order_id = sanitize_text_field(wp_unslash($request['token']??''));
        $payer_id = sanitize_text_field(wp_unslash($request['PayerID']??''));
        if( $paypal_order_id && $payer_id && $transaction_id == $paypal_order_id ){
            $paypal = new PayPalPayment();

            $order_amount = mcv_lms_get_order_last_amount( $tradeno );

            // $ipn = $paypal->handleIPN();
            // if( !$ipn ) return 'ipn error';

            $is_valid = $paypal->verifyPayment($paypal_order_id, $payer_id, $order_amount);
            if( !$is_valid ) return 'verify error';

            //付款时间
            $gmt_payment = wp_date( 'Y-m-d H:i:s' );
            
            update_post_meta( $mcv_order->ID, '_mcv_order_status', 'payed' );
            update_post_meta( $mcv_order->ID, '_mcv_order_gmt_payment', $gmt_payment );
            
            $order_items = get_post_meta( $mcv_order->ID, '_mcv_order_items', true );
            mcv_order_update_items( $order_items, $mcv_order->post_author, $mcv_order, $order_amount );
            
            update_post_meta( $mcv_order->ID, '_mcv_order_payment', $this->payment );
            wp_safe_redirect( get_permalink( $order_items[0] ) );
        }
        exit;
    }
    public function admin_options( $payments ){
        $payments[] = array(
            'id'        => $this->payment,
            'type'      => 'fieldset',
            'title'     => 'PayPal',
            'fields'    => array(
                array(
                    'id'    => 'status',
                    'type'  => 'switcher',
                    'title' => __('State', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => 'PayPal官方支付.', 
                ),
                array(
                    'id'    => 'name',
                    'type'  => 'text',
                    'title' => 'PayPal',
                    'dependency' => array('status', '==', true),
                    'default' => 'PayPal',
                ),
                array(
                    'id'    => 'class',
                    'type'  => 'text',
                    'title' => '',
                    'dependency' => array('status', '==', 'none'),
                    'default' => 'MineCloudvod\Payment\Paypal',
                ),
                array(
                    'id'    => 'currency',
                    'type'  => 'select',
                    'title' => __('Currency' , 'mine-cloudvod'),
                    'default' => 'CNY',
                    'options' => [
                        'AUD' => __('Australian dollar', 'mine-cloudvod'),
                        'BRL' => __('Brazilian real', 'mine-cloudvod'),
                        'CAD' => __('Canadian dollar', 'mine-cloudvod'),
                        'CNY' => __('Chinese Renmenbi', 'mine-cloudvod'),
                        'CZK' => __('Czech koruna', 'mine-cloudvod'),
                        'DKK' => __('Danish krone', 'mine-cloudvod'),
                        'EUR' => __('Euro', 'mine-cloudvod'),
                        'HKD' => __('Hong Kong dollar', 'mine-cloudvod'),
                        'HUF' => __('Hungarian forint', 'mine-cloudvod'),
                        'ILS' => __('Israeli new shekel', 'mine-cloudvod'),
                        'JPY' => __('Japanese yen', 'mine-cloudvod'),
                        'MYR' => __('Malaysian ringgit', 'mine-cloudvod'),
                        'MXN' => __('Mexican peso', 'mine-cloudvod'),
                        'TWD' => __('New Taiwan dollar', 'mine-cloudvod'),
                        'NZD' => __('New Zealand dollar', 'mine-cloudvod'),
                        'NOK' => __('Norwegian krone', 'mine-cloudvod'),
                        'PHP' => __('Philippine peso', 'mine-cloudvod'),
                        'PLN' => __('Polish złoty', 'mine-cloudvod'),
                        'GBP' => __('Pound sterling', 'mine-cloudvod'),
                        'RUB' => __('Russian ruble', 'mine-cloudvod'),
                        'SGD' => __('Singapore dollar', 'mine-cloudvod'),
                        'SEK' => __('Swedish krona', 'mine-cloudvod'),
                        'CHF' => __('Swiss franc', 'mine-cloudvod'),
                        'THB' => __('Thai baht', 'mine-cloudvod'),
                        'USD' => __('United States dollar', 'mine-cloudvod'),
                    ],
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'rate',
                    'type'  => 'text',
                    'title' => '汇率',
                    'default' => '1',
                    'after' => '<p>如果你的站点不是使用USD计价，您的PayPal账号又不支持您的币种，请填写相应的汇率，例如站点使用CNY计价，PayPal使用USD计价，则填写7.2（假设当前汇率为1美元=7.2人民币）</p>',
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'apiKey',
                    'type'  => 'text',
                    'title' => __('API Key' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'apiSecret',
                    'type'  => 'text',
                    'title' => __('API Secret' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                    'after' => '<p><a href="https://www.paypal.com/merchantapps/setup/checkout/apicredentials" target="_blank">点击获取密钥</a></p>'
                ),
                array(
                    'id'    => 'sandbox',
                    'type'  => 'switcher',
                    'title' => 'Sandbox',
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => true,
                    'after' => '<p>测试好之后，请关闭沙箱模式，同时记得把密钥换成正式的</p>',
                    'dependency' => array('status', '==', true),
                ),
            ),
        );
        return $payments;
    }

    public function handlePayment( $payAmount, $outTradeNo, $orderName, $course_id, $request ){
        $orderName = substr( $orderName, 0, 126 );
        if( !$orderName ){
            $orderName = 'Order_'. $outTradeNo;
        }
        $oid = $outTradeNo;
        $outTradeNo .= '_' . time();

        $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/paypal_notify?orderid='. $outTradeNo;
        $course_url = get_the_permalink( $course_id );

        $paypal = new PayPalPayment();
        $order = $paypal->createOrder( $payAmount, $outTradeNo, $orderName, $course_url, $notifyUrl );
        $result = [];
        if( isset( $order['message'] ) ){
            $result['msg'] = $order['message'];
        }
        else{ 
            $tourl = '';
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'payer-action') {
                    $tourl = $link['href'];
                    break;
                }
            }
            if( $tourl ){
                $result['tourl'] = $tourl;
                update_post_meta( $oid, '_mcv_order_transaction_id', $order['id'] );
            }
        }
        $result['amount'] = $payAmount;
        return $result;
    }
    /**
     * 返回需要执行的js代码
     */
    public function handleScripts( $request ){
        $script = '';
        // Native
        $script = 'window.open(res.tourl, "_self");';
        
        return $script;
    }
}
class PayPalPayment {
    private $client_id;
    private $client_secret;
    private $api_url;
    private $access_token;
    private $currency;
    private $rate;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $paypal = MINECLOUDVOD_SETTINGS['mcv_payment']['paypal'];
        $this->currency = $paypal['currency'] ?? 'CNY';
        $sandbox = $paypal['sandbox'] ?? true;
        $this->client_id = trim( $paypal['apiKey'] );
        $this->client_secret = trim( $paypal['apiSecret'] );
        $this->rate = $paypal['rate']??1;
        $this->api_url = $sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }
    
    /**
     * 获取访问令牌
     * 
     * @return string
     * @throws Exception
     */
    private function getAccessToken() {
        $url = $this->api_url . '/v1/oauth2/token';
        
        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('获取访问令牌失败: ' . esc_html($response->get_error_message()));
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($http_code == 200 && isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            return $this->access_token;
        } else {
            throw new \Exception("获取PayPal访问令牌失败: " . esc_html($body));
        }
    }
    
    /**
     * 创建支付订单
     * 
     * @param array $purchase_units 购买单元数组
     * @param string $intent 支付意图 (CAPTURE 或 AUTHORIZE)
     * @param array $application_context 应用上下文
     * @return array
     * @throws Exception
     */
    public function createOrder( $payAmount, $outTradeNo, $orderName, $returnUrl, $notifyUrl ) {
        if (empty($this->access_token)) {
            $this->getAccessToken();
        }
        if( $this->rate ){
            $payAmount = round( $payAmount / floatval( $this->rate ), 2 );
        }
        $url = $this->api_url . '/v2/checkout/orders';
        
        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'custom_id' => $outTradeNo,
                    'description' => $orderName,
                    'amount' => array(
                        'currency_code' => $this->currency,
                        'value' => $payAmount,
                        'breakdown' => array(
                            'item_total' => array(
                                'currency_code' => $this->currency,
                                'value' => $payAmount
                            )
                        )
                    ),
                    'items' => array(
                        array(
                            'name' => $orderName,
                            'description' => '',
                            'unit_amount' => array(
                                'currency_code' => $this->currency,
                                'value' => $payAmount
                            ),
                            'quantity' => '1',
                        )
                    )
                )
            ),
            'payment_source' => array(
                'paypal' => [
                    'experience_context' =>[
                        "brand_name" => get_bloginfo('name'),
                        "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                        "landing_page" => "LOGIN",
                        "shipping_preference" => "NO_SHIPPING",
                        "user_action" => "PAY_NOW",
                        "return_url" => $notifyUrl,
                        "locale" => str_replace('_', '-', get_locale()),
                        "cancel_url" => $returnUrl,
                    ]
                ],
            )
        );
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token,
                'PayPal-Request-Id' => uniqid()
            ),
            'body' => json_encode($order_data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('创建订单失败: ' . esc_html($response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }
    
    /**
     * 获取订单详情
     * 
     * @param string $order_id 订单ID
     * @return array
     * @throws Exception
     */
    public function getOrderDetails($order_id) {
        if (empty($this->access_token)) {
            $this->getAccessToken();
        }
        
        $url = $this->api_url . '/v2/checkout/orders/' . $order_id;
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('获取订单详情失败: ' . esc_html($response->get_error_message()));
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code == 200) {
            return json_decode($body, true);
        } else {
            throw new \Exception("获取PayPal订单详情失败: " . esc_html($body));
        }
    }
    
    /**
     * 捕获已授权的支付
     * 
     * @param string $order_id 订单ID
     * @return array
     * @throws Exception
     */
    public function captureOrder($order_id) {
        if (empty($this->access_token)) {
            $this->getAccessToken();
        }
        
        $url = $this->api_url . '/v2/checkout/orders/' . $order_id . '/capture';
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token,
                'Prefer' => 'return=representation'
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('捕获支付失败: ' . esc_html($response->get_error_message()));
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code == 201 || $http_code == 200) {
            return json_decode($body, true);
        } else {
            throw new \Exception("捕获PayPal支付失败: " . esc_html($body));
        }
    }
    
    /**
     * 验证回调请求
     * 
     * @param string $order_id 订单ID
     * @param string $payer_id 支付者ID
     * @param float $expected_amount 预期金额
     * @return bool
     * @throws Exception
     */
    public function verifyPayment($order_id, $payer_id, $expected_amount) {
        try {
            // 获取订单详情
            $order_details = $this->getOrderDetails($order_id);
            
            // 验证订单状态
            if ($order_details['status'] !== 'APPROVED') {
                throw new \Exception('订单状态未批准');
            }
            
            // 验证支付者ID
            if (isset($order_details['payer']['payer_id']) && $order_details['payer']['payer_id'] !== $payer_id) {
                throw new \Exception('支付者ID不匹配');
            }
            
            // 验证金额
            $actual_amount = $order_details['purchase_units'][0]['amount']['value'];
            if (floatval($actual_amount) !== floatval($expected_amount)) {
                throw new \Exception('支付金额不匹配');
            }
            
            // 如果是授权支付，执行捕获
            if ($order_details['intent'] === 'AUTHORIZE') {
                $capture_result = $this->captureOrder($order_id);
                return $capture_result['status'] === 'COMPLETED';
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('PayPal验证错误: ' . esc_html($e->getMessage()));
            return false;
        }
    }
    
    /**
     * 处理IPN通知
     * 
     * @param callable $callback 处理回调函数
     * @return bool
     */
    public function handleIPN($callback=null) {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $post_data = array();
        
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $post_data[$keyval[0]] = urldecode($keyval[1]);
            }
        }
        
        // 准备验证请求
        $req = 'cmd=_notify-validate';
        foreach ($post_data as $key => $value) {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }
        
        // 发送验证请求到PayPal
        $paypal_url = $this->api_url === 'https://api-m.sandbox.paypal.com' 
            ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' 
            : 'https://ipnpb.paypal.com/cgi-bin/webscr';
        
        $args = array(
            'body' => $req,
            'timeout' => 30,
            'httpversion' => '1.1',
            'headers' => array(
                'Connection' => 'Close',
                'Host' => 'www.paypal.com'
            )
        );
        
        $response = wp_remote_post($paypal_url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('IPN验证请求失败: ' . esc_html($response->get_error_message()));
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        
        if (strcmp($response_body, 'VERIFIED') == 0) {
            // IPN验证成功，调用回调函数处理
            if (is_callable($callback)) {
                call_user_func($callback, $post_data);
            }
            return true;
        } elseif (strcmp($response_body, 'INVALID') == 0) {
            // IPN验证失败
            throw new \Exception('IPN验证失败');
            return false;
        }
        
        return false;
    }
}