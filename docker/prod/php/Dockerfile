FROM php:7.3-fpm AS base


RUN apt-get update && \
    apt-get install -y \
    git \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libicu-dev \
    cron \
    supervisor \
    sudo \
    p7zip-full


RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install pdo_mysql zip gd opcache intl bcmath && \
    pecl install apcu && \
    docker-php-ext-enable apcu

# Install Composer
RUN curl https://getcomposer.org/composer-2.phar --output /usr/local/bin/composer && \
    chmod a+x /usr/local/bin/composer

# Set timezone
RUN rm /etc/localtime && \
    ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime

# Create user and set permissions
RUN useradd -ms /bin/bash symfony && \
    echo "symfony:symfony" | chpasswd && \
    usermod -aG sudo symfony && \
    echo 'devsymfony:x:407:symfony' >> /etc/group && \
    echo '%devsymfony ALL=NOPASSWD: /sbin/cleanAndTest /sbin/clean' >> /etc/sudoers && \
    echo 'alias sf="php bin/console"' >> /home/symfony/.bashrc

WORKDIR /var/www/html

# Copy scripts and allow user to use it
COPY docker/scripts/cleanAndTest.sh /usr/bin/cleanAndTest
COPY docker/scripts/clean.sh        /usr/bin/clean
COPY docker/scripts/crontab         /etc/cron.d/reporting-cron
COPY docker/scripts/cron-launch.sh  /usr/bin/cron-launch
COPY docker/scripts/cron-script.sh  /usr/bin/cron-script

RUN chgrp devsymfony /usr/bin/cleanAndTest && \
    chmod g+x /usr/bin/cleanAndTest && \
    chgrp devsymfony /usr/bin/clean && \
    chmod g+x /usr/bin/clean && \
    touch /etc/cron.allow && \
    echo "symfony"  > /etc/cron.allow && \
    chmod 0644 /etc/cron.d/reporting-cron && \
    crontab -u symfony /etc/cron.d/reporting-cron && \
    chgrp devsymfony /usr/bin/cron-launch && \
    chgrp devsymfony /usr/bin/cron-script && \
    chmod g+x /usr/bin/cron-script

USER symfony
