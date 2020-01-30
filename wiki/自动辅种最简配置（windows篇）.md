以下教程以windows为基础进行讲解，通用威联通、群晖、铁威马等Linux系统。
博客链接：https://www.iyuu.cn/archives/324/
## 第一步 下载压缩包
从[码云仓库][1]，下载最新源码，解压缩到D盘的根目录下。

## 第二步 复制一份配置文件
打开`D:\IYUUAutoReseed\config`目录，复制一份`config.sample.php`，另存为`config.php`。

这样操作后，需要升级新版本时，直接覆盖即可，不会影响到配置。

## 第三步 编辑配置文件
提醒：千万不要用windows记事本来编辑配置文件（会导致乱码）！！
推荐编辑软件：`VS code`、`EditPlus`、`SublimeText`、`Notepad++`等（保存格式，选UTF8 无BOM）；
配置文件内容较多，新手往往很迷茫，不知道改哪里，在这里我重点强调2个步骤：
`1.编辑全局客户端； 2.编辑各站的秘钥，即passkey。`

其他配置，如果不懂也没有关系；先保持默认，等脚本运行起来，再修改也不迟。另外，修改时一定要细心，仔细看教程。
打开`D:\IYUUAutoReseed\config\config.php`文件，如下图：
![编辑配置1.png][2]

### 填写全局客户端
上图红框内的是`transmission`的示例配置，绿框是`qBittorrent`的示例配置；
IYUU自动辅种工具，目前支持这两种下载器，支持多盘位，辅种时全自动对应资源的下载目录。
1，编辑`transmission`下载器
`http://127.0.0.1:9091/transmission/rpc`是下载器的连接参数，你要修改的部分是`127.0.0.1:9091`改成你的IP与端口（本机使用无需修改），局域网内的机器请填写局域网IP与端口；远程使用请填写DDNS的远程连接域名与端口。
username是用户名、password是密码。
如果你没有用到`transmission`下载器，请把红框的内容都删除。

2，编辑`qBittorrent`下载器
方法与上一步相同，只需填写ip、端口、用户名、密码即可。如果您是windows下的qBittorrent，请参考下图打开`WEB用户界面`：
![qb设置WEB用户界面.png][3]

因为我两个下载器都在用，编辑好后，如图：
![编辑配置2.png][4]

### 填写各站秘钥passkey
IYUU自动辅种：需要您配置各站的passkey（没有配置passkey的站点会自动跳过）。
从各站点的控制面板，找到您的`秘钥`复制粘贴过来即可。
配置好后如图：
![编辑配置3.png][5]

----------


## 第四步，重点讲解Ourbits站点的配置
IYUU自动辅种工具、Ourbits双方达成合作，可以对使用接口的用户，实现认证。
### 申请爱语飞飞微信通知token，新用户访问：http://iyuu.cn 申请！
1.点击`开始使用`，出现二维码，用`微信扫码`
![微信通知1.png][6]
![微信通知2.png][7]
![微信通知3.png][8]
2.复制您的token令牌到`/config/config.php`文件内的`iyuu.cn`对应的配置字段，保存。如图：
![微信通知4.png][9]

### 设置Ourbits：
![编辑配置4.png][10]
`passkey`，在你的控制面板 - 密钥
`is_vip`，根据你的实际情况填写，因站点有下载种子的流控，如果你不在限制之列，可以`设置为1`
`id`，为用户中心打开后，浏览器地址栏**http://xxxxx.xxx/userdetails.php?id=`46880`**等号=后面的几个数字，如图：
![编辑配置6.png][11]

到此，配置文件编辑完毕，请记得保存。
如果提示保存格式，请保存为UTF8（无BOM）格式。

------

## 群晖、铁威马、威联通等Linux环境

经过上面步骤，其实已经完成了配置，只需要把脚本复制到设备内，用php命令运行脚本即可。

群晖php命令：`php`

威联通php命令：`/mnt/ext/opt/apache/bin/php`

铁威马php命令：`php`

----------

## Windows安装PHP运行环境
也可以去官方下载【https://www.php.net/downloads】，官方下载的记得开启`curl、fileinfo、mbstring`，这3个扩展。
另外我打包了一份，下载地址：
微云链接：https://share.weiyun.com/5I13dek 密码：utcjsx
下载回来是一个ZIP压缩包，解压到`D:\IYUUAutoReseed\`目录内，文件结构如图：
![编辑配置7.png][12]
点击红框内`执行辅种`即可。
如果你前期严格按照配置一步步操作，这里会正常显示跑动的辅种列表。正常如图：
![编辑配置8.png][13]


[1]: https://gitee.com/ledc/IYUUAutoReseed
[2]: https://www.iyuu.cn/usr/uploads/2019/12/2720183833.png
[3]: https://www.iyuu.cn/usr/uploads/2019/12/405587689.png
[4]: https://www.iyuu.cn/usr/uploads/2019/12/441257656.png
[5]: https://www.iyuu.cn/usr/uploads/2019/12/890327305.png
[6]: https://www.iyuu.cn/usr/uploads/2019/12/2331433923.png
[7]: https://www.iyuu.cn/usr/uploads/2019/12/3324442680.png
[8]: https://www.iyuu.cn/usr/uploads/2019/12/3181272964.png
[9]: https://www.iyuu.cn/usr/uploads/2019/12/3669828008.png
[10]: https://www.iyuu.cn/usr/uploads/2019/12/3696916642.png
[11]: https://www.iyuu.cn/usr/uploads/2019/12/1230288911.png
[12]: https://www.iyuu.cn/usr/uploads/2019/12/3189986236.png
[13]: https://www.iyuu.cn/usr/uploads/2019/12/2523845772.png