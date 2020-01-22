IYUU新推出RSS订阅功能！
## 功能
自动订阅站点的新种，自动添加下载任务，支持大小过滤。

## 优势
1.弥补部分下载器没有RSS订阅的缺陷；
2.与下载器本身的RSS功能相比：IYUU自动RSS订阅，支持远程连接下载器，支持下载器多盘位、支持多目录，支持筛选，支持下载器集群；

## 需要配置什么？
1.必须配置各站的`passkey`秘钥，从各站点的`控制面板`复制到配置文件内对应站点`passkey`处即可；
2.TTG的密钥比较特殊，请从RSS链接处复制，并给站点配置加一个`rss`配置项（详情参考示例配置文件TTG配置）。
前期如果你正确使用了`IYUUAutoReseed自动辅种工具`的话，本部分就非常简单了。

----------


## 如何配置`workingMode`工作模式、`watch`监控目录、`cliects`下载器？
### 第一步完善全局配置
```php
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
            ),
            # 结束
            # 开始
            array(
                'type'	=>	'qBittorrent',	// 支持：transmission、qBittorrent
                'host'	=>	'http://www.baidu.com:8083',
                'username'	=>	'',
                'password'	=>	'',
            ),
            # 结束
            // 全局客户端设置 结束
        ),
        'move' =>array(
            'type' => 2,		// 0保持不变，1减，2加， 3直接替换
            'path' =>array(
                '/sda1' => '/volume1',
            ),
        ),
        'workingMode'	=> 0,
        'watch'         => '/volume1/downloads',
        'filter' => array(
            'size'=>array(
                'min'	=>	'10GB',
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
```

----------


### 第二步，完善站点配置（示例配置：m-team）
本部分以馒头为例，讲解`工作模式1：负载均衡`
关键地方：`clients`、`workingMode`、`watch`、`filter`
 - 全局已经配置clients两个用来辅种，站点单独配置clients一个用来下载，在RSS订阅添加下载任务时，**以站点单独配置的clients为准**。
 - `workingMode=>1,` 代表当前站点将会工作在模式1；
 - `watch=>''` 因为站点工作在模式1，配置了也无效，**留空即可**；
 - 全局和站点都配置filter，该站点RSS订阅添加下载任务时，**过滤规则以站点配置为准**；
下面是m-team的示例配置代码。
```php
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
```

----------


### 第三步，完善站点配置（示例配置：keepfrds）
本部分以朋友为例，讲解工作模式1：负载均衡
关键地方：`clients`、`workingMode`、`watch`、`filter`
 - 全局已经配置clients两个用来辅种，**站点没有单独配置clients，在RSS订阅添加下载任务时，以全局配置的clients为准**。
 - `workingMode=>1,` 代表当前站点将会工作在模式1；
 - `watch=>''` 因为站点工作在模式1，配置了也无效，**留空即可**；
 - 全局和站点都配置filter，该站点RSS订阅添加下载任务时，**过滤规则以站点配置为准**；
下面是keepfrds的示例配置代码。
```php
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
```

----------


### 第四步，完善站点配置（示例配置：ourbits）
本部分以我堡为例，讲解工作模式0：watch监控目录
关键地方：`workingMode`、`watch`、`filter`
 - `workingMode=>0,` 代表当前站点将会工作在模式0：**脚本会往指定的watch目录内下载种子**，由下载器添加下载任务；
 - `watch=>'/root/downloads'` 脚本会往`/root/downloads`目录内下载种子，由下载器添加下载任务；
 - 全局和站点都配置filter，该站点RSS订阅添加下载任务时，**过滤规则以站点配置为准**；
下面是ourbits的示例配置代码。
```php
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
```

**总结：以上概况讲解了RSS订阅下载、下载免费种时的各种配置的情况，请仔细阅读务必理解！**