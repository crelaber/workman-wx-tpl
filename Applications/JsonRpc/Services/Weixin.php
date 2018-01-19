<?php

/**
 *  测试
 * @author walkor <worker-man@qq.com>
 */
//use Statistics\Lib\WeixinUtil;

//use Statistics\Lib\CommonCache;
//use Statistics\Lib\HTTP;

require_once realpath(__DIR__.'/../../').'/Statistics/Lib/Http.php';


class Weixin
{
    const WEIXIN_API = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 获取access_token接口
     * @param  $params 入参
     *              env 环境参数，qa为测试环境，wenda为正式环境
     */
    public static function getToken($params){
        echo 'input params is =>'.json_encode($params)."\n";
        $env = $params['env'];
        $config = self::get_config($env);
        $data = self::get_access_token($config['app_id'],$config['app_secret']);
        return $data;
    }

    /**
     * 发送模板消息的接口
     * @param  $params 入参
     *              env 环境参数，qa为测试环境，prod为正式环境
     *              data 模板消息的内容
     *              openid 用户的openid
     *              url 模板消息的url
     */
    public static function sendTplMsg($params){
        echo 'input params is =>'.json_encode($params)."\n";
        $msg_id = $params['tpl_msg_id'];
        $env = $params['env'];
        $config = self::get_config($env);
        $openid = $params['openid'];
        $data = $params['data'];
        $url = $params['url']?$params['url']:'';
        $result = self::send_template_message($config['app_id'],$config['app_secret'],$msg_id,$openid, $data, $url);
        echo 'send_tpl_msg result is :'.json_encode($result)."\n";
        return $result;
    }


    /**
     * 发送异步链接
     */
    public static function sendAsynHttpRequest($params){
        echo 'input params is =>'.json_encode($params)."\n";
        if($url = $params['url']){
            $method = $params['method'] ? $params['method'] : 'get';
            if($method == 'post'){
                $postParam = $params['params'];
                $result = HTTP::post($url,$postParam);
            }else{
                $result = self::curl_get_contents($url);
            }
            echo $result;
        }
    }

    public static function get_config($env)
    {
        switch ($env) {
            default :
                $config = array(
                    'app_id' => 'APPID',
                    'app_secret' => 'APPSECRET',
                );

                break;
            case 'test':
                $config = array(
                    'app_id' => 'APPID',
                    'app_secret' => 'APPID',
                );
                break;
        }
        return $config;
    }


    /**
     * CURL 获取文件内容
     *
     * 用法同 file_get_contents
     *
     * @param string
     * @param integerr
     * @return string
     */
    public static function curl_get_contents($url, $timeout = 10)
    {
        if (!function_exists('curl_init')) {
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36');


        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        }

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }



    private static function get_access_token($app_id, $app_secret)
    {
        if (!$app_id OR !$app_secret) {
            return false;
        }

        echo 'app_id====>'.$app_id."\n";;
        echo 'app_secret====>'.$app_secret."\n";
        echo 'access_token_key====>'."\n";
        $access_token_key = md5($app_id.'_'.$app_secret.'_'.'access_token');
        echo 'access_token_key====>'.$access_token_key."\n";
        $redis = new Redis();
        $redis_info = self::get_redis_config();
        $redis->pconnect($redis_info['host'], $redis_info['port']);
        if($redis_info['auth']){
            $redis->auth($redis_info['auth']);
        }
        echo 'redis_info====>'.json_encode($redis_info)."\n";
        $access_token = self::get_token_from_redis($redis,$access_token_key);
        echo 'access_token ====>'.json_encode($access_token)."\n";


        if ($access_token) {
            echo 'load_from redis'."\n";
            return $access_token['access_token'];
        }

        echo 'fetch data ====>'."\n";
        $result = self::curl_get_contents(self::WEIXIN_API . 'token?grant_type=client_credential&appid=' . $app_id . '&secret=' . $app_secret);
        if (!$result) {
            return false;
        }


        $result = json_decode($result, true);
        if (!$result['access_token']) {
            return false;
        }
        $result['time'] = time();
        echo 'result is ======>'.json_encode($result)."\n";
        self::set_data_to_redis($redis,$access_token_key,'',json_encode($result));
        return $result['access_token'];
    }


    /**
     * 发送微信模板消息
     * @param type $template_id
     * @param type $openid
     * @param type $data
     * @param type $url
     * @return type
     */
    private static function send_template_message($app_id,$app_secret,$template_id, $openid, $data, $url = '')
    {
        echo 'begin to send template msg.';
        $token = self::get_access_token($app_id, $app_secret);
        foreach ($data as &$d) {
            if (!is_array($d)) {
                $d = array('value' => $d, 'color' => '#173177');
            }
        }
        $post_data = array(
            "touser" => "$openid",
            "template_id" => "$template_id",
            "url" => "$url",
            "topcolor" => "#FF0000",
            "data" => $data
        );
        $result = HTTP::post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token , json_encode($post_data));
        return json_decode($result, true);
    }



    public static function get_token_from_redis($redis,$key,$hash = ''){
        $json = $redis->hGet($key,$hash);
        echo 'json is ====>'.$json."\n";
        $data = array();
        $result = json_decode($json,true);
        $now = time();
        //提前十分钟获取access_token
        if($now - $result['time'] <= 60){
            $data = $result;
        }
        return $data;
    }

    public static function set_data_to_redis($redis,$key,$hash = '' ,$val=''){
        $redis->hSet($key,$hash,$val);
    }


    public static function get_redis_config(){
        return array (
            'host' => '127.0.0.1',
            'port' => '6379',
            'auth' => ''
        );
    }


}
