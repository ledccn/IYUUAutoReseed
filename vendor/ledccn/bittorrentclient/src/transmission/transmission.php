<?php
namespace IYUU\Client\transmission;

use Curl\Curl;
use IYUU\Client\AbstractClient;
use IYUU\Client\ClientException;

/**
 * Transmission下载服务器的RPC操作类
 * 开源项目地址：https://github.com/transmission/transmission
 * API文档：https://github.com/transmission/transmission/blob/master/extras/rpc-spec.txt
 */
class transmission extends AbstractClient
{
    /**
     * UserAgent
     */
    const UA = 'TransmissionRPC for PHP/7.0.0';

    /**
     * Transmission RPC version
     * @var int
     */
    protected $rpc_version = 0;

    /**
     * CSRF使用的Session或者Cookie
     * @var string
     */
    protected $session_id = '';

    /**
     * 种子状态码 torrent status
     */
    const TR_STATUS_STOPPED         = 0;    // Torrent is stopped
    const TR_STATUS_CHECK_WAIT      = 1;    // Queued to check files
    const TR_STATUS_CHECK           = 2;    // Checking files
    const TR_STATUS_DOWNLOAD_WAIT   = 3;    // Queued to download
    const TR_STATUS_DOWNLOAD        = 4;    // Downloading
    const TR_STATUS_SEED_WAIT       = 5;    // Queued to seed
    const TR_STATUS_SEED            = 6;    // Seeding

    const RPC_LT_14_TR_STATUS_CHECK_WAIT = 1;
    const RPC_LT_14_TR_STATUS_CHECK = 2;
    const RPC_LT_14_TR_STATUS_DOWNLOAD = 4;
    const RPC_LT_14_TR_STATUS_SEED = 8;
    const RPC_LT_14_TR_STATUS_STOPPED = 16;

    /**
     * Exception: Invalid arguments
     */
    const E_INVALIDARG = -1;

    /**
     * Exception: Invalid Session-Id
     */
    const E_SESSIONID = -2;

    /**
     * Exception: Error while connecting
     */
    const E_CONNECTION = -3;

    /**
     * Exception: Error 401 returned, unauthorized
     */
    const E_AUTHENTICATION = -4;

    /**
     * Curl实例
     * @var
     */
    private $curl;

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->initialize($config);
        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);    // 超时
        $this->curl->setOpt(CURLOPT_TIMEOUT, 600);          // 超时
    }

    /**
     * 开始一个或多个种子
     * Start one or more torrents
     * @param int|array ids A list of transmission torrent ids
     * @return mixed
     * @throws ClientException
     */
    public function start($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array("ids" => $ids);
        return $this->request("torrent-start", $request);
    }

    /**
     * 停止一个或多个种子
     * Stop one or more torrents
     * @param int|array ids A list of transmission torrent ids
     * @return mixed
     * @throws ClientException
     */
    public function stop($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array("ids" => $ids);
        return $this->request("torrent-stop", $request);
    }

    /**
     * 校验一个或多个种子
     * Verify one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     * @return mixed
     * @throws ClientException
     */
    public function verify($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array("ids" => $ids);
        return $this->request("torrent-verify", $request);
    }

    /**
     * 删除一个或多个种子
     * Remove torrent from transmission
     * @param int|array ids A list of transmission torrent ids
     * @param bool $delete_local_data 是否删除数据
     * @return mixed
     * @throws ClientException
     */
    public function delete($ids, $delete_local_data = false)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array(
            "ids" => $ids,
            "delete-local-data" => $delete_local_data
        );
        return $this->request("torrent-remove", $request);
    }

    /**
     * 从announce获取一个或多个种子的更多Peer
     * Reannounce one or more torrents
     * @param int|array ids A list of transmission torrent ids
     * @return mixed
     * @throws ClientException
     */
    public function reannounce($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array("ids" => $ids);
        return $this->request("torrent-reannounce", $request);
    }

    /**
     * 获取一个种子或所有种子的参数
     * Get information on torrents in transmission, if the ids parameter is
     * empty all torrents will be returned. The fields array can be used to return certain
     * fields. Default fields are: "id", "name", "status", "doneDate", "haveValid", "totalSize".
     *  key                         | type                        | source
        ----------------------------+-----------------------------+---------
        activityDate                | number                      | tr_stat
        addedDate                   | number                      | tr_stat
        bandwidthPriority           | number                      | tr_priority_t
        comment                     | string                      | tr_info
        corruptEver                 | number                      | tr_stat
        creator                     | string                      | tr_info
        dateCreated                 | number                      | tr_info
        desiredAvailable            | number                      | tr_stat
        doneDate                    | number                      | tr_stat
        downloadDir                 | string                      | tr_torrent
        downloadedEver              | number                      | tr_stat
        downloadLimit               | number                      | tr_torrent
        downloadLimited             | boolean                     | tr_torrent
        error                       | number                      | tr_stat
        errorString                 | string                      | tr_stat
        eta                         | number                      | tr_stat
        etaIdle                     | number                      | tr_stat
        files                       | array (see below)           | n/a
        fileStats                   | array (see below)           | n/a
        hashString                  | string                      | tr_info
        haveUnchecked               | number                      | tr_stat
        haveValid                   | number                      | tr_stat
        honorsSessionLimits         | boolean                     | tr_torrent
        id                          | number                      | tr_torrent
        isFinished                  | boolean                     | tr_stat
        isPrivate                   | boolean                     | tr_torrent
        isStalled                   | boolean                     | tr_stat
        leftUntilDone               | number                      | tr_stat
        magnetLink                  | string                      | n/a
        manualAnnounceTime          | number                      | tr_stat
        maxConnectedPeers           | number                      | tr_torrent
        metadataPercentComplete     | double                      | tr_stat
        name                        | string                      | tr_info
        peer-limit                  | number                      | tr_torrent
        peers                       | array (see below)           | n/a
        peersConnected              | number                      | tr_stat
        peersFrom                   | object (see below)          | n/a
        peersGettingFromUs          | number                      | tr_stat
        peersSendingToUs            | number                      | tr_stat
        percentDone                 | double                      | tr_stat
        pieces                      | string (see below)          | tr_torrent
        pieceCount                  | number                      | tr_info
        pieceSize                   | number                      | tr_info
        priorities                  | array (see below)           | n/a
        queuePosition               | number                      | tr_stat
        rateDownload (B/s)          | number                      | tr_stat
        rateUpload (B/s)            | number                      | tr_stat
        recheckProgress             | double                      | tr_stat
        secondsDownloading          | number                      | tr_stat
        secondsSeeding              | number                      | tr_stat
        seedIdleLimit               | number                      | tr_torrent
        seedIdleMode                | number                      | tr_inactvelimit
        seedRatioLimit              | double                      | tr_torrent
        seedRatioMode               | number                      | tr_ratiolimit
        sizeWhenDone                | number                      | tr_stat
        startDate                   | number                      | tr_stat
        status                      | number                      | tr_stat
        trackers                    | array (see below)           | n/a
        trackerStats                | array (see below)           | n/a
        totalSize                   | number                      | tr_info
        torrentFile                 | string                      | tr_info
        uploadedEver                | number                      | tr_stat
        uploadLimit                 | number                      | tr_torrent
        uploadLimited               | boolean                     | tr_torrent
        uploadRatio                 | double                      | tr_stat
        wanted                      | array (see below)           | n/a
        webseeds                    | array (see below)           | n/a
        webseedsSendingToUs         | number                      | tr_stat
                                    |                             |
        -------------------+--------+-----------------------------+
        files              | array of objects, each containing:   |
                            +-------------------------+------------+
                            | bytesCompleted          | number     | tr_torrent
                            | length                  | number     | tr_info
                            | name                    | string     | tr_info
        -------------------+--------------------------------------+
        fileStats          | a file's non-constant properties.    |
                            | array of tr_info.filecount objects,  |
                            | each containing:                     |
                            +-------------------------+------------+
                            | bytesCompleted          | number     | tr_torrent
                            | wanted                  | boolean    | tr_info
                            | priority                | number     | tr_info
        -------------------+--------------------------------------+
        peers              | array of objects, each containing:   |
                            +-------------------------+------------+
                            | address                 | string     | tr_peer_stat
                            | clientName              | string     | tr_peer_stat
                            | clientIsChoked          | boolean    | tr_peer_stat
                            | clientIsInterested      | boolean    | tr_peer_stat
                            | flagStr                 | string     | tr_peer_stat
                            | isDownloadingFrom       | boolean    | tr_peer_stat
                            | isEncrypted             | boolean    | tr_peer_stat
                            | isIncoming              | boolean    | tr_peer_stat
                            | isUploadingTo           | boolean    | tr_peer_stat
                            | isUTP                   | boolean    | tr_peer_stat
                            | peerIsChoked            | boolean    | tr_peer_stat
                            | peerIsInterested        | boolean    | tr_peer_stat
                            | port                    | number     | tr_peer_stat
                            | progress                | double     | tr_peer_stat
                            | rateToClient (B/s)      | number     | tr_peer_stat
                            | rateToPeer (B/s)        | number     | tr_peer_stat
        -------------------+--------------------------------------+
        peersFrom          | an object containing:                |
                            +-------------------------+------------+
                            | fromCache               | number     | tr_stat
                            | fromDht                 | number     | tr_stat
                            | fromIncoming            | number     | tr_stat
                            | fromLpd                 | number     | tr_stat
                            | fromLtep                | number     | tr_stat
                            | fromPex                 | number     | tr_stat
                            | fromTracker             | number     | tr_stat
        -------------------+--------------------------------------+
        pieces             | A bitfield holding pieceCount flags  | tr_torrent
                            | which are set to 'true' if we have   |
                            | the piece matching that position.    |
                            | JSON doesn't allow raw binary data,  |
                            | so this is a base64-encoded string.  |
        -------------------+--------------------------------------+
        priorities         | an array of tr_info.filecount        | tr_info
                            | numbers. each is the tr_priority_t   |
                            | mode for the corresponding file.     |
        -------------------+--------------------------------------+
        trackers           | array of objects, each containing:   |
                            +-------------------------+------------+
                            | announce                | string     | tr_tracker_info
                            | id                      | number     | tr_tracker_info
                            | scrape                  | string     | tr_tracker_info
                            | tier                    | number     | tr_tracker_info
        -------------------+--------------------------------------+
        trackerStats       | array of objects, each containing:   |
                            +-------------------------+------------+
                            | announce                | string     | tr_tracker_stat
                            | announceState           | number     | tr_tracker_stat
                            | downloadCount           | number     | tr_tracker_stat
                            | hasAnnounced            | boolean    | tr_tracker_stat
                            | hasScraped              | boolean    | tr_tracker_stat
                            | host                    | string     | tr_tracker_stat
                            | id                      | number     | tr_tracker_stat
                            | isBackup                | boolean    | tr_tracker_stat
                            | lastAnnouncePeerCount   | number     | tr_tracker_stat
                            | lastAnnounceResult      | string     | tr_tracker_stat
                            | lastAnnounceStartTime   | number     | tr_tracker_stat
                            | lastAnnounceSucceeded   | boolean    | tr_tracker_stat
                            | lastAnnounceTime        | number     | tr_tracker_stat
                            | lastAnnounceTimedOut    | boolean    | tr_tracker_stat
                            | lastScrapeResult        | string     | tr_tracker_stat
                            | lastScrapeStartTime     | number     | tr_tracker_stat
                            | lastScrapeSucceeded     | boolean    | tr_tracker_stat
                            | lastScrapeTime          | number     | tr_tracker_stat
                            | lastScrapeTimedOut      | boolean    | tr_tracker_stat
                            | leecherCount            | number     | tr_tracker_stat
                            | nextAnnounceTime        | number     | tr_tracker_stat
                            | nextScrapeTime          | number     | tr_tracker_stat
                            | scrape                  | string     | tr_tracker_stat
                            | scrapeState             | number     | tr_tracker_stat
                            | seederCount             | number     | tr_tracker_stat
                            | tier                    | number     | tr_tracker_stat
        -------------------+-------------------------+------------+
        wanted             | an array of tr_info.fileCount        | tr_info
                            | 'booleans' true if the corresponding |
                            | file is to be downloaded.            |
        -------------------+--------------------------------------+
        webseeds           | an array of strings:                 |
                            +-------------------------+------------+
                            | webseed                 | string     | tr_info
                            +-------------------------+------------+
     *
     * @param array fields An array of return fields
     * @param int|array ids A list of transmission torrent ids
     *  示例 Example:
        Say we want to get the name and total size of torrents #7 and #10.

        请求 Request:
        {
            "arguments": {
            "fields": [ "id", "name", "totalSize" ],
            "ids": [ 7, 10 ]
        },
            "method": "torrent-get",
            "tag": 39693
        }

        响应 Response:
        {
            "arguments": {
                "torrents": [
                    {
                        "id": 10,
                        "name": "Fedora x86_64 DVD",
                        "totalSize": 34983493932,
                    },
                    {
                        "id": 7,
                        "name": "Ubuntu x86_64 DVD",
                        "totalSize", 9923890123,
                    }
                ]
            },
            "result": "success",
            "tag": 39693
        }
     *
     * @return mixed
     * @throws ClientException
     */
    public function get($ids = [], $fields = [])
    {
        $default = ["id", "name", "status", "doneDate", "haveValid", "totalSize"];
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (empty($fields)) {
            $fields = $default;
        } else {
            $fields = is_array($fields) ? array_merge($default, $fields) : $default;
        }
        $request = array(
            "fields" => $fields,
            "ids" => $ids
        );
        return $this->request("torrent-get", $request);
    }

    /**
     * 设置一个或多个种子的参数
     * Set properties on one or more torrents, available fields are:
     *   string                | value type & description
        ----------------------+-------------------------------------------------
        "bandwidthPriority"   | number     this torrent's bandwidth tr_priority_t
        "downloadLimit"       | number     maximum download speed (KBps)
        "downloadLimited"     | boolean    true if "downloadLimit" is honored
        "files-wanted"        | array      indices of file(s) to download
        "files-unwanted"      | array      indices of file(s) to not download
        "honorsSessionLimits" | boolean    true if session upload limits are honored
        "ids"                 | array      torrent list, as described in 3.1
        "location"            | string     new location of the torrent's content
        "peer-limit"          | number     maximum number of peers
        "priority-high"       | array      indices of high-priority file(s)
        "priority-low"        | array      indices of low-priority file(s)
        "priority-normal"     | array      indices of normal-priority file(s)
        "queuePosition"       | number     position of this torrent in its queue [0...n)
        "seedIdleLimit"       | number     torrent-level number of minutes of seeding inactivity
        "seedIdleMode"        | number     which seeding inactivity to use.  See tr_idlelimit
        "seedRatioLimit"      | double     torrent-level seeding ratio
        "seedRatioMode"       | number     which ratio to use.  See tr_ratiolimit
        "trackerAdd"          | array      strings of announce URLs to add
        "trackerRemove"       | array      ids of trackers to remove
        "trackerReplace"      | array      pairs of <trackerId/new announce URLs>
        "uploadLimit"         | number     maximum upload speed (KBps)
        "uploadLimited"       | boolean    true if "uploadLimit" is honored
     *
     * @param array arguments An associative array of arguments to set
     * @param int|array ids A list of transmission torrent ids
     * @return mixed
     * @throws ClientException
     */
    public function set($ids = array(), $arguments = array())
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (!isset($arguments['ids'])) {
            $arguments['ids'] = $ids;
        }
        return $this->request("torrent-set", $arguments);
    }

    /**
     * 添加新种子 (URL)
     * Add a new torrent
     *
     * Request arguments:
     *  key                  | value type & description
        ---------------------+-------------------------------------------------
        "cookies"            | string      pointer to a string of one or more cookies.
        "download-dir"       | string      path to download the torrent to
        "filename"           | string      filename or URL of the .torrent file
        "metainfo"           | string      base64-encoded .torrent content
        "paused"             | boolean     if true, don't start the torrent
        "peer-limit"         | number      maximum number of peers
        "bandwidthPriority"  | number      torrent's bandwidth tr_priority_t
        "files-wanted"       | array       indices of file(s) to download
        "files-unwanted"     | array       indices of file(s) to not download
        "priority-high"      | array       indices of high-priority file(s)
        "priority-low"       | array       indices of low-priority file(s)
        "priority-normal"    | array       indices of normal-priority file(s)
     *
     * 参数内必须包含"filename"或"metainfo"字段，其他字段都可选。
     * Either "filename" OR "metainfo" MUST be included.
        All other arguments are optional.
     *
     * @param string $filename 字符串文件名或种子url；The URL or path to the torrent file
     * @param string $save_path Folder to save torrent in
     * @param array $extra_options Optional extra torrent options
     * @return mixed
     * @throws ClientException
     */
    public function add($filename, $save_path = '', $extra_options = array())
    {
        if (!empty($save_path)) {
            $extra_options['download-dir'] = $save_path;
        }
        $extra_options['filename'] = $filename;

        return $this->request("torrent-add", $extra_options);
    }

    /**
     * 添加新种子 (元数据)
     * Add a torrent using the raw torrent data
     * @param string $torrent_metainfo The raw, unencoded contents (metainfo) of a torrent
     * @param string $save_path Folder to save torrent in
     * @param array $extra_options Optional extra torrent options
     * @return mixed
     * @throws ClientException
     */
    public function add_metainfo($torrent_metainfo, $save_path = '', $extra_options = array())
    {
        if (!empty($save_path)) {
            $extra_options['download-dir'] = $save_path;
        }
        $extra_options['metainfo'] = base64_encode($torrent_metainfo);

        return $this->request("torrent-add", $extra_options);
    }

    /**
     * 变更数据保存目录
     * Move local storage location
     *
     * Request arguments:
        string                           | value type & description
        ---------------------------------+-------------------------------------------------
        "ids"                            | array      torrent list, as described in 3.1
        "location"                       | string     the new torrent location
        "move"                           | boolean    if true, move from previous location.
                                         |            otherwise, search "location" for files
                                         |            (default: false)
     *
     * @param int|array $ids A list of transmission torrent ids
     * @param string $target_location The new storage location
     * @param boolean $move_existing_data Move existing data or scan new location for available data
     * @return mixed
     * @throws ClientException
     */
    public function move($ids, $target_location, $move_existing_data = true)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $request = array(
            "ids" => $ids,
            "location" => $target_location,
            "move" => $move_existing_data
        );
        return $this->request("torrent-set-location", $request);
    }

    /**
     * 修改种子文件或目录名称
     * Renaming a Torrent's Path
     *
     * For more information on the use of this function, see the transmission.h
        documentation of tr_torrentRenamePath(). In particular, note that if this
        call succeeds you'll want to update the torrent's "files" and "name" field
        with torrent-get.

        Request arguments:

        string                           | value type & description
        ---------------------------------+-------------------------------------------------
        "ids"                            | array      the torrent torrent list, as described in 3.1
                                         |            (must only be 1 torrent)
        "path"                           | string     the path to the file or folder that will be renamed
        "name"                           | string     the file or folder's new name

        Response arguments: "path", "name", and "id", holding the torrent ID integer
     *
     * @param int|array ids A 1-element list of transmission torrent ids
     * @param string path The path to the file or folder that will be renamed
     * @param string name The file or folder's new name
     * @return mixed
     * @throws ClientException
     */
    public function rename($ids, $path, $name)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (count($ids) !== 1) {
            throw new ClientException('A single id is accepted', self::E_INVALIDARG);
        }

        $request = array(
            "ids" => $ids,
            "path" => $path,
            "name" => $name
        );
        return $this->request("torrent-rename-path", $request);
    }

    /**
     * Session 请求
     * (4.1)  Session 参数
     * string                           | value type | description
     * ---------------------------------+------------+-------------------------------------
     * "alt-speed-down"                 | number     | max global download speed (KBps)
     * "alt-speed-enabled"              | boolean    | true means use the alt speeds
     * "alt-speed-time-begin"           | number     | when to turn on alt speeds (units: minutes after midnight)
     * "alt-speed-time-enabled"         | boolean    | true means the scheduled on/off times are used
     * "alt-speed-time-end"             | number     | when to turn off alt speeds (units: same)
     * "alt-speed-time-day"             | number     | what day(s) to turn on alt speeds (look at tr_sched_day)
     * "alt-speed-up"                   | number     | max global upload speed (KBps)
     * "blocklist-url"                  | string     | location of the blocklist to use for "blocklist-update"
     * "blocklist-enabled"              | boolean    | true means enabled
     * "blocklist-size"                 | number     | number of rules in the blocklist
     * "cache-size-mb"                  | number     | maximum size of the disk cache (MB)
     * "config-dir"                     | string     | location of transmission's configuration directory
     * "download-dir"                   | string     | default path to download torrents
     * "download-queue-size"            | number     | max number of torrents to download at once (see download-queue-enabled)
     * "download-queue-enabled"         | boolean    | if true, limit how many torrents can be downloaded at once
     * "dht-enabled"                    | boolean    | true means allow dht in public torrents
     * "encryption"                     | string     | "required", "preferred", "tolerated"
     * "idle-seeding-limit"             | number     | torrents we're seeding will be stopped if they're idle for this long
     * "idle-seeding-limit-enabled"     | boolean    | true if the seeding inactivity limit is honored by default
     * "incomplete-dir"                 | string     | path for incomplete torrents, when enabled
     * "incomplete-dir-enabled"         | boolean    | true means keep torrents in incomplete-dir until done
     * "lpd-enabled"                    | boolean    | true means allow Local Peer Discovery in public torrents
     * "peer-limit-global"              | number     | maximum global number of peers
     * "peer-limit-per-torrent"         | number     | maximum global number of peers
     * "pex-enabled"                    | boolean    | true means allow pex in public torrents
     * "peer-port"                      | number     | port number
     * "peer-port-random-on-start"      | boolean    | true means pick a random peer port on launch
     * "port-forwarding-enabled"        | boolean    | true means enabled
     * "queue-stalled-enabled"          | boolean    | whether or not to consider idle torrents as stalled
     * "queue-stalled-minutes"          | number     | torrents that are idle for N minuets aren't counted toward seed-queue-size or download-queue-size
     * "rename-partial-files"           | boolean    | true means append ".part" to incomplete files
     * "rpc-version"                    | number     | the current RPC API version              当前RPC版本号
     * "rpc-version-minimum"            | number     | the minimum RPC API version supported    支持RPC的最小版本号
     * "script-torrent-done-filename"   | string     | filename of the script to run
     * "script-torrent-done-enabled"    | boolean    | whether or not to call the "done" script
     * "seedRatioLimit"                 | double     | the default seed ratio for torrents to use
     * "seedRatioLimited"               | boolean    | true if seedRatioLimit is honored by default
     * "seed-queue-size"                | number     | max number of torrents to uploaded at once (see seed-queue-enabled)
     * "seed-queue-enabled"             | boolean    | if true, limit how many torrents can be uploaded at once
     * "speed-limit-down"               | number     | max global download speed (KBps)
     * "speed-limit-down-enabled"       | boolean    | true means enabled
     * "speed-limit-up"                 | number     | max global upload speed (KBps)
     * "speed-limit-up-enabled"         | boolean    | true means enabled
     * "start-added-torrents"           | boolean    | true means added torrents will be started right away
     * "trash-original-torrent-files"   | boolean    | true means the .torrent file of added torrents will be deleted
     * "units"                          | object     | see below
     * "utp-enabled"                    | boolean    | true means allow utp
     * "version"                        | string     | long version string "$version ($revision)"
     * ---------------------------------+------------+-----------------------------+
     * units                            | object containing:                       |
                                        +--------------+--------+------------------+
                                        | speed-units  | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
                                        | speed-bytes  | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
                                        | size-units   | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
                                        | size-bytes   | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
                                        | memory-units | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
                                        | memory-bytes | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
                                        +--------------+--------+------------------+
     *
     * 当前RPC版本号"rpc-version" indicates the RPC interface version supported by the RPC server.
     *  It is incremented when a new version of Transmission changes the RPC interface.
     *
     * 支持RPC的最小版本号"rpc-version-minimum" indicates the oldest API supported by the RPC server.
     *  It is changes when a new version of Transmission changes the RPC interface
     *  in a way that is not backwards compatible.  There are no plans for this
     *  to be common behavior.
     */

    /**
     * 获取会话
     * Retrieve all session variables
     * Method name: "session-get"
        Request arguments: an optional "fields" array of keys (see 4.1)
        Response arguments: key/value pairs matching the request's "fields"
        argument if present, or all supported fields (see 4.1) otherwise.
     *
     * @returns array of session information
     * @throws ClientException
     */
    public function sessionGet()
    {
        return $this->request("session-get", array());
    }

    /**
     * 设置会话
     * Set session variable(s)
     * Method name: "session-set"
        Request arguments: one or more of 4.1's arguments, except: "blocklist-size",
        "config-dir", "rpc-version", "rpc-version-minimum",
        "version", and "session-id"
        Response arguments: none
     *
     * @param array of session variables to set
     * @return mixed
     * @throws ClientException
     */
    public function sessionSet($arguments)
    {
        return $this->request("session-set", $arguments);
    }

    /**
     * 会话状态统计
     * Retrieve session statistics
     *
     * Method name: "session-stats"
        Request arguments: none
        Response arguments:

        string                     | value type
        ---------------------------+-------------------------------------------------
        "activeTorrentCount"       | number
        "downloadSpeed"            | number
        "pausedTorrentCount"       | number
        "torrentCount"             | number
        "uploadSpeed"              | number
        ---------------------------+-------------------------------+
        "cumulative-stats"         | object, containing:           |
                                    +------------------+------------+
                                    | uploadedBytes    | number     | tr_session_stats
                                    | downloadedBytes  | number     | tr_session_stats
                                    | filesAdded       | number     | tr_session_stats
                                    | sessionCount     | number     | tr_session_stats
                                    | secondsActive    | number     | tr_session_stats
        ---------------------------+-------------------------------+
        "current-stats"            | object, containing:           |
                                    +------------------+------------+
                                    | uploadedBytes    | number     | tr_session_stats
                                    | downloadedBytes  | number     | tr_session_stats
                                    | filesAdded       | number     | tr_session_stats
                                    | sessionCount     | number     | tr_session_stats
                                    | secondsActive    | number     | tr_session_stats
     *
     * @returns array of statistics
     * @throws ClientException
     */
    public function sessionStats()
    {
        return $this->request("session-stats", array());
    }

    /**
     * Return the interpretation of the torrent status
     *
     * @param int The integer "torrent status"
     * @returns string The translated meaning
     * @return string
     */
    public function getStatusString($intstatus)
    {
        if ($this->rpc_version < 14) {
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK) {
                return "Verifying local files";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_DOWNLOAD) {
                return "Downloading";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_SEED) {
                return "Seeding";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_STOPPED) {
                return "Stopped";
            }
        } else {
            if ($intstatus == self::TR_STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }
            if ($intstatus == self::TR_STATUS_CHECK) {
                return "Verifying local files";
            }
            if ($intstatus == self::TR_STATUS_DOWNLOAD) {
                return "Downloading";
            }
            if ($intstatus == self::TR_STATUS_SEED) {
                return "Seeding";
            }
            if ($intstatus == self::TR_STATUS_STOPPED) {
                return "Stopped";
            }
            if ($intstatus == self::TR_STATUS_SEED_WAIT) {
                return "Queued for seeding";
            }
            if ($intstatus == self::TR_STATUS_DOWNLOAD_WAIT) {
                return "Queued for download";
            }
        }
        return "Unknown";
    }

    /**
     * 对请求数据预处理
     * Clean up the request array. Removes any empty fields from the request
     *
     * @param array array The request associative array to clean
     * @returns array The cleaned array
     * @return array|null
     */
    protected function cleanRequestData($array)
    {
        if (!is_array($array) || count($array) == 0) {
            return null;
        }
        setlocale(LC_NUMERIC, 'en_US.utf8');    // Override the locale - if the system locale is wrong, then 12.34 will encode as 12,34 which is invalid JSON
        foreach ($array as $index => $value) {
            if (is_object($value)) {
                $array[$index] = $value->toArray();
            }    // Convert objects to arrays so they can be JSON encoded
            if (is_array($value)) {
                $array[$index] = $this->cleanRequestData($value);
            }    // Recursion
            if (empty($value) && ($value !== 0 || $value !== false)) {    // Remove empty members
                unset($array[$index]);
                continue; // Skip the rest of the tests - they may re-add the element.
            }
            if (is_numeric($value)) {
                $array[$index] = $value + 0;
            }    // Force type-casting for proper JSON encoding (+0 is a cheap way to maintain int/float/etc)
            if (is_bool($value)) {
                $array[$index] = ($value ? 1 : 0);
            }    // Store boolean values as 0 or 1
            if (is_string($value)) {
                $type = mb_detect_encoding($value, 'auto');
                if ($type !== 'UTF-8') {
                    $array[$index] = mb_convert_encoding($value, 'UTF-8');
                }
            }
        }
        return $array;
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
     * 执行 rpc 请求
     * @param string $method 请求类型/方法, 详见 $this->allowMethods
     * @param array $arguments 附加参数, 可选
     * @return array
     * @throws ClientException
     */
    protected function request($method, $arguments = array())
    {
        if (!is_scalar($method)) {
            throw new ClientException('Method name has no scalar value', self::E_INVALIDARG);
        }
        if (!is_array($arguments)) {
            throw new ClientException('Arguments must be given as array', self::E_INVALIDARG);
        }

        $arguments = $this->cleanRequestData($arguments);    // Sanitize input

        // Grab the X-Transmission-Session-Id if we don't have it already
        if (!$this->session_id) {
            if (!$this->GetSessionID()) {
                throw new ClientException('Unable to acquire X-Transmission-Session-Id', self::E_SESSIONID);
            }
        }

        $data = array(
            'method' => $method,
            'arguments' => $arguments
        );
        $header = array(
            'Content-Type'              =>  'application/json',
            'Authorization'             =>  'Basic ' . base64_encode(sprintf("%s:%s", $this->username, $this->password)),
            'X-Transmission-Session-Id' =>  $this->session_id
        );
        $curl = $this->curl;
        if (stripos($this->url, 'https://') === 0) {
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false); // 禁止验证证书
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);     // 不检查证书
        }
        foreach ($header as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setUserAgent(self::UA);
        $curl->setBasicAuthentication($this->username, $this->password);
        $curl->post($this->url, $data, true);
        $content = $curl->response;

        if ($this->debug) {
            var_dump($curl->request_headers);
            var_dump($curl->response_headers);
            var_dump($curl->response);
        }

        if (!$content) {
            $content = array('result' => 'failed');
        }
        return json_decode($content, true);
    }

    /**
     * Performs an empty GET on the Transmission RPC to get the X-Transmission-Session-Id
     * and store it in $this->session_id
     * @return string
     */
    public function GetSessionID()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        $error_code     = curl_errno($ch);
        $error_message  = curl_error($ch);
        $http_status_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if ($this->debug) {
            var_dump($http_status_code);
            var_dump($error_message);
            var_dump($error_code);
            var_dump($content);
        }

        // 401 Invalid username/password
        // 409 成功
        // 其他 Unexpected response from Transmission RPC
        curl_close($ch);
        if($content && preg_match("/<code>X-Transmission-Session-Id: (.*?)<\/code>/", $content, $match)) {
            $this->session_id = isset($match[1]) ? $match[1] : null;
        }

        return $this->session_id;
    }

    /**
     * 抽象方法，子类实现
     * 获取下载器链接状态
     * @throws ClientException
     */
    public function status()
    {
        $rs = $this->sessionStats();
        return isset($rs['result']) ? $rs['result'] : 'error';
    }

    /**
     * 抽象方法，子类实现
     * 获取所有种子的列表
     * @param array $torrentList
     * @return array
     * array(
     * 'hash'       => string json,
     * 'sha1'       => string,
     * 'hashString '=> array
     * )
     * @throws ClientException
     */
    public function all(&$torrentList = array())
    {
        $ids = array();
        $fields = array( "id", "status", "name", "hashString", "downloadDir", "torrentFile" );
        $res = $this->get($ids, $fields);
        if (isset($res['result']) && $res['result'] === 'success') {
            // 成功
        } else {
            // 失败
            echo "从客户端获取种子列表失败，可能transmission暂时无响应，请稍后重试！".PHP_EOL;
            return array();
        }
        if (empty($res['arguments']['torrents'])) {
            echo "从客户端未获取到数据，请稍后重试！".PHP_EOL;
            return array();
        }
        $res = $res['arguments']['torrents'];
        // 过滤，只保留正常做种
        $res = array_filter($res, function ($v) {
            return isset($v['status']) && $v['status'] === 6;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($res)) {
            echo "从客户端未获取到正常做种数据，请多保种，然后重试！".PHP_EOL;
            return array();
        }
        // 提取数组：hashString
        $info_hash = array_column($res, 'hashString');
        // 升序排序
        sort($info_hash);
        $json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
        // 去重 应该从文件读入，防止重复提交
        $sha1 = sha1($json);
        // 组装返回数据
        $hashArray['hash'] = $json;
        $hashArray['sha1'] = $sha1;
        // 变换数组：hashString为键名、目录为键值
        $hashArray['hashString'] = array_column($res, "downloadDir", 'hashString');
        $torrentList = array_column($res, null, 'hashString');
        return $hashArray;
    }
}
