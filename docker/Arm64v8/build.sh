#!/bin/sh
docker build -t iyuu:latest .
docker run -it -v /root/config.php:/config.php -v /var/lib/transmission/torrents:/torrents -v /var/lib/qbittorrent/.local/share/data/qBittorrent/BT_backup:/BT_backup --network bridge --name IYUUAutoReseed --restart always -d iyuu:latest
docker exec -it IYUUAutoReseed php iyuu.php