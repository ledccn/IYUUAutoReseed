#!/bin/sh
cd /root
git clone https://gitee.com/ledc/IYUUAutoReseed.git
cd /root/IYUUAutoReseed/docker
chmod +x ./*.sh
docker build -t iyuu:latest .
docker run -it -v /root/IYUUAutoReseed:/var/www -v /var/lib/qbittorrent/.local/share/data/qBittorrent/BT_backup:/BT_backup -p 8510:9000 --network bridge --name IYUUAutoReseed --restart always -d iyuu:latest
./iyuu.sh