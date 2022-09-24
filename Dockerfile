FROM php:7.4-fpm

RUN apt-get update
RUN apt-get install -y libpq-dev zlib1g-dev libzip-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev libgd-dev libmcrypt-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/
RUN docker-php-ext-install gd
RUN docker-php-ext-install zip
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql
RUN pecl install mcrypt

ADD conf/php.ini /usr/local/etc/php/conf.d/