ARG PHP_VER=8.0
ARG PHP_FPM_PORT=9000


FROM php:${PHP_VER}-fpm

# create user [docker] with passwd [docker]
USER root
RUN groupadd -f -g 1000 docker && useradd -G docker,www-data,root -N -m -s /bin/bash -u 1000 -d /home/docker docker 
RUN echo docker:docker | chpasswd

# install packages
RUN set -xe; \
    apt-get update -yqq && \
    pecl channel-update pecl.php.net && \
    apt-get install -yqq \
    apt-utils \
    mc \
    gnupg2 \
    git \
    libzip-dev zip unzip\
    libpcre3-dev \
    libmcrypt-dev \
    libxml2-dev \
    zlib1g-dev \
    libssh2-1 \
    libssh2-1-dev \
    libpq-dev \
    libpng-dev

# php extensions
RUN docker-php-ext-install soap zip gd

# ini file
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# xdebug
RUN if [ ${PHP_VER} = "8.2" ]; then \
    pecl install xdebug-3.2.0; \
    else \
    pecl install xdebug-3.1.4; \
    fi;
RUN docker-php-ext-enable xdebug
COPY /php-fpm/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY /php-fpm/xdebug.ini /etc/php/${PHP_VER}/cli/conf.d/

#phalcon
RUN pecl install phalcon-5.1.1 && docker-php-ext-enable phalcon

# ssh
RUN apt-get install -yqq openssh-server

# composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    # && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

# Clean up
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    rm /var/log/lastlog /var/log/faillog

WORKDIR /var/www

CMD ["php-fpm"]

EXPOSE ${PHP_FPM_PORT}
EXPOSE 9003
EXPOSE 22
