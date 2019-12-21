<?php
/**
 * 定义站点解码类接口
 */
interface decodeBase
{
	/**
     * 初始化配置
     */
    public static function init();

    /**
     * 执行
     *
     * @param string
     * @return array
     */
    public static function run();

    /**
     * 接口方法，在类中实现
     * 请求url，获取html页面
     * @param string        $url
     * @return array
     */
    public static function get($url = 'torrents.php');

    /**
     * 接口方法，在类中实现
     * 解码html为种子数组
     * @param array $html
     * @return array
     * Array
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
    public static function decode($html = array());
}
