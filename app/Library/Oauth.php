<?php
namespace IYUU\Library;

use Curl\Curl;

/**
 * IYUU用户注册、认证
 */
class Oauth
{
    // 合作的站点
    public static $sites = ['ourbits','hddolby','hdhome','pthome','chdbits'];
    // 爱语飞飞token
    public static $token = '';
    // 合作站点用户id
    public static $user_id = 0;
    // 合作站点密钥
    public static $passkey = '';
    // 合作站名字
    public static $site = '';
    // 登录缓存路径
    public static $SiteLoginCache = ROOT_PATH.DS.'config'.DS.'siteLoginCache_{}.json';
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
    public static function login($apiUrl = '', $sites = array())
    {
        global $configALL;
        // 云端下发合作的站点标识
        self::$sites = $sites ? $sites : self::$sites;
        $ret = false;
        self::$token = self::getSign();
        foreach (self::$sites as $name) {
            if (is_file(str_replace('{}', $name, self::$SiteLoginCache))) {
                // 存在鉴权缓存
                $ret = true;
                continue;
            }
            if (isset($configALL[$name]['passkey']) && $configALL[$name]['passkey'] && isset($configALL[$name]['id']) && $configALL[$name]['id']) {
                self::$user_id = $configALL[$name]['id'];
                self::$passkey =  sha1($configALL[$name]['passkey']);     // 避免泄露用户passkey秘钥
                self::$site = $name;

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

                $rs = json_decode($res->response, true);
                if (isset($rs['ret']) && $rs['ret'] == 200 && isset($rs['data']['success']) && $rs['data']['success']) {
                    self::setSiteLoginCache($name, $rs);
                    $ret = true;
                } else {
                    $msg = isset($rs['msg']) && $rs['msg'] ? $rs['msg'] : '远端服务器无响应，请稍后重试！';
                    $msg = isset($rs['data']['errmsg']) && $rs['data']['errmsg'] ? $rs['data']['errmsg'] : $msg;
                    echo $msg . PHP_EOL;
                }
            } else {
                echo $name.'合作站点参数配置不完整，请同时填写passkey和用户id。' . PHP_EOL;
                echo '合作站点鉴权配置，请查阅：https://www.iyuu.cn/archives/337/'. PHP_EOL. PHP_EOL;
            }
        }
        return $ret;
    }
    /**
     * 写鉴权成功缓存
     * @desc 作用：减少对服务器请求，跳过鉴权提示信息；
     */
    private static function setSiteLoginCache($key = '', $array = [])
    {
        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        $myfile = str_replace('{}', $key, self::$SiteLoginCache);
        $file_pointer = @fopen($myfile, "w");
        $worldsnum = @fwrite($file_pointer, $json);
        @fclose($file_pointer);
    }
}
