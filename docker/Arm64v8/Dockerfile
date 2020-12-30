# FROM arm64v8/alpine
# FROM arm64v8/alpine:latest
FROM arm64v8/alpine:3.12

ENV TZ Asia/Shanghai

ENV cron="3 */10 * * *"

RUN set -ex \
    && sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/' /etc/apk/repositories \
   # && sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.cn/' /etc/apk/repositories \
    && apk update \
    && apk add --no-cache \
    tzdata \
    php7 php7-curl php7-json php7-mbstring php7-dom php7-simplexml php7-xml php7-zip \
    git \
    && git clone https://gitee.com/ledc/IYUUAutoReseed.git /IYUU \
    && cp /IYUU/config/config.sample.php /IYUU/config/config.php \
    && ln -sf /IYUU/config/config.php /config.php \
    && cp /IYUU/docker/entrypoint.sh /entrypoint.sh \
    && chmod +x /entrypoint.sh \
    && apk del --purge *-dev \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo "${TZ}" > /etc/timezone \
    && ln -sf /usr/share/zoneinfo/${TZ} /etc/localtime \
    # && echo '* * * * * echo "iyuu.cn" >/dev/null 2>&1' >>  /etc/crontabs/root \
    && echo '3 */6 * * * cd /IYUU && git fetch --all && git reset --hard origin/master' >> /etc/crontabs/root \
    # && echo "${cron} /usr/bin/php /IYUU/iyuu.php >/dev/null 2>&1" >>  /etc/crontabs/root \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

WORKDIR /IYUU
ENTRYPOINT ["/entrypoint.sh"]