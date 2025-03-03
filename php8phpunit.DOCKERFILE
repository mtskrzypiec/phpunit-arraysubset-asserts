FROM php:8.3

ADD . /var/www

COPY --from=composer:2.1 /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y git zip