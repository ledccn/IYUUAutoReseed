<?php
namespace IYUU\Client\qBittorrent;

use Curl\Curl;
use IYUU\Client\AbstractClientInterface;

/**
 * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation
 */
class qBittorrent implements AbstractClientInterface
{
    private $debug;
    private $url;
    private $api_version;
    private $curl;
    protected $delimiter;
    private $endpoints = [
        'login' => [
            '1' => '/login',
            '2' => '/api/v2/auth/login'
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
        'torrent_list' => [
            '1' => null,
            '2' => '/api/v2/torrents/info'
        ],
        'torrent_add' => [
            '1' => null,
            '2' => '/api/v2/torrents/add'
        ],
        'torrent_delete' => [
            '1' => null,
            '2' => '/api/v2/torrents/delete'
        ],
        'torrent_pause' => [
            '1' => null,
            '2' => '/api/v2/torrents/pause'
        ],
        'torrent_resume' => [
            '1' => null,
            '2' => '/api/v2/torrents/resume'
        ],
        'set_torrent_location' => [
            '1' => null,
            '2' => '/api/v2/torrents/setLocation'
        ],
        'maindata' => [
            '1' => null,
            '2' => '/api/v2/sync/maindata'
        ]
    ];

    public function __construct($url='', $username='', $password='', $api_version = 2, $debug = false)
    {
        $this->debug = $debug;
        $this->url = rtrim($url, '/');
        $this->username = $username;
        $this->password = $password;
        $this->api_version = $api_version;
        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false); // 禁止验证证书
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, false); // 不检查证书
        $this->curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);    // 超时
        $this->curl->setOpt(CURLOPT_TIMEOUT, 600);          // 超时
        // Authenticate and get cookie, else throw exception
        if (!$this->authenticate()) {
            throw new \Exception("Unable to authenticate with Web Api.");
        }
    }

    public function appVersion()
    {
        return $this->getData('app_version');
    }

    public function apiVersion()
    {
        return $this->getData('api_version');
    }

    public function buildInfo()
    {
        return $this->getData('build_info');
    }

    public function preferences($data = null)
    {
        if (!empty($data)) {
            return $this->postData('setPreferences', ['json' => json_encode($data)]);
        }

        return $this->getData('preferences');
    }

    public function torrentList()
    {
        return $this->getData('torrent_list');
    }
    /**
     * @param array $extra_options
        array(
            'urls'    =>  '',
            'savepath'    =>  '',
            'cookie'    =>  '',
            'category'    =>  '',
            'skip_checking'    =>  true,
            'paused'    =>  true,
            'root_folder'    =>  true,
        )
     *  @return array
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

    public function add_metainfo($torrent_metainfo, $save_path = '', $extra_options = array())
    {
        if (!empty($save_path)) {
            $extra_options['savepath'] = $save_path;
        }
        $extra_options['torrents'] = $torrent_metainfo;
        #$extra_options['skip_checking'] = 'true';    //跳校验
        // 关键 上传文件流 multipart/form-data【严格按照api文档编写】
        $post_data = $this->buildData($extra_options);
        #p($post_data);
        // 设置请求头
        $this->curl->setHeader('Content-Type', 'multipart/form-data; boundary='.$this->delimiter);
        $this->curl->setHeader('Content-Length', strlen($post_data));
        return $this->postData('torrent_add', $post_data);
    }

    public function torrentDelete($hash='', $deleteFiles = false)
    {
        return $this->postData('torrent_delete', ['hashes' => $hash, 'deleteFiles' => $deleteFiles ? 'true':'false']);
    }

    public function torrentDeleteAll($deleteFiles = false)
    {
        $torrents = json_decode($this->torrentList());
        $response = '';
        foreach ($torrents as $torrent) {
            $response .= $this->torrentDelete($torrent->hash, $deleteFiles);
        }

        return $response;
    }

    public function torrentPause($hash)
    {
        return $this->postData('torrent_pause', ['hashes' => $hash]);
    }

    public function torrentResume($hash)
    {
        return $this->postData('torrent_resume', ['hashes' => $hash]);
    }

    public function setTorrentLocation($hash, $location)
    {
        return $this->postData('set_torrent_location', ['hashes' => $hash, 'location' => $location]);
    }

    private function getData($endpoint)
    {
        $this->curl->get($this->url . $this->endpoints[$endpoint][$this->api_version]);

        if ($this->debug) {
            var_dump($this->curl->request_headers);
            var_dump($this->curl->response_headers);
        }

        if ($this->curl->error) {
            return $this->errorMessage();
        }

        return $this->curl->response;
    }

    private function postData($endpoint, $data)
    {
        $this->curl->post($this->url . $this->endpoints[$endpoint][$this->api_version], $data);

        if ($this->debug) {
            var_dump($this->curl->request_headers);
            var_dump($this->curl->response_headers);
        }

        if ($this->curl->error) {
            return $this->errorMessage();
        }

        return $this->curl->response;
    }

    private function authenticate()
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
                $this->curl->setHeader('Cookie', $matches[0]);
                return true;
            }
        };

        return false;
    }

    private function errorMessage()
    {
        return 'Curl Error Code: ' . $this->curl->error_code . ' (' . $this->curl->response . ')';
    }
    /**
     * 拼接种子urls multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     */
    private function buildUrls($param)
    {
        $this->delimiter = uniqid();
        $eol = "\r\n";
        $data = '';
        // 拼接文件流
        foreach ($param as $name => $content) {
            $data .= "--" . $this->delimiter . $eol;
            $data .= 'Content-Disposition: form-data; name' . '="' .$name. '"' . "\r\n\r\n";
            $data .= $content . $eol;
        }
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }
    /**
     * 拼接种子上传文件流 multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     */
    private function buildData($param)
    {
        $this->delimiter = uniqid();
        $eol = "\r\n";
        $data = '';
        $torrents = $param['torrents'];
        unset($param['torrents']);
        // 拼接文件流
        $data .= "--" . $this->delimiter . $eol
        . 'Content-Disposition: form-data; ';
        foreach ($param as $name => $content) {
            $data.= $name . '="' . $content.'"; ';
        }
        $data .= $eol;
        $data .= 'Content-Type: application/x-bittorrent'."\r\n\r\n";
        $data .= $torrents . $eol;
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function status()
    {
        return $this->appVersion();
    }
}
