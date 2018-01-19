<?php

namespace Statistics\Lib;
/**
 *  测试
 * @author walkor <worker-man@qq.com>
 */
use Statistics\Lib\CommonCache;
use Statistics\Lib\HTTP;

class WeixinUtil
{
    const WEIXIN_API = 'https://api.weixin.qq.com/cgi-bin/';


    public static function get_token($env){
        $config = self::get_config($env);
        $token = self::get_access_token($config['app_id'],$config['app_secret']);
        return $token;
    }

    public function send_tpl_msg($msg_id,$env = 'qa'){
        $config = $this->get_config($env);
        return $this->send_template_message($config['app_id'],$config['app_secret'],$msg_id);
    }


    public function get_config($env)
    {
        switch ($env) {
            default :
                $config = array(
                    'app_id' => 'wx4ca23e8785936de0',
                    'app_secret' => '9c9c434e774abd8d30e7cb099ad0c2c1',
                );

                break;
            case 'qa':
                $config = array(
                    'app_id' => 'wx95fc895bebd3743b',
                    'app_secret' => 'a2ed31c3b442a72b13f7802992d3678c',
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
    function curl_get_contents($url, $timeout = 10)
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


    public function get_access_token($app_id, $app_secret)
    {
        if (!$app_id OR !$app_secret) {
            return false;
        }

        $cached_token = 'weixin_access_token_' . md5($app_id . $app_secret);

        $cache = new CommonCache('./Cache/',60,$cached_token);
        $access_token = $cache->load();

        if ($access_token) {
//            echo 'load_from cache';
            return $access_token;
        }

        $result = $this->curl_get_contents(self::WEIXIN_API . 'token?grant_type=client_credential&appid=' . $app_id . '&secret=' . $app_secret);

        if (!$result) {
            return false;
        }

        $result = json_decode($result, true);

        if (!$result['access_token']) {
            return false;
        }
        $cache->write(1,$result['access_token']);
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
    public function send_template_message($app_id,$app_secret,$template_id, $openid, $data, $url = '')
    {
        $token = $this->get_access_token($app_id, $app_secret);
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
        $result = HTTP::request('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token, 'POST', json_encode($post_data));
        return json_decode($result, true);
    }


}
