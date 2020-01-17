<?php
/**
 * IYUU用户注册、认证
 */
namespace IYUU\Library;

use Curl\Curl;

class Oauth
{
    // 合作的站点
    public static $sites = ['ourbits'];
    // 爱语飞飞token
    public static $token = '';
    // 合作站点用户id
    public static $user_id = 0;
    // 合作站点密钥
    public static $passkey = '';
    // 合作站名字
    public static $site = '';
    /**
     * 初始化配置
     */
    public static function init()
    {
        global $configALL;
        foreach (self::$sites as $name) {
            if (isset($configALL[$name]['passkey']) && $configALL[$name]['passkey'] && isset($configALL[$name]['id']) && $configALL[$name]['id']) {
                self::$token = self::getSign();
                self::$user_id = $configALL[$name]['id'];
                self::$passkey =  sha1($configALL[$name]['passkey']);     // 避免泄露用户passkey秘钥
                self::$site = $name;
                return true;
            }
        }
        echo "-----缺少合作站点登录参数：token, user_id, passkey, site \n";
        echo "-----当前正在使用测试接口，功能可能会受到限制！ \n\n";
        return false;
    }
    /**
     * 从配置文件内读取爱语飞飞token作为鉴权参数
     */
    public static function getSign()
    {
        global $configALL;
        // 爱语飞飞
        $token = isset($configALL['iyuu.cn']) && $configALL['iyuu.cn'] ? $configALL['iyuu.cn'] : '';
        if (empty($token) || strlen($token)<46) {
            echo "缺少辅种接口请求参数：爱语飞飞token \n";
            echo "请访问https://iyuu.cn 用微信扫码申请，并填入配置文件config.php内。\n\n";
            exit(1);
        }
        return $token;
    }
    /**
     * 用户注册与登录
     * 作用：在服务器端实现微信用户与合作站点用户id的关联
     * 参数：爱语飞飞token + 合作站点用户id + sha1(合作站点密钥passkey) + 合作站点标识
     */
    public static function login($apiUrl = '')
    {
        $is_oauth = self::init();
        if ($is_oauth) {
            $curl = new Curl();
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $data = [
                'token'  => self::$token,
                'id'     => self::$user_id,
                'passkey'=> self::$passkey,
                'site'   => self::$site,
            ];
            $res = $curl->get($apiUrl, $data);
            p($res->response);
            return true;
        }
        return false;
    }
}
