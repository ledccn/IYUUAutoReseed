<?php
namespace IYUU;

use Curl\Curl;
use IYUU\Client\AbstractClient;
use IYUU\Library\IFile;
use IYUU\Library\Oauth;
use IYUU\Library\Table;

/**
 * IYUUAutoReseed自动辅种类
 */
class AutoReseed
{
    // 版本号
    const VER = '1.10.9';
    // RPC连接
    private static $links = [];
    // 客户端配置
    private static $clients = [];
    // 站点列表
    private static $sites = [];
    // 不辅种的站点 'pt','hdchina'
    private static $noReseed = [];
    // cookie检查
    private static $cookieCheck = ['hdchina','hdcity','hdsky'];
    // 缓存路径
    public static $cacheDir  = TORRENT_PATH.'cache'.DS;
    public static $cacheHash = TORRENT_PATH.'cachehash'.DS;
    public static $cacheMove = TORRENT_PATH.'cachemove'.DS;
    // API接口配置
    public static $apiUrl = 'http://api.iyuu.cn';
    public static $endpoints = array(
        'login'   => '/user/login',
        'sites'   => '/api/sites',
        'infohash'=> '/api/infohash',
        'hash'    => '/api/hash',
        'notify'  => '/api/notify',
    );
    // curl
    private static $curl = null;
    // 退出状态码
    public static $ExitCode = 0;
    // 客户端转移做种 格式：['客户端key', '移动参数move']
    private static $move = null;
    // 微信消息体
    private static $wechatMsg = array(
        'hashCount'			=>	0,		// 提交给服务器的hash总数
        'sitesCount'		=>	0,		// 可辅种站点总数
        'reseedCount'		=>	0,		// 返回的总数据
        'reseedSuccess'		=>	0,		// 成功：辅种成功（会加入缓存，哪怕种子在校验中，下次也会过滤）
        'reseedError'		=>	0,		// 错误：辅种失败（可以重试）
        'reseedRepeat'		=>	0,		// 重复：客户端已做种
        'reseedSkip'		=>	0,		// 跳过：因未设置passkey，而跳过
        'reseedPass'		=>	0,		// 忽略：因上次成功添加、存在缓存，而跳过
        'MoveSuccess'       =>  0,      // 移动成功
        'MoveError'         =>  0,      // 移动失败
    );
    // 错误通知消息体
    private static $errNotify = array(
        'sign' => '',
        'site' => '',
        'sid'   => 0,
        'torrent_id'=> 0,
        'error'   => '',
    );
    // 初始化
    public static function init()
    {
        global $configALL;
        echo '正在初始化运行参数，版本号：'.self::VER.PHP_EOL;
        echo '当前时间：'.date('Y-m-d H:i:s').PHP_EOL;
        //sleep(mt_rand(1, 5));
        self::backup('config', $configALL);
        self::$curl = new Curl();
        self::$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);

        // 合作站点鉴权绑定
        Oauth::login(self::$apiUrl . self::$endpoints['login']);

        // 显示支持站点列表
        self::ShowTableSites();
        self::$clients = empty($configALL['default']['clients']) ? [] : $configALL['default']['clients'];

