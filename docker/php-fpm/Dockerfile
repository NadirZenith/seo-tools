FROM php:7.1-fpm
#FROM php:7.1-fpm-alpine # missing gcc make etc (build-esentials ubuntu)

#COPY php.ini /usr/local/etc/php/

RUN apt-get update && apt-get install -y \
    supervisor cron gcc git zip unzip libzip-dev libjpeg-dev libpng-dev openjdk-7-jre \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsS -o /tmp/icu.tgz -L http://download.icu-project.org/files/icu4c/59.1/icu4c-59_1-src.tgz \
  && tar -zxf /tmp/icu.tgz -C /tmp \
  && cd /tmp/icu/source \
  && ./configure --prefix=/usr/local \
  && make \
  && make install \
  && rm -rf /tmp/icu*

# PHP_CPPFLAGS are used by the docker-php-ext-* scripts
ENV PHP_CPPFLAGS="$PHP_CPPFLAGS -std=c++11"

RUN docker-php-ext-configure intl --with-icu-dir=/usr/local && \
    docker-php-ext-configure gd --with-png-dir=/usr/lib/ --with-jpeg-dir=/usr/lib/ && \
    docker-php-ext-install intl pdo pdo_mysql opcache gd zip && \
    pecl install xdebug-2.5.0 apcu zip && \
    docker-php-ext-enable xdebug apcu

# xdebug
#RUN pecl install xdebug-2.5.0 apcu\
#    && docker-php-ext-enable xdebug apcu

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

ENV JAVA_HOME /usr/lib/jvm/java-7-openjdk-amd64

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
#RUN systemctl disable php-fpm #
## Create the log file to be able to run tail
#RUN touch /application/var/logs/cron.log
# Add crontab file in the cron directory
ADD crontab /etc/cron.d/parser
## Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/parser
RUN /usr/bin/crontab /etc/cron.d/parser
##RUN service cron start # dont work, must be set on container start

RUN echo "root:root"|chpasswd
#RUN usermod -u 1000 www-data
RUN useradd -ms /bin/bash dev
RUN usermod -u 1000 dev
#USER dev

RUN echo 'alias sf="php app/console"' >> ~/.bashrc \
    && echo 'alias sf3="php bin/console"' >> ~/.bashrc

#ADD start.sh /start.sh

#CMD ["php-fpm"]

WORKDIR  "/application"

#COPY run.sh /docker-php-entrypoint
#RUN echo "echo 'okapa';" >> ./web/index.php
#RUN ls -la /app
#ENTRYPOINT ["/start.sh"]

#CMD ["/usr/bin/supervisord"]
#CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

COPY entrypoint.sh /entrypoint.sh
#RUN chmod 755 /entrypoint.sh
CMD ["/entrypoint.sh"]