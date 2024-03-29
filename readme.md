## 免责声明

在使用本工具前，请认真阅读《免责声明》全文如下：

使用IYUUAutoReseed自动辅种工具本身是非常安全的，IYUU脚本辅种时不会跟PT站点的服务器产生任何交互，只是会把下载种子链接推送给下载器，由下载器去站点下载种子。理论上，任何站点、任何技术都无法检测你是否使用了IYUUAutoReseed。危险来自于包括但不限于以下几点：

第一：建议不要自己手动跳校验，任何因为跳校验ban号，别怪我没提醒，出事后请不要怪到IYUU的头上；

第二：官方首发资源、其他一切首发资源的种子，IYUUAutoReseed自动辅种工具也无法在出种前辅种，如果因为你个人的作弊而被ban号，跟IYUU无关；

第三：您使用IYUU工具造成的一切损失，与IYUU无关。如不接受此条款，请不要使用IYUUAutoReseed，并立刻删除已经下载的源码。

![stars](https://img.shields.io/github/stars/ledccn/IYUUAutoReseed)![forks](https://img.shields.io/github/forks/ledccn/IYUUAutoReseed)![release](https://img.shields.io/github/release/ledccn/IYUUAutoReseed.svg)

## 获取脚本，四种方式皆可

1. 通过git命令安装

    `git clone https://github.com/ledccn/IYUUAutoReseed.git`

2. 通过composer命令安装

    `composer create-project ledccn/iyuuautoreseed:dev-master`

3. 直接下载zip源码包

    `https://github.com/ledccn/IYUUAutoReseed/archive/master.zip`
    
4. Docker使用

     [https://gitee.com/ledc/IYUUAutoReseed/tree/master/docker](https://gitee.com/ledc/IYUUAutoReseed/tree/master/docker)

## 功能

IYUU自动辅种工具，功能分为两大块：自动辅种、自动转移。

- 自动辅种：目前能对国内大部分的PT站点自动辅种，支持下载器集群，支持多盘位，支持多下载目录，支持远程连接等；

- 自动转移：可以实现各下载器之间自动转移做种客户端，让下载器各司其职（专职的保种、专职的下载）。

## 原理
IYUU自动辅种工具（英文名：IYUUAutoReseed），是一款PHP语言编写的Private Tracker辅种脚本，通过计划任务或常驻内存，按指定频率调用transmission、qBittorrent下载软件的API接口，提取正在做种的info_hash提交到辅种服务器API接口（辅种过程和PT站没有任何交互），根据API接口返回的数据拼接种子连接，提交给下载器，自动辅种各个站点。

## 优势
 - 全程自动化，无需人工干预；
 - 支持多盘位，多做种目录，多下载器，支持远程下载器；
 - 辅种精确度高，精度可配置；
 - 支持微信通知，消息即时达；
 - 自动对合集包，进行拆包辅种（暂未开发）
 - 安全：所有隐私信息只在本地存储，绝不发送给第三方。
 - 拥有专业的问答社区和交流群

## 支持的下载器
 1. transmission
 2. qBittorrent

## 支持自动辅种的站点
学校、杜比、家园、天空、朋友、馒头、萌猫、我堡、猫站、铂金家、烧包、北洋、TCCF、南洋、TTG、映客、城市、52pt、brobits、备胎、SSD、CHD、ptmsg、leaguehd、聆音、瓷器、hdarea、eastgame(TLF)、1ptba、hdtime、hd4fans、opencd、hdbug、hdstreet、joyhd、u2、upxin(HDU)、oshen、discfan(GZT)、cnscg圣城(已删除)、北邮、CCFBits、dicmusic、天雪、葡萄、HDRoute、伊甸园hdbd、海胆haidan、HDfans、龙之家、百川PT、HDAI、蒲公英、阿童木、蝴蝶。

## 运行环境
具备PHP运行环境的所有平台，例如：Linux、Windows、MacOS！

官方下载的记得开启curl、json、mbstring，这3个扩展。

 1. Windows安装php环境：https://www.php.net/downloads
    

## 下载源码
 - github仓库：https://github.com/ledccn/IYUUAutoReseed
 - 码云仓库：https://gitee.com/ledc/IYUUAutoReseed


## 使用方法
- 详见Wiki：https://gitee.com/ledc/IYUUAutoReseed/tree/master/wiki
- 博客：https://www.iyuu.cn/

## 接口开发文档
如果您懂得其他语言的开发，可以基于接口做成任何您喜欢的样子，比如手机APP，二进制包，Windows的GUI程序，浏览器插件等。欢迎分享您的作品！

实时更新的接口文档：http://api.iyuu.cn/docs.php

## 相关项目


| 项目名| 简介|
| - | --- |
| [IYUU GUI](https://github.com/Rhilip/IYUU-GUI) | 这是一个基于IYUU提供的API，产生一个可视化操作项目。目的是为了降低直接上手PHP版IYUUAutoReseed的困难。 |
| [IYUU-Fly](https://github.com/PlexPt/iyuu-fly) | 带GUI的iyuu自动辅种程序。 |
| [goreseed](https://github.com/gaoluhua99/goreseed) | golang编写调用IYUU接口的CLI辅种程序。 |
| [IYUUAutoReseed-web](https://github.com/goveeta/IYUUAutoReseed-web) |   |
| [AutoPT](https://github.com/lyssssssss/AutoPT) | 此程序用于自动下载PT免费种子，并自动辅种和一体化管理。开发目的为了释放双手，专注观影！  |
| [flexget_qbittorrent_mod](https://github.com/IvonWei/flexget_qbittorrent_mod) | Flexget qBittorrent插件，实现全自动化辅种，删除种，免费种筛选，签到等。|


## 需求提交/错误反馈

 - QQ群：859882209[2000人群]，931954050[1000人群],924099912[2000人群]
 - 问答社区：http://wenda.iyuu.cn
 - 博客：https://www.iyuu.cn/
 - issues： https://gitee.com/ledc/IYUUAutoReseed/issues 

## 捐助开发者
如果觉得我的付出，节约了您的宝贵时间，请随意打赏一杯咖啡！或者一杯水！

如果喜欢，请帮忙在[Github](https://github.com/ledccn/IYUUAutoReseed)或[码云](https://gitee.com/ledc/IYUUAutoReseed)给个Star，也可以对IYUUAutoReseed进行[捐赠](https://gitee.com/ledc/IYUUAutoReseed#%E6%8D%90%E5%8A%A9%E5%BC%80%E5%8F%91%E8%80%85)哦 ^_^。

**您所有的打赏将用于服务器维护及续期，增加服务的延续性。**



## 捐赠者列表
感谢以下捐赠者，排名不分先后！

|名字 | 金额 | 时间|
| - | :-: | ---- |
| 祭 | ¥6元 | 2019年12月10日18:02 |
| 未署名 | ¥88.88元 | 2019年12月16日20:38 |
| 当下丶 [阿里云1H2G VPS]2021.9.17 | ¥1300元 | 2019年12月16日16:00 |
| xzs | ¥20元 | 2019年12月24日11:29 |
| loveB杉 | ¥20元 | 2019年12月24日20:59 |
| 风少 | ¥20元 | 2019年12月24日23:30 |
| 小夏 | ¥1元 | 2019年12月25日11:38 |
| 优つ伤 | ¥50元 | 2019年12月25日19:21 |
| Nice | ¥20元 | 2019年12月27日12:54 |
| 木腕清（天才） | ¥10元 | 2019年12月28日11:26 |
| @希望功能越来越多 | ¥20元 | 2019年12月28日17:29 |
| 竹节香附 | ¥20元 | 2019年12月28日18:21 |
| 李元芳 | ¥6.66 | 2019年12月30日16:19 |
| Ge(附言:client0修正) | ¥20元 | 2019年12月31日12:02 |
| 怪叔叔 | ¥20元 | 2019年12月31日15:46 |
| Shaopeng | ¥10元 | 2020年1月1日18:57 |
| III（感谢大佬的软件） | ¥10元 | 2020年1月1日22:34 |
| 子不语 | ¥10元 | 2020年1月3日13:31 |
| 寒山先生 | ¥100元 | 2020年1月3日20:35 |
| 阿腾 | ¥20元 | 2020年1月3日22:37 |
| 手动滑稽 | ¥23.33元 | 2020年1月4日01:38 |
| 凭樊 | ¥5元 | 2020年1月4日17:58 |
| Mocar | ¥10元 | 2020年1月4日20:03 |
| Throne | ¥10元 | 2020年1月4日20:09 |
| JeSsiE杰西 | ¥200元 | 2020年1月5日09:48 |
| 人生五十载 | ¥30元 | 2020年1月5日12:29 |
| C陈奕轰隆隆 | ¥20元 | 2020年1月5日15:55 |
| 寒山先生 | ¥100元 | 2020年1月6日12:17 |
| 244574970 | ¥20元 | 2020年1月6日16:18 |
| Shaopeng | ¥10元 | 2020年1月6日22:01 |
| 轲 | ¥387元 | 2020年1月7日20:34 |
| 纸鸢 | ¥2元 | 2020年1月9日11:45 |
| 寒山先生 | ¥100元 | 2020年1月9日11:51 |
| 王浩淼 | ¥50元 | 2020年1月9日11:53 |
| 寒山先生 | ¥100元 | 2020年1月11日11:47 |
| 天空(感谢远程指导小意思) | ¥20元 | 2020年1月14日23:11 |
| 寒山先生 | ¥200元 | 2020年1月18日12:37 |
| 小城流水 | ¥5元 | 2020年1月22日22:14 |
| 国旗(未署名) | ¥8.8元 | 2020年1月22日23:28 |
| Alpha | ¥10.81元 | 2020年1月24日20:23 |
| 羽生 | ¥88.88元 | 2020年1月24日21:06 |
| 当下丶 | ¥100元 | 2020年1月28日1:45 |
| 陈君政 | ¥10元 | 2020年2月3日11:32 |
| 不寐夜游 | ¥10元 | 2020年2月8日17:17 |
| Jack | ¥10元 | 2020年2月13日08:05 |
| 陈伟平 | ¥28.88元 | 2020年2月13日12:35 |
| PhalApi Pro商业授权 | ¥-950元 | 2020年2月14日21:56 |
| jonnaszheng | ¥10元 | 2020年2月15日10:25 |
| weekend（sd54zdk） | ¥10元 | 2020年2月17日14:31 |
| 寒山先生 | ¥200元 | 2020年2月17日17:00 |
| PLC组态远程服务 | ¥8.88元 | 2020年2月18日02:14 |
| JeSsiE杰西 | ¥66元 | 2020年2月20日19:38 |
| 黄叶梓（炮王） | ¥10元 | 2020年2月20日21:10 |
| 里奥龙 | ¥88.8元 | 2020年2月20日21:48 |
| 寒山先生 | ¥200元 | 2020年2月21日17:32 |
| 李永超 | ¥10元 | 2020年2月22日16:24 |
| Always | ¥5元 | 2020年2月22日21:31 |
| 车站 | ¥30元 | 2020年2月22日21:32 |
| 寒山先生 | ¥200元 | 2020年2月23日22:21 |
| 莫凡 | ¥10元 | 2020年2月24日19:43 |
| 未署名 | ¥200元 | 2020年2月25日14:36 |
| 锦年 | ¥6.66元 | 2020年2月25日19:00 |
| 金力 | ¥10元 | 2020年2月26日22:45 |
| 飞翔鱼 | ¥100元 | 2020年2月24日17:58 |
| 团 | ¥1元 | 2020年2月29日1:12 |
| 沙鸥 | ¥10元 | 2020年2月29日17:03 |
| lsy | ¥229.5元 | 2020年3月1日15:15 |
| 慧宇 | ¥30元 | 2020年3月3日16:39 |
| sz贺贺 | ¥100元 | 2020年3月7日14:40 |
| 一介凡人 | ¥8.88元 | 2020年3月9日22:34 |
|  |  |  |


补充说明：

1.  此明细不是为了竞价排名，而是以公开、透明的制度说明所捐赠资源的使用情况和去处； 
2.  所捐赠的资源不属于任何个人，而应作为项目或者开发团队的所需开销； 
3.  如果捐赠了却不希望您的名字出现在这里，可以联系我们进行相应处理；
4.  更新有延时，如未能及时更新，可联系我。
