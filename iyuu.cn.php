<?php
/**
 * IYUU自动辅种脚本
 */
use Curl\Curl;
require_once __DIR__ . '/app/init.php';
iyuuAutoReseed::init();
$hashArray = iyuuAutoReseed::get();
if ( iyuuAutoReseed::$move != null ) {
	echo "种子移动完毕，请重新编辑配置，再尝试辅种！ \n\n";
	exit;
}
iyuuAutoReseed::call($hashArray);
iyuuAutoReseed::wechatMessage();
/**
 * iyuu自动辅种类
 */
class iyuuAutoReseed
{
	/**
     * 版本号
     * @var string
     */
	const VER = '0.1.0';
	// RPC连接池
	public static $links = array();
	/**
     * 客户端配置
     */
	public static $clients = array();
	/**
	 * 不辅种的站点 'ourbits','hdchina'
	 */
	public static $noReseed = [];
	/**
     * 缓存路径
     */
	public static $cacheDir  = TORRENT_PATH.'cache'.DS;
	public static $cacheHash = TORRENT_PATH.'cachehash'.DS;
	/**
     * API接口配置
     */
	public static $apiUrl = 'http://iyuu.cn:2122';
	public static $endpoints = array(
		'add' => '/api/add',
		'update' => '/api/update',
		'reseed' => '/api/reseed',
		'login'  => '/login',
	);
	/**
     * 退出状态码
     */
	public static $ExitCode = 0;
	/**
	 * 客户端转移做种 状态码[请把transmission配置为第一个客户端]
	 */
	public static $move = null;
	/**
	 * 微信消息体
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
     */
	public static function init(){
		global $configALL;

		self::$clients = isset($configALL['default']['clients']) && $configALL['default']['clients'] ? $configALL['default']['clients'] : array();
		echo "程序正在初始化运行参数... \n";
		// 递归删除上次历史记录
		IFile::rmdir(self::$cacheDir, true);
		// 建立目录
		IFile::mkdir(self::$cacheDir);
		IFile::mkdir(self::$cacheHash);
		// 连接全局客户端
		self::links();
		// 合作站点自动注册鉴权
		Oauth::login(self::$apiUrl . self::$endpoints['login']);
	}
	/**
     * 连接远端RPC服务器
     *
     * @param string
     * @return array
     */
    public static function links()
    {
        if(empty(self::$links)){
			foreach ( self::$clients as $k => $v ){
				// 跳过未配置的客户端
				if (empty($v['username']) || empty( $v['password'])) {
					unset(self::$clients[$k]);
					echo "clients_".$k." 用户名或密码未配置，已跳过 \n\n";
					continue;
				}
				try
				{
					switch($v['type']){
						case 'transmission':
							self::$links[$k]['rpc'] = new TransmissionRPC($v['host'], $v['username'], $v['password']);
							$result = self::$links[$k]['rpc']->sstats();
							print $v['type'].'：'.$v['host']." Rpc连接 [{$result->result}] \n";
							break;
						case 'qBittorrent':
							self::$links[$k]['rpc'] = new qBittorrent($v['host'], $v['username'], $v['password']);
							$result = self::$links[$k]['rpc']->appVersion();
							print $v['type'].'：'.$v['host']." Rpc连接 [{$result}] \n";
							break;
						default:
							echo '[ERROR] '.$v['type'];
							exit(1);
							break;
					}
					self::$links[$k]['type'] = $v['type'];
					// 检查是否转移种子的做种客户端？
					if ( isset($v['move']) && $v['move'] ) {
						self::$move = array($k,$v['type']);
					}
				} catch (Exception $e) {
					echo '[ERROR] ' . $e->getMessage() . PHP_EOL;
					exit(1);
				}
			}
		}
		return true;
	}
	/**
     * 从客户端获取种子的哈希列表
     */
	public static function get(){
		$hashArray = array();
		foreach ( self::$clients as $k => $v ){
			$result = array();
			$res = $info_hash = array();
			$json = $sha1 = '';
			try
			{
				switch($v['type']){
					case 'transmission':
						$ids = $fields = array();
						#$fields = array( "id", "status", "name", "hashString", "downloadDir", "torrentFile" );
						$fields = array( "id", "status", "hashString", "downloadDir");
						$result = self::$links[$k]['rpc']->get($ids, $fields);
						if ( empty($result->result) || $result->result != 'success' ){
							// 获取种子列表 失败
							echo "获取种子列表失败，原因可能是transmission暂时无响应，请稍后重试！ \n";
							break;
						}
						if( empty($result->arguments) ){
							echo "未获取到需要辅种的数据，请多多保种，然后重试！ \n";
							break;
						}
						// 对象转数组
						$res = object_array($result->arguments->torrents);
						// 过滤，只保留正常做种
						$res = array_filter($res, "filterStatus");
						// 提取数组：hashString
						$info_hash = array_column($res, 'hashString');
						// 升序排序
						sort($info_hash);
						// 微信模板消息 统计
						self::$wechatMsg['hashCount'] += count($info_hash);
						$json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
						// 去重 应该从文件读入，防止重复提交
						$sha1 = sha1( $json );
						if ( isset($hashArray['sha1']) && (in_array($sha1, $hashArray['sha1']) != false) ) {
							break;
						}
						// 组装返回数据
						$hashArray['hash']['clients_'.$k] = $json;
						$hashArray['sha1'][] = $sha1;
						// 变换数组：hashString为键
						self::$links[$k]['hash'] = array_column($res, "downloadDir", 'hashString');
						#p(self::$links[$k]['hash']);exit;
						break;
					case 'qBittorrent':
						$result = self::$links[$k]['rpc']->torrentList();
						$res = json_decode($result,true);
						if ( empty($res) ) {
							echo "未获取到需要辅种的数据，请多多保种，然后重试！ \n";
							break;
						}
						#p($res);exit;
						// 过滤，只保留正常做种
						$res = array_filter($res, "qbfilterStatus");
						// 提取数组：hashString
						$info_hash = array_column($res, 'hash');
						// 升序排序
						sort($info_hash);
						// 微信模板消息 统计
						self::$wechatMsg['hashCount'] += count($info_hash);
						$json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
						// 去重 应该从文件读入，防止重复提交
						$sha1 = sha1( $json );
						if ( isset($hashArray['sha1']) && (in_array($sha1, $hashArray['sha1']) != false) ) {
							break;
						}
						// 组装返回数据
						$hashArray['hash']['clients_'.$k] = $json;
						$hashArray['sha1'][] = $sha1;
						// 变换数组：hash为键
						self::$links[$k]['hash'] = array_column($res, "save_path", 'hash');
						#p(self::$links[$k]['hash']);exit;
						break;
					default:
						echo '[ERROR] '.$v['type'];
						exit(1);
						break;
				}
				// 是否执行转移种子做种客户端？
				if ( self::$move != null && (empty($v['move'])) ) {
					self::move($res, $v['type']);
				}
			} catch (Exception $e) {
				echo '[ERROR] ' . $e->getMessage() . PHP_EOL;
				exit(1);
			}
		}
		return $hashArray;
	}
	/**
	 * @brief 添加下载任务
	 * @param string $torrent 种子元数据
	 * @param string $save_path 保存路径
	 * @return bool
	 */
    public static function add($rpcKey, $torrent, $save_path = '', $extra_options = array())
    {
		try
		{
			$type = self::$links[$rpcKey]['type'];
			if( (strpos($torrent,'http://')===0) || (strpos($torrent,'https://')===0) || (strpos($torrent,'magnet:?xt=urn:btih:')===0)){
				$result = self::$links[$rpcKey]['rpc']->add( $torrent, $save_path, $extra_options );			// 种子URL添加下载任务
			} else{
				if ( $type == 'qBittorrent' ) {
					$extra_options['name'] = 'torrents';
					$extra_options['filename'] = rand(1,4294967200).'.torrent';
				}
				$result = self::$links[$rpcKey]['rpc']->add_metainfo( $torrent, $save_path, $extra_options );	// 种子元数据添加下载任务
			}
			// 调试
			#p($result);
			// 下载服务器类型 判断
			switch( $type ){
				case 'transmission':
					if(isset($result->result) && $result->result == 'success'){
						$id = $name = '';
						if( isset($result->arguments->torrent_duplicate) ){
							$id = $result->arguments->torrent_duplicate->id;
							$name = $result->arguments->torrent_duplicate->name;
						}elseif( isset($result->arguments->torrent_added) ){
							$id = $result->arguments->torrent_added->id;
							$name = $result->arguments->torrent_added->name;
						}
						print "********RPC添加下载任务成功 [{$result->result}] (id=$id) \n";
						if( (strpos($torrent,'http://')===0) || (strpos($torrent,'https://')===0) || (strpos($torrent,'magnet:?xt=urn:btih:')===0) ){
							print "种子：".$torrent. "\n";
						}
						print "名字：".$name."\n\n";
						return true;
					}else{
						$errmsg = isset($result->result) ? $result->result : '未知错误，请稍后重试！';
						print "-----RPC添加种子任务，失败 [{$errmsg}] \n";
						if( (strpos($torrent,'http://')===0) || (strpos($torrent,'https://')===0) || (strpos($torrent,'magnet:?xt=urn:btih:')===0) ){
							print "种子：".$torrent. "\n";
						}
					}
					break;
				case 'qBittorrent':
					if ($result === 'Ok.') {
						print "********RPC添加下载任务成功 [{$result}] \n\n";
						return true;
					} else {
						print "-----RPC添加种子任务，失败 [{$result}] \n\n";
					}
					break;
				default:
					echo '[ERROR] '.$type;
					break;
			}
		} catch (Exception $e) {
			echo '[ERROR] ' . $e->getMessage() . PHP_EOL;
		}
		return false;
	}
	
