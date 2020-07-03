<?php
/**
 * 技术讨论及后续更新，请加入QQ群！！！！！！！
    群名称：IYUU自动辅种交流
    QQ群号：859882209
 * IYUU自动辅种工具-【安装篇】如何下载最新源码？ https://www.iyuu.cn/archives/338/
 * IYUU自动辅种工具-【安装篇】Windows之git https://www.iyuu.cn/archives/367/
 * IYUU自动辅种工具-【安装篇】群晖Linux之git https://www.iyuu.cn/archives/372/
 * IYUU自动辅种工具-【安装篇】小钢炮手把手教程 https://www.iyuu.cn/archives/386/
 * IYUU自动辅种工具--最简配置（所有平台通用教程） https://www.iyuu.cn/archives/324/
 * IYUU自动辅种工具--合作站点鉴权配置说明 https://www.iyuu.cn/archives/337/
 * IYUU自动下载种子--之RSS订阅使用教程 https://www.iyuu.cn/archives/349/
 * IYUU自动转移做种客户端--使用教程 https://www.iyuu.cn/archives/351/
    脚本仓库GIT下载法：
    git clone https://gitee.com/ledc/IYUUAutoReseed.git
    cd IYUUAutoReseed
    php ./iyuu.php
 */
return array(
    // 1.【必须配置】爱语飞飞 微信通知，请访问https://iyuu.cn 用微信扫码申请
    'iyuu.cn'		=> 'IYUU',
    // 2.全局默认配置
    'default'      => array(
        // 3.【必须配置】浏览器UA，打开http://demo.iyuu.cn 复制过来即可
        'userAgent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.1303.189 Safari/537.36',
        // 4.【自动辅种必须配置】全局客户端设置（条目不够可以复制）
        'clients'   => array(
            // 全局客户端设置 开始
            # 开始
            array(
                'type'	=>	'transmission',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:9091/transmission/rpc',		// 警告！注意：transmission/rpc这段别动，你只需要修改 127.0.0.1:9091
                'username'	=>	'',
                'password'	=>	'',
                'BT_backup' =>  '/var/lib/transmission/torrents',                        // 移动做种：如果脚本与当前客户端不在一台机器，必须配置
                'move'      =>  0,      // 0不移动，1移动并辅种，2移动且只在当前客户端辅种
            ),
            # 结束
            # 开始
            array(
                'type'	=>	'qBittorrent',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:8083',
                'username'	=>	'',
                'password'	=>	'',
                'root_folder'=> 1,   // 0不创建根目录，1创建根目录
                'BT_backup' =>  'C:\Users\ASUS\AppData\Local\qBittorrent\BT_backup',    // 移动做种：必须配置，Linux搜索方法：find / -name BT_backup
                'move'      =>  0,      // 0不移动，1移动并辅种，2移动且只在当前客户端辅种
            ),
            # 结束
            // 全局客户端设置 结束
        ),
        // 5.移动做种必须配置
        'move' =>array(
            'type' => 0,		// 0保持不变，1减，2加，3替换
            'path' =>array(
                // 当前路径 => 目标路径
                '/downloads' => '/volume1',
            ),
            'paused'         => 0,      //转移成功，自动开始任务：0开始，1暂停
            'skip_check'     => 0,      //转移成功，跳校验：0不跳、1跳校验
            'delete_torrent' => 0,      //转移成功，删除当前做种：0不删除、1删除
        ),
        // 6.RSS工作模式
        'workingMode'	=> 0,
        // 7.监控目录
        'watch'         => '/volume1/downloads',
        // 8.RSS过滤参数配置
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
    // 9.server酱 微信通知配置
    'sc.ftqq.com'   => '',
    // 10.发布员鉴权
    'secret' 		=> '',
    /**
     * 以下为各站点的独立配置（互不影响、互不冲突）
     * 自动辅种：需要配置各站的passkey（没有配置passkey的站点会自动跳过）
     */
    // ourbits
    'ourbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'workingMode'	=> 0,
        'watch'         => '/root/downloads',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
        ),
    ),
    // hddolby
    'hddolby'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
    ),
    // hdhome
    'hdhome'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
    ),
    // PTHome
    'pthome'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
    ),
    // MoeCat
    'moecat'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        // 种子Tracker的IP地址选择 可选：ipv4，ipv6
        'ip_type'		=> 'ipv4',
    ),
    // m-team
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
    // keepfrds
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
    // pter
    'pter'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // tjupt
    'tjupt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // btschool
    'btschool'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // HDSky
    'hdsky'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // TorrentCCF
    'torrentccf'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // PTMSG
    'ptmsg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // totheglory
    'ttg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        // 如果需要rss订阅，必须配置
        'rss'       => '',
    ),
    // nanyangpt
    'nanyangpt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // springsunday.net
    'ssd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // yingk
    'yingk'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdcity
    'hdcity'      => array(
        // 必须配置
        'cookie'        => '',
        // 如果需要自动辅种，必须配置cuhash
        'passkey'       => '',
    ),
    // 52pt.site
    '52pt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // brobits
    'brobits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // beitai
    'beitai'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // eastgame
    'eastgame'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // soulvoice
    'soulvoice'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // chdbits
    'chdbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // leaguehd
    'leaguehd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // ptsbao
    'ptsbao'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdchina
    'hdchina'      => array(
        // 必须配置
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdarea
    'hdarea'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdtime
    'hdtime'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // 1ptba
    '1ptba'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hd4fans
    'hd4fans'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hddisk.life
    'hdbug'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // opencd皇后
    'opencd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdstreet
    'hdstreet'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // joyhd
    'joyhd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // dmhy幼儿园
    'dmhy'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdu
    'upxin'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // oshen
    'oshen'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // discfan港知堂
    'discfan'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdzone
    'hdzone'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // nicept老师
    'nicept'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdbd伊甸园
    'hdbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // byr北邮
    'byr'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // CCFBits
    'ccfbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdbits
    'hdbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // PTPBD
    'ptpbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // HD-T
    'hd-torrents'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // skyeysnow天雪
    'skyeysnow'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // pt.sjtu葡萄
    'pt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdroute
    'hdroute'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // haidan
    'haidan'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hdfans
    'hdfans'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // 配置结束，后面的一行不能删除，必须保留！！！
);
