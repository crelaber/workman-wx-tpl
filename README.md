
所需环境
========

workerman需要PHP版本不低于5.3，只需要安装PHP的Cli即可，无需安装PHP-FPM、nginx、apache
workerman不能运行在Window平台

安装
=========

以ubuntu为例

安装PHP Cli  
`sudo apt-get install php5-cli`

强烈建议安装libevent扩展，以便支持更高的并发量  
`sudo pecl install libevent`


启动停止
=========

启动  
`php start.php start -d`

重启启动  
`php start.php restart`

平滑重启/重新加载配置  
`php start.php reload`

查看服务状态  
`php start.php status`

停止  
`php start.php stop`

如何发送微信模板消息
=========

###修改公众号相关的配置

### 将项目部署到服务器上
1、假设项目部署在/data/wwwroot/server目录下
```
    cd /data/wwwroot/server/workman-wx-tpl
    git clone https://github.com/crelaber/workman-wx-tpl
```
#### 修改Weixin.php中的公众号appid相关的配置的设置
1、方法是Applications/JsonRpc/Services/Weixin.php中的get_config方法
2、将get_config方法中的$config变量中的appid和appsecret改为你自己的appid和appsecret，这里的$env是指环境，test表示测试环境，prod为正式环境
3、如果要使用阿里云的redis服务器的话，需要Weixin.php的中的get_redis_config方法中的配置，改成对应的阿里云redis服务器的地址就行了
```
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
```
#### 启动服务
```
  php start.php start -d  
```

###客户端同步调用：

1、封装一个方法，用于获取weixin实例
注意
1）代码中的get_setting('workman_jsonrpc_dir')的路径地址其实就是上面配置的在服务器上的完整路径，如示例中的/data/wwwroot/server/workman-wx-tpl目录
2）客户端的测试用例一定要放到服务器上进行调用，负责会提示RpcClient.php找不到

```php
class WxHelper{
    /**
     * 获取远程异步实例
     */
    public static function get_asyn_weixin_instance(){
        $path = get_setting('workman_jsonrpc_dir').'/Applications/JsonRpc/Clients/RpcClient.php';
        include_once $path;
        $address_array = array('tcp://0.0.0.0:23469');
        RpcClient::config($address_array);
        $weixin = RpcClient::instance('Weixin');
        return $weixin;
    }
}
```
2、新建一个测试用例文件，比如Test.php，并更改其中的$template_id为对用的模板消息id
```php
    /**
     * @todo 发送模板通知
     */
    public function send_wx_tpl_msg(){
        $msg_data = $this->get_tpl_msg_data();
        $weixin = WxHelper::get_asyn_weixin_instance();
        //env只有test和prod两种，如果为test表示测试环境的公众号，如果为prod表示正式环境的
        $env = get_setting('environment')?get_setting('environment'):'qa';
        $template_id = "请填入微信公众号的模板编号";
        $params  = array(
            'env' => $env,
            'tpl_msg_id' => $template_id,
            'openid' => $author['openid'],
            'data' => $msg_data['msg_data'],
            'url' => $msg_data['url']
        );
        $weixin->asend_sendTplMsg($params);
    }
    
    /**
     * 获取模板消息的内容数据
     * @param array $note 笔记信息
     * @param array $user 用户信息
     * @return array
     */
    public function get_tpl_msg_data(){
        $msg = array(
            'first' => '【王某某】偷看了你的微笔记【2018年元旦活动】',
            'orderMoneySum' => 10 ,
            'orderProductName' => '微笔记' ,
            'Remark' => '目前累积的未发放红包为100元，小编将会定期为您推送',
        );
        $url = "http://www.baidu.com"
        return array(
            'msg_data' => $msg,
            'url' => $url
        );
    }

```

3、定义好Test.php的路由之后，调用Test.php中的send_wx_tpl_msg方法，在浏览器中访问对应的链接，查看模板消息是否已经发送成功



