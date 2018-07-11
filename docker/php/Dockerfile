# ./Docker/php/Dockerfile

FROM php:7.2-fpm


RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    apt-transport-https \
    libpng-dev \
    zlib1g-dev \
    libicu-dev \
    g++


RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql zip gd intl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHPUNIT
RUN apt-get install --yes wget
RUN wget https://phar.phpunit.de/phpunit-6.5.phar
RUN chmod +x phpunit-6.5.phar
RUN mv phpunit-6.5.phar /usr/local/bin/phpunit
RUN phpunit --version

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime
RUN "date"

RUN useradd -ms /bin/bash symfony

RUN /bin/bash -c  "echo 'devsymfony:x:407:symfony' >> /etc/group"
RUN /bin/bash -c  "echo '%devsymfony ALL=NOPASSWD: /sbin/cleanAndTest' >> /etc/sudoers"


RUN /bin/bash -c  "echo 'alias sf=\"php bin/console\"' >> /home/symfony/.bashrc"
WORKDIR /var/www/html/symfony
COPY cleanAndTest.sh /usr/bin/cleanAndTest

RUN chgrp devsymfony /usr/bin/cleanAndTest
RUN chmod g+x /usr/bin/cleanAndTest

USER symfony

