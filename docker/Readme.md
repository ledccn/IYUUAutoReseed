# 使用方法：
### 1.拉取镜像、创建容器，运行

#### ARM平台通用方法

```
docker run -d \
--name IYUUAutoReseed \
-e cron='0 9 * * 0' \
-v /root/config.php:/config.php \
--restart=always \
iyuucn/iyuuautoreseed:arm64v8
```
#### 小钢炮方法：

```
docker run  -d \
--name IYUUAutoReseed \
-e cron='0 8 * * 0' \
-v /root/config.php:/config.php \
-v /var/lib/transmission/torrents:/torrents \
-v /var/lib/qbittorrent/.local/share/data/qBittorrent/BT_backup:/BT_backup \
--restart always \
iyuucn/iyuuautoreseed:arm64v8
```

#### AMD64平台（MAC OS、台式、服务器、NAS等）

```
docker run -d \
--name IYUUAutoReseed \
-e cron='0 9 * * 0' \
-v /root/config.php:/config.php \
--restart=always \
iyuucn/iyuuautoreseed:latest
```


**命令解释**

| 参数        | 解释                                                         |
| ----------- | ------------------------------------------------------------ |
| `--name`    | 容器名字                                                     |
| `-e`        | 环境变量，定时任务执行时间                                   |
| `-v`        | 本地目录或文件:容器目录文件，资源挂载到容器。<br />请把你的配置文件放在/root/config.php，会把你的配置映射进容器内。 |
| `--restart` | 启动模式                                                     |

------


### 2.停止

```
docker stop IYUUAutoReseed
```


### 3.运行

```
docker start IYUUAutoReseed
```

### 4.删除容器
```
docker rm IYUUAutoReseed
```

### 5.删除镜像
```
docker rmi iyuucn/iyuuautoreseed:arm64v8
```



------



#### 功能

IYUU自动辅种工具，功能分为两大块：自动辅种、自动转移。

- 自动辅种：目前能对国内大部分的PT站点自动辅种，支持下载器集群，支持多盘位，支持多下载目录，支持远程连接等；

- 自动转移：可以实现各下载器之间自动转移做种客户端，让下载器各司其职（专职的保种、专职的下载）。

#### 原理

IYUU自动辅种工具（英文名：IYUUAutoReseed），是一款PHP语言编写的Private Tracker辅种脚本，通过计划任务或常驻内存，按指定频率调用transmission、qBittorrent下载软件的API接口，提取正在做种的info_hash提交到辅种服务器API接口（辅种过程和PT站没有任何交互），根据API接口返回的数据拼接种子连接，提交给下载器，自动辅种各个站点。

#### 优势

 - 全程自动化，无需人工干预；
 - 支持多盘位，多做种目录，多下载器，支持远程下载器；
 - 辅种精确度高，精度可配置；
 - 支持微信通知，消息即时达；
 - 自动对合集包，进行拆包辅种（暂未开发）
 - 安全：所有隐私信息只在本地存储，绝不发送给第三方。
 - 拥有专业的问答社区和交流群

#### 支持的下载器

  1. transmission
  2. qBittorrent

#### 运行环境

具备PHP运行环境的所有平台，例如：Linux、Windows、MacOS！

官方下载的记得开启curl、json、mbstring，这3个扩展。

  1. Windows安装php环境：https://www.php.net/downloads

#### 源码仓库

 - github仓库：https://github.com/ledccn/IYUUAutoReseed
 - 码云仓库：https://gitee.com/ledc/IYUUAutoReseed


#### 使用方法

- 博客：https://www.iyuu.cn/

#### 接口开发文档

如果您懂得其他语言的开发，可以基于接口做成任何您喜欢的样子，比如手机APP，二进制包，Windows的GUI程序，浏览器插件等。欢迎分享您的作品！

实时更新的接口文档：http://api.iyuu.cn/docs.php


#### 需求提交/错误反馈

 - QQ群：859882209[2000人.入门群]，931954050[1000人.进阶群]
 - 问答社区：http://wenda.iyuu.cn
 - 博客：https://www.iyuu.cn/
 - issues： https://gitee.com/ledc/IYUUAutoReseed/issues 