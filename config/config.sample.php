<?php
/**
 * 技术讨论及后续更新，请加入QQ群！！！！！！！
    群名称：IYUU自动辅种交流
    QQ群号：859882209
 * IYUU自动辅种工具--最简配置（所有平台通用教程） https://www.iyuu.cn/archives/324/
 * IYUU自动辅种工具--如何下载最新源码？ https://www.iyuu.cn/archives/338/
 * IYUU自动辅种工具--合作站点鉴权配置说明 https://www.iyuu.cn/archives/337/
 * IYUU自动下载种子之RSS订阅使用教程 https://www.iyuu.cn/archives/349/
 * IYUU自动转移做种客户端-使用教程 https://www.iyuu.cn/archives/351/
    脚本仓库下载法：
    git clone https://github.com/ledccn/IYUUAutoReseed
    cd IYUUAutoReseed
    composer install
 */
return array(
    // 1.【必须配置】爱语飞飞 微信通知，请访问https://iyuu.cn 用微信扫码申请
    'iyuu.cn'		=> 'IYUU',
    // 2.server酱 微信通知配置
    'sc.ftqq.com'   => '',
    // 3.发布员鉴权
    'secret' 		=> '',
    // 4.全局默认配置
    'default'      => array(
        // 5.【必须配置】浏览器UA，打开http://demo.iyuu.cn 复制过来即可
        'userAgent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36',
        // 6.【自动辅种必须配置】全局客户端设置（条目不够可以复制）
        'clients'   => array(
            // 全局客户端设置 开始
            # 开始
            array(
                'type'	=>	'transmission',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:9091/transmission/rpc',		// 警告！注意：transmission/rpc这段别动，你只需要修改 127.0.0.1:9091
                'username'	=>	'',
                'password'	=>	'',
                'BT_backup' =>  '/var/lib/transmission/torrents',                        // 移动做种：如果脚本与当前客户端不在一台机器，必须配置
                'move'      =>  0,      // 0不移动，1移动并辅种，2移动仅辅种自身，3未定义
            ),
            # 结束
            # 开始
            array(
                'type'	=>	'qBittorrent',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:8083',
                'username'	=>	'',
                'password'	=>	'',
                'BT_backup' =>  'C:\Users\ASUS\AppData\Local\qBittorrent\BT_backup',    // 移动做种：必须配置
                'move'      =>  0,      // 0不移动，1移动并辅种，2移动仅辅种自身，3未定义
            ),
            # 结束
            // 全局客户端设置 结束
        ),
        // 移动做种必须配置
        'move' =>array(
            'type' => 0,		// 0保持不变，1减，2加，3替换
            'path' =>array(
                // 当前路径 => 目标路径
                '/downloads' => '/volume1',
            ),
        ),
        'workingMode'	=> 0,
        'watch'         => '/volume1/downloads',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
            'seeders'=>array(
                'min'	=>	1,
                'max'	=>	3,
            ),
            'leechers'=>array(
                'min'	=>	0,
                'max'	=>	10000,
            ),
            'completed'=>array(
                'min'	=>	0,
                'max'	=>	10000,
            ),
        ),
        'CONNECTTIMEOUT'=> 60,
        'TIMEOUT'       => 600,
    ),
    /**
     * 以下为各站点的独立配置（互不影响、互不冲突）
     * 自动辅种：需要配置各站的passkey（没有配置passkey的站点会自动跳过）
     */
    // m-team 序号：1
    'm-team'      => array(
        // 14.m-team的cookie	如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => 'tp=',
        // 15.m-team的passkey	【必须配置】
        'passkey'       => '',
        // 种子Tracker的IP地址选择 可选：ipv4，ipv6
        'ip_type'		=> 'ipv4',
        'clients'   => array(
            array(
                'type'	=>	'transmission',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:9091/transmission/rpc',		// 警告！注意：transmission/rpc这段别动，你只需要修改 127.0.0.1:9091
                'username'	=>	'',
                'password'	=>	'',
                'downloadDir'=> '',
            ),
        ),
        'workingMode'	=> 1,
        'watch'         => '',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
        ),
    ),
    // keepfrds 序号：2
    'keepfrds'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'workingMode'	=> 1,
        'watch'         => '',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
        ),
    ),
    // ourbits 序号：3
    'ourbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID
        'is_vip'		=> 0,		// 是否具有VIP或特殊权限？0 普通，1 VIP
        'workingMode'	=> 0,
        'watch'         => '/root/downloads',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
        ),
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
        // 种子Tracker的IP地址选择 可选：ipv4，ipv6
        'ip_type'		=> 'ipv4',
    ),
    // totheglory 序号：14
    'ttg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        // 如果需要rss订阅，必须配置
        'rss'       => '',
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
        // 必须配置
        'cookie'        => '',
        // 如果需要自动辅种，必须配置cuhash
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
        // 必须配置
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
    // hddisk.life 序号：32
    'hdbug'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // opencd 序号：33	皇后
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
    // dmhy 序号：36	幼儿园
    'dmhy'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdu 序号：37
    'upxin'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // oshen 序号：38
    'oshen'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // discfan 序号：39	港知堂
    'discfan'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdzone 序号：40
    'hdzone'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // cnscg 序号：41	圣城
    'cnscg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // nicept 序号：42	老师
    'nicept'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdbd 序号：43	伊甸园
    'hdbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // byr 序号：44	北邮
    'byr'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // CCFBits 序号：45
    'ccfbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdbits 序号：46
    'hdbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // PTPBD 序号：47
    'ptpbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // HD-T 序号：48
    'hd-torrents'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),

    // 配置文件结束
);
