<?php
/**
 * 调试函数
 */
function p($data, $echo=true){
	$str='******************************'."\n";
	// 如果是boolean或者null直接显示文字；否则print
	if (is_bool($data)) {
		$show_data=$data ? 'true' : 'false';
	}elseif (is_null($data)) {
		$show_data='null';
	}else{
		$show_data=print_r($data,true);
	}
	$str.=$show_data;
	$str.="\n".'******************************'."\n";
	if($echo){
		echo $str;
		return null;
	}
	return $str;
}
/**
 * 微信推送Server酱
 */
function sc($text='', $desp='')
{
	global $configALL;
	$token = $configALL['sc.ftqq.com'];
	$desp = ($desp=='')?date("Y-m-d H:i:s") :$desp;
	$postdata = http_build_query(array(
			'text' => $text,
			'desp' => $desp
		));
	$opts = array('http' =>	array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		));
	$context  = stream_context_create($opts);
	$result = file_get_contents('http://sc.ftqq.com/'.$token.'.send', false, $context);
	return  $result;
}
/**
 * 微信推送 爱语飞飞
 */
function ff($text='', $desp='')
{
	global $configALL;
	$token = $configALL['iyuu.cn'];
	$desp = ($desp=='')?date("Y-m-d H:i:s") :$desp;
	$postdata = http_build_query(array(
			'text' => $text,
			'desp' => $desp
		));
	$opts = array('http' =>	array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		));
	$context  = stream_context_create($opts);
	$result = file_get_contents('http://iyuu.cn/'.$token.'.send', false, $context);
	return  $result;
}

/**
 * 微信推送 爱语飞飞
 * @param array  $torrent 种子数组
		Array
		(
			[id] => 118632
			[h1] => CCTV5+ 2019 ATP Men's Tennis Final 20191115B HDTV 1080i H264-HDSTV
			[title] => 央视体育赛事频道 2019年ATP男子网球年终总决赛 单打小组赛 纳达尔VS西西帕斯 20191115[优惠剩余时间：4时13分]
			[details] => https://hdsky.me/details.php?id=118632
			[download] => https://hdsky.me/download.php?id=118632
			[filename] => 118632.torrent
			[type] => 0
			[sticky] => 1
			[time] => Array
				(
					[0] => "2019-11-16 20:41:53">4时13分
					[1] => "2019-11-16 14:41:53">1时<br />46分
				)
			[comments] => 0
			[size] => 5232.64MB
			[seeders] => 69
			[leechers] => 10
			[completed] => 93
			[percentage] => 100%
			[owner] => 匿名
		)
 */
function send($site = '', $torrent = array())
{
	$br = "\r\n";
	$text = $site. ' 免费：' .$torrent['filename']. '，添加成功';
	$desp = '主标题：'.$torrent['h1'] . $br;

	if ( isset($torrent['title']) ) {
		$desp .= '副标题：'.$torrent['title']. $br;
	}
	if ( isset($torrent['size']) ) {
		$desp .= '大小：'.$torrent['size']. $br;
	}
	if ( isset($torrent['seeders']) ) {
		$desp .= '做种数：'.$torrent['seeders']. $br;
	}
	if ( isset($torrent['leechers']) ) {
		$desp .= '下载数：'.$torrent['leechers']. $br;
	}
	if ( isset($torrent['owner']) ) {
		$desp .= '发布者：'.$torrent['owner']. $br;
	}
	return ff($text, $desp);
}

/**
 * @brief 下载种子
 * @param string $url 种子URL
 * @param string  $cookies 模拟登陆的cookie
 * @return mixed 返回的数据
 */
function download($url, $cookies, $useragent, $method = 'GET')
{
	$header = array(
		"Content-Type:application/x-www-form-urlencoded",
		'User-Agent: '.$useragent);
	$ch = curl_init();
	if($method === 'POST'){
		curl_setopt($ch, CURLOPT_POST, true );
	}
	if(stripos($url, 'https://') !== FALSE) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
	curl_setopt($ch, CURLOPT_COOKIE,$cookies);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,60);
	curl_setopt($ch, CURLOPT_TIMEOUT,600);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
/**
 * @brief 文件大小格式化为MB
 * @param string $from 文件大小
 * @return int 单位MB
 */
function convertToMB($from){
    $number=substr($from,0,-2);
    switch(strtoupper(substr($from,-2))){
        case "KB":
            return $number/1024;
        case "MB":
            return $number;
        case "GB":
            return $number*pow(1024,1);
        case "TB":
            return $number*pow(1024,2);
        case "PB":
            return $number*pow(1024,3);
        default:
            return $from;
    }
}

