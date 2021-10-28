FROM nginx
ARG ENV

COPY --chown=www-data:www-data web/ /var/www/html/web
COPY docker/${ENV}/nginx/nginx.conf /etc/nginx/conf.d/default.conf
