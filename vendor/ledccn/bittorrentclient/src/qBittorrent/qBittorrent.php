<?php
namespace IYUU\Client\qBittorrent;

use Curl\Curl;
use IYUU\Client\AbstractClient;
use IYUU\Client\ClientException;

/**
 * qBittorrent下载服务器的API操作类
 * 开源项目地址：https://github.com/qbittorrent/qBittorrent
 * API文档：https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation
 */
class qBittorrent extends AbstractClient
{
    /**
     * API主版本号
     * @var int|mixed|string
     */
    private $api_version = '';

    /**
     * CSRF使用的Session或者Cookie
     * @var string
     */
    private $session_id = '';

    /**
     * curl实例
     * @var Curl
     */
    private $curl;

    /**
     * 分隔符
     * @var string
     */
    protected $delimiter = '';

    /**
     * 各版的API接入点
     * @var array
     */
    private $endpoints = [
        'login' => [
            '1' => '/login',
            '2' => '/api/v2/auth/login'
        ],
        'logout'=> [
            '1' => null,
            '2' => '/api/v2/auth/logout'
        ],
        'app_version' => [
            '1' => '/version/qbittorrent',
            '2' => '/api/v2/app/version'
        ],
        'api_version' => [
            '1' => '/version/api',
            '2' => '/api/v2/app/webapiVersion'
        ],
        'build_info' => [
            '1' => null,
            '2' => '/api/v2/app/buildInfo'
        ],
        'preferences' => [
            '1' => null,
            '2' => '/api/v2/app/preferences'
        ],
        'setPreferences' => [
            '1' => null,
            '2' => '/api/v2/app/setPreferences'
        ],
        'defaultSavePath' => [
            '1' => null,
            '2' => '/api/v2/app/defaultSavePath'
        ],
        'downloadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/downloadLimit'
        ],
        'setDownloadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/setDownloadLimit'
        ],
        'uploadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/uploadLimit'
        ],
        'setUploadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/setUploadLimit'
        ],
        'torrent_list' => [
            '1' => null,
            '2' => '/api/v2/torrents/info'
        ],
        'torrent_properties' => [
            '1' => null,
            '2' => '/api/v2/torrents/properties'
        ],
        'torrent_trackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/trackers'
        ],
        'torrent_files' => [
            '1' => null,
            '2' => '/api/v2/torrents/files'
        ],
        'torrent_pieceStates' => [
            '1' => null,
            '2' => '/api/v2/torrents/pieceStates'
        ],
        'torrent_pieceHashes' => [
            '1' => null,
            '2' => '/api/v2/torrents/pieceHashes'
        ],
        'torrent_pause' => [
            '1' => null,
            '2' => '/api/v2/torrents/pause'
        ],
        'torrent_resume' => [
            '1' => null,
            '2' => '/api/v2/torrents/resume'
        ],
        'torrent_delete' => [
            '1' => null,
            '2' => '/api/v2/torrents/delete'
        ],
        'torrent_recheck' => [
            '1' => null,
            '2' => '/api/v2/torrents/recheck'       // 重新校验种子
        ],
        'torrent_reannounce' => [
            '1' => null,
            '2' => '/api/v2/torrents/reannounce'    // 重新宣告种子
        ],
        'torrent_add' => [
            '1' => null,
            '2' => '/api/v2/torrents/add'
        ],
        'torrent_addTrackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/addTrackers'
        ],
        'torrent_editTracker' => [
            '1' => null,
            '2' => '/api/v2/torrents/editTracker'
        ],
        'torrent_removeTrackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeTrackers'
        ],
        'torrent_addPeers' => [
            '1' => null,
            '2' => '/api/v2/torrents/addPeers'
        ],
        'torrent_increasePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/increasePrio'
        ],
        'torrent_decreasePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/decreasePrio'
        ],
        'torrent_downloadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/downloadLimit'
        ],
        'torrent_setDownloadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/setDownloadLimit'
        ],
        'torrent_setShareLimits' => [
            '1' => null,
            '2' => '/api/v2/torrents/setShareLimits'
        ],
        'torrent_uploadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/uploadLimit'
        ],
        'torrent_setUploadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/setUploadLimit'
        ],
        'torrent_setLocation' => [
            '1' => null,
            '2' => '/api/v2/torrents/setLocation'
        ],
        'torrent_rename' => [
            '1' => null,
            '2' => '/api/v2/torrents/rename'
        ],
        'torrent_setCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/setCategory'
        ],
        'torrent_categories' => [
            '1' => null,
            '2' => '/api/v2/torrents/categories'
        ],
        'torrent_createCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/createCategory'
        ],
        'torrent_editCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/editCategory'
        ],
        'torrent_removeCategories' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeCategories'
        ],
        'torrent_addTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/addTags'
        ],
        'torrent_removeTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeTags'
        ],
        'torrent_tags' => [
            '1' => null,
            '2' => '/api/v2/torrents/tags'
        ],
        'torrent_createTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/createTags'
        ],
        'torrent_deleteTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/deleteTags'
        ],
        'torrent_setAutoManagement' => [
            '1' => null,
            '2' => '/api/v2/torrents/setAutoManagement'
        ],
        'torrent_toggleSequentialDownload' => [
            '1' => null,
            '2' => '/api/v2/torrents/toggleSequentialDownload'
        ],
        'torrent_toggleFirstLastPiecePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/toggleFirstLastPiecePrio'
        ],
        'torrent_setForceStart' => [
            '1' => null,
            '2' => '/api/v2/torrents/setForceStart'
        ],
        'torrent_setSuperSeeding' => [
            '1' => null,
            '2' => '/api/v2/torrents/setSuperSeeding'
        ],
        'torrent_renameFile' => [
            '1' => null,
            '2' => '/api/v2/torrents/renameFile'
        ],
        'maindata' => [
            '1' => null,
            '2' => '/api/v2/sync/maindata'
        ],
    ];

    /**
     * 构造函数
     * @param array $config
     * @throws ClientException
     */
    public function __construct($config = [])
    {
        $this->initialize($config);
        $this->api_version = isset($config['api_version']) && $config['api_version'] ? $config['api_version'] : 2;
        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false); // 禁止验证证书
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);     // 不检查证书
        $this->curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);    // 超时
        $this->curl->setOpt(CURLOPT_TIMEOUT, 600);          // 超时
        if (!$this->login()) {
            throw new ClientException("qBittorrent Unable to authenticate with Web Api.");
        }
    }

    /**
     * app编译版本
     * @return string
     */
    public function appVersion()
    {
        return $this->getData('app_version');
    }

    /**
     * api版本
     * @return string
     */
    public function apiVersion()
    {
        return $this->getData('api_version');
    }

    /**
     * 编译信息
     * @return array
     */
    public function buildInfo()
    {
        return $this->getData('build_info');
    }

    /**
     * 下载器验证
     * @return bool
     */
    public function login()
    {
        $this->curl->post($this->url . $this->endpoints['login'][$this->api_version], [
            'username' => $this->username,
            'password' => $this->password
        ]);

        if ($this->debug) {
            var_dump($this->curl->request_headers);
            var_dump($this->curl->response_headers);
        }

        // Find authentication cookie and set in curl connection
        foreach ($this->curl->response_headers as $header) {
            if (preg_match('/SID=(\S[^;]+)/', $header, $matches)) {
                $this->session_id = $matches[0];
                $qb415 = '; QB_'.$matches[0];   // 兼容qBittorrent v4.1.5[小钢炮等]
                $this->curl->setHeader('Cookie', $matches[0].$qb415);
                return true;
            }
        };

        return false;
    }

    /**
     * 退出登录
     * @return mixed
     */
    public function logout()
    {
        $this->session_id = '';
        $this->getData('logout');
        $this->curl->reset();
        return $this;
    }

    /**
     * 获取下载器首选项
     * @return mixed
     */
    public function preferences()
    {
        return $this->getData('preferences');
    }

    /**
     * 设置下载器首选项
     * @param array $data
     * @return array|mixed
     */
    public function setPreferences($data = [])
    {
        if (!empty($data)) {
            return $this->postData('setPreferences', ['json' => json_encode($data)]);
        }
        return [];
    }

    /**
     * 获取种子列表
     * @return mixed
     */
    public function torrentList()
    {
        return $this->getData('torrent_list');
    }

    /**
     * 添加种子链接
     * @param $torrent_url
     * @param string $save_path
     * @param array $extra_options
     * array(
     * 'urls'    =>  '',
     * 'savepath'    =>  '',
     * 'cookie'    =>  '',
     * 'category'    =>  '',
     * 'skip_checking'    =>  true,
     * 'paused'    =>  true,
     * 'root_folder'    =>  true,
     * )
     * @return array
     */
    public function add($torrent_url, $save_path = '', $extra_options = array())
    {
        if (!empty($save_path)) {
            $extra_options['savepath'] = $save_path;
        }
        $extra_options['urls'] = $torrent_url;
        #$extra_options['skip_checking'] = 'true';    //跳校验
        // 关键 上传文件流 multipart/form-data【严格按照api文档编写】
        $post_data = $this->buildUrls($extra_options);
        #p($post_data);
        // 设置请求头
        $this->curl->setHeader('Content-Type', 'multipart/form-data; boundary='.$this->delimiter);
        $this->curl->setHeader('Content-Length', strlen($post_data));
        return $this->postData('torrent_add', $post_data);
    }

    /**
     * 添加种子元数据
     * @param string $torrent_metainfo
     * @param string $save_path
     * @param array $extra_options
     * @return false|string|null
     */
    public function add_metainfo($torrent_metainfo, $save_path = '', $extra_options = array())
    {
        if (!empty($save_path)) {
            $extra_options['savepath'] = $save_path;
        }
        $extra_options['torrents'] = $torrent_metainfo;
        #$extra_options['skip_checking'] = 'true';    //跳校验
        // 关键 上传文件流 multipart/form-data【严格按照api文档编写】
        $post_data = $this->buildData($extra_options);
        // 设置请求头
        $this->curl->setHeader('Content-Type', 'multipart/form-data; boundary='.$this->delimiter);
        $this->curl->setHeader('Content-Length', strlen($post_data));
        return $this->postData('torrent_add', $post_data);
    }

    /**
     * 删除所有种子
     * @param bool $deleteFiles
     * @return string
     */
    public function deleteAll($deleteFiles = false)
    {
        $torrents = json_decode($this->torrentList());
        $response = '';
        foreach ($torrents as $torrent) {
            $response .= $this->delete($torrent->hash, $deleteFiles);
        }

        return $response;
    }

    /**
     * 暂停种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     */
    public function pause($hash)
    {
        return $this->postData('torrent_pause', ['hashes' => $hash]);
    }

    /**
     * 恢复做种
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     */
    public function resume($hash)
    {
        return $this->postData('torrent_resume', ['hashes' => $hash]);
    }

    /**
     * 抽象方法，子类实现
     * 删除种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @param bool $deleteFiles     是否同时删除数据
     * @return false|string|null
     */
    public function delete($hash = '', $deleteFiles = false)
    {
        return $this->postData('torrent_delete', ['hashes' => $hash, 'deleteFiles' => $deleteFiles ? 'true':'false']);
    }

    /**
     * 重新校验种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     */
    public function recheck($hash)
    {
        return $this->postData('torrent_recheck', ['hashes' => $hash]);
    }

    /**
     * 重新宣告种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     */
    public function reannounce($hash)
    {
        return $this->postData('torrent_reannounce', ['hashes' => $hash]);
    }

    /**
     * @param $hash
     * @param $location
     * @return false|string|null
     */
    public function setTorrentLocation($hash, $location)
    {
        return $this->postData('torrent_setLocation', ['hashes' => $hash, 'location' => $location]);
    }

    /**
     * 获取当前Curl对象
     * @return Curl
     */
    public function curl()
    {
        return $this->curl;
    }

    /**
     * 基本get方法
     * @param $endpoint
     * @return mixed
     */
    private function getData($endpoint)
    {
        $this->curl->get($this->url . $this->endpoints[$endpoint][$this->api_version]);

        if ($this->debug) {
            var_dump($this->curl->request_headers);
            var_dump($this->curl->response_headers);
            var_dump($this->curl->response);
        }

        if ($this->curl->error) {
            return $this->errorMessage();
        }

        return $this->curl->response;
    }

    /**
     * 基本post方法
     * @param $endpoint
     * @param $data
     * @return mixed
     */
    private function postData($endpoint, $data)
    {
        $this->curl->post($this->url . $this->endpoints[$endpoint][$this->api_version], $data);

        if ($this->debug) {
            var_dump($this->curl->request_headers);
            var_dump($this->curl->response_headers);
            var_dump($this->curl->response);
        }

        if ($this->curl->error) {
            return $this->errorMessage();
        }

        return $this->curl->response;
    }

    /**
     * 返回错误信息
     * @return string
     */
    private function errorMessage()
    {
        return 'Curl Error Code: ' . $this->curl->error_code . ' (' . $this->curl->response . ')';
    }

    /**
     * 拼接种子urls multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     * @param array $param
     * @return string
     */
    public function buildUrls($param)
    {
        $this->delimiter = uniqid();
        $eol = "\r\n";
        $data = '';
        // 拼接文件流
        foreach ($param as $name => $content) {
            $data .= "--" . $this->delimiter . $eol;
            $data .= 'Content-Disposition: form-data; name="' .$name. '"' . $eol . $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }

    /**
     * 拼接种子上传文件流 multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     * @param array $param
     * @return string
     */
    public function buildData($param)
    {
        $this->delimiter = uniqid();
        $eol = "\r\n";
        $data = '';
        // 拼接文件流
        $data .= "--" . $this->delimiter . $eol;
        $data .= 'Content-Disposition: form-data; name="' .$param['name']. '"; filename="'.$param['filename'].'"' . $eol;
        $data .= 'Content-Type: application/x-bittorrent' . $eol . $eol;
        $data .= $param['torrents'] . $eol;
        unset($param['name']);
        unset($param['filename']);
        unset($param['torrents']);
        if (!empty($param)) {
            foreach ($param as $name => $content) {
                $data .= "--" . $this->delimiter . $eol;
                $data .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
                $data .= $content . $eol;
            }
        }
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }

    /**
     * 抽象方法，子类实现
     */
    public function status()
    {
        return $this->appVersion();
    }

    /**
     * 抽象方法，子类实现
     * @param array $torrentList
     * @return array
     */
    public function all(&$torrentList = array())
    {
        $result = $this->getData('torrent_list');
        $res = json_decode($result, true);
        if (empty($res)) {
            echo "获取种子列表失败，可能qBittorrent暂时无响应，请稍后重试！".PHP_EOL;
            return array();
        }
        // 过滤，只保留正常做种
        $res = array_filter($res, function ($v) {
            if (isset($v['state']) && in_array($v['state'], array('uploading','stalledUP','pausedUP','queuedUP','checkingUP','forcedUP'))) {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($res)) {
            echo "未获取到正常做种数据，请多保种，然后重试！".PHP_EOL;
            return array();
        }
        // 提取数组：hashString
        $info_hash = array_column($res, 'hash');
        // 升序排序
        sort($info_hash);
        $json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
        // 去重 应该从文件读入，防止重复提交
        $sha1 = sha1($json);
        // 组装返回数据
        $hashArray['hash'] = $json;
        $hashArray['sha1'] = $sha1;
        // 变换数组：hashString键名、目录为键值
        $hashArray['hashString'] = array_column($res, "save_path", 'hash');
        $torrentList = array_column($res, null, 'hash');
        return $hashArray;
    }
}
