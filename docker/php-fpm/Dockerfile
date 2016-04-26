FROM debian:jessie

MAINTAINER Pan Luo <pan.luo@ubc.ca>

ENV DEBIAN_FRONTEND noninteractive
ENV ENVIRONMENT prod

RUN apt-get update \
  && apt-get install -y php5-common php5-cli php5-fpm php5-mcrypt php5-mysql php5-apcu php5-curl php5-xsl php5-redis php5-xdebug \
  && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \
  && rm /etc/php5/fpm/pool.d/www.conf /etc/php5/cli/conf.d/20-xdebug.ini /etc/php5/fpm/conf.d/20-xdebug.ini

ADD docker/php-fpm/examdb.ini /etc/php5/fpm/conf.d/
ADD docker/php-fpm/examdb.ini /etc/php5/cli/conf.d/
ADD docker/php-fpm/entrypoint.sh /entrypoint.sh

ADD docker/php-fpm/examdb.pool.conf /etc/php5/fpm/pool.d/
ADD . /data
RUN chown www-data:www-data /data/app/cache /data/app/logs

WORKDIR /data
RUN composer install
RUN setfacl -R -m u:www-data:rwX app/cache app/logs app/data/uploads

VOLUME /data/app/logs
VOLUME /data/app/data/uploads

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php5-fpm", "-F"]

EXPOSE 9000
