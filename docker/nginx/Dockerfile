FROM nginx

MAINTAINER Pan Luo <pan.luo@ubc.ca>

ENV DEBIAN_FRONTEND noninteractive
ENV ENVIRONMENT prod
ENV NGINX_HOST examdb.dev
ENV NGINX_PORT 80
ENV APP_BACKEND app:9000

ADD . /data

RUN apt-get update \
  && apt-get install --no-install-recommends --no-install-suggests -y \
      php5-common php5-cli php5-fpm php5-mcrypt php5-mysql php5-apcu php5-curl php5-xsl php5-redis \
  && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \
  && rm /etc/nginx/conf.d/default.conf \
  && cd /data && composer install \
  && apt-get remove -y php5-common php5-cli php5-fpm php5-mcrypt php5-mysql php5-apcu php5-curl php5-xsl php5-redis \
  && rm /usr/local/bin/composer \
  && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

ENTRYPOINT ["/data/docker/nginx/entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
