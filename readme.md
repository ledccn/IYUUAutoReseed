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

## 运行环境
所有具备PHP运行环境的所有平台！
例如：Linux、Windows、MacOS

## 下载源码
 - 码云仓库：https://gitee.com/ledc/IYUUAutoReseed

## 使用方法
详见Wiki

## 需求提交/错误反馈
 - 点击链接加入群聊【IYUU自动辅种交流】：[https://jq.qq.com/?_wv=1027&k=5JOfOlM][1]

## 捐助开发者
如果觉得我的付出，节约了您的宝贵时间，请随意打赏一杯咖啡！或者一杯水！
您所有的打赏将用于服务器续期，增加服务的延续性。


![微信打赏.png][2]


  [1]: https://jq.qq.com/?_wv=1027&k=5JOfOlM
  [2]: https://www.iyuu.cn/usr/uploads/2019/12/801558607.png