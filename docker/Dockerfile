FROM base AS result
ARG ENV

COPY --chown=symfony:symfony app/ /var/www/html/app
COPY --chown=symfony:symfony bin/ /var/www/html/bin
COPY --chown=symfony:symfony src/ /var/www/html/src
COPY --chown=symfony:symfony var/ /var/www/html/var
COPY --chown=symfony:symfony web/ /var/www/html/web
COPY --chown=symfony:symfony tests/ /var/www/html/tests
COPY --chown=symfony:symfony composer.json /var/www/html/
COPY --chown=symfony:symfony composer.lock /var/www/html/
COPY --chown=symfony:symfony ci/clean-database.sh /var/www/html/
COPY --chown=symfony:symfony docker/$ENV/php/php.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY --chown=symfony:symfony docker/$ENV/php/messenger-worker.conf /etc/supervisor/conf.d/messenger-worker.conf

# TODO: add --no-dev after composer.json is fixed
RUN composer install --optimize-autoloader && \
  rm -rf var/cache/*
