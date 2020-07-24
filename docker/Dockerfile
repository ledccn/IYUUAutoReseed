FROM composer:1.10.9

ENV TZ Asia/Shanghai
ENV cron="0 */1 * * *"

COPY . /IYUU
COPY ./docker/entrypoint.sh /entrypoint.sh

WORKDIR /IYUU

RUN apk add --no-cache bash tzdata \
    && composer i

ENTRYPOINT [ "/entrypoint.sh" ]
