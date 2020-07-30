#!/bin/sh
docker build -t iyuu:arm64v8 .
docker run -it -v /root/config.php:/config.php -v /var/lib/transmission/torrents:/torrents -v /var/lib/qbittorrent/.local/share/data/qBittorrent/BT_backup:/BT_backup --network bridge --name IYUUAutoReseed --restart always -d iyuu:arm64v8
docker exec -it IYUUAutoReseed php iyuu.php