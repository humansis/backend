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
    g++ \
    cron
    
RUN apt-get install -y sudo


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
RUN composer require phpoffice/phpspreadsheet

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime
RUN "date"

RUN useradd -ms /bin/bash symfony
RUN echo "symfony:symfony" | chpasswd
RUN usermod -aG sudo symfony

RUN /bin/bash -c  "echo 'devsymfony:x:407:symfony' >> /etc/group"
RUN /bin/bash -c  "echo '%devsymfony ALL=NOPASSWD: /sbin/cleanAndTest /sbin/clean' >> /etc/sudoers"

RUN /bin/bash -c  "echo 'alias sf=\"php bin/console\"' >> /home/symfony/.bashrc"
WORKDIR /var/www/html/symfony

COPY cleanAndTest.sh /usr/bin/cleanAndTest
RUN chgrp devsymfony /usr/bin/cleanAndTest
RUN chmod g+x /usr/bin/cleanAndTest

COPY clean.sh /usr/bin/clean
RUN chgrp devsymfony /usr/bin/clean
RUN chmod g+x /usr/bin/clean

RUN touch /etc/cron.allow
RUN echo "symfony"  > /etc/cron.allow

COPY crontab /etc/cron.d/reporting-cron
RUN chmod 0644 /etc/cron.d/reporting-cron
RUN crontab -u symfony /etc/cron.d/reporting-cron

COPY cron-launch.sh /usr/bin/cron-launch
RUN chgrp devsymfony /usr/bin/cron-launch
RUN chmod g+x /usr/bin/cron-launch

COPY cron-script.sh /usr/bin/cron-script
RUN chgrp devsymfony /usr/bin/cron-script
RUN chmod g+x /usr/bin/cron-script

USER symfony
