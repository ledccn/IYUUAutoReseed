## 功能
IYUU自动辅种工具，目前能对国内大部分的PT站点自动辅种；同时，附带的下载模块可订阅各站免费种。支持下载器集群，支持多盘位，支持多下载目录，支持远程连接等。

## 原理
IYUU自动辅种工具（英文名：iyuuAutoReseed），是一款PHP语言编写的Private Tracker辅种脚本，通过计划任务或常驻内存，按指定频率调用transmission、qBittorrent下载软件的API接口，提取正在做种的info_hash提交到服务器API接口，根据API接口返回的数据拼接种子连接，提交给下载器，自动辅种各个站点。

## 优势
 - 全程自动化，无需人工干预；
 - 支持多盘位，多做种目录；
 - 辅种精确度高，精度可配置；
 - 支持微信通知，消息即时达；
 - 自动对合集包，进行拆包辅种（正在开发）
 - 兼容支持TJUPT站大神的Reseed辅种方式（暂未开发）

## 支持的下载器
 1. transmission
 2. qBittorrent

## 支持自动辅种的站点
学校、杜比、家园、天空、朋友、馒头、萌猫、我堡、猫站、铂金家、烧包、北洋、TCCF、南洋、TTG、映客、城市、52pt、brobits、备胎、SSD、CHD、ptmsg、leaguehd、聆音、瓷器、hdarea、eastgame(TLF)、1ptba、hdtime、hd4fans、opencd、hdbug、hdstreet、joyhd、u2、upxin(HDU)、oshen、discfan(GZT)、cnscg圣城。

## 运行环境
所有具备PHP运行环境的所有平台！官方下载的记得开启crul、fileinfo、mbstring，这3个扩展。
例如：Linux、Windows、MacOS
 1. Windows下安装php环境：https://www.php.net/downloads
    

## 下载源码
 - github仓库：https://github.com/ledccn/IYUUAutoReseed
 - 码云仓库：https://gitee.com/ledc/IYUUAutoReseed

## 使用方法
详见Wiki： https://gitee.com/ledc/IYUUAutoReseed/wikis 

## 需求提交/错误反馈
 - 点击链接加入群聊【IYUU自动辅种交流】：[https://jq.qq.com/?_wv=1027&k=5JOfOlM][1]
 - QQ群：859882209
 - issues： https://gitee.com/ledc/IYUUAutoReseed/issues 

## 捐助开发者
如果觉得我的付出，节约了您的宝贵时间，请随意打赏一杯咖啡！或者一杯水！

如果喜欢，请帮忙在[Github](https://github.com/ledccn/IYUUAutoReseed)或[码云](https://gitee.com/ledc/IYUUAutoReseed)给个Star，也可以对IYUUAutoReseed进行[捐赠](https://gitee.com/ledc/IYUUAutoReseed#%E6%8D%90%E5%8A%A9%E5%BC%80%E5%8F%91%E8%80%85)哦 ^_^。

**您所有的打赏将用于服务器续期，增加服务的延续性。**


![微信打赏.png][2]


## 捐赠者列表
感谢以下捐赠者，排名不分先后！

|名字 | 金额 | 时间|
| - | :-: | ---- |
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
| 寒山先生 | ¥100元 | 2020年1月3日20:35 |
| 阿腾 | ¥20元 | 2020年1月3日22:37 |
|  | ¥23.33元 | 2020年1月4日01:38 |

补充说明：

1.  此明细不是为了竞价排名，而是以公开、透明的制度说明所捐赠资源的使用情况和去处； 
2.  所捐赠的资源不属于任何个人，而应作为项目或者开发团队的所需开销； 
3.  如果捐赠了却不希望您的名字出现在这里，可以联系我们进行相应处理；
4.  更新有延时，如未能及时更新，可联系我。



[1]: https://jq.qq.com/?_wv=1027&k=5JOfOlM
[2]: https://www.iyuu.cn/usr/uploads/2019/12/801558607.png