<?php
/**
 * chdbits.co解码类
 */
use phpspider\core\requests;
use phpspider\core\selector;

class Chdbits implements decodeBase
{
	/**
     * 站点标志
     * @var string
     */
    const SITE = 'chdbits';
	/**
     * 域名
     * @var string
     */
    const domain = 'chdbits.co';
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
    const downloadPrefix = 'download.php?id=';
	/**
     * 种子详情页前缀
     */
    const detailsPrefix = 'details.php?id=';
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
    public static $getTorrent = array('class="pro_free','title="免费"');
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
    public static function get($url = 'torrents.php')
    {
        // 发起请求
		$html = requests::get(self::HOST.$url);
		// 获取列表页数据
		$data = selector::select($html, "//*[@class='torrentname']");
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
		$len = $downloadStrLen + 10;		// 截取长度
        foreach ( $data as $k => $v ){
			$arr = array();
			// 种子基本信息处理
			// 偏移量
			$offset = strpos($v,self::downloadPrefix);
			// 截取
			$urlTemp = substr($v,$offset,$len);
			// 种子地址
			$arr['url'] = substr($urlTemp,0,strpos($urlTemp,$downloadStrEnd));
			// 种子id
			$arr['id'] = substr($arr['url'],$downloadStrLen);

			// 获取主标题
			// 偏移量
			$h1_offset = strpos($v, '<a title="') + strlen('<a title="');
			$h1_len = strpos($v, '" href="details.php?id=') - $h1_offset;
			$arr['h1'] = substr($v, $h1_offset, $h1_len);

			// 获取副标题(倒序算法)
			// 偏移量
			$h2StrStart = '<br />';
			$h2StrEnd = '</td><td width="50" class="embedded"';
			$h2_endOffset = strpos($v,$h2StrEnd);
			$temp = substr($v, 0, $h2_endOffset);
			$h2_offset = strrpos($temp,$h2StrStart);
			if ($h2_offset === false ) {
				$arr['title'] = '';
			} else {
				$h2_len = strlen($temp) - $h2_offset - strlen($h2StrStart);
				//存在副标题
				$titleTemp = substr($temp, $h2_offset + strlen($h2StrStart), $h2_len);
				// 第二次过滤				
				// 精确适配标签 begin
				$titleSpan = '';
				$title = selector::remove($titleTemp, "//div");
				$span = selector::select($titleTemp, '//div');
				if(!empty($span)){
					if(is_array($span)){
						foreach ( $span as $vv ){
							if( empty($vv) || (strpos($titleTemp,'<div') != false) ){
								continue;
							}
							$titleSpan.='['.$vv.'] ';
						}
					}else{		
						$titleSpan.='['.$span.'] ';
					}
				}
				// 精确适配标签 end
				// 最后过滤
				if ( strpos($title,'<font') != false ) {
					$offset = 0;
					$offset = strpos($title,'>')+1;
					$title = substr($title, $offset);
				}
				$title = str_replace('</font>',"",$title);
				$arr['title'] = $titleSpan . $title;
			}

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
