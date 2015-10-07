FROM registry.service.dsd.io/opguk/php-fpm:0.1.78

RUN  apt-get update && apt-get install -y \
     php-pear php5-curl php5-memcached php5-redis php5-pgsql \
     nodejs dos2unix postgresql-client && \
     apt-get clean && apt-get autoremove && \
     rm -rf /var/lib/cache/* /var/lib/log/* /tmp/* /var/tmp/*

RUN  cd /tmp && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# build app dependencies
COPY composer.json /app/
COPY composer.lock /app/
RUN  chown -R app /app
WORKDIR /app
USER app
ENV  HOME /app
RUN  composer install --prefer-source --no-interaction --no-scripts

# install remaining parts of app
ADD  . /app
USER root
RUN  chown -R app /app
USER app
ENV  HOME /app
RUN  composer run-script post-install-cmd --no-interaction

# cleanup
RUN  rm /app/app/config/parameters.yml
USER root
ENV  HOME /root

# app configuration
ADD docker/confd /etc/confd

# let's make sure they always work
RUN dos2unix /app/scripts/*

# copy init scripts
ADD  docker/my_init.d /etc/my_init.d
RUN  chmod a+x /etc/my_init.d/*

ENV  OPG_SERVICE api
ADD  docker/beaver.d /etc/beaver.d
