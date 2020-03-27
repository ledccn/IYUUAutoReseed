# IYUUAutoReseed自动辅种docker安装教程

### 教程地址：https://www.iyuu.cn/archives/386/

### 命令行安装步骤：

```sh
cd /root
git clone https://gitee.com/ledc/IYUUAutoReseed.git
cd /root/IYUUAutoReseed/docker
chmod +x ./*.sh
./build.sh
./iyuu.sh
```



### 命令解释：

第一步：进入`/root`目录内；

第二步：通过git命令，拉取最新辅种脚本代码；

第三步：进入`/root/IYUUAutoReseed/docker`目录

第四步：给予`build.sh`、`iyuu.sh`可执行权限；

第五步：编译镜像并运行容器，请耐心等待完成；

第六步：测试IYUU辅种脚本是否正常运行。

第七步：测试是否安装完成，命令为：`./iyuu.sh`

做完以上6步，确认没问题后，然后看教程：https://www.iyuu.cn/archives/324/，来编辑配置即可。

#### 必读：脚本会在`/root`目录，创建`IYUUAutoReseed`文件夹，您只需要按照上述教程编辑好配置，放到`/root/IYUUAutoReseed/config/config.php`



### 辅种时执行的命令：`iyuu.sh`



## 如何定时辅种？

把`iyuu.sh`加入Linux计划任务内。



## 小钢炮qBittorrent连接失败？

v4.1.5无法连接，请安装灯大高版本的qbittorrent，做种列表不丢失且不用校验。

```sh
IMAGE_NAME=80x86/qbittorrent
WEB_PORT=8083
DOWNLOAD_PATH=$(cat /var/lib/qbittorrent/.config/qBittorrent/qBittorrent.conf | grep -i 'Downloads\\SavePath' | cut -d'=' -f2)
BT_PORT=8999
QBT_AUTH_SERVER_ADDR=$(ip -4 addr show docker0 | grep inet | awk '{print $2}' | cut -d'/' -f1)
docker run -d --name qbittorrent \
        -e PUID=$(id -u qbittorrent) \
        -e PGID=$(cat /etc/group | grep -e '^users' | cut -d':' -f3) \
        -e WEB_PORT=$WEB_PORT \
        -e BT_PORT=$BT_PORT \
        -e QBT_AUTH_SERVER_ADDR=$QBT_AUTH_SERVER_ADDR \
        --restart unless-stopped \
        -p $WEB_PORT:$WEB_PORT -p $BT_PORT:$BT_PORT/tcp -p $BT_PORT:$BT_PORT/udp \
        -v /var/lib/qbittorrent/.config/qBittorrent:/config \
        -v /var/lib/qbittorrent/.local/share/data/qBittorrent:/data \
        -v "$DOWNLOAD_PATH":/downloads \
        --mount type=tmpfs,destination=/tmp \
        ${IMAGE_NAME}
```

