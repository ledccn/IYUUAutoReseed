<?php
/**
 * totheglory解码类
 *
 */
use phpspider\core\requests;
use phpspider\core\selector;

class Totheglory implements decodeBase
{
	/**
     * 站点标志
     * @var string
     */
    const SITE = 'ttg';
	/**
     * 域名
     * @var string
     */
    const domain = 'totheglory.im';
	const HOST = 'https://'.self::domain.'/';
	// 下载种子的请求类型
	const METHOD = 'GET';
	/**
     * 种子存放路径
     * @var string
     */
    const TORRENT_DIR = TORRENT_PATH . self::SITE . DS;
	/**
     * 种子下载前缀
     */
    const downloadPrefix = 'dl/';
	/**
     * 种子详情页前缀
     */
    const detailsPrefix = 't/{}/';
	// 网页编码
	const encoding = 'UTF-8';
	// 超时时间
	const CONNECTTIMEOUT = 30;
	const TIMEOUT = 600;
	/**
     * cookie
     */
    public static $cookies = '';
	/**
     * 浏览器 User-Agent
     */
    public static $userAgent = '';
	/**
     * passkey
     */
    public static $passkey = '';
	/**
     * 获取的种子标志
     */
    public static $getTorrent = array('ico_free.gif');
	/**
     * H&R 标志
     */
    public static $HR = array('hit_run.gif','title="Hit and Run"','title="Hit');
	/**
     * 解码后种子列表数组
     */
	public static $TorrentList = array();
	/**
     * 初始化配置
     */
	public static function init(){
		global $configALL;

		$config = $configALL[self::SITE];
		self::$cookies = $config['cookie'];
		self::$userAgent = isset($config['userAgent']) && $config['userAgent'] ? $config['userAgent'] : $configALL['default']['userAgent'];
		self::$passkey = isset($config['passkey']) ? '/'.$config['passkey'] : '';

		requests::$input_encoding = self::encoding;	//输入的网页编码
		requests::$output_encoding = self::encoding;	//输出的网页编码
		requests::set_cookies(self::$cookies, self::domain);
		requests::set_useragent([self::$userAgent]);
		requests::set_timeout([self::CONNECTTIMEOUT,self::TIMEOUT]);
	}

	/**
     * 执行
     *
     * @param string
     * @return array
     */
    public static function run()
    {
		self::init();
		Rpc::init(self::SITE, self::METHOD);
		$html = self::get();
		if ( $html === null ) {
			exit(1);
		}
		$data = self::decode($html);
		Rpc::call($data);
		exit(0);
    }

    /**
     * 请求页面
     *
     * @param string        $url
     * @return array
     */
    public static function get($url = 'browse.php')
    {
        // 发起请求
		$html = requests::get(self::HOST.$url);
		// 获取列表页数据
		$html = selector::select($html, "//*[@id='torrent_table']");
		$data = selector::select($html, "//div[@class='name_left']");
		#p($data);exit;
		if(!$data){
			echo "登录信息过期，请重新设置！ \n";
			return null;
		}
        return $data;
    }

    /**
     * 解码
     *
     * @param array $data
     * @return array
     */
    public static function decode($data = array())
    {
		// 种子ID前缀
		$torrentIdPrefix = 'torrent="';
		$toorentIdStrLen = strlen($torrentIdPrefix);
        foreach ( $data as $k => $v ){
			$arr = array();
			// 种子基本信息处理
			// 种子id[单独截取]
			$idOffset = $idTemp = '';
			$idOffset = strpos($v,$torrentIdPrefix);
			$idTemp =substr($v,$idOffset + $toorentIdStrLen,10);
			$arr['id'] = substr($idTemp,0,strpos($idTemp,'"'));
			// 种子地址
			$arr['url'] = self::downloadPrefix . $arr['id'] . self::$passkey;
			#p($arr);exit;
			// 获取主标题
			// 偏移量
			$h1_offset = strpos($v, 'torrentname="') + strlen('torrentname="');
			$h1_len = strpos($v, '" torrent="') - $h1_offset;
			$arr['h1'] = substr($v, $h1_offset, $h1_len);
			if (strpos($arr['h1'],'&#x') != false) {
				$arr['h1'] = mb_convert_encoding($arr['h1'], 'UTF-8', 'HTML-ENTITIES');
			}

			// 组合返回数组
			self::$TorrentList[$k]['id'] = $arr['id'];
			self::$TorrentList[$k]['h1'] = $arr['h1'];
			self::$TorrentList[$k]['title'] = isset( $arr['title'] ) && $arr['title'] ? $arr['title'] : '';
			self::$TorrentList[$k]['details'] = self::HOST.str_replace('{}',$arr['id'],self::detailsPrefix);
			self::$TorrentList[$k]['download'] = self::HOST.$arr['url'];
			self::$TorrentList[$k]['filename'] = $arr['id'].'.torrent';

			// 种子促销类型解码
			if(strpos($v,self::$getTorrent[0]) === false){
				// 不免费
				self::$TorrentList[$k]['type'] = 1;
			}else{
				// 免费种子
				self::$TorrentList[$k]['type'] = 0;
			}
			// H&R检测
			foreach ( self::$HR as $hrV ){
				if(strpos($v,$hrV) != false){
					self::$TorrentList[$k]['hr'] = 1;
					// 删除
					#unset( self::$TorrentList[$k] );
					break;
				}
			}
			// 存活时间
			// 大小
			// 种子数
			// 下载数
			// 完成数
			// 完成进度
		}
		#p(self::$TorrentList);exit;
        return self::$TorrentList;
    }
}