/**
 * @brief 种子过滤器
 * @param string $site 站点标识
 * @param array  $torrent 种子数组
 * 	Array
	(
		[id] => 118632
		[h1] => CCTV5+ 2019 ATP Men's Tennis Final 20191115B HDTV 1080i H264-HDSTV
		[title] => 央视体育赛事频道 2019年ATP男子网球年终总决赛 单打小组赛 纳达尔VS西西帕斯 20191115[优惠剩余时间：4时13分]
		[details] => https://hdsky.me/details.php?id=118632
		[download] => https://hdsky.me/download.php?id=118632
		[filename] => 118632.torrent
		[type] => 0
		[sticky] => 1
		[time] => Array
			(
				[0] => "2019-11-16 20:41:53">4时13分
				[1] => "2019-11-16 14:41:53">1时<br />46分
			)
		[comments] => 0
		[size] => 5232.64MB
		[seeders] => 69
		[leechers] => 10
		[completed] => 93
		[percentage] => 100%
		[owner] => 匿名
	)
 * @return bool 或 string 	false不过滤
 */
function filter($site = '', $torrent = array()){
	global $configALL;
	$config = $configALL[$site];
	$filter = array();
	// 读取配置
	if (isset($configALL['default']['filter']) || isset($config['filter'])) {
		$filter = isset($config['filter']) && $config['filter'] ? $config['filter'] : $configALL['default']['filter'];
	}else {
		return false;
	}
	$filename = $torrent['filename'];

	// 兼容性
	if ( empty($torrent['size']) ) {
		return false;
	}
	// 大小过滤
	$size = convertToMB($torrent['size']);
	$min = isset($filter['size']['min']) ? convertToMB($filter['size']['min']) : 0;
	$max = isset($filter['size']['max']) ? convertToMB($filter['size']['max']) : 2097152;	//默认 2097152MB = 2TB
	if ($min > $size || $size > $max) {
		return $filename. ' ' .$size. 'MB，被大小过滤';
	}

	// 兼容性
	if ( empty($torrent['seeders']) ) {
		return false;
	}
	// 种子数过滤
	$seeders = $torrent['seeders'];
	$min = isset($filter['seeders']['min']) ? $filter['seeders']['min'] : 1;	//默认 1
	$max = isset($filter['seeders']['max']) ? $filter['seeders']['max'] : 3;	//默认 3
	if ($min > $seeders || $seeders > $max) {
		return $filename. ' 当前做种' .$seeders. '人，被过滤';
	}

	// 兼容性
	if ( empty($torrent['leechers']) ) {
		return false;
	}
	// 下载数过滤
	$leechers = $torrent['leechers'];
	$min = isset($filter['leechers']['min']) ? $filter['leechers']['min'] : 0;		//默认
	$max = isset($filter['leechers']['max']) ? $filter['leechers']['max'] : 30000;	//默认
	if ($min > $leechers || $leechers > $max) {
		return $filename. ' 当前下载' .$leechers. '人，被过滤';
	}

	// 兼容性
	if ( empty($torrent['completed']) ) {
		return false;
	}
	// 完成数过滤
	$completed = $torrent['completed'];
	$min = isset($filter['completed']['min']) ? $filter['completed']['min'] : 0;		//默认
	$max = isset($filter['completed']['max']) ? $filter['completed']['max'] : 30000;	//默认
	if ($min > $completed || $completed > $max) {
		return $filename. ' 已完成数' .$completed. '人，被过滤';
	}

	return false;
}

function oddFilter($var)
{
    // 返回$var最后一个二进制位，
    // 为1则保留（奇数的二进制的最后一位肯定是1）
    return($var & 1);
}

function evenFilter($var)
{
	// 返回$var最后一个二进制位，
    // 为0则保留（偶数的二进制的最后一位肯定是0）
	return(!($var & 1));
}

// 签名函数
function sign( $timestamp ){
	global $configALL;
	// 爱语飞飞
	$token = isset($configALL['iyuu.cn']) && $configALL['iyuu.cn'] ? $configALL['iyuu.cn'] : '';
	// 鉴权
	$token = isset($configALL['secret']) && $configALL['secret'] ? $configALL['secret'] : $token;
	return sha1($timestamp . $token);
}

/**
 * @brief 分离token中的用户uid
 * token算法：IYUU + uid + T + sha1(openid+time+盐)
 * @param string $token		用户请求token
 */
function getUid($token){
	//验证是否IYUU开头，strpos($token,'T')<15,token总长度小于60(40+10+5)
	return (strlen($token)<60)&&(strpos($token,'IYUU')===0)&&(strpos($token,'T')<15) ? substr($token,4,strpos($token,'T')-4): false;
}
