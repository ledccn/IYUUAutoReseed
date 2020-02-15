<?php
/**
 * IYUUAutoReseed自动辅种
 */
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
    /**
     * 版本号
     * @var string
     */
    const VER = '1.0.0';
    /**
     * RPC连接池
     * @var array
     */
    public static $links = array();
    /**
     * 客户端配置
     * @var array
     */
    public static $clients = array();
    /**
     * 站点列表
     */
    public static $sites = array();
    /**
     * 不辅种的站点 'ourbits','hdchina'
     * @var array
     */
    public static $noReseed = array();
    /**
     * 不转移的站点 'hdarea','hdbd'
     * @var array
     */
    public static $noMove = array('hdarea');
    /**
     * cookie检查
     * @var array
     */
    public static $cookieCheck = array('hdchina','hdcity');
    /**
     * 缓存路径
     * @var string
     */
    public static $cacheDir  = TORRENT_PATH.'cache'.DS;
    public static $cacheHash = TORRENT_PATH.'cachehash'.DS;
    public static $cacheMove = TORRENT_PATH.'cachemove'.DS;
    /**
     * API接口配置
     * @var string
     * @var array
     */
    public static $apiUrl = 'http://api.iyuu.cn';
    public static $endpoints = array(
        'add'     => '/api/add',
        'update'  => '/api/update',
        'reseed'  => '/api/reseed',
        'infohash'=> '/api/infohash',
        'sites'   => '/api/sites',
        'move'    => '/api/move',
        'login'   => '/user/login',
    );
    /**
     * curl
     */
    public static $curl = null;
    /**
     * 退出状态码
     * @var int
     */
    public static $ExitCode = 0;
    /**
     * 客户端转移做种
     * @var array
     */
    public static $move = null;
    /**
     * 微信消息体
     * @var array
     */
    public static $wechatMsg = array(
        'hashCount'			=>	0,		// 提交给服务器的hash总数
        'sitesCount'		=>	0,		// 可辅种站点总数
        'reseedCount'		=>	0,		// 返回的总数据
        'reseedSuccess'		=>	0,		// 成功：辅种成功（会加入缓存，哪怕种子在校验中，下次也会过滤）
        'reseedError'		=>	0,		// 错误：辅种失败（可以重试）
        'reseedRepeat'		=>	0,		// 重复：客户端已做种
        'reseedSkip'		=>	0,		// 跳过：因未设置passkey，而跳过
        'reseedPass'		=>	0,		// 忽略：因上次成功添加、存在缓存，而跳过
    );
    /**
     * 初始化
     * @return void
     */
    public static function init()
    {
        global $configALL;
        self::$curl = new Curl();
        self::$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        // 显示支持站点列表
        self::ShowTableSites();
        self::$clients = isset($configALL['default']['clients']) && $configALL['default']['clients'] ? $configALL['default']['clients'] : array();
        echo "程序正在初始化运行参数... ".PHP_EOL;
        // 递归删除上次历史记录
        IFile::rmdir(self::$cacheDir, true);
        // 建立目录
        IFile::mkdir(self::$cacheDir);
        IFile::mkdir(self::$cacheHash);
        IFile::mkdir(self::$cacheMove);
        // 连接全局客户端
        self::links();
        // 合作站点自动注册鉴权
        Oauth::login(self::$apiUrl . self::$endpoints['login']);
    }

    /**
     * 显示支持站点列表
     */
    private static function ShowTableSites()
    {
        $list[] = 'gitee 源码仓库：https://gitee.com/ledc/IYUUAutoReseed';
        $list[] = 'github源码仓库：https://github.com/ledccn/IYUUAutoReseed';
        $list[] = '教程：https://gitee.com/ledc/IYUUAutoReseed/tree/master/wiki';
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
                die($rs['msg']);
            }
            die('远端服务器无响应，请稍后再试！！！');
        }
        $data = [];
        $i = $j = $k = 0;
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
     * 连接远端RPC服务器
     *
     * @param string
     * @return bool
     */
    public static function links()
    {
        if (empty(self::$links)) {
            foreach (self::$clients as $k => $v) {
                // 跳过未配置的客户端
                if (empty($v['username']) || empty($v['password'])) {
                    self::$links[$k] = array();
                    echo "clients_".$k." 用户名或密码未配置，已跳过".PHP_EOL.PHP_EOL;
                    continue;
                }
                try {
                    $client = AbstractClient::create($v);
                    self::$links[$k]['BT_backup'] = isset($v['BT_backup']) && $v['BT_backup'] ? $v['BT_backup'] : '';
                    self::$links[$k]['type'] = $v['type'];
                    self::$links[$k]['rpc'] = $client;
                    $result = $client->status();
                    print $v['type'].'：'.$v['host']." Rpc连接 [{$result}] \n";
                    // 检查是否转移种子的做种客户端？
                    if (isset($v['move']) && $v['move'] && is_null(self::$move)) {
                        self::$move = array($k,$v['move']);
                    }
                } catch (\Exception $e) {
                    echo '[Links ERROR] ' . $e->getMessage() . PHP_EOL;
                    exit(1);
                }
            }
        }
        return true;
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
                    $extra_options['paused'] = true;
                    if ($is_url) {
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// 种子URL添加下载任务
                    } else {
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 种子元数据添加下载任务
                    }
                    if (isset($result->result) && $result->result == 'success') {
                        $id = $name = '';
                        if (isset($result->arguments->torrent_duplicate)) {
                            $id = $result->arguments->torrent_duplicate->id;
                            $name = $result->arguments->torrent_duplicate->name;
                        } elseif (isset($result->arguments->torrent_added)) {
                            $id = $result->arguments->torrent_added->id;
                            $name = $result->arguments->torrent_added->name;
                        }
                        if ($is_url) {
                            print "种子：".$torrent . PHP_EOL;
                        }
                        print "名字：".$name . PHP_EOL;
                        print "********RPC添加下载任务成功 [{$result->result}] (id=$id)".PHP_EOL.PHP_EOL;
                        
                        return true;
                    } else {
                        $errmsg = isset($result->result) ? $result->result : '未知错误，请稍后重试！';
                        if ($is_url) {
                            print "种子：" . substr($torrent, 0, (strpos($torrent, 'passkey') ? strpos($torrent, 'passkey') : strlen($torrent))) . PHP_EOL;
                        }
                        print "-----RPC添加种子任务，失败 [{$errmsg}]" . PHP_EOL.PHP_EOL;
                    }
                    break;
                case 'qBittorrent':
                    $extra_options['paused'] = 'true';
                    $extra_options['autoTMM'] = 'false';	//关闭自动种子管理
                    if ($is_url) {
                        $result = self::$links[$rpcKey]['rpc']->add($torrent, $save_path, $extra_options);			// 种子URL添加下载任务
                    } else {
                        $extra_options['name'] = 'torrents';
                        $rand = mt_rand(10, 42949672);
                        $extra_options['filename'] = intval($rand).'.torrent';
                        $result = self::$links[$rpcKey]['rpc']->add_metainfo($torrent, $save_path, $extra_options);	// 种子元数据添加下载任务
                    }
                    if ($is_url) {
                        print "种子：". substr($torrent, 0, (strpos($torrent, 'passkey') ? strpos($torrent, 'passkey') : strlen($torrent))) . PHP_EOL;
                    }
                    if ($result === 'Ok.') {
                        print "********RPC添加下载任务成功 [{$result}]".PHP_EOL.PHP_EOL;
                        return true;
                    } else {
                        print "-----RPC添加种子任务，失败 [{$result}]".PHP_EOL.PHP_EOL;
                    }
                    break;
                default:
                    echo '[ERROR] '.$type. PHP_EOL. PHP_EOL;
                    break;
            }
        } catch (\Exception $e) {
            echo '[ERROR] ' . $e->getMessage() . PHP_EOL;
        }
        return false;
    }
    /**
     * @brief 提交种子hash给远端API，用来获取辅种数据
     * @return
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
            if (self::$move!==null && self::$move[1]==2) {
                echo "clients_".$k." 根据设置无需辅种，已跳过！";
                continue;
            }
            echo "正在从下载器 clients_".$k." 获取种子哈希……".PHP_EOL;
            $hashArray = self::$links[$k]['rpc']->getList();
            if (empty($hashArray)) {
                // 失败
                continue;
            } else {
                $infohash_Dir = $hashArray['hashString'];
                #p($infohash_Dir);
                unset($hashArray['hashString']);
                // 签名
                $hashArray['sign'] = Oauth::getSign();
                $hashArray['timestamp'] = time();
                $hashArray['version'] = self::VER;
                // 写请求日志
                wlog($hashArray, 'hashString'.$k);
                self::$wechatMsg['hashCount'] +=count($infohash_Dir);
            }
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
                    $sitesID = $value['sid'];
                    // 站点名
                    $siteName = $sites[$sitesID]['site'];
                    // 页面规则
                    $download_page = str_replace('{}', $value['torrent_id'], $sites[$sitesID]['download_page']);
                    $_url = 'https://' .$sites[$sitesID]['base_url']. '/' .$download_page;
                    echo "clients_".$k."正在辅种... {$siteName}".PHP_EOL;
                    /**
                     * 前置检测
                     */
                    // passkey检测
                    if (empty($configALL[$siteName]['passkey'])) {
                        echo '-------因当前' .$siteName. "站点未设置passkey，已跳过！！".PHP_EOL.PHP_EOL;
                        self::$wechatMsg['reseedSkip']++;
                        continue;
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
                            $details_page = str_replace('{}', $value['torrent_id'], 'details.php?id={}&hit=1');
                            $_url = 'https://' .$sites[$sitesID]['base_url']. '/' .$details_page;
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
                    /**
                     * 种子URL组合方式区分
                     */
                    $url = self::getTorrentUrl($siteName, $_url);
                    /**
                     * 检查站点是否可以辅种
                     */
                    if ((in_array($siteName, self::$noReseed)==false)) {
                        /**
                         *  可以辅种
                         */
                        // 特殊站点：推送给下载器种子元数据
                        switch ($siteName) {
                            case 'hdchina':
                                $cookie = isset($configALL[$siteName]['cookie']) ? $configALL[$siteName]['cookie'] : '';
                                $userAgent = $configALL['default']['userAgent'];
                                // 拼接URL
                                $details_page = str_replace('{}', $value['torrent_id'], 'details.php?id={}&hit=1');
                                $details_url = 'https://' .$sites[$sitesID]['base_url']. '/' .$details_page;
                                $details_html = download($details_url, $cookie, $userAgent);
                                if (strpos($details_html, '没有该ID的种子') != false) {
                                    # code... 错误通知
                                }
                                print "种子详情页：".$details_url.PHP_EOL;
                                // 提取种子下载地址
                                $download_page = str_replace('{}', '', $sites[$sitesID]['download_page']);
                                $offset = strpos($details_html, $download_page);
                                $urlTemp = substr($details_html, $offset, 50);
                                // 种子地址
                                $_url = substr($urlTemp, 0, strpos($urlTemp, '">'));
                                $_url = 'https://' .$sites[$sitesID]['base_url']. '/' . $_url;
                                print "种子下载页：".$_url.PHP_EOL;
                                $url = download($_url, $cookie, $userAgent);
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
                                }
                                if (strpos($url, '系统检测到过多的种子下载请求') != false) {
                                    echo "当前站点触发人机验证，已加入排除列表".PHP_EOL;
                                    ff($siteName. '站点，辅种时触发人机验证！');
                                    $configALL[$siteName]['limit'] = 1;
                                    self::$noReseed[] = 'hdchina';
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
                                    $html = download('https://' .$sites[$sitesID]['base_url']. '/pt', $cookie, $userAgent);
                                    // 提取种子下载地址
                                    $offset = strpos($html, 'cuhash=');
                                    $len = strlen('cuhash=');
                                    $cuhashTemp = substr($html, $offset+$len, 40);
                                    $configALL[$siteName]['cuhash'] = substr($cuhashTemp, 0, strpos($cuhashTemp, '"'));
                                }
                                $url = $_url."&cuhash=". $configALL[$siteName]['cuhash'];
                                // 城市下载种子时会302转向
                                $url = download($url, $cookie, $userAgent);
                                break;
                            default:
                                // 默认站点：推送给下载器种子URL链接
                                break;
                        }
                        // 把拼接的种子URL，推送给下载器
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
                        if ($ret) {
                            // 成功的种子
                            wlog($url.PHP_EOL, $value['info_hash'], self::$cacheHash);
                            wlog($url.PHP_EOL, 'reseedSuccess');
                            // 成功累加
                            self::$wechatMsg['reseedSuccess']++;
                            continue;
                        } else {
                            // 失败的种子
                            wlog($url.PHP_EOL, 'reseedError');
                            // 失败累加
                            self::$wechatMsg['reseedError']++;
                            continue;
                        }
                    } else {
                        /**
                         *  不辅种
                         */
                        echo '-------已跳过不辅种的站点：'.$_url.PHP_EOL.PHP_EOL;
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
                        // 写入日志文件，供用户手动辅种
                        wlog('clients_'.$k.PHP_EOL.$downloadDir.PHP_EOL.$url.PHP_EOL.$details_url.PHP_EOL.PHP_EOL, $siteName);
                    }
                }
            }
        }
        // 按客户端循环辅种 结束
    }
    /**
     * IYUUAutoReseed做种客户端转移
     */
    public static function move()
    {
        global $configALL;
        $sites = self::$sites;
        foreach (self::$links as $k => $v) {
            if (self::$move[0] == $k) {
                echo "clients_".$k."是目标转移客户端，避免冲突，已跳过！".PHP_EOL.PHP_EOL;
                continue;
            }
            echo "正在从下载器 clients_".$k." 获取种子哈希……".PHP_EOL;
            $hashArray = self::$links[$k]['rpc']->getList($move);
            #p($move);exit;
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

            foreach ($infohash_Dir as $info_hash => $downloadDir) {
                // 做种实际路径与相对路径之间互转
                echo '转换前：'.$downloadDir.PHP_EOL;
                $downloadDir = self::pathReplace($downloadDir);
                echo '转换后：'.$downloadDir.PHP_EOL;
                if (is_null($downloadDir)) {
                    die("全局配置的move数组内，路径转换参数配置错误，请重新配置！！！".PHP_EOL);
                }

                // 种子扩展参数
                $extra_options = array();
                $path = self::$links[$k]['BT_backup'];
                // 待删除种子
                $torrentDelete = '';

                // 获取种子原文件的实际路径

                switch ($v['type']) {
                    case 'transmission':
                        $torrentPath = $move[$info_hash]['torrentFile'];
                        $torrentDelete = $move[$info_hash]['id'];
                        // 脚本与tr不在一起的兼容性处理
                        if (!is_file($torrentPath)) {
                            $torrentPath = str_replace("\\", "/", $torrentPath);
                            $torrentPath = $path . strrchr($torrentPath, '/');
                        }
                        break;
                    case 'qBittorrent':
                        if (empty($path)) {
                            die("clients_".$k." 未设置种子的BT_backup目录，无法完成转移！");
                        }
                        $torrentPath = $path .DS. $info_hash . '.torrent';
                        $torrentDelete = $info_hash;
                        $extra_options['skip_checking'] = 'true';    //跳校验
                        break;
                    default:
                        # code...
                        break;
                }
                
                // 判断种子原文件是否存在
                if (!is_file($torrentPath)) {
                    die("clients_".$k." 的种子文件{$torrentPath}不存在，无法完成转移！");
                }
                echo '存在种子：'.$torrentPath.PHP_EOL;
                $torrent = file_get_contents($torrentPath);
                // 正式开始转移
                echo "种子已推送给下载器，正在转移做种...".PHP_EOL;
                $ret = false;
                // 成功返回：true
                $ret = self::add(self::$move[0], $torrent, $downloadDir, $extra_options);

                /**
                 * 转移成功的种子写日志
                 */
                $log = $info_hash.PHP_EOL.$torrentPath.PHP_EOL.$downloadDir.PHP_EOL.PHP_EOL;
                if ($ret) {
                    // 删除做种，不删资源
                    #self::$links[$k]['rpc']->delete($torrentDelete);

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
                    if (strpos($path, $key)===0) {
                        return $val . $path;
                    }
                }
                break;
            case 3:         // 替换
                foreach ($pathArray as $key => $val) {
                    if (strpos($path, $key)===0) {
                        return $val . substr($path, strlen($key));
                    }
                }
                break;
            default:
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
                $ip_type = '';
                if (isset($configALL[$site]['ip_type'])) {
                    $ip_type = $configALL[$site]['ip_type'] == 'ipv6' ? '&ipv6=1' : '';
                }
                $url = $_url."&passkey=". $configALL[$site]['passkey'] . $ip_type. "&https=1";
                break;
            case 'ccfbits':
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
        $desp = '总做种：'.self::$wechatMsg['hashCount'] . '  [客户端正在做种的hash总数]' .$br;
        $desp .= '返回数据：'.self::$wechatMsg['reseedCount']. '  [服务器返回的可辅种数据]' .$br;
        $desp .= '支持站点：'.self::$wechatMsg['sitesCount']. '  [当前支持自动辅种的站点数量]' .$br;
        $desp .= '成功：'.self::$wechatMsg['reseedSuccess']. '  [辅种成功，会把hash加入缓存]' .$br;
        $desp .= '失败：'.self::$wechatMsg['reseedError']. '  [下载器下载种子失败或网络超时引起，可以重试]' .$br;
        $desp .= '重复：'.self::$wechatMsg['reseedRepeat']. '  [客户端已做种]' .$br;
        $desp .= '跳过：'.self::$wechatMsg['reseedSkip']. '  [未设置passkey]' .$br;
        $desp .= '忽略：'.self::$wechatMsg['reseedPass']. '  [成功添加存在缓存]' .$br;
        return ff($text, $desp);
    }
}
