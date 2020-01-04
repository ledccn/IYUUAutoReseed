<?php
/**
 * hdsky.me解码类
 * 明星(Veteran User)及以上用户会永远保留账号，	必须注册至少25周，并且下载至少4TB，分享率大于4.0。[16TB保号]
 */
use phpspider\core\requests;
use phpspider\core\selector;

class Hdsky implements decodeBase
{
	/**
     * 站点标志
     * @var string
     */
    const SITE = 'hdsky';
	/**
     * 域名
     * @var string
     */
    const domain = 'hdsky.me';
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
    public static $getTorrent = array('class="pro_free');
	/**
     * 促销时间特征
     */
    public static $proTime = '优惠剩余时间';
	/**
     * 置顶标志
     */
    public static $sticky = 'title="置顶"';
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
    public static function run($url = 'torrents.php')
    {
		self::init();
		Rpc::init(self::SITE, self::METHOD);
		$html = self::get($url);
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
		$data1 = selector::select($html, "//*[@class='stickbg progresstr']");
		$data2 = selector::select($html, "//*[@class='progresstr']");
		if (empty($data1)) {
			$data = $data2;
		}else{
			$data = array_merge($data1, $data2);
		}		
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

			// 获取副标题
			// 偏移量
			$h2_offset = strpos($v,'<br />') + strlen('<br />');
			$h2_len = strpos($v,'</td><td width="32"',$h2_offset) - $h2_offset;
			if($h2_len > 0){
				//存在副标题
				$titleTemp = substr($v, $h2_offset, $h2_len);
				$titleSpan = '';
				$title = selector::remove($titleTemp, "//span");

				// 精确适配标签 begin
				$span = selector::select($titleTemp, '//span');
				if(!empty($span)){
					if(is_array($span)){
						// 查询促销时间特征
						if(strpos($title, self::$proTime) != false){
							$key = count($span);
							// 替换占位符
							$title = str_replace('<b></b>',$span[$key-1],$title);
							// 适配
							unset($span[$key-1]);
						}
						foreach ( $span as $vv ){
							$titleSpan.='['.$vv.'] ';
						}
					}else{
						// 查询促销时间特征
						if(strpos($title, self::$proTime) != false){
							// 替换占位符
							$title = str_replace('<b></b>',$span,$title);
						}else {
							$titleSpan.='['.$span.'] ';
						}
					}
				}
				// 精确适配标签 end

				$arr['title'] = $titleSpan . $title;
				#echo $arr['title'];
			}else {
				$arr['title'] = '';
			}

			// 组合返回数组
			self::$TorrentList[$k]['id'] = $arr['id'];
			self::$TorrentList[$k]['h1'] = $arr['h1'];
			self::$TorrentList[$k]['title'] = isset( $arr['title'] ) && $arr['title'] ? $arr['title'] : '';
			self::$TorrentList[$k]['details'] = self::HOST.self::detailsPrefix.$arr['id'];
			self::$TorrentList[$k]['download'] = self::HOST.$arr['url'];
			self::$TorrentList[$k]['filename'] = $arr['id'].'.torrent';

			// 种子促销类型discount
			if(strpos($v,self::$getTorrent[0]) === false){
				// 不免费
				self::$TorrentList[$k]['type'] = 1;
				// 非免费，是否需要获取扩展信息
				//continue;
			}else{
				// 免费种子
				self::$TorrentList[$k]['type'] = 0;
			}

			// 是否置顶sticky
			self::$TorrentList[$k]['sticky'] = strpos($v,self::$sticky)===false ? 0 : 1;

			// 优惠剩余时间proTime（可选）
			// 存活时间added（必有）
			$added = selector::select($v, '@<span title=(.*?)</span>@', "regex");
			self::$TorrentList[$k]['time'] = $added;
			#p($added);
			/*
				Array
                (
                    [0] => "2019-11-20 15:01:17">3天22时
                    [1] => "2014-09-24 00:09:20">5年<br />2月
                )

				string
					"2016-04-24 15:43:25">3年<br />7月
			*/

			$options = selector::select($v, "//*[@class='rowfollow']");
			#p($options);
			/*
				$options = Array
				(
					[0] => <a href="comment.php?action=add&amp;pid=118608&amp;type=torrent" title="&#x6DFB;&#x52A0;&#x8BC4;&#x8BBA;">0</a>
					[1] => 7.11<br/>GB
					[2] => <b><a href="details.php?id=118608&amp;hit=1&amp;dllist=1#seeders"><font color="#ff0000">1</font></a></b>
					[3] => <b><a href="details.php?id=118608&amp;hit=1&amp;dllist=1#leechers">105</a></b>
					[4] => 0
					[5] => 0%
					[6] => <span class="nowrap"><a href="userdetails.php?id=70966" class="Uploader_Name"><b>r9ruibu</b></a><img class="star" src="pic/trans.gif" alt="Donor" style="margin-left: 2pt"/></span>
				)

				对应的：
					[0] => 评论
					[1] => 大小size
					[2] => 种子数seeders
					[3] => 下载数leechers
					[4] => 完成数
					[5] => 完成进度
					[6] => 发布者
			*/

			// 0 评论comments
			self::$TorrentList[$k]['comments'] = selector::select($options[0], "//a");
			// 1 大小size
			self::$TorrentList[$k]['size'] = str_replace('<br/>','',$options[1]);
			// 2 种子数seeders
			if ( empty($options[2]) ) {
				$seeders = 0;
			} else {
				if( strpos($options[2],'</font>') === false ){
					if ( strpos($options[2],'</span>') === false ) {
						// 普通特征
						$seeders = selector::select($options[2], "//a");
					} else {
						// 新种 0做种特征
						$seeders = selector::select($options[2], "//span");
					}
				}else{
					// 新种 1做种特征
					$seeders = selector::select($options[2], "//font");
				}
			}
			self::$TorrentList[$k]['seeders'] = $seeders;

			// 3 下载数leechers
			self::$TorrentList[$k]['leechers'] = empty($options[3]) ? 0 : selector::select($options[3], "//a");
			// 4 完成数completed
			self::$TorrentList[$k]['completed'] = empty($options[4]) ? 0 : selector::select($options[4], "//b");
			// 5 完成百分比percentage
			self::$TorrentList[$k]['percentage'] = strpos($options[5],'</b>') === false ? $options[5] : selector::select($options[5], "//b");
			// 6 发布者owner
			$owner = selector::select($options[6], "//b");
			self::$TorrentList[$k]['owner'] = empty($owner) ? '匿名' : $owner;

			#exit(0);
		}
        return self::$TorrentList;
    }
}