	/**
	 * 正常做种的种子在各下载器的互相转移
	 */
	public static function move($torrent=array(), $type = 'qBittorrent'){
		switch($type){
			case 'transmission':
				break;
			case 'qBittorrent':
				foreach ($torrent as $k => $v) {
					// 路径转换
					#$v['save_path'] = '/volume3' . $v['save_path'];	// docker路径转换
					self::add(self::$move[0], $v['magnet_uri'], $v['save_path'] );
				}
				break;
			default:
				echo '[ERROR] '.$type;
				break;
		}
	}
	/**
	 * @brief 提交种子hash给远端API，用来获取辅种数据
	 * @param array $hashArray 种子hash数组
	 * @return
	 */
	public static function call($hashArray = array())
	{
		global $configALL;
		$resArray = $sites = array();
		$curl = new Curl();
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		// 签名
		$hashArray['timestamp'] = time();
		// 爱语飞飞token
		$hashArray['sign'] = Oauth::getSign();
		$hashArray['version'] = self::VER;
		// 写日志
		if (true) {
			// 文件句柄
			$resource = fopen(self::$cacheDir.'hashString.txt', "wb");
			// 成功：返回写入字节数，失败返回false
			$worldsnum = fwrite($resource, p($hashArray, false));
			fclose($resource);
		}
		// 发起请求
		echo "正在提交辅种信息…… \n";
		$res = $curl->post(self::$apiUrl . self::$endpoints['reseed'], $hashArray);

		$resArray = json_decode($res->response, true);
		// 写日志
		if(true){
			// 文件句柄
			$resource = fopen(self::$cacheDir.'reseed.txt', "wb");
			// 成功：返回写入字节数，失败返回false
			$worldsnum = fwrite($resource, p($resArray, false));
			fclose($resource);
		}
		// 判断返回值
		if ( isset($resArray['errmsg']) && ($resArray['errmsg'] == 'ok') ) {
			echo "辅种信息提交成功！！！ \n\n";
		}else{
			$errmsg = isset($resArray['errmsg']) ? $resArray['errmsg'] : '远端服务器无响应，请稍后重试！';
			echo '-----辅种失败，原因：' .$errmsg. " \n\n";
			exit(1);
		}
		// 可辅种站点信息列表
		$sites = $resArray['sites'];
		self::$wechatMsg['sitesCount'] = count($sites);
		#p($sites);
		// 按客户端循环辅种 开始
		foreach (self::$links as $k => $v) {
			$reseed = $infohash_Dir = array();
			// info_hash 对应的下载目录
			$infohash_Dir = self::$links[$k]['hash'];
			if (empty($resArray['clients_'.$k])) {
				echo "clients_".$k."没有查询到可辅种数据 \n\n";
				continue;
			}
			#p($infohash_Dir);
			// 当前客户端辅种数据
			$reseed = $resArray['clients_'.$k];			
			foreach ($reseed as $info_hash => $vv) {
				// 当前种子哈希对应的目录
				$downloadDir = $infohash_Dir[$info_hash];
				foreach ($vv['torrent'] as $id => $value) {
					// 匹配的辅种数据累加
					self::$wechatMsg['reseedCount']++;
					// 站点id
					$sitesID = $value['sid'];
					$url = $_url = '';
					$download_page = $details_url = '';
					// 页面规则
					$download_page = str_replace('{}', $value['torrent_id'], $sites[$sitesID]['download_page']);
					$_url = 'https://' .$sites[$sitesID]['base_url']. '/' .$download_page;
					if ( empty($configALL[$sites[$sitesID]['site']]['passkey']) ) {
						echo '-------因当前' .$sites[$sitesID]['site']. '站点未设置passkey，已跳过！！' . "\n\n";
						self::$wechatMsg['reseedSkip']++;
						continue;
					}
					// 种子URL组合方式区分
					switch ($sites[$sitesID]['site']) {
						case 'ttg':
							$url = $_url."/". $configALL[$sites[$sitesID]['site']]['passkey'];
							break;
						case 'm-team':
							$ip_type = '';
							if (isset($configALL[$sites[$sitesID]['site']]['ip_type'])) {
								$ip_type = $configALL[$sites[$sitesID]['site']]['ip_type'] == 'ipv6' ? '&ipv6=1' : '';
							}
							$url = $_url."&passkey=". $configALL[$sites[$sitesID]['site']]['passkey'] . $ip_type. "&https=1";						
							break;
						case 'hdchina':
							if ( empty($configALL[$sites[$sitesID]['site']]['cookie']) ) {
								echo '-------因当前' .$sites[$sitesID]['site']. '站点未设置cookie，已跳过！！' . "\n\n";
								self::$wechatMsg['reseedSkip']++;
								break;
							}
							$cookie = isset($configALL[$sites[$sitesID]['site']]['cookie']) ? $configALL[$sites[$sitesID]['site']]['cookie'] : '';
							$userAgent = $configALL['default']['userAgent'];
							// 拼接URL
							$details_page = str_replace('{}', $value['torrent_id'], 'details.php?id={}&hit=1');
							$details_url = 'https://' .$sites[$sitesID]['base_url']. '/' .$details_page;
							$details_html = download($details_url, $cookie, $userAgent);
							print "种子详情页：".$details_url. "\n";
							// 提取种子下载地址
							$download_page = str_replace('{}', '', $sites[$sitesID]['download_page']);
							$offset = strpos($details_html, $download_page);
							$urlTemp = substr($details_html, $offset, 50);
							// 种子地址
							$_url = substr($urlTemp,0,strpos($urlTemp,'">'));
							$_url = 'https://' .$sites[$sitesID]['base_url']. '/' . $_url;
							print "种子下载页：".$_url. "\n";
							$url = download($_url, $cookie, $userAgent);
							#$torrentArray = Bencode::decode($url);
							break;
						case 'hdcity':
							if ( empty($configALL[$sites[$sitesID]['site']]['cookie']) ) {
								echo '-------因当前' .$sites[$sitesID]['site']. '站点未设置cookie，已跳过！！' . "\n\n";
								self::$wechatMsg['reseedSkip']++;
								break;
							}
							print "种子：".$_url. "\n";
							echo '当前' .$sites[$sitesID]['site']. '站点需配置cuhash，而不是passkey！如果添加任务失败，请查阅常见问题！！' . "\n";
							$url = $_url."&cuhash=". $configALL[$sites[$sitesID]['site']]['passkey'];
							$cookie = isset($configALL[$sites[$sitesID]['site']]['cookie']) ? $configALL[$sites[$sitesID]['site']]['cookie'] : '';
							$userAgent = $configALL['default']['userAgent'];
							// 城市下载种子时会302转向
							$url = download($url, $cookie, $userAgent);
							break;
						default:
							$url = $_url."&passkey=". $configALL[$sites[$sitesID]['site']]['passkey'];
							break;
					}
					/**
					 * 检查站点是否可以辅种
					 */
					// 判断是否具有VIP或特殊权限？
					$is_vip = isset($configALL[$sites[$sitesID]['site']]['is_vip']) && $configALL[$sites[$sitesID]['site']]['is_vip'] ? 1 : 0;
					if ( (in_array($sites[$sitesID]['site'], self::$noReseed)==false) || $is_vip ) {
						/**
						 *  可以辅种
						 */
						if ( isset($infohash_Dir[$value['info_hash']]) ) {
							// 与客户端现有种子重复
							echo '-------与客户端现有种子重复：'.$_url."\n\n";
							self::$wechatMsg['reseedRepeat']++;
							continue;
						}else{
							// 判断上次是否成功添加？
							if ( is_file(self::$cacheHash . $value['info_hash'].'.txt') ) {
								echo '-------当前种子上次辅种已成功添加，已跳过！'.$_url."\n\n";
								self::$wechatMsg['reseedPass']++;
								continue;
							}
							// 把拼接的种子URL，推送给下载器
							$ret = false;
							// 成功返回：true
							$ret = self::add($k, $url, $downloadDir);
							// 添加成功的种子，以infohash为文件名，写入缓存
							if ($ret) {
								// 成功的种子
								// 文件句柄
								$resource = fopen(self::$cacheHash . $value['info_hash'].'.txt', "wb");
								// 成功：返回写入字节数，失败返回false
								$worldsnum = fwrite($resource, $url);
								fclose($resource);
								self::$wechatMsg['reseedSuccess']++;
								continue;
							}else{
								// 失败的种子
								self::$wechatMsg['reseedError']++;
								continue;
							}
						}
					}else{
						/**
						 *  不辅种
						 */
						echo '-------已跳过不辅种的站点：'.$_url."\n\n";
						// 写入日志文件，供用户手动辅种
						if ( !isset($infohash_Dir[$value['info_hash']]) ) {
							// 站点类型判断
							switch ($sites[$sitesID]['site']) {
								case 'hdchina':
									$url = $_url;
									break;
								default:
									break;
							}
							// 文件句柄
							$resource = fopen(self::$cacheDir . $sites[$sitesID]['site'].'.txt', 'a');
							// 成功：返回写入字节数，失败返回false
							$worldsnum = fwrite($resource, 'clients_'.$k."\n".$downloadDir."\n".$url."\n".$details_url."\n\n");
							fclose($resource);
						}
					}
				}
			}
		}
		// 按客户端循环辅种 结束
	}
	/**
	 * 
	 */
	public static function wechatMessage(){
		$br = "\r\n";
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

/**
 * transmission过滤函数，只保留正常做种
 */
function filterStatus( $v ){
	return isset($v['status']) && $v['status']===6;
}

/**
 * qBittorrent过滤函数，只保留正常做种
 */
function qbfilterStatus( $v ){
	if( ($v['state']=='uploading') || ($v['state'] == 'stalledUP') || ($v['state'] == 'pausedUP') || ($v['state'] == 'queuedUP') || ($v['state'] == 'checkingUP') || ($v['state'] == 'forcedUP') ){
		return true;
	}
	return false;
}
//PHP stdClass Object转array
function object_array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	}
	if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object_array($value);
		}
	}
	return $array;
}
// 对象转数组
function object2array(&$object) {
	return json_decode( json_encode( $object ), true );
}