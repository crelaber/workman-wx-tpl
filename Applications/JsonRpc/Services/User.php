<?php
use Statistics\Lib\WeixinUtil;

/**
 *  测试
 * @author walkor <worker-man@qq.com>
 *
 */
class User
{
    public static function getInfoByUid($uid)
    {
        return array(
            'uid' => $uid,
            'name' => 'test',
            'age' => 18,
            'sex' => 'hmm..',
        );
    }

    public static function getEmail($uid)
    {
        return 'worker-man@qq.com';
    }


    public static function getConfig($env){
        $weixinUtil = new WeixinUtil($env);
        $data = $weixinUtil->getConfig();
        return $data;
    }

}
