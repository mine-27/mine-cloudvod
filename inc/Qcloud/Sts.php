<?php

namespace MineCloudvod\Qcloud;

class Sts{
	// 临时密钥计算样例
	
	function _hex2bin($data) {
		$len = strlen($data);
		return pack("H" . $len, $data);
	}
	// obj 转 query string
	function json2str($obj, $notEncode = false) {
		ksort($obj);
		$arr = array();
		if(!is_array($obj)){
			throw new \Exception('$obj must be an array, the actual value is:' . json_encode($obj));
		}
		foreach ($obj as $key => $val) {
			array_push($arr, $key . '=' . ($notEncode ? $val : rawurlencode($val)));
		}
		return join('&', $arr);
	}
	// 计算临时密钥用的签名
	function getSignature($opt, $key, $method, $config) {
		$formatString = $method . $config['domain'] . '/?' . $this->json2str($opt, 1);
		$sign = hash_hmac('sha1', $formatString, $key);
		$sign = base64_encode($this->_hex2bin($sign));
		return $sign;
	}
	// v2接口的key首字母小写，v3改成大写，此处做了向下兼容
	function backwardCompat($result) {
		if(!is_array($result)){
			throw new \Exception('$result must be an array, the actual value is:' . json_encode($result));
		}
		$compat = array();
		foreach ($result as $key => $value) {
			if(is_array($value)) {
				$compat[lcfirst($key)] = $this->backwardCompat($value);
			} elseif ($key == 'Token') {
				$compat['sessionToken'] = $value;
			} else {
				$compat[lcfirst($key)] = $value;
			}
		}
		return $compat;
	}
	// 获取临时密钥
	function getTempKeys($config) {
		$result = null;
		try{
			if(array_key_exists('policy', $config)){
				$policy = $config['policy'];
			}else{
				if(array_key_exists('bucket', $config)){
					$ShortBucketName = substr($config['bucket'],0, strripos($config['bucket'], '-'));
					$AppId = substr($config['bucket'], 1 + strripos($config['bucket'], '-'));
				}else{
					throw new \Exception("bucket== null");
				}
				if(array_key_exists('allowPrefix', $config)){
					if(!(strpos($config['allowPrefix'], '/') === 0)){
					$config['allowPrefix'] = '/' . $config['allowPrefix'];
					}
				}else{
					throw new \Exception("allowPrefix == null");
				}
				if(!array_key_exists('region', $config)) {
					throw new \Exception("region == null");
				}
				$policy = array(
					'version'=> '2.0',
					'statement'=> array(
						array(
							'action'=> $config['allowActions'],
							'effect'=> 'allow',
							'principal'=> array('qcs'=> array('*')),
							'resource'=> array(
								'qcs::cos:' . $config['region'] . ':uid/' . $AppId . ':' . $config['bucket'] . $config['allowPrefix']
							)
						)
					)
				);	
			}
			$policyStr = str_replace('\\/', '/', json_encode($policy));
			$Action = 'GetFederationToken';
			$Nonce = wp_rand(10000, 20000);
			$Timestamp = time();
			$Method = 'POST';
			if(array_key_exists('durationSeconds', $config)){
				if(!(is_integer($config['durationSeconds']))){
					throw new \Exception("durationSeconds must be a int type");
				}
			}
			$params = array(
				'SecretId'=> $config['secretId'],
				'Timestamp'=> $Timestamp,
				'Nonce'=> $Nonce,
				'Action'=> $Action,
				'DurationSeconds'=> $config['durationSeconds'],
				'Version'=>'2018-08-13',
				'Name'=> 'cos',
				'Region'=> $config['region'],
				'Policy'=> urlencode($policyStr)
			);
			$params['Signature'] = $this->getSignature($params, $config['secretKey'], $Method, $config);
			$url = $config['url'];
			// 构建请求参数
			$args = [
				'method'      => 'POST',
				'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'], // 适配表单提交格式
				'body'        => $this->json2str($params), // 沿用原数据处理方法
			];

			// 处理代理配置
			if (isset($config['proxy']) && !empty($config['proxy'])) {
				$args['proxy'] = $config['proxy']; // WordPress支持直接设置代理
			}

			// 发送请求
			$response = wp_remote_post($url, $args);

			// 处理响应结果
			if (is_wp_error($response)) {
				$result = $response->get_error_message(); // 获取错误信息，对应curl_errno处理
			} else {
				$result = wp_remote_retrieve_body($response); // 获取响应体，对应CURLOPT_RETURNTRANSFER=1
			}

			// 解析JSON响应
			$result = json_decode($result, true);
			if (isset($result['Response'])) {
				$result = $result['Response'];
				if(isset($result['Error'])){
					throw new \Exception("get cam failed");
				}
				$result['startTime'] = $result['ExpiredTime'] - $config['durationSeconds'];
			}
			$result = $this->backwardCompat($result);
			return $result;
		}catch(\Exception $e){
			if($result == null){
				$result = "error: " . $e->getMessage();
			}else{
				$result = json_encode($result);
			}
			throw new \Exception(esc_html($result));
		}
	}
	
	// get policy
	function getPolicy($scopes){
		if (!is_array($scopes)){
			return null;
		}
		$statements = array();
		
		for($i=0, $counts=count($scopes); $i < $counts; $i++){
			$actions=array();
			$resources = array();
			array_push($actions, $scopes[$i]->get_action());
			array_push($resources, $scopes[$i]->get_resource());
			
			$statement = array(
			'action' => $actions,
			'effect' => $scopes[$i]->get_effect(),
			'resource' => $resources
			);
			array_push($statements, $statement);
		}
			
		$policy = array(
			'version' => '2.0',
			'statement' => $statements
		);
		return $policy;
	}	
}
?>