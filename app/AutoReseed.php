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
    const VER = '1.8.3';
    // RPC连接
    private static $links = [];
    // 客户端配置
    private static $clients = [];
    // 站点列表
    private static $sites = [];
    // 不辅种的站点 'pt','hdchina'
    private static $noReseed = [];
    // 不转移的站点 'hdarea','hdbd'
    private static $noMove = [];
    // cookie检查
    private static $cookieCheck = ['hdchina','hdcity'];
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
        'notify'  => '/api/notify',
        'alike'   => '/api/alike',
        'hash'    => '/api/hash',
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
        echo "版本号：".self::VER.PHP_EOL;
        self::backup('config', $configALL);
        self::$curl = new Curl();
        self::$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);

        // 合作站点自动注册鉴权
        $is_login = Oauth::login(self::$apiUrl . self::$endpoints['login']);
        if (!$is_login) {
            echo '合作站点鉴权配置，请查阅：https://www.iyuu.cn/archives/337/' .PHP_EOL;
        }

        echo "程序正在初始化运行参数... ".PHP_EOL;
        // 显示支持站点列表
        self::ShowTableSites();
        self::$clients = isset($configALL['default']['clients']) && $configALL['default']['clients'] ? $configALL['default']['clients'] : array();

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
        $list[] = 'gitee源码仓库：https://gitee.com/ledc/IYUUAutoReseed';
        $list[] = 'github源码仓库：https://github.com/ledccn/IYUUAutoReseed';
        $list[] = '教程：https://gitee.com/ledc/IYUUAutoReseed/tree/master/wiki';
        $list[] = '问答社区：http://wenda.iyuu.cn';
        $list[] = "QQ群：859882209 【IYUU自动辅种交流】".PHP_EOL;
        foreach ($list as $key => $value) {
            echo $value.PHP_EOL;
        }
        echo "正在连接IYUUAutoReseed服务器，查询支持列表…… ".PHP_EOL;
        $res = self::$curl->get(self::$apiUrl.self::$endpoints['sites'].'?sign='.Oauth::getSign());
        $rs = json_decode($res->response, true);
        $sites = isset($rs['data']['sites']) && $rs['data']['sites'] ? $rs['data']['sites'] : false;
        // 数据写入本地
        if ($sites) {
            self::$sites = array_column($sites, null, 'id');
            $json = array_column($sites, null, 'site');
            ksort($json);
            $json = json_encode($json, JSON_UNESCAPED_UNICODE);
            $myfile = ROOT_PATH.DS.'config'.DS.'sites.json';
            $file_pointer = @fopen($myfile, "w");
            $worldsnum = @fwrite($file_pointer, $json);
            @fclose($file_pointer);
        } else {
            if (isset($rs['msg']) && $rs['msg']) {
                die($rs['msg'].PHP_EOL);
            }
            if (isset($rs['errmsg']) && $rs['errmsg']) {
                die($rs['errmsg'].PHP_EOL);
            }
            die('远端服务器无响应，请稍后再试！！！');
        }
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
        //输出表格
        $table = new Table();
        $table->setRows($data);
        echo($table->render());
    }
    /**
     * 连接远端RPC下载器
     */
    public static function links()
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
                $result = $client->status();
                print $v['type'].'：'.$v['host']." Rpc连接 [{$result}] \n";
                // 检查转移做种 (移动配置为真、self::$move为空)
                if (isset($v['move']) && $v['move'] && is_null(self::$move)) {
                    self::$move = array($k,$v['move']);
                }
            } catch (\Exception $e) {
                die('[Links ERROR] ' . $e->getMessage() . PHP_EOL);
            }
        }
    }
    /**
     * @brief 添加下载任务
     * @param string $torrent 种子元数据
     * @param string $save_path 保存路径
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
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// 种子URL添加下载任务
                    } else {
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 种子元数据添加下载任务
                    }
                    if (isset($result['result']) && $result['result'] == 'success') {
                        $id = $name = '';
                        if (isset($result['arguments']['torrent-duplicate'])) {
                            $id = $result['arguments']['torrent-duplicate']['id'];
                            $name = $result['arguments']['torrent-duplicate']['name'];
                        } elseif (isset($result['arguments']['torrent-added'])) {
                            $id = $result['arguments']['torrent-added']['id'];
                            $name = $result['arguments']['torrent-added']['name'];
                        }
                        print "名字：" .$name . PHP_EOL;
                        print "********RPC添加下载任务成功 [" .$result['result']. "] (id=" .$id. ")".PHP_EOL.PHP_EOL;
                        return true;
                    } else {
                        $errmsg = isset($result['result']) ? $result['result'] : '未知错误，请稍后重试！';
                        if (strpos($errmsg, 'http error 404: Not Found') !== false) {
                            self::sendNotify('404');
                        } elseif (strpos($errmsg, 'http error 403: Forbidden')  !== false) {
                            self::sendNotify('403');
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
                    if ($is_url) {
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// 种子URL添加下载任务
                    } else {
                        $extra_options['name'] = 'torrents';
                        $rand = mt_rand(10, 42949672);
                        $extra_options['filename'] = intval($rand).'.torrent';
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 种子元数据添加下载任务
                    }
                    if ($result === 'Ok.') {
                        print "********RPC添加下载任务成功 [{$result}]".PHP_EOL.PHP_EOL;
                        return true;
                    } else {
                        print "-----RPC添加种子任务，失败 [{$result}]".PHP_EOL.PHP_EOL;
                    }
                    break;
                default:
                    echo '[add ERROR] '.$type. PHP_EOL. PHP_EOL;
                    break;
            }
        } catch (\Exception $e) {
            echo '[add ERROR] ' . $e->getMessage() . PHP_EOL;
        }
        return false;
    }
    /**
     * 转移、辅种总入口
     */
    public static function call()
    {
        if (self::$move!==null) {
            self::move();
        }
        self::reseed();
        self::wechatMessage();
    }
    /**
     * IYUUAutoReseed辅种
     */
    public static function reseed()
    {
        global $configALL;
        // 支持站点数量
        self::$wechatMsg['sitesCount'] = count(self::$sites);
        $sites = self::$sites;
        // 按客户端循环辅种 开始
        foreach (self::$links as $k => $v) {
            if (empty($v)) {
                echo "clients_".$k." 用户名或密码未配置，已跳过".PHP_EOL.PHP_EOL;
                continue;
            }
            // 过滤无需辅种的客户端
            if (self::$move!==null && self::$move[0]!=$k && self::$move[1]==2) {
                echo "clients_".$k." 根据设置无需辅种，已跳过！";
                continue;
            }
            echo "正在从下载器 clients_".$k." 获取种子哈希……".PHP_EOL;
            $hashArray = self::$links[$k]['rpc']->getList();
            if (empty($hashArray)) {
                // 失败
                continue;
            }
            self::backup('clients_'.$k, $hashArray);
            $infohash_Dir = $hashArray['hashString'];
            unset($hashArray['hashString']);
            // 签名
            $hashArray['sign'] = Oauth::getSign();
            $hashArray['timestamp'] = time();
            $hashArray['version'] = self::VER;
            // 写请求日志
            wlog($hashArray, 'hashString'.$k);
            self::$wechatMsg['hashCount'] +=count($infohash_Dir);
            // 此处优化大于一万条做种时，设置超时
            if (count($infohash_Dir) > 5000) {
                $connecttimeout = isset($configALL['default']['CONNECTTIMEOUT']) && $configALL['default']['CONNECTTIMEOUT']>60 ? $configALL['default']['CONNECTTIMEOUT'] : 60;
                $timeout = isset($configALL['default']['TIMEOUT']) && $configALL['default']['TIMEOUT']>600 ? $configALL['default']['TIMEOUT'] : 600;
                self::$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $connecttimeout);
                self::$curl->setOpt(CURLOPT_TIMEOUT, $timeout);
            }
            // P($infohash_Dir);      // 调试：打印目录对应表
            echo "正在向服务器提交 clients_".$k." 种子哈希……".PHP_EOL;
            $res = self::$curl->post(self::$apiUrl . self::$endpoints['infohash'], $hashArray);
            $res = json_decode($res->response, true);
            // 写返回日志
            wlog($res, 'reseed'.$k);
            $reseed = isset($res['data']) && $res['data'] ? $res['data'] : array();
            if (empty($reseed)) {
                echo "clients_".$k." 没有查询到可辅种数据".PHP_EOL.PHP_EOL;
                continue;
            }
            // 判断返回值
            if (empty($res['msg'])) {
                echo "clients_".$k." 辅种数据下载成功！！！".PHP_EOL.PHP_EOL;
                echo '【提醒】未配置passkey的站点都会跳过！'.PHP_EOL.PHP_EOL;
            } else {
                $errmsg = isset($res['msg']) && $res['msg'] ? $res['msg'] : '远端服务器无响应，请稍后重试！';
                echo '-----辅种失败，原因：' .$errmsg.PHP_EOL.PHP_EOL;
                continue;
            }
            // 当前客户端可辅种数据
            foreach ($reseed as $info_hash => $vv) {
                // 当前种子哈希对应的目录
                $downloadDir = $infohash_Dir[$info_hash];
                foreach ($vv['torrent'] as $id => $value) {
                    $_url = $url = '';
                    $download_page = $details_url = '';
                    // 匹配的辅种数据累加
                    self::$wechatMsg['reseedCount']++;
                    // 站点id
                    $sid = $value['sid'];
                    // 种子id
                    $torrent_id = $value['torrent_id'];
                    // 站点名
                    if (empty($sites[$sid]['site'])) {
                        echo '-----当前站点不受支持，已跳过。' .PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedSkip']++;
                        continue;
                    }
                    $siteName = $sites[$sid]['site'];
                    // 错误通知
                    self::setNotify($siteName, $sid, $torrent_id);
                    // 页面规则
                    $download_page = str_replace('{}', $torrent_id, $sites[$sid]['download_page']);
                    $_url = 'https://' .$sites[$sid]['base_url']. '/' .$download_page;

                    /**
                     * 前置检测
                     */
                    // passkey检测
                    if (empty($configALL[$siteName]['passkey'])) {
                        //echo '-------因当前' .$siteName. "站点未设置passkey，已跳过！！".PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedSkip']++;
                        continue;
                    } else {
                        echo "clients_".$k."正在辅种... {$siteName}".PHP_EOL;
                    }
                    // cookie检测
                    if (in_array($siteName, self::$cookieCheck) && empty($configALL[$siteName]['cookie'])) {
                        echo '-------因当前' .$siteName. '站点未设置cookie，已跳过！！' .PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedSkip']++;
                        continue;
                    }
                    // 流控检测
                    if (isset($configALL[$siteName]['limit'])) {
                        echo "-------因当前" .$siteName. "站点触发流控，已跳过！！ {$_url}".PHP_EOL.PHP_EOL;
                        // 流控日志
                        if ($siteName == 'hdchina') {
                            $details_page = str_replace('{}', $torrent_id, 'details.php?id={}&hit=1');
                            $_url = 'https://' .$sites[$sid]['base_url']. '/' .$details_page;
                        }
                        wlog('clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL."-------因当前" .$siteName. "站点触发流控，已跳过！！ {$_url}".PHP_EOL.PHP_EOL, 'reseedLimit');
                        self::$wechatMsg['reseedSkip']++;
                        continue;
                    }
                    // 重复做种检测
                    if (isset($value['info_hash']) && isset($infohash_Dir[$value['info_hash']])) {
                        echo '-------与客户端现有种子重复：'.$_url.PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedRepeat']++;
                        continue;
                    }
                    // 历史添加检测
                    if (is_file(self::$cacheHash . $value['info_hash'].'.txt')) {
                        echo '-------当前种子上次辅种已成功添加，已跳过！ '.$_url.PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedPass']++;
                        continue;
                    }
                    // 检查站点是否可以辅种
                    if (in_array($siteName, self::$noReseed)) {
                        echo '-------已跳过不辅种的站点：'.$_url.PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedPass']++;
                        // 写入日志文件，供用户手动辅种
                        wlog('clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL.$_url.PHP_EOL.PHP_EOL, $siteName);
                        continue;
                    }
                    /**
                     * 种子URL组合方式区分
                     */
                    $url = self::getTorrentUrl($siteName, $_url);
                    $reseedPass = false;
                    // 特殊站点：种子元数据推送给下载器
                    switch ($siteName) {
                        case 'hdchina':
                            $cookie = isset($configALL[$siteName]['cookie']) ? $configALL[$siteName]['cookie'] : '';
                            $userAgent = $configALL['default']['userAgent'];
                            // 拼接URL
                            $details_page = str_replace('{}', $value['torrent_id'], 'details.php?id={}&hit=1');
                            $details_url = 'https://' .$sites[$sid]['base_url']. '/' .$details_page;
                            print "种子详情页：".$details_url.PHP_EOL;
                            $details_html = download($details_url, $cookie, $userAgent);
                            if (empty($details_html)) {
                                echo 'cookie已过期，请更新后重新辅种！已加入排除列表'.PHP_EOL;
                                $t = 30;
                                do {
                                    echo microtime(true)." cookie已过期，请更新后重新辅种！已加入排除列表！，{$t}秒后继续...".PHP_EOL;
                                    sleep(1);
                                } while (--$t > 0);
                                $configALL[$siteName]['cookie'] = '';
                                // 标志：跳过辅种
                                $reseedPass = true;
                                break;
                            }
                            if (strpos($details_html, '没有该ID的种子') != false) {
                                echo '种子已被删除！'.PHP_EOL;
                                self::sendNotify('404');
                                // 标志：跳过辅种
                                $reseedPass = true;
                                break;
                            }
                            // 提取种子下载地址
                            $download_page = str_replace('{}', '', $sites[$sid]['download_page']);
                            $offset = strpos($details_html, $download_page);
                            $urlTemp = substr($details_html, $offset, 50);
                            // 种子地址
                            $_url = substr($urlTemp, 0, strpos($urlTemp, '">'));
                            if (empty($_url)) {
                                echo '未知错误，未提取到种子URL，请联系脚本作者！'.PHP_EOL;
                                // 标志：跳过辅种
                                $reseedPass = true;
                                break;
                            }
                            $_url = 'https://' .$sites[$sid]['base_url']. '/' . $_url;
                            print "种子下载页：".$_url.PHP_EOL;
                            $url = download($_url, $cookie, $userAgent);
                            #p($url);
                            if (strpos($url, '第一次下载提示') != false) {
                                echo "当前站点触发第一次下载提示，已加入排除列表".PHP_EOL;
                                echo "请进入瓷器详情页，点右上角蓝色框：下载种子，成功后更新cookie！".PHP_EOL;
                                $t = 30;
                                do {
                                    echo microtime(true)." 请进入瓷器详情页，点右上角蓝色框：下载种子，成功后更新cookie！，{$t}秒后继续...".PHP_EOL;
                                    sleep(1);
                                } while (--$t > 0);
                                ff($siteName. '站点，辅种时触发第一次下载提示！');
                                self::$noReseed[] = 'hdchina';
                                // 标志：跳过辅种
                                $reseedPass = true;
                            }
                            if (strpos($url, '系统检测到过多的种子下载请求') != false) {
                                echo "当前站点触发人机验证，已加入排除列表".PHP_EOL;
                                ff($siteName. '站点，辅种时触发人机验证！');
                                $configALL[$siteName]['limit'] = 1;
                                self::$noReseed[] = 'hdchina';
                                // 标志：跳过辅种
                                $reseedPass = true;
                            }
                            break;
                        case 'hdcity':
                            $cookie = isset($configALL[$siteName]['cookie']) ? $configALL[$siteName]['cookie'] : '';
                            $userAgent = $configALL['default']['userAgent'];
                            print "种子：".$_url.PHP_EOL;
                            if (isset($configALL[$siteName]['cuhash'])) {
                                // 已获取cuhash
                                # code...
                            } else {
                                // 获取cuhash
                                $html = download('https://' .$sites[$sid]['base_url']. '/pt', $cookie, $userAgent);
                                // 提取种子下载地址
                                $offset = strpos($html, 'cuhash=');
                                $len = strlen('cuhash=');
                                $cuhashTemp = substr($html, $offset+$len, 40);
                                $configALL[$siteName]['cuhash'] = substr($cuhashTemp, 0, strpos($cuhashTemp, '"'));
                            }
                            $url = $_url."&cuhash=". $configALL[$siteName]['cuhash'];
                            // 城市下载种子时会302转向
                            $url = download($url, $cookie, $userAgent);
                            if (strpos($url, 'Non-exist torrent id!') != false) {
                                echo '种子已被删除！'.PHP_EOL;
                                self::sendNotify('404');
                                // 标志：跳过辅种
                                $reseedPass = true;
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
                    // 把拼接的种子URL，推送给下载器
                    echo '推送种子：' . $_url . PHP_EOL;
                    $ret = false;
                    // 成功返回：true
                    $ret = self::add($k, $url, $downloadDir);

                    // 按站点规范日志内容
                    switch ($siteName) {
                        case 'hdchina':
                            $url = $details_url;
                            break;
                        case 'hdcity':
                            $url = $_url;
                            break;
                        default:
                            break;
                    }
                    // 添加成功的种子，以infohash为文件名，写入缓存
                    $log = 'clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL.$url.PHP_EOL.PHP_EOL;
                    if ($ret) {
                        // 成功的种子
                        wlog($log, $value['info_hash'], self::$cacheHash);
                        wlog($log, 'reseedSuccess');
                        // 成功累加
                        self::$wechatMsg['reseedSuccess']++;
                    } else {
                        // 失败的种子
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
     * IYUUAutoReseed做种客户端转移
     */
    public static function move()
    {
        global $configALL;
        foreach (self::$links as $k => $v) {
            if (self::$move[0] == $k) {
                echo "clients_".$k."是目标转移客户端，避免冲突，已跳过！".PHP_EOL.PHP_EOL;
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
            // 循环转移做种客户端
            foreach ($infohash_Dir as $info_hash => $downloadDir) {
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
                        # code...
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
                } else {
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
                } else {
                    // 失败的种子
                    wlog($log, 'MoveError'.$k);
                }
            }
        }
    }
    /**
     * 过滤已转移的种子hash
     */
    public static function hashFilter(&$infohash_Dir = array())
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
     */
    public static function pathReplace($path = '')
    {
        global $configALL;
        $type = $configALL['default']['move']['type'];
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
     * 获取站点种子的URL
     */
    public static function getTorrentUrl($site = '', $_url = '')
    {
        global $configALL;
        switch ($site) {
            case 'ttg':
                $url = $_url."/". $configALL[$site]['passkey'];
                break;
            case 'm-team':
            case 'moecat':
            case 'hdbd':
                $ip_type = '';
                if (isset($configALL[$site]['ip_type'])) {
                    $ip_type = $configALL[$site]['ip_type'] == 'ipv6' ? '&ipv6=1' : '';
                }
                $url = $_url."&passkey=". $configALL[$site]['passkey'] . $ip_type. "&https=1";
                break;
            case 'dicmusic':
                $_url = str_replace('{torrent_pass}', $configALL[$site]['passkey'], $_url);
                $url = str_replace('{authkey}', $configALL[$site]['authkey'], $_url);
                break;
            case 'ccfbits':
            case 'hdroute':
                $url = str_replace('{passkey}', $configALL[$site]['passkey'], $_url);
                break;
            default:
                $url = $_url."&passkey=". $configALL[$site]['passkey'];
                break;
        }
        return $url;
    }
    /**
     * 微信模板消息拼接方法
     */
    public static function wechatMessage()
    {
        $br = PHP_EOL;
        $text = 'IYUU自动辅种-统计报表';
        $desp = '版本号：'. self::VER . $br;
        $desp .= '总做种：'.self::$wechatMsg['hashCount'] . '  [客户端正在做种的hash总数]' .$br;
        $desp .= '返回数据：'.self::$wechatMsg['reseedCount']. '  [服务器返回的可辅种数据]' .$br;
        $desp .= '支持站点：'.self::$wechatMsg['sitesCount']. '  [当前支持自动辅种的站点数量]' .$br;
        $desp .= '成功：'.self::$wechatMsg['reseedSuccess']. '  [辅种成功，会把hash加入缓存]' .$br;
        $desp .= '失败：'.self::$wechatMsg['reseedError']. '  [下载器下载种子失败或网络超时引起，可以重试]' .$br;
        $desp .= '重复：'.self::$wechatMsg['reseedRepeat']. '  [客户端已做种]' .$br;
        $desp .= '跳过：'.self::$wechatMsg['reseedSkip']. '  [未设置passkey]' .$br;
        $desp .= '忽略：'.self::$wechatMsg['reseedPass']. '  [成功添加存在缓存]' .$br;
        return ff($text, $desp);
    }
    /**
     * 错误的种子通知服务器
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
            echo '感谢您的参与，种子被删除，上报成功！！'.PHP_EOL;
        }
        return true;
    }
    /**
     * 设置通知主体
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
