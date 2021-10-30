<?php
namespace MineCloudvod;

class MineCloudVodAPI {
	private $_url = 'https://cloudvod.zwtt8.com/index.php';

	private $_key, $_secret, $_version;
	private $timeout = 1000;

	public function __construct(){
		$this->_version = 'minecloudvod-' . MINECLOUDVOD_VERSION;
		if(!MINECLOUDVOD_SETTINGS || !isset(MINECLOUDVOD_SETTINGS['siteid']) || !isset(MINECLOUDVOD_SETTINGS['secret']) || empty(MINECLOUDVOD_SETTINGS['siteid']) || empty(MINECLOUDVOD_SETTINGS['secret'])){
			$my_theme = wp_get_theme();
			$sinfo = $this->call('register', array(
				"theme" 	=> $my_theme->get( 'Name' ).' v-'. $my_theme->get( 'Version' ),
				"homeurl" 	=> home_url(),
				"host" 		=> $_SERVER['HTTP_HOST'],
				"wpversion" 	=> get_bloginfo('version'),
				"blog_name" => get_bloginfo('name'),
				"admin_email"	=> get_bloginfo('admin_email'),
			));
			if(isset($sinfo['siteid']) && isset($sinfo['secret'])){
				$setting = MINECLOUDVOD_SETTINGS;
				$setting['siteid']  = $sinfo['siteid'];
				$setting['secret']  = $sinfo['secret'];
				$setting['endtime'] = date('Y-m-d h:i:s', intval($sinfo['endtime']));
				update_option('mcv_settings', $setting);
			}
			else{
				//add_action( 'admin_notices', function($sinfo){echo '<div class="error fade"><p><strong></strong></p></div>';});
			}
		}
		else{
			$this->_key = MINECLOUDVOD_SETTINGS['siteid'];
			$this->_secret = MINECLOUDVOD_SETTINGS['secret'];
		}
	}

	public function version() {
		return $this->_version;
	}

	//数据签名
	private function _sign( $args ) {
		ksort( $args );
		$sbs = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
		$signature = sha1( $sbs . $this->_secret );
		return $signature;
	}

	// Add required api_* arguments
	private function _args( $args, $sign = true ) {
		$args['api_nonce'] = str_pad( mt_rand( 0, 99999999 ), 8, STR_PAD_LEFT );
		$args['api_timestamp'] = time();
		$args['homeurl'] = home_url();
		if ( $sign ) {
			$args['api_key'] = $this->_key;
		}
		$args['api_ver'] = 'php-' . $this->_version;
		if(!isset($args['mode'])) $args['mode'] = isset(MINECLOUDVOD_SETTINGS['mode'])?MINECLOUDVOD_SETTINGS['mode']:'';
		
		if(($args['mode'] == 'tcvod' || $args['mode'] == 'tccos')
		&& isset(MINECLOUDVOD_SETTINGS['tcvod'])){
			$args['sdk'] = $this->encrypt(MINECLOUDVOD_SETTINGS['tcvod']);
		}
		elseif(($args['mode'] == 'alivod' || $args['mode'] == 'alioss')
		&& isset(MINECLOUDVOD_SETTINGS['alivod'])){
			//$args['mode'] = 'alivod';
			$args['sdk'] = $this->encrypt(MINECLOUDVOD_SETTINGS['alivod']);
		}
		if ( $sign ) {
			$args['api_signature'] = $this->_sign( $args );
		}
		return $args;
	}

	// Make an API call
	public function call( $action, $args = array() ) {
		$url = $this->_url;
		$args['action'] = $action;
		$args = $this->_args($args);
		$response = wp_remote_post( $url, array(
			'method'	=> 'POST',
			'timeout'	=> $this->timeout,
			"body"		=> $args
		));

		if ( is_wp_error( $response ) ) {
			return 'Error: call to WP CloudVod API failed';
		}

		$response = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response, true );
		return $decoded_response;
	}

	
	private function encrypt($data){
		if(is_array($data)){
			$data = serialize($data);
		}
		$key = $this->_secret;
		$key    =    md5($key);
		$x      =    0;
		$len    =    strlen($data);
		$l      =    strlen($key);
		$char   =    '';
		for ($i = 0; $i < $len; $i++){
			if ($x == $l){
				$x = 0;
			}
			$char .= $key[$x];
			$x++;
		}
		$str  =  '';
		for ($i = 0; $i < $len; $i++){
			$str .= chr(ord($data[$i]) + (ord($char[$i])) % 256);
		}
		return base64_encode($str);
	}
}