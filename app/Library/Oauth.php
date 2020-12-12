<?php
namespace IYUU\Library;

use Curl\Curl;

/**
 * IYUU用户注册、认证
 */
class Oauth
{
    // 登录缓存路径
    const SiteLoginCache = ROOT_PATH.DS.'config'.DS.'siteLoginCache_{}.json';
    /**
     * 从配置文件内读取爱语飞飞token作为鉴权参数
     */
    public static function getSign()
    {
        global $configALL;
        $token = empty($configALL['iyuu.cn'])  ? '' : $configALL['iyuu.cn'];
        if (empty($token) || strlen($token) < 46) {
            echo "缺少辅种接口请求参数：爱语飞飞token ".PHP_EOL;
            echo "请访问https://iyuu.cn 用微信扫码申请，并填入配置文件config.php内。".PHP_EOL.PHP_EOL;
            exit(1);
        }
        return $token;
    }

    /**
     * 用户注册与登录
     * 作用：在服务器端实现微信用户与合作站点用户id的关联
     * 参数：爱语飞飞token + 合作站点用户id + sha1(合作站点密钥passkey) + 合作站点标识
     * @param string $apiUrl
     * @param array $sites
     * @return bool
     * @throws \ErrorException
     */
    public static function login($apiUrl = '', $sites = array())
    {
        global $configALL;
        // 云端下发合作的站点标识
        if (empty($sites)) {
            die('云端下发合作站点信息失败，请稍后重试');
        }
        $_sites = array_column($sites, 'site');
        $ret = false;
        $token = self::getSign();
        foreach ($_sites as $k => $site) {
            if (is_file(str_replace('{}', $site, self::SiteLoginCache))) {
                // 存在鉴权缓存
                $ret = true;
                continue;
            }
            if (isset($configALL[$site]['passkey']) && $configALL[$site]['passkey'] && isset($configALL[$site]['id']) && $configALL[$site]['id']) {
                $user_id = $configALL[$site]['id'];
                $passkey =  $configALL[$site]['passkey'];

                $curl = new Curl();
                $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
                $data = [
                    'token'  => $token,
                    'id'     => $user_id,
                    'passkey'=> sha1($passkey),     // 避免泄露用户passkey秘钥
                    'site'   => $site,
                ];
                $res = $curl->get($apiUrl, $data);
                p($res->response);

                $rs = json_decode($res->response, true);
                if (isset($rs['ret']) && ($rs['ret'] === 200) && isset($rs['data']['success']) && $rs['data']['success']) {
                    self::setSiteLoginCache($site, $rs);
                    $ret = true;
                } else {
                    $msg = !empty($rs['msg']) ? $rs['msg'] : '远端服务器无响应，请稍后重试！';
                    $msg = !empty($rs['data']['errmsg']) ? $rs['data']['errmsg'] : $msg;
                    echo $msg . PHP_EOL;
                }
            } else {
                echo $site.'合作站点参数配置不完整，请同时填写passkey和用户id。' . PHP_EOL;
                echo '合作站点鉴权配置，请查阅：https://www.iyuu.cn/archives/337/'. PHP_EOL. PHP_EOL;
            }
        }
        return $ret;
    }

    /**
     * 写鉴权成功缓存
     * @desc 作用：减少对服务器请求，跳过鉴权提示信息；
     * @param string $site
     * @param array $array
     * @return bool|int
     */
    private static function setSiteLoginCache($site = '', $array = [])
    {
        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        $myfile = str_replace('{}', $site, self::SiteLoginCache);
        $file_pointer = @fopen($myfile, "w");
        $worldsnum = @fwrite($file_pointer, $json);
        @fclose($file_pointer);
        return $worldsnum;
    }
}
