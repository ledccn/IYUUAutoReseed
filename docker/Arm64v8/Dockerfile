#FROM alpine:latest
FROM arm64v8/alpine

RUN set -ex \
    && sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/' /etc/apk/repositories \
    && apk --update add --no-cache tzdata php7 php7-curl php7-json php7-mbstring git \
    && git clone https://gitee.com/ledc/IYUUAutoReseed.git /IYUU \
    && cp /IYUU/config/config.sample.php /IYUU/config/config.php \
    && ln -sf /IYUU/config/config.php /config.php 

ENV TZ Asia/Shanghai
ENV cron="0 9 * * 0"

WORKDIR /IYUU

CMD ["sh", "-c", "/usr/bin/php /IYUU/iyuu.php ; /usr/sbin/crond ; (crontab -l ;echo \"$cron /usr/bin/php /IYUU/iyuu.php &> /dev/null\") | crontab - ; tail -f /dev/null"]
