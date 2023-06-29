ARG phpVersion=8.2

FROM php:${phpVersion}-fpm

# default env vars
ENV ORIGIN_TYPE=web

# s3 origin env vars
ENV S3_ACCESS_KEY_ID=
ENV S3_BUCKET=
ENV S3_ENDPOINT=
ENV S3_PATH_STYLE_ENDPOINT=false
ENV S3_REGION=us-east-1
ENV S3_SECRET_ACCESS_KEY=

# web origin env vars
ENV WEB_URL=
ENV WEB_USER_AGENT="dockfront/99.0"

# install system software
RUN apt-get update && \
    apt-get install -y git nginx supervisor zip

# install php-xml extension
RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install -j$(nproc) xml

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# override nginx vhost
COPY vhost.conf /etc/nginx/sites-available/default

# override supervisor config
COPY services.conf /etc/supervisor/supervisord.conf

# override php-fpm pool
COPY pool.conf /usr/local/etc/php-fpm.d/www.conf

# set consistent uid and gid
RUN usermod -u 1000 www-data && \
    groupmod -g 1000 www-data

# set working directory
WORKDIR /var/www/html

# copy composer files
COPY composer.json composer.lock .

# install project deps
RUN composer install --optimize-autoloader

# copy project files
COPY . .

# expose http port
EXPOSE 80

# run processes via supervisor
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "--logfile", "/dev/null", "--pidfile", "/dev/null"]
