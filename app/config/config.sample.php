<?php
/**
 * 技术讨论及后续更新，请加入QQ群！！！！！！！
	群名称：IYUU自动辅种交流
	QQ群号：859882209
 */
return array(
	// 1.爱语飞飞 微信通知配置
	'iyuu.cn'		=> 'IYUU',
	// 2.server酱 微信通知配置
	'sc.ftqq.com'   => '',
	// 发布员鉴权
	'secret' 		=> '',
	// 全局默认配置
    'default'      => array(
		// 4.【必须配置】浏览器UA，打开http://demo.iyuu.cn 复制过来即可
		'userAgent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36',
		// 5.【可选配置】下载软件的监控目录（下载免费种时：工作模式0 必须配置，工作模式1 不用配置）
        'watch'         => '/volume3/downloads/watch/',
		// 6.【可选配置】全局工作模式：0 watch优先[默认]，1 负载均衡，2 混合模式（这里保持默认即可）
		'workingMode'	=> 0,
		// 7.【自动辅种必须配置】全局客户端设置（条目不够可以复制，用不到的请删除）
        'clients'   => array(
			// 全局客户端设置 开始
            array(
				'type'	=>	'transmission',	// 支持：transmission、qBittorrent
				'host'	=>	'http://127.0.0.1:9091/transmission/rpc',
				'username'	=>	'',
				'password'	=>	'',
				'downloadDir'	=>	'/volume2/dawei/mt',	// 这个目录是默认下载目录，与自动辅种没有任何关系。
				//'move' =>array(
				//	'type' => 2,		// 0保持不变，1减，2加， 3直接替换
				//	'path' =>array(
				//		'/sda1' => '/volume1',
				//	),
				//),
			),
			// （条目不够可以复制，用不到的请删除）
			array(
				'type'	=>	'qBittorrent',	// 支持：transmission、qBittorrent
				'host'	=>	'http://www.baidu.com:8083',
				'username'	=>	'',
				'password'	=>	'',
				'downloadDir'	=>	'',		// 这个目录是默认下载目录，与自动辅种没有任何关系。
			),
			// 全局客户端设置 结束
		),
		// 8.下载过滤规则（目前仅对天空、我堡有效）
		'filter' => array(
			// 9.是否下载HR种子：0 不下载，1 下载
			'hr'=> 0,
			// 10.种子大小
			'size'=>array(
				'min'	=>	'1GB',
				'max'	=>	'280GB',
			),
			// 11.做种人数
			'seeders'=>array(
				'min'	=>	1,
				'max'	=>	3,
			),
			// 12.下载人数
			'leechers'=>array(
				'min'	=>	0,
				'max'	=>	10000,
			),
			// 13.完成人数
			'completed'=>array(
				'min'	=>	0,
				'max'	=>	10000,
			),
		),
		// 适配器，暂时用不到
		'adapter' => array(
			'free'	=>	'',
			'2x'	=>	'',
			'2xfree'=>	'',
			'30%'	=>	'',
			'50%'	=>	'',
			'2x50%'	=>	'',
		),
		'url'           => array(
            'torrents.php',
        ),
		'CONNECTTIMEOUT'=> 60,
        'TIMEOUT'       => 600,
	),
	/**
	 * 以下为各站点的独立配置（互不影响、互不冲突）
	 * 1、自动辅种：需要配置各站的passkey（没有配置passkey的站点会自动跳过）
	 * 2、可以根据各站自己的需要，按照馒头站的完整配置示例，自己补全！
	 */
    // m-team 序号：1
    'm-team'      => array(
		// 14.m-team的cookie	如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => 'tp=',
		// 15.m-team的passkey	【必须配置】
		'passkey'       => '',
		// 种子Tracker的IP地址选择 可选：ipv4，ipv6
		'ip_type'		=> 'ipv4',
		// 16.站点单独使用的下载客户端配置（每个站点可以独立配置，不冲突）（条目不够可以复制，用不到的请删除）
        'clients'   => array(
			array(
				'type'	=>	'transmission',	// 支持：transmission、qBittorrent
				'host'	=>	'http://127.0.0.1:9091/transmission/rpc',
				'username'	=>	'',
				'password'	=>	'',
				'downloadDir'	=>	'/volume2/dawei/mt',
			),
			array(
				'type'	=>	'transmission',
				'host'	=>	'http://baidu.com:9092/transmission/rpc',
				'username'	=>	'',
				'password'	=>	'',
				'downloadDir'	=>	'/media/sony/qb',
			),
			array(
				'type'	=>	'qBittorrent',
				'host'	=>	'http://www.baidu.com:8083',
				'username'	=>	'',
				'password'	=>	'',
				'downloadDir'	=>	'',
			),
		),
		// 17.工作模式选择：0 watch优先[默认]，1 负载均衡，2 混合模式
		'workingMode'	=> 1,
    ),
    // keepfrds 序号：2
    'keepfrds'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// ourbits 序号：3
	'ourbits'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
		'id' => 0,					// 用户ID
		'is_vip'		=> 0,		// 是否具有VIP或特殊权限？0 普通，1 VIP
    ),
    // HDSky 序号：4
    'hdsky'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// pter 序号：5
	'pter'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// tjupt 序号：6
	'tjupt'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// hdhome 序号：7
	'hdhome'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// btschool 序号：8
	'btschool'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// PTHome 序号：9
    'pthome'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// hddolby 序号：10
	'hddolby'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// TorrentCCF 序号：11
    'torrentccf'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// PTMSG 序号：12
    'ptmsg'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// MoeCat 序号：13
    'moecat'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // totheglory 序号：14
    'ttg'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
	// nanyangpt 序号：15
    'nanyangpt'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// springsunday.net 序号：16
    'ssd'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// yingk 序号：17
    'yingk'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// hdcity 序号：18
    'hdcity'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// 52pt.site 序号：19
    '52pt'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// brobits.cc 序号：20
    'brobits'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// www.beitai.pt 序号：21
    'beitai'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// pt.eastgame.org 序号：22
    'eastgame'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// pt.soulvoice.club 序号：23
    'soulvoice'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// chdbits 序号：24
    'chdbits'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// leaguehd 序号：25
    'leaguehd'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
        'passkey'       => '',
	),
	// ptsbao.club 序号：26
	'ptsbao'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hdchina 序号：27
	'hdchina'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hdarea 序号：28
	'hdarea'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hdtime 序号：29
	'hdtime'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// 1ptba 序号：30
	'1ptba'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hd4fans 序号：31
	'hd4fans'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hdbug 序号：32
	'hdbug'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// opencd 序号：33
	'opencd'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// hdstreet 序号：34
	'hdstreet'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// joyhd 序号：35
	'joyhd'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// dmhy 序号：36
	'dmhy'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),
	// upxin 序号：37
	'upxin'      => array(
		// 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
		'cookie'        => '',
		// 如果需要自动辅种，必须配置
		'passkey'       => '',
	),

	// 配置文件结束
);