#!/bin/sh
curl -o ./IYUUAutoReseed.zip http://api.iyuu.cn/IYUUAutoReseed.zip
unzip -o ./IYUUAutoReseed.zip -d /root
rm ./IYUUAutoReseed.zip
docker build -t iyuu:latest .
docker run -it -v /root/IYUUAutoReseed:/var/www -p 8510:9000 --network bridge --name IYUUAutoReseed --restart always -d iyuu:latest