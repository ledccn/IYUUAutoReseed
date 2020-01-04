FROM alpine:3.7

RUN apk add --no-cache php5-cli php5-curl php5-gd php5-phar php5-json php5-openssl php5-dom

COPY php-curl-test php-curl-test

EXPOSE 80

CMD ["php5", "-S", "0.0.0.0:80", "-t", "php-curl-test"]
