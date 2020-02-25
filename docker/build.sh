#!/bin/sh
wget -c https://gitee.com/ledc/IYUUAutoReseed/repository/archive/master.zip -O IYUUAutoReseed.zip
wget -c http://api.iyuu.cn/uploads/vendor.zip -O vendor.zip
unzip -o ./IYUUAutoReseed.zip -d /root
unzip -o ./vendor.zip -d /root/IYUUAutoReseed
rm ./IYUUAutoReseed.zip
rm ./vendor.zip
docker build -t iyuu:latest .
docker run -it -v /root/IYUUAutoReseed:/var/www -p 8510:9000 --network bridge --name IYUUAutoReseed --restart always -d iyuu:latest