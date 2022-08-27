#FROM walkero/phpunit-alpine:php7.3-phpunit7
#FROM walkero/phpunit-alpine:php8.1-phpunit8
FROM walkero/phpunit-alpine:php5.6-phpunit5

WORKDIR /app

COPY composer.json composer.json
COPY ./src ./src
COPY ./Tests ./Tests
COPY ./phpunit.xml ./phpunit.xml
RUN composer update