        // 递归删除上次历史记录
        IFile::rmdir(self::$cacheDir, true);
        // 建立目录
        IFile::mkdir(self::$cacheDir);
        IFile::mkdir(self::$cacheHash);
        IFile::mkdir(self::$cacheMove);
        // 连接全局客户端
        self::links();
    }
    /**
     * 显示支持站点列表
     */
    private static function ShowTableSites()
    {
        $list = [
            'gitee源码仓库：https://gitee.com/ledc/IYUUAutoReseed',
            'github源码仓库：https://github.com/ledccn/IYUUAutoReseed',
            '教程：https://gitee.com/ledc/IYUUAutoReseed/tree/master/wiki',
            '问答社区：http://wenda.iyuu.cn',
            '【IYUU自动辅种交流】QQ群：859882209、931954050'.PHP_EOL,
            '正在连接IYUUAutoReseed服务器，查询支持列表……'.PHP_EOL
        ];
        array_walk($list,function ($v, $k){
            echo $v.PHP_EOL;
        });
        $res = self::$curl->get(self::$apiUrl.self::$endpoints['sites'].'?sign='.Oauth::getSign().'&version='.self::VER);
        $rs = json_decode($res->response, true);
        $sites = empty($rs['data']['sites']) ? [] : $rs['data']['sites'];
        // 数据写入本地
        if (empty($sites)) {
            if (!empty($rs['msg'])) {
                die($rs['msg'].PHP_EOL);
            }
            die('网络故障或远端服务器无响应，请稍后再试！！！');
        }
        self::$sites = array_column($sites, null, 'id');

        $data = [];
        $i = $j = $k = 0;   // i列、j序号、k行
        foreach ($sites as $v) {
            // 控制多少列
            if ($i > 4) {
                $k++;
                $i = 0;
            }
            $i++;
            $j++;
            $data[$k][] = $j.". ".$v['site'];
        }
        echo "IYUUAutoReseed自动辅种脚本，目前支持以下站点：".PHP_EOL;
        // 输出支持站点表格
        $table = new Table();
        $table->setRows($data);
        echo($table->render());

        // 生成IYUUPTT使用的JSON
        $json = array_column($sites, null, 'site');
        ksort($json);
        $sitesConfig = ROOT_PATH.DS.'config'.DS.'sites.json';
        file_put_contents($sitesConfig, \json_encode($json, JSON_UNESCAPED_UNICODE));
    }
    /**
     * 连接远端RPC下载器
     */
    private static function links()
    {
        foreach (self::$clients as $k => $v) {
            // 跳过未配置的客户端
            if (empty($v['username']) || empty($v['password'])) {
                self::$links[$k] = array();
                echo "clients_".$k." 用户名或密码未配置，已跳过".PHP_EOL.PHP_EOL;
                continue;
            }
            try {
                // 传入配置，创建客户端实例
                $client = AbstractClient::create($v);
                self::$links[$k]['rpc'] = $client;
                self::$links[$k]['type'] = $v['type'];
                self::$links[$k]['BT_backup'] = isset($v['BT_backup']) && $v['BT_backup'] ? $v['BT_backup'] : '';
                self::$links[$k]['root_folder'] = isset($v['root_folder']) ? $v['root_folder'] : 1;
                self::$links[$k]['category'] = isset($v['category']) && $v['category'] ? $v['category'] : '';
                $result = $client->status();
                print $v['type'].'：'.$v['host']." Rpc连接 [{$result}]".PHP_EOL;
                // 检查转移做种 (self::$move为空，移动配置为真)
                if (is_null(self::$move) && !empty($v['move'])) {
                    self::$move = array($k,$v['move']);
                }
            } catch (\Exception $e) {
                die('[连接错误] '. $v['host'] . $e->getMessage() . PHP_EOL);
            }
        }
    }

    /**
     * @brief 添加下载任务
     * @param $rpcKey
     * @param string $torrent 种子元数据
     * @param string $save_path 保存路径
     * @param array $extra_options
     * @return bool
     */
    public static function add($rpcKey, $torrent, $save_path = '', $extra_options = array())
    {
        try {
            $is_url = false;
            if ((strpos($torrent, 'http://')===0) || (strpos($torrent, 'https://')===0) || (strpos($torrent, 'magnet:?xt=urn:btih:')===0)) {
                $is_url = true;
            }
            // 下载服务器类型
            $type = self::$links[$rpcKey]['type'];
            // 判断
            switch ($type) {
                case 'transmission':
                    $extra_options['paused'] = isset($extra_options['paused']) ? $extra_options['paused'] : true;
                    if ($is_url) {
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// URL添加
                    } else {
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 元数据添加
                    }
                    if (isset($result['result']) && $result['result'] == 'success') {
                        $_key = isset($result['arguments']['torrent-added']) ? 'torrent-added' : 'torrent-duplicate';
                        $id = $result['arguments'][$_key]['id'];
                        $name = $result['arguments'][$_key]['name'];
                        print "名字：" .$name . PHP_EOL;
                        print "********RPC添加下载任务成功 [" .$result['result']. "] (id=" .$id. ")".PHP_EOL.PHP_EOL;
                        return true;
                    } else {
                        $errmsg = isset($result['result']) ? $result['result'] : '未知错误，请稍后重试！';
                        if (strpos($errmsg, 'http error 404: Not Found') !== false) {
                            self::sendNotify('404');
                        }
                        print "-----RPC添加种子任务，失败 [{$errmsg}]" . PHP_EOL.PHP_EOL;
                    }
                    break;
                case 'qBittorrent':
                    $extra_options['autoTMM'] = 'false';	//关闭自动种子管理
                    #$extra_options['skip_checking'] = 'true';    //跳校验
                    // 添加任务校验后是否暂停
                    if (isset($extra_options['paused'])) {
                        $extra_options['paused'] = $extra_options['paused'] ? 'true' : 'false';
                    } else {
                        $extra_options['paused'] = 'true';
                    }
                    // 是否创建根目录
                    $extra_options['root_folder'] = self::$links[$rpcKey]['root_folder'] ? 'true' : 'false';
                    if (isset(self::$links[$rpcKey]['category'])) {
                        $extra_options['category'] = self::$links[$rpcKey]['category'];
                    }
                    if ($is_url) {
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// URL添加
                    } else {
                        $extra_options['name'] = 'torrents';
                        $extra_options['filename'] = time().'.torrent';
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 元数据添加
                    }
                    if ($result === 'Ok.') {
                        print "********RPC添加下载任务成功 [{$result}]".PHP_EOL.PHP_EOL;
                        return true;
                    } else {
                        print "-----RPC添加种子任务，失败 [{$result}]".PHP_EOL.PHP_EOL;
                    }
                    break;
                default:
                    echo '[下载器类型错误] '.$type. PHP_EOL. PHP_EOL;
                    break;
            }
        } catch (\Exception $e) {
            echo '[添加下载任务出错] ' . $e->getMessage() . PHP_EOL;
        }
        return false;
    }
    /**
     * 辅种或转移，总入口
     */
    public static function call()
    {
        if (self::$move!==null) {
            self::move();
        }
        self::reseed();
        self::wechatMessage();
        exit(self::$ExitCode);
    }
    /**
     * IYUUAutoReseed辅种
     */
    private static function reseed()
    {
        global $configALL;
        // 支持站点数量
        self::$wechatMsg['sitesCount'] = count(self::$sites);
        // 遍历客户端 开始
        foreach (self::$links as $k => $v) {
            if (empty($v)) {
                echo "clients_".$k." 用户名或密码未配置，已跳过".PHP_EOL.PHP_EOL;
                continue;
            }
            // 过滤无需辅种的客户端
            if ((self::$move !== null) && (self::$move[0] != $k) && (self::$move[1] == 2)) {
                echo "clients_".$k." 根据设置无需辅种，已跳过！";
                continue;
            }
            echo "正在从下载器 clients_".$k." 获取种子哈希……".PHP_EOL;
            $hashArray = self::$links[$k]['rpc']->getList();
            if (empty($hashArray)) {
                continue;
            }

            $infohash_Dir = $hashArray['hashString'];   // 哈希目录对应字典
            unset($hashArray['hashString']);
            // 签名
            $hashArray['sign'] = Oauth::getSign();
            $hashArray['timestamp'] = time();
            $hashArray['version'] = self::VER;
            // 写请求日志
            wlog($hashArray, 'hashString'.$k);
            self::$wechatMsg['hashCount'] += count($infohash_Dir);
            // 此处优化大于一万条做种时，设置超时
            if (count($infohash_Dir) > 5000) {
                $connecttimeout = isset($configALL['default']['CONNECTTIMEOUT']) && $configALL['default']['CONNECTTIMEOUT'] > 60 ? $configALL['default']['CONNECTTIMEOUT'] : 60;
                $timeout = isset($configALL['default']['TIMEOUT']) && $configALL['default']['TIMEOUT'] > 600 ? $configALL['default']['TIMEOUT'] : 600;
                self::$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $connecttimeout);
                self::$curl->setOpt(CURLOPT_TIMEOUT, $timeout);
            }
            // p($infohash_Dir);      // 调试：打印目录对应表
            echo "正在向服务器提交 clients_".$k." 种子哈希……".PHP_EOL;
            $res = self::$curl->post(self::$apiUrl . self::$endpoints['infohash'], $hashArray);
            $res = json_decode($res->response, true);
            // 写返回日志
            wlog($res, 'reseed'.$k);
            $data = isset($res['data']) && $res['data'] ? $res['data'] : array();
            if (empty($data)) {
                echo "clients_".$k." 没有查询到可辅种数据".PHP_EOL.PHP_EOL;
                continue;
            }
            // 判断返回值
            if (isset($res['ret']) && $res['ret']==200) {
                echo "clients_".$k." 辅种数据下载成功！！！".PHP_EOL.PHP_EOL;
                echo '【提醒】未配置passkey的站点都会跳过！'.PHP_EOL.PHP_EOL;
            } else {
                $errmsg = isset($res['msg']) && $res['msg'] ? $res['msg'] : '远端服务器无响应，请稍后重试！';
                echo '-----辅种失败，原因：' .$errmsg.PHP_EOL.PHP_EOL;
                continue;
            }
            // 遍历当前客户端可辅种数据
            foreach ($data as $info_hash => $reseed) {
                $downloadDir = $infohash_Dir[$info_hash];   // 辅种目录
                foreach ($reseed['torrent'] as $id => $value) {
                    // 匹配的辅种数据累加
                    self::$wechatMsg['reseedCount']++;
                    // 站点id
                    $sid = $value['sid'];
                    // 种子id
                    $torrent_id = $value['torrent_id'];
                    // 检查禁用站点
                    if (empty(self::$sites[$sid])) {
                        echo '-----当前站点不受支持，已跳过。' .PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedSkip']++;
                        continue;
                    }
                    // 站名
                    $siteName = self::$sites[$sid]['site'];
                    // 错误通知
                    self::setNotify($siteName, $sid, $torrent_id);
                    // 协议
                    $protocol = self::$sites[$sid]['is_https'] == 0 ? 'http://' : 'https://';
                    // 种子页规则
                    $download_page = str_replace('{}', $torrent_id, self::$sites[$sid]['download_page']);

                    // 临时种子连接（会写入辅种日志）
                    $_url = $protocol . self::$sites[$sid]['base_url']. '/' .$download_page;
                    /**
                     * 辅种前置检查
                     */
                    if (!self::reseedCheck($k, $value, $infohash_Dir, $downloadDir, $_url)) {
                        continue;
                    }
                    /**
                     * 种子推送方式区分
                     */
                    if (in_array($siteName, self::$cookieCheck)) {
                        // 特殊站点：种子元数据推送给下载器
                        $reseedPass = false;    // 标志：跳过辅种
                        $cookie = trim($configALL[$siteName]['cookie']);
                        $userAgent = $configALL['default']['userAgent'];
                        switch ($siteName) {
                            case 'hdchina':
                                // 请求详情页
                                $details_html = self::getNexusPHPdetailsPage($protocol, $value, $cookie, $userAgent);
                                if (is_null($details_html)) {
                                    $reseedPass = true;
                                    break;
                                }
                                // 搜索种子地址
                                $remove = '{hash}';
                                $offset = strpos($details_html, str_replace($remove, '', self::$sites[$sid]['download_page']));
                                if ($offset === false) {
                                    $reseedPass = true;
                                    self::cookieExpired($siteName);     // cookie失效
                                    break;
                                }
                                // 提取种子地址
                                $regex = "/download.php\?hash\=(.*?)[\"|\']/i";   // 提取种子hash的正则表达式
                                if (preg_match($regex, $details_html, $matchs)) {
                                    // 拼接种子地址
                                    $_url = str_replace($remove, $matchs[1], $_url);
                                    echo "下载种子：".$_url.PHP_EOL;
                                    $url = download($_url, $cookie, $userAgent);
                                    if (strpos($url, '第一次下载提示') != false) {
                                        self::$noReseed[] = $siteName;
                                        $reseedPass = true;

                                        echo "当前站点触发第一次下载提示，已加入排除列表".PHP_EOL;
                                        sleepIYUU(30, '请进入瓷器详情页，点右上角蓝色框：下载种子，成功后更新cookie！');
                                        ff($siteName. '站点，辅种时触发第一次下载提示！');
                                        break;
                                    }
                                    if (strpos($url, '系统检测到过多的种子下载请求') != false) {
                                        $configALL[$siteName]['limit'] = 1;
                                        $reseedPass = true;

                                        echo "当前站点触发人机验证，已加入流控列表".PHP_EOL;
                                        ff($siteName. '站点，辅种时触发人机验证！');
                                        break;
                                    }
                                } else {
                                    $reseedPass = true;
                                    sleepIYUU(15, $siteName.'正则表达式未匹配到种子地址，可能站点已更新，请联系IYUU作者！');
                                }
                                break;
                            case 'hdcity':
                                $details_url = $protocol . self::$sites[$sid]['base_url'] . '/t-' .$torrent_id;
                                print "种子详情页：".$details_url.PHP_EOL;
                                if (empty($configALL[$siteName]['cuhash'])) {
                                    // 请求包含cuhash的列表页
                                    $html = download($protocol .self::$sites[$sid]['base_url']. '/pt', $cookie, $userAgent);
                                    // 搜索cuhash
                                    $offset = strpos($html, 'cuhash=');
                                    if ($offset === false) {
                                        self::cookieExpired($siteName);     // cookie失效
                                        $reseedPass = true;
                                        break;
                                    }
                                    // 提取cuhash
                                    $regex = "/cuhash\=(.*?)[\"|\']/i";   // 提取种子cuhash的正则表达式
                                    if (preg_match($regex, $html, $matchs)) {
                                        $configALL[$siteName]['cuhash'] = $matchs[1];
                                    } else {
                                        $reseedPass = true;
                                        sleepIYUU(15, $siteName.'正则表达式未匹配到cuhash，可能站点已更新，请联系IYUU作者！');
                                        break;
                                    }
                                }
                                // 拼接种子地址
                                $remove = '{cuhash}';
                                $_url = str_replace($remove, $configALL[$siteName]['cuhash'], $_url);
                                // 城市下载种子会302转向
                                echo "下载种子：".$_url.PHP_EOL;
                                $url = download($_url, $cookie, $userAgent);
                                if (strpos($url, 'Non-exist torrent id!') != false) {
                                    echo '种子已被删除！'.PHP_EOL;
                                    self::sendNotify('404');
                                    // 标志：跳过辅种
                                    $reseedPass = true;
                                }
                                break;
                            case 'hdsky':
                                // 请求详情页
                                $details_html = self::getNexusPHPdetailsPage($protocol, $value, $cookie, $userAgent);
                                if (is_null($details_html)) {
                                    $reseedPass = true;
                                    break;
                                }
                                // 搜索种子地址
                                $remove = 'id={}&passkey={passkey}';
                                $offset = strpos($details_html, str_replace($remove, '', self::$sites[$sid]['download_page']));
                                if ($offset === false) {
                                    self::cookieExpired($siteName);     // cookie失效
                                    $reseedPass = true;
                                    break;
                                }
                                // 提取种子地址
                                $regex = '/download.php\?(.*?)["|\']/i';
                                if (preg_match($regex, $details_html, $matchs)) {
                                    // 拼接种子地址
                                    $download_page = str_replace($remove, '', self::$sites[$sid]['download_page']).str_replace('&amp;', '&', $matchs[1]);
                                    $_url = $protocol . self::$sites[$sid]['base_url']. '/' . $download_page;
                                    print "下载种子：".$_url.PHP_EOL;
                                    $url = download($_url, $cookie, $userAgent);
                                    if (strpos($url, '第一次下载提示') != false) {
                                        self::$noReseed[] = $siteName;
                                        $reseedPass = true;

                                        echo "当前站点触发第一次下载提示，已加入排除列表".PHP_EOL;
                                        echo "请进入种子详情页，下载种子，成功后更新cookie！".PHP_EOL;
                                        sleepIYUU(30, '请进入种子详情页，下载种子，成功后更新cookie！');
                                        ff($siteName. '站点，辅种时触发第一次下载提示！');
                                    }
                                } else {
                                    $reseedPass = true;
                                    sleepIYUU(15, $siteName.'正则表达式未匹配到种子地址，可能站点已更新，请联系IYUU作者！');
                                }
                                break;
                            default:
                                // 默认站点：推送给下载器种子URL链接
                                break;
                        }
                        // 检查switch内是否异常
                        if ($reseedPass) {
                            continue;
                        }
                        $downloadUrl = $_url;
                    } else {
                        $url = self::getTorrentUrl($siteName, $_url);
                        $downloadUrl = $url;
                    }

                    // 把种子URL，推送给下载器
                    echo '推送种子：' . $_url . PHP_EOL;
                    // 成功true | 失败false
                    $ret = self::add($k, $url, $downloadDir);

                    // 规范日志内容
                    $log = 'clients_'. $k . PHP_EOL . $downloadDir . PHP_EOL . $downloadUrl . PHP_EOL.PHP_EOL;
                    if ($ret) {
                        // 成功
                        // 操作流控参数
                        if (isset($configALL[$siteName]['limitRule']) && $configALL[$siteName]['limitRule']) {
                            $limitRule = $configALL[$siteName]['limitRule'];
                            if ($limitRule['count']) {
                                $configALL[$siteName]['limitRule']['count']--;
                                $configALL[$siteName]['limitRule']['time'] = time();
                            }
                        }
                        // 添加成功，以infohash为文件名，写入缓存；所有客户端共用缓存，不可以重复辅种！如果需要重复辅种，请经常删除缓存！
                        wlog($log, $value['info_hash'], self::$cacheHash);
                        wlog($log, 'reseedSuccess');
                        // 成功累加
                        self::$wechatMsg['reseedSuccess']++;
                    } else {
                        // 失败
                        wlog($log, 'reseedError');
                        // 失败累加
                        self::$wechatMsg['reseedError']++;
                    }
                }
                // 当前种子辅种 结束
            }
            // 当前客户端辅种 结束
        }
        // 按客户端循环辅种 结束
    }
    /**
     * 请求NexusPHP详情页
     * @param $protocol     string      协议
     * @param $torrent      array       种子
     * @param $cookie       string      Cookie
     * @param $userAgent    string      UA
     * @return mixed|null
     */
    private static function getNexusPHPdetailsPage($protocol, $torrent, $cookie, $userAgent)
    {
        $sid = $torrent['sid'];
        $torrent_id = $torrent['torrent_id'];

        // 拼接详情页URL
        $details = str_replace('{}', $torrent_id, 'details.php?id={}&hit=1');
        $details_url = $protocol . self::$sites[$sid]['base_url'] . '/' .$details;
        print "种子详情页：".$details_url.PHP_EOL;
        $details_html = download($details_url, $cookie, $userAgent);
        // 删种检查
        if (strpos($details_html, '没有该ID的种子') != false) {
            echo '种子已被删除！'.PHP_EOL;
            self::sendNotify('404');
            return null;
        }
        return $details_html;
    }
    /**
     * 微信通知cookie失效，延时15秒提示
     * @param $siteName
     */
    private static function cookieExpired($siteName)
    {
        global $configALL;
        $configALL[$siteName]['cookie'] = '';

        ff($siteName. '站点，cookie已过期，请更新后重新辅种！');
        sleepIYUU(15, 'cookie已过期，请更新后重新辅种！已加入排除列表');
    }
    /**
     * IYUUAutoReseed做种客户端转移
     */
    private static function move()
    {
        global $configALL;
        //遍历客户端
        foreach (self::$links as $k => $v) {
            if (self::$move[0] == $k) {
                echo "clients_".$k."是目标转移客户端，避免冲突，已跳过！".PHP_EOL.PHP_EOL;
                continue;
            }
            if (empty(self::$links[$k])) {
                echo "clients_".$k." 用户名或密码未配置，已跳过".PHP_EOL.PHP_EOL;
                continue;
            }
            echo "正在从下载器 clients_".$k." 获取种子哈希……".PHP_EOL;
            $move = [];     // 客户端做种列表 传址
            $hashArray = self::$links[$k]['rpc']->getList($move);
            if (empty($hashArray)) {
                // 失败
                continue;
            } else {
                $infohash_Dir = $hashArray['hashString'];
                // 写日志
                wlog($hashArray, 'move'.$k);
            }
            // 前置过滤：移除转移成功的hash
            $rs = self::hashFilter($infohash_Dir);
            if ($rs) {
                echo "clients_".$k." 全部转移成功，本次无需转移！".PHP_EOL.PHP_EOL;
                continue;
            }
            //遍历当前客户端种子
            foreach ($infohash_Dir as $info_hash => $downloadDir) {
                // 调用路径过滤
                if (self::pathFilter($downloadDir)) {
                    continue;
                }
                // 做种实际路径与相对路径之间互转
                echo '转换前：'.$downloadDir.PHP_EOL;
                $downloadDir = self::pathReplace($downloadDir);
                echo '转换后：'.$downloadDir.PHP_EOL;
                if (is_null($downloadDir)) {
                    echo 'IYUU自动转移做种客户端--使用教程 https://www.iyuu.cn/archives/351/'.PHP_EOL;
                    die("全局配置的move数组内，路径转换参数配置错误，请重新配置！！！".PHP_EOL);
                }
                // 种子目录：脚本要能够读取到
                $path = self::$links[$k]['BT_backup'];
                $torrentPath = '';
                // 待删除种子
                $torrentDelete = '';
                // 获取种子原文件的实际路径
                switch ($v['type']) {
                    case 'transmission':
                        // 优先使用API提供的种子路径
                        $torrentPath = $move[$info_hash]['torrentFile'];
                        $torrentDelete = $move[$info_hash]['id'];
                        // API提供的种子路径不存在时，使用配置内指定的BT_backup路径
                        if (!is_file($torrentPath)) {
                            $torrentPath = str_replace("\\", "/", $torrentPath);
                            $torrentPath = $path . strrchr($torrentPath, '/');
                        }
                        break;
                    case 'qBittorrent':
                        if (empty($path)) {
                            echo 'IYUU自动转移做种客户端--使用教程 https://www.iyuu.cn/archives/351/'.PHP_EOL;
                            die("clients_".$k." 未设置种子的BT_backup目录，无法完成转移！");
                        }
                        $torrentPath = $path .DS. $info_hash . '.torrent';
                        $torrentDelete = $info_hash;
                        break;
                    default:
                        break;
                }
                if (!is_file($torrentPath)) {
                    echo 'IYUU自动转移做种客户端--使用教程 https://www.iyuu.cn/archives/351/'.PHP_EOL;
                    die("clients_".$k." 的种子文件{$torrentPath}不存在，无法完成转移！");
                }
                echo '存在种子：'.$torrentPath.PHP_EOL;
                $torrent = file_get_contents($torrentPath);
                // 正式开始转移
                echo "种子已推送给下载器，正在转移做种...".PHP_EOL;

                // 目标下载器类型
                $rpcKey = self::$move[0];
                $type = self::$links[$rpcKey]['type'];
                $extra_options = array();
                // 转移后，是否开始？
                $extra_options['paused'] = isset($configALL['default']['move']['paused']) && $configALL['default']['move']['paused'] ? true : false;
                if ($type == 'qBittorrent') {
                    if (isset($configALL['default']['move']['skip_check']) && $configALL['default']['move']['skip_check'] === 1) {
                        $extra_options['skip_checking'] = "true";    //转移成功，跳校验
                    }
                }

                // 添加转移任务：成功返回：true
                $ret = self::add(self::$move[0], $torrent, $downloadDir, $extra_options);
                /**
                 * 转移成功的种子写日志
                 */
                $log = $info_hash.PHP_EOL.$torrentPath.PHP_EOL.$downloadDir.PHP_EOL.PHP_EOL;
                if ($ret) {
                    //转移成功时，删除做种，不删资源
                    if (isset($configALL['default']['move']['delete_torrent']) && $configALL['default']['move']['delete_torrent'] === 1) {
                        self::$links[$k]['rpc']->delete($torrentDelete);
                    }
                    // 转移成功的种子，以infohash为文件名，写入缓存
                    wlog($log, $info_hash, self::$cacheMove);
                    wlog($log, 'MoveSuccess'.$k);
                    self::$wechatMsg['MoveSuccess']++;
                } else {
                    // 失败的种子
                    wlog($log, 'MoveError'.$k);
                    self::$wechatMsg['MoveError']++;
                }
            }
        }
    }
    /**
     * 辅种前置检查
     * @param $k                int         客户端key
     * @param $torrent          array       可辅的种子
     * @param $infohash_Dir     array       当前客户端hash目录对应字典
     * @param $downloadDir      string      辅种目录
     * @param $_url             string      种子临时连接
     * @return bool     true 可辅种 | false 不可辅种
     */
    private static function reseedCheck($k, $torrent, $infohash_Dir, $downloadDir, $_url)
    {
        global $configALL;

        $sid = $torrent['sid'];
        $torrent_id = $torrent['torrent_id'];
        $info_hash = $torrent['info_hash'];
        $siteName = self::$sites[$sid]['site'];

        // passkey检测 [优先检查passkey，排除用户没有的站点]
        if (empty($configALL[$siteName]) || empty($configALL[$siteName]['passkey'])) {
            //echo '-------因当前' .$siteName. "站点未设置passkey，已跳过！！".PHP_EOL.PHP_EOL;
            self::$wechatMsg['reseedSkip']++;
            return false;
        } else {
            echo "clients_".$k."正在辅种... {$siteName}".PHP_EOL;
        }
        // cookie检测
        if (in_array($siteName, self::$cookieCheck) && empty($configALL[$siteName]['cookie'])) {
            echo '-------因当前' .$siteName. '站点未设置cookie，已跳过！！' .PHP_EOL.PHP_EOL;
            self::$wechatMsg['reseedSkip']++;
            return false;
        }
        // 重复做种检测
        if (isset($infohash_Dir[$info_hash])) {
            echo '-------与客户端现有种子重复：'.$_url.PHP_EOL.PHP_EOL;
            self::$wechatMsg['reseedRepeat']++;
            return false;
        }
        // 历史添加检测
        if (is_file(self::$cacheHash . $info_hash.'.txt')) {
            echo '-------当前种子上次辅种已成功添加【'.self::$cacheHash . $info_hash.'】，已跳过！ '.$_url.PHP_EOL.PHP_EOL;
            self::$wechatMsg['reseedPass']++;
            return false;
        }
        // 检查站点是否可以辅种
        if (in_array($siteName, self::$noReseed)) {
            echo '-------已跳过不辅种的站点：'.$_url.PHP_EOL.PHP_EOL;
            self::$wechatMsg['reseedPass']++;
            // 写入日志文件，供用户手动辅种
            wlog('clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL.$_url.PHP_EOL.PHP_EOL, $siteName);
            return false;
        }
        // 流控检测
        if (isset($configALL[$siteName]['limit'])) {
            echo "-------因当前" .$siteName. "站点触发流控，已跳过！！ {$_url}".PHP_EOL.PHP_EOL;
            // 流控日志
            if ($siteName == 'hdchina') {
                $details_page = str_replace('{}', $torrent_id, 'details.php?id={}&hit=1');
                $_url = 'https://' .self::$sites[$sid]['base_url']. '/' .$details_page;
            }
            wlog('clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL."-------因当前" .$siteName. "站点触发流控，已跳过！！ {$_url}".PHP_EOL.PHP_EOL, 'reseedLimit');
            self::$wechatMsg['reseedSkip']++;
            return false;
        }
        // 操作站点流控的配置
        if (isset($configALL[$siteName]['limitRule']) && $configALL[$siteName]['limitRule']) {
            $limitRule = $configALL[$siteName]['limitRule'];
            if (isset($limitRule['count']) && isset($limitRule['sleep'])) {
                if ($limitRule['count'] <= 0) {
                    echo '-------当前站点辅种数量已满足规则，保障账号安全已跳过：'.$_url.PHP_EOL.PHP_EOL;
                    self::$wechatMsg['reseedPass']++;
                    return false;
                } else {
                    // 异步间隔流控算法：各站独立、执行时间最优
                    $lastTime = isset($limitRule['time']) ? $limitRule['time'] : 0; // 最近一次辅种成功的时间
                    if ($lastTime) {
                        $interval = time() - $lastTime;   // 间隔时间
                        if ($interval < $limitRule['sleep']) {
                            $t = $limitRule['sleep'] - $interval +  mt_rand(1, 5);
                            do {
                                echo microtime(true)." 为账号安全，辅种进程休眠 {$t} 秒后继续...".PHP_EOL;
                                sleep(1);
                            } while (--$t > 0);
                        }
                    }
                }
            } else {
                echo '-------当前站点流控规则错误，缺少count或sleep参数！请重新配置！'.$_url.PHP_EOL.PHP_EOL;
                self::$wechatMsg['reseedPass']++;
                return false;
            }
        }
        return true;
    }

    /**
     * 过滤已转移的种子hash
     * @param array $infohash_Dir       infohash与路径对应的字典
     * @return bool     true 过滤 | false 不过滤
     */
    private static function hashFilter(&$infohash_Dir = array())
    {
        foreach ($infohash_Dir as $info_hash => $dir) {
            if (is_file(self::$cacheMove . $info_hash.'.txt')) {
                unset($infohash_Dir[$info_hash]);
                echo '-------当前种子上次已成功转移，前置过滤已跳过！ ' .PHP_EOL.PHP_EOL;
            }
        }
        return empty($infohash_Dir) ? true : false;
    }

    /**
     * 实际路径与相对路径之间互相转换
     * @param string $path
     * @return string | null        string转换成功
     */
    private static function pathReplace($path = '')
    {
        global $configALL;
        $type = intval($configALL['default']['move']['type']);
        $pathArray = $configALL['default']['move']['path'];
        $path = rtrim($path, DIRECTORY_SEPARATOR);      // 提高Windows转移兼容性
        switch ($type) {
            case 1:         // 减
                foreach ($pathArray as $key => $val) {
                    if (strpos($path, $key)===0) {
                        return substr($path, strlen($key));
                    }
                }
                break;
            case 2:         // 加
                foreach ($pathArray as $key => $val) {
                    if (strpos($path, $key)===0) {      // 没用$path == $key判断，是为了提高兼容性
                        return $val . $path;
                    }
                }
                break;
            case 3:         // 替换
                foreach ($pathArray as $key => $val) {
                    if (strpos($path, $key)===0) {      // 没用$path == $key判断，是为了提高兼容性
                        return $val . substr($path, strlen($key));
                    }
                }
                break;
            default:        // 不变
                return $path;
                break;
        }
        return null;
    }

    /**
     * 处理转移种子时所设置的过滤器、选择器
     * @param string $path
     * @return bool   true 过滤 | false 不过滤
     */
    private static function pathFilter(&$path = '')
    {
        global $configALL;
        $path = rtrim($path, DIRECTORY_SEPARATOR);      // 提高Windows转移兼容性
        // 转移过滤器、选择器 David/2020年7月11日
        $path_filter = !empty($configALL['default']['move']['path_filter']) ? $configALL['default']['move']['path_filter'] : null;
        $path_selector = !empty($configALL['default']['move']['path_selector']) ? $configALL['default']['move']['path_selector'] : null;
        if (\is_null($path_filter) && \is_null($path_selector)) {
            return false;
        }

        if (\is_null($path_filter)) {
            //选择器
            if (\is_array($path_selector)) {
                foreach ($path_selector as $pathName) {
                    if (strpos($path, $pathName)===0) {      // 没用$path == $key判断，是为了提高兼容性
                        return false;
                    }
                }
                echo '已跳过！转移选择器未匹配到：'.$path.PHP_EOL;
                return true;
            }
        } elseif (\is_null($path_selector)) {
            //过滤器
            if (\is_array($path_filter)) {
                foreach ($path_filter as $pathName) {
                    if (strpos($path, $pathName)===0) {      // 没用$path == $key判断，是为了提高兼容性
                        echo '已跳过！转移过滤器匹配到：'.$path.PHP_EOL;
                        return true;
                    }
                }
                return false;
            }
        } else {
            //同时设置过滤器、选择器
            if (\is_array($path_filter) && \is_array($path_selector)) {
                //先过滤器
                foreach ($path_filter as $pathName) {
                    if (strpos($path, $pathName)===0) {
                        echo '已跳过！转移过滤器匹配到：'.$path.PHP_EOL;
                        return true;
                    }
                }
                //后选择器
                foreach ($path_selector as $pathName) {
                    if (strpos($path, $pathName)===0) {
                        return false;
                    }
                }
                echo '已跳过！转移选择器未匹配到：'.$path.PHP_EOL;
                return true;
            }
        }
        return false;
    }

    /**
     * 获取站点种子的URL
     * @param string $site
     * @param string $url
     * @return string           带host的完整种子下载连接
     */
    private static function getTorrentUrl($site = '', $url = '')
    {
        global $configALL;
        // 兼容旧配置，进行补全
        if (isset($configALL[$site]['passkey']) && $configALL[$site]['passkey']) {
            if (empty($configALL[$site]['url_replace'])) {
                $configALL[$site]['url_replace'] = array('{passkey}' => trim($configALL[$site]['passkey']));
            }
            if (empty($configALL[$site]['url_join'])) {
                $configALL[$site]['url_join'] = array();
                if (in_array($site, array('m-team','hdbd'))) {
                    if (isset($configALL[$site]['ip_type'])) {
                        $configALL[$site]['url_join'][] = $configALL[$site]['ip_type'].'=1';
                    }
                    $configALL[$site]['url_join'][] = 'https=1';
                }
            }
        }
        // 通用操作：替换
        if (isset($configALL[$site]['url_replace']) && $configALL[$site]['url_replace']) {
            $url = strtr($url, $configALL[$site]['url_replace']);
        }
        // 通用操作：拼接
        if (isset($configALL[$site]['url_join']) && $configALL[$site]['url_join']) {
            $url = $url.(strpos($url, '?') === false ? '?' : '&').implode('&', $configALL[$site]['url_join']);
        }
        return $url;
    }
    /**
     * 微信模板消息拼接方法
     * @return string           发送情况，json
     */
    private static function wechatMessage()
    {
        global $configALL;
        if (isset($configALL['notify_on_change']) && $configALL['notify_on_change'] && self::$wechatMsg['reseedSuccess'] == 0 && self::$wechatMsg['reseedError'] == 0) {
            return;
        }
        $br = PHP_EOL;
        $text = 'IYUU自动辅种-统计报表';
        $desp = '### 版本号：'. self::VER . $br;
        $desp .= '**支持站点：'.self::$wechatMsg['sitesCount']. '**  [当前支持自动辅种的站点数量]' .$br;
        $desp .= '**总做种：'.self::$wechatMsg['hashCount'] . '**  [客户端做种的hash总数]' .$br;
        $desp .= '**返回数据：'.self::$wechatMsg['reseedCount']. '**  [服务器返回的可辅种数据]' .$br;
        $desp .= '**成功：'.self::$wechatMsg['reseedSuccess']. '**  [会把hash加入辅种缓存]' .$br;
        $desp .= '**失败：'.self::$wechatMsg['reseedError']. '**  [种子下载失败或网络超时引起]' .$br;
        $desp .= '**重复：'.self::$wechatMsg['reseedRepeat']. '**  [客户端已做种]' .$br;
        $desp .= '**跳过：'.self::$wechatMsg['reseedSkip']. '**  [未设置passkey]' .$br;
        $desp .= '**忽略：'.self::$wechatMsg['reseedPass']. '**  [成功添加存在缓存]' .$br;
        // 失败详情
        if (self::$wechatMsg['reseedError']) {
            $desp .= '**失败详情，见 ./torrent/cache/reseedError.txt**'.$br;
        }
        // 重新辅种
        $desp .= '**如需重新辅种，请删除 ./torrent/cachehash 辅种缓存。**'.$br;
        // 移动做种
        if (self::$wechatMsg['MoveSuccess'] || self::$wechatMsg['MoveError']) {
            $desp .= $br.'----------'.$br;
            $desp .= '**移动成功：'.self::$wechatMsg['MoveSuccess']. '**  [会把hash加入移动缓存]' .$br;
            $desp .= '**移动失败：'.self::$wechatMsg['MoveError']. '**  [解决错误提示，可以重试]' .$br;
            $desp .= '**如需重新移动，请删除 ./torrent/cachemove 移动缓存。**'.$br;
        }
        $desp .= $br.'*此消息将在3天后过期*。';
        return ff($text, $desp);
    }

    /**
     * 错误的种子通知服务器
     * @param string $error
     * @return bool
     */
    private static function sendNotify($error = '')
    {
        self::$errNotify['error'] = $error;
        $notify = http_build_query(self::$errNotify);
        self::$errNotify = array(
            'sign' => '',
            'site' => '',
            'sid'   => 0,
            'torrent_id'=> 0,
            'error'   => '',
        );
        $res = self::$curl->get(self::$apiUrl.self::$endpoints['notify'].'?'.$notify);
        $res = json_decode($res->response, true);
        if (isset($res['data']['success']) && $res['data']['success']) {
            echo '感谢您的参与，失效种子上报成功！！'.PHP_EOL;
        }
        return true;
    }

    /**
     * 设置通知主体
     * @param string $siteName
     * @param int $sid
     * @param int $torrent_id
     */
    private static function setNotify($siteName = '', $sid = 0, $torrent_id = 0)
    {
        self::$errNotify = array(
            'sign' => Oauth::getSign(),
            'site' => $siteName,
            'sid'   => $sid,
            'torrent_id'=> $torrent_id,
        );
    }

    /**
     * 备份功能
     * @param string $key
     * @param array $array
     * @return bool|int
     */
    private static function backup($key = '', $array = [])
    {
        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        $myfile = ROOT_PATH.DS.'config'.DS.$key.'.json';
        $file_pointer = @fopen($myfile, "w");
        $worldsnum = @fwrite($file_pointer, $json);
        @fclose($file_pointer);
        return $worldsnum;
    }
}
