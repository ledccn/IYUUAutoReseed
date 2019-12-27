<?php
/**
 * hdcity.city解码类
 *
 */
use phpspider\core\requests;
use phpspider\core\selector;
class hdcity implements decodeBase
{
	/**
     * 站点标志
     * @var string
     */
    const SITE = 'hdcity';
	/**
     * 域名
     * @var string
     */
    const domain = 'hdcity.city';
	const HOST = 'https://'.self::domain.'/';
	// 下载种子的请求类型
	const METHOD = 'POST';
	/**
     * 种子存放路径
     * @var string
     */
    const TORRENT_DIR = TORRENT_PATH . self::SITE . DS;
	/**
     * 种子下载前缀
     */
    const downloadPrefix = 'download?id=';
	/**
     * 种子详情页前缀
     */
    const detailsPrefix = 't-';
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
    public static $getTorrent = array('class="pro_free');
	/**
     * H&R 标志
     */
    public static $HR = array('class="hitandrun"','alt="H&amp;R"','title="H&amp;R"');
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
		self::$passkey = isset($config['passkey']) ? '&passkey='.$config['passkey'].'&https=1' : '';

		requests::$input_encoding = self::encoding;
		requests::$output_encoding = self::encoding;
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
    public static function run($url = 'pt')
    {
		self::init();
		Rpc::init(self::SITE, self::METHOD);
		$html = self::get($url);
		#p($html);exit;
		if ( $html === null ) {
			exit(1);
		}
		$data = self::decode($html);
		#p($data);exit;
		Rpc::call($data);
    }
    /**
     * 请求页面
     *
     * @param string        $url
     * @return array
     */
    public static function get($url = 'pt')
    {
        // 发起请求
		$html = requests::get(self::HOST.$url);
		// 获取列表页数据
		$data = selector::select($html, "//*[@class='tr_normal trblock']");
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
		$downloadStrLen = strlen(self::downloadPrefix);	// 前缀长度
		$downloadStrEnd = '"';	//种子地址结束标志
		$len = $downloadStrLen + 60;		// 截取长度
        foreach ( $data as $k => $v ){
			$arr = array();
			$urlTemp = '';
			// 种子基本信息处理
			// 提取种子id
			$idPrefix = 'href="t-';
			$offset = strpos($v,$idPrefix) + strlen($idPrefix);
			// 截取
			$urlTemp = substr($v, $offset, 13);
			// 种子id
			$arr['id'] = substr($urlTemp,0,strpos($urlTemp,$downloadStrEnd));

			// 提取种子下载地址
			$offset = strpos($v,self::downloadPrefix);
			// 截取
			$urlTemp = substr($v,$offset,$len);
			// 种子地址
			$arr['url'] = substr($urlTemp,0,strpos($urlTemp,$downloadStrEnd));
			// 种子地址过滤
			$arr['url'] = str_replace("&amp;","&",$arr['url']);

			// 获取主标题
			// 偏移量
			$aTemp = selector::select($v, '//a');
			$arr['h1'] = $aTemp[0];
			// 主标题过滤：加粗
			if (strpos($arr['h1'],'</strong>') != false) {
				$arr['h1'] = selector::select($v, '//strong');
				#$arr['h1'] = selector::select($v, '@<strong>(.*?)</strong>@', "regex");
			}
			// 主标题二次过滤：制作组
			if (strpos($arr['h1'],'</span>') != false) {
				$arr['h1'] = selector::remove($arr['h1'], '//span');
			}

			// 获取副标题
			// 偏移量
			$arr['title'] = selector::select($v, "//div[@class='trbi']/a");
			if(strpos($arr['title'],'</span>') != false){
				$arr['title'] = selector::select($arr['title'], '//span');
			}
			$ploto = selector::select($v, "//div[@class='ploto']");
			if ( strpos($ploto,'</a>') != false ) {
				// 副标题二次过滤：码率
				$ploto = selector::remove($ploto, "//a");
			}
			$arr['title'] = $ploto ? $arr['title'] .' '. $ploto : $arr['title'];
			#p($arr);exit;
			// 组合返回数组
			self::$TorrentList[$k]['id'] = $arr['id'];
			self::$TorrentList[$k]['h1'] = $arr['h1'];
			self::$TorrentList[$k]['title'] = isset( $arr['title'] ) && $arr['title'] ? $arr['title'] : '';
			self::$TorrentList[$k]['details'] = self::HOST.self::detailsPrefix.$arr['id'];
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
			// 存活时间
			// 大小
			// 种子数
			// 下载数
			// 完成数
			// 完成进度
		}
		#p(self::$TorrentList);
        return self::$TorrentList;
    }
}
