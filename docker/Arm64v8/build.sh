#!/bin/sh
docker build -f Dockerfile -t iyuu:arm64v8 .
docker run -it -v /root/config.php:/config.php --network bridge --name IYUUAutoReseed --restart always -d iyuu:arm64v8
docker exec -it IYUUAutoReseed php iyuu.php