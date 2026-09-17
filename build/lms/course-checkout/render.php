<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();

if( !$user_id ) {
    $viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/user/view.asset.php' );
    foreach($viewDependencies['dependencies'] as $dpc){
        wp_enqueue_style( $dpc );
    }
    wp_enqueue_script( 'mcv_layer' );
    wp_enqueue_style( 'mine-cloudvod-user-style' );
    wp_enqueue_script( 'mine-cloudvod-user-script' );
    wp_add_inline_script( 'mine-cloudvod-user-script', 'window.onload = function() {
        window?.renderMcvLogin();
    };' );
    return;
}
    //加载样式
    $viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/course-checkout/view.asset.php' );
    wp_register_script(
        'mine-cloudvod-course-checkout-view-script',
        MINECLOUDVOD_URL.'/build/lms/course-checkout/view.js',
        array_merge(['jquery', 'mcv_layer'], $viewDependencies['dependencies']),
        MINECLOUDVOD_VERSION,
        true
    );
    foreach($viewDependencies['dependencies'] as $dpc){
        wp_enqueue_style( $dpc );
    }
    wp_register_style(
        'mine-cloudvod-course-checkout-view-style',
        MINECLOUDVOD_URL.'/build/lms/course-checkout/view.css',
        null,
        MINECLOUDVOD_VERSION
    );
    wp_enqueue_style( 'mine-cloudvod-course-checkout-view-style' );
    $style_color = mcv_get_style_color();
    wp_add_inline_style( 'mine-cloudvod-course-checkout-view-style', $style_color );
    wp_enqueue_script( 'mine-cloudvod-course-checkout-view-script' );

    $order_data = mcv_handle_order_data();
    if( !$order_data ){
        $viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/user/view.asset.php' );
        foreach($viewDependencies['dependencies'] as $dpc){
            wp_enqueue_style( $dpc );
        }
        wp_enqueue_script( 'mcv_layer' );
        wp_enqueue_style( 'mine-cloudvod-user-style' );
        wp_enqueue_script( 'mine-cloudvod-user-script' );
        wp_add_inline_script( 'mine-cloudvod-user-script', 'jQuery(function(){
            window?.renderMcvLogin();
        });' );
        return;
    }
    $payments = $order_data['payments']??[];
    $order_info = $order_data['order_info']??'';
    $price = mcv_lms_get_order_last_amount($order_data['id']);//$order_data['price']??'';
    $orderid = $order_data['id']??0;
    $course_id = $order_data['course']['id']??0;
    $thumbnail = $order_data['course']['thumb']??'';
    $type = $order_data['type']??'course';
    $item_id = $order_data['item_id']??0;

    wp_localize_script( 'mine-cloudvod-course-checkout-view-script', 'mcv_course_data', ['payments' => $payments] );


do_action( 'mcv_lms_before_checkout_body', $order_data );
?>
<div id="mcv-purchase-body">
    <section class="purchase">
        <div class="purchase-detail purchase-item">
            <p class="purchase-detail-title">订单信息确认</p>
            
            <?php if( $type == 'course' ): ?>
            <div class="purchase-detail-wrap">
                <img class="purchase-detail-cover" src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_html($order_data['title']); ?>">
                <div class="purchase-detail-content">
                    <h1 class="purchase-detail-name" title="<?php echo esc_html($order_data['title']); ?>"><?php echo esc_html($order_data['title']); ?></h1>
                    <?php if( isset( $order_data['course']['terms'] ) ) : foreach( $order_data['course']['terms'] as $term ): ?>
                    <a class="purchase-detail-agency" target="_blank" title="<?php echo esc_html($term['name']); ?>" href="<?php echo esc_url($term['url']); ?>"><?php echo esc_html($term['name']); ?></a>
                    <?php endforeach; endif; ?>
                    <span class="purchase-detail-price"><?php echo esc_html(mcv_lms_show_course_price($course_id, true)); ?></span>
                    <div class="purchase-detail-term">
                        <h2 class="purchase-detail-term--title">课程信息</h2>
                        <?php echo wp_kses($order_info, mcsf_allowed_html()); ?>
                    </div>
                </div>
            </div>
            <?php elseif( $type == 'video' ): ?>
            <div class="purchase-detail-wrap">
                <div>
                    <h1 class="purchase-detail-name" title="<?php echo esc_html($order_data['title']); ?>"><?php echo esc_html($order_data['title']); ?></h1>
                    <span class="purchase-detail-price">¥<?php echo esc_html($order_data['price']); ?></span>
                </div>
            </div>
            <?php else: ?>
                <?php 
                /**
                 * 输出不同订单类型的展示信息
                 */
                do_action( 'mcv_checkout_info_'.$type, $order_data, $item_id );
                ?>
            <?php endif; ?>
        </div>
        <div class="purchase-price purchase-item">
            <div class="purchase-price--rows">
                <div class="purchase-price--label">支付方式</div>
                <div class="purchase-price--right">
                <?php if( !$payments ): ?>
                    <div className="purchase-price--right">请启用支付方式</div>
                <?php else: for($i = 0; $i < count( $payments ); $i++ ): 
                    $pm = $payments[$i];
                    $selected = '';
                    $get_payment = isset($_GET['payment']) ? sanitize_text_field(wp_unslash($_GET['payment'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 前台只读支付方式选中态，无写操作
                    if($get_payment){
                        if( $pm['id'] == $get_payment ){
                            $selected = ' selected';
                        }
                    }
                    elseif( $i == 0 ){
                        $selected = ' selected';
                    }
                ?>
                    <div class="purchase-price--channel<?php if( $i == 0 ) echo esc_html($selected); ?>" data-id="<?php echo esc_html($pm['id']); ?>">
                        <i class="<?php echo esc_html($pm['id']); ?>-icon"></i><?php echo esc_html($pm['name']); ?>
                    </div>
                <?php endfor; endif; ?>
                </div>
            </div>
        </div>
        <section class="purchase-bottom purchase-item">
            <div class="purchase-bottom-content">
                <?php
                do_action( 'mcv_checkout_purchase_bottom', $order_data );
                ?>
                <div class="purchase-bottom-rows">
                    <p class="purchase-bottom-label">需支付金额</p>
                    <p class="purchase-bottom-right">￥<span id="mcv-need-pay-amount"><?php echo esc_html($price); ?></span></p>
                </div>
            </div>
            <p class="f-checkbox" id="mcv-purchase-btn"><button class="im-btn purchase-bottom-btn btn-default btn-m">确认支付</button></p>
        </section>
    </section>
    <input type="hidden" value="<?php echo esc_attr($orderid);?>" id="mcv_orderid" />
</div>
<?php
do_action( 'mcv_lms_after_checkout_body', $orderid );