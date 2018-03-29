FROM php:7.1-fpm
#FROM php:7.1-fpm-alpine # missing gcc make etc (build-esentials ubuntu)

#COPY php.ini /usr/local/etc/php/

RUN apt-get update && apt-get install -y \
    gcc git zip unzip libjpeg-dev libpng-dev openjdk-7-jre \
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
    docker-php-ext-install intl pdo pdo_mysql opcache gd

# xdebug
RUN pecl install xdebug-2.5.0 apcu\
    && docker-php-ext-enable xdebug apcu

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

ENV JAVA_HOME /usr/lib/jvm/java-7-openjdk-amd64

RUN echo "root:root"|chpasswd
#RUN usermod -u 1000 www-data
RUN useradd -ms /bin/bash dev
RUN usermod -u 1000 dev
USER dev

RUN echo 'alias sf="php app/console"' >> ~/.bashrc \
    && echo 'alias sf3="php bin/console"' >> ~/.bashrc

ADD start.sh /start.sh

#RUN ls -l /application
#CMD ["php-fpm"]

WORKDIR  "/application"

#COPY run.sh /docker-php-entrypoint
#RUN echo "echo 'okapa';" >> ./web/index.php
#RUN ls -la /app
ENTRYPOINT ["/start.sh"]
