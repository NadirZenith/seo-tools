#!/usr/bin/env bash


#php-fpm

su -c "cd /application && ./build/deploy.sh dev" - dev

/usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf