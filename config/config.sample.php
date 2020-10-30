<?php
/**
 * 技术讨论及后续更新，请加入QQ群！！！！！！！
    群名称：IYUU自动辅种交流
    QQ群号：859882209、931954050
 * IYUU自动辅种工具-【安装篇】如何下载最新源码？ https://www.iyuu.cn/archives/338/
 * IYUU自动辅种工具-【安装篇】Windows之git https://www.iyuu.cn/archives/367/
 * IYUU自动辅种工具-【安装篇】群晖Linux之git https://www.iyuu.cn/archives/372/
 * IYUU自动辅种工具-【安装篇】小钢炮手把手教程 https://www.iyuu.cn/archives/386/
 * IYUU自动辅种工具-【安装篇】全平台Docker安装方式 https://www.iyuu.cn/archives/401/
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
    // 有变化才发送通知（辅种成功 + 失败 > 0）
    'notify_on_change' => false,
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
                'username'	=>	'',     // 没有用户名请填写null
                'password'	=>	'',     // 没有密码  请填写null
                'BT_backup' =>  '/torrents',                        // 移动做种：如果脚本与当前客户端不在一台机器，必须配置
                'move'      =>  0,      // 0不移动，1移动并辅种，2移动且只在当前客户端辅种
            ),
            # 结束
            # 开始
            array(
                'type'	=>	'qBittorrent',	// 支持：transmission、qBittorrent
                'host'	=>	'http://127.0.0.1:8083',
                'category' => '', // 辅种任务默认分类
                'add_site_tag' => false,
                'username'	=>	'admin',
                'password'	=>	'',
                'root_folder'=> 1,   // 0不创建根目录，1创建根目录(下载器默认1)
                'BT_backup' =>  '/BT_backup',    // 移动做种：必须配置，Linux搜索方法：find / -name BT_backup
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
            'path_filter'=> array(),          //转移过滤器：不转移此路径内文件
            'path_selector' => array(),       //转移选择器：只转移此路径内文件(为空时，全转移)    【优先级：过滤器 ＞ 选择器】
            'paused'         => 1,      //转移成功，自动开始任务：0开始，1暂停
            'skip_check'     => 0,      //转移成功，跳校验：0不跳、1跳校验
            'delete_torrent' => 0,      //转移成功，删除当前做种：0不删除、1删除
        ),
        // 6.RSS工作模式
        'workingMode'	=> 0,
        // 7.监控目录
        'watch'         => '/volume1/watch',
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
    // 【合作站点用户鉴权】ourbits
    'ourbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(
            //'ipv6=1',   // 种子Tracker的IP地址选择 可选：ipv4，ipv6
            //'https=1',
        ),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 15,      // 最少休眠15秒
        ),
        'workingMode'	=> 0,
        'watch'         => '/root/downloads',
        'filter' => array(
            'size'=>array(
                'min'	=>	'1GB',
                'max'	=>	'280GB',
            ),
        ),

    ),
    // 【合作站点用户鉴权】hddolby
    'hddolby'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 5,      // 最少休眠5秒
        ),
    ),
    // 【合作站点用户鉴权】hdhome
    'hdhome'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 5,      // 最少休眠5秒
        ),
    ),
    // 【合作站点用户鉴权】PTHome
    'pthome'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 5,      // 最少休眠5秒
        ),
    ),
    // 【合作站点用户鉴权】chdbits
    'chdbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // MoeCat
    'moecat'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'id' => 0,					// 用户ID(不是用户名)
        'url_replace' => array(),
        'url_join' => array(
            //'ipv6=1',   // 种子Tracker的IP地址选择 可选：ipv4，ipv6
            'https=1',
        ),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 15,      // 最少休眠15秒
        ),
    ),
    // m-team
    'm-team'      => array(
        // 14.m-team的cookie	如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => 'tp=',
        // 15.m-team的passkey	【必须配置】
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(
            //'ipv6=1',   // 种子Tracker的IP地址选择 可选：ipv4，ipv6
            'https=1',
        ),
    ),
    // keepfrds
    'keepfrds'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
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
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // tjupt
    'tjupt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // btschool
    'btschool'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // HDSky
    'hdsky'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 20,      // 最少休眠20秒
        ),
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // TorrentCCF
    'torrentccf'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // PTMSG
    'ptmsg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // totheglory
    'ttg'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        // 如果需要rss订阅，必须配置
        'rss'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // nanyangpt
    'nanyangpt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // springsunday.net
    'ssd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 15,      // 最少休眠15秒
        ),
    ),
    // yingk
    'yingk'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdcity
    'hdcity'      => array(
        // 必须配置
        'cookie'        => '',
        // 如果需要自动辅种，必须配置cuhash
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // 52pt.site
    '52pt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // brobits
    'brobits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // beitai
    'beitai'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // eastgame
    'eastgame'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // soulvoice
    'soulvoice'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // leaguehd
    'leaguehd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // ptsbao
    'ptsbao'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdchina
    'hdchina'      => array(
        // 必须配置
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 10,      // 每次辅种10个
            'sleep' => 5,      // 最少休眠15秒
        ),
    ),
    // hdarea
    'hdarea'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdtime
    'hdtime'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // 1ptba
    '1ptba'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hd4fans
    'hd4fans'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hddisk.life
    'hdbug'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // opencd皇后
    'opencd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdstreet
    'hdstreet'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // joyhd
    'joyhd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // dmhy幼儿园
    'dmhy'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdu
    'upxin'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // oshen
    'oshen'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // discfan港知堂
    'discfan'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdzone
    'hdzone'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // nicept老师
    'nicept'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdbd伊甸园
    'hdbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(
            //'ipv6=1',   // 种子Tracker的IP地址选择 可选：ipv4，ipv6
            //'https=1',
        ),
    ),
    // byr北邮
    'byr'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // CCFBits
    'ccfbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdbits
    'hdbits'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // PTPBD
    'ptpbd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // HD-T
    'hd-torrents'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // skyeysnow天雪
    'skyeysnow'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // pt.sjtu葡萄
    'pt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
        'limitRule' => array(
            'count' => 20,      // 每次辅种20个
            'sleep' => 20,      // 最少休眠20秒
        ),
    ),
    // hdroute
    'hdroute'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // haidan
    'haidan'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // hdfans
    'hdfans'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // dragonhd
    'dragonhd'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
    ),
    // hitpt 百川
    'hitpt'      => array(
        // 如果需要用下载免费种脚本，须配置（只是自动辅种，可以不配置此项）
        'cookie'        => '',
        // 如果需要自动辅种，必须配置
        'passkey'       => '',
        'url_replace' => array(),
        'url_join' => array(),
    ),
    // 配置结束，后面的一行不能删除，必须保留！！！
);
