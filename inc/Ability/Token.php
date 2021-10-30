<?php
namespace MineCloudvod\Ability;

class Token
{
    protected $key ;
    public function __construct($key = "mine-cloudvod")
    {
        if(!$key) $key = "mine-cloudvod";
        $this->key = $key;
    }

    public function generrate_token(){
        global $current_user;
        $uid = $current_user->ID;
        $yx = 21600;
        if(!$uid){
            $uid='guest';
            $yx = 1800;
        }
        

        $time = time();
        $end_time = $time + $yx;
        $info = $uid. '.' .$time.'.'.$end_time;
        $signature = hash_hmac('md5', $info, $this->key);
        $token = $info . '.' . $signature;
        $token = base64_encode($token);
		return $token;
	}

    public function check_token($token){
        if(!isset($token) || empty($token))
        {
            $msg['code']='400';
            $msg['msg']=__( 'Illegal request', 'mine-cloudvod' );//非法请求
            return $msg;
        }
        $token = base64_decode($token);
        $explode = explode('.',$token);
        if(!empty($explode[0]) && !empty($explode[1]) && !empty($explode[2]) && !empty($explode[3]) )
        {
            $info = $explode[0].'.'.$explode[1].'.'.$explode[2];
            $true_signature = hash_hmac('md5', $info, $this->key);
            if(time() > $explode[2])
            {
                $msg['code']='401';
                $msg['msg']=__('Token has expired, please log in again.', 'mine-cloudvod');//'Token已过期,请重新登录';
                return $msg;
            }
            if ($true_signature == $explode[3])
            {
                $msg['code']='200';
                $msg['msg']=__('Token is legal', 'mine-cloudvod');//'Token合法';
                return $msg;
            }
            else
            {
                $msg['code']='400';
                $msg['msg']=__('Token is illegal', 'mine-cloudvod');//'Token不合法';
                return $msg;
            }
        }
        else
        {
            $msg['code']='400';
            $msg['msg']=__('Token is illegal', 'mine-cloudvod');//'Token不合法';
            return $msg;
        }
        
    }
}
