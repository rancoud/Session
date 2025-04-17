ARG PHPVERSION="8.4"
FROM php:$PHPVERSION-cli-alpine

RUN apk --update --no-cache add \
    mysql-client \
  && rm -rf /tmp/* /var/cache/apk/*

RUN docker-php-ext-install \
  pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /app

COPY composer.json .
COPY composer.lock .
RUN composer validate
RUN composer install --no-interaction --no-progress

COPY . .

ENTRYPOINT ["./entrypoint.sh"]
