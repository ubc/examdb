#!/bin/bash

set -eo pipefail

PROJECT_DIR=/data

# Check if environment is dev
if [ "$ENVIRONMENT" == "dev" ]
then
    # PHP Settings
    sed -i 's/display_errors = Off/display_errors = On/' /etc/php5/cli/php.ini
    sed -i 's/display_errors = Off/display_errors = On/' /etc/php5/fpm/php.ini

    # Enable xdebug
    echo "xdebug.cli_color = 1" >> "/etc/php5/mods-available/xdebug.ini"
    echo "xdebug.remote_connect_back = 1" >> "/etc/php5/mods-available/xdebug.ini"
    echo "xdebug.coverage_enable = 0" >> "/etc/php5/mods-available/xdebug.ini"
    echo "xdebug.profiler_enable_trigger = 1" >> "/etc/php5/mods-available/xdebug.ini"
    [[ -f /etc/php5/cli/conf.d/20-xdebug.ini ]] || ln -s /etc/php5/mods-available/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
    [[ -f /etc/php5/fpm/conf.d/20-xdebug.ini ]] || ln -s /etc/php5/mods-available/xdebug.ini /etc/php5/fpm/conf.d/20-xdebug.ini
fi

# if command starts with an option, prepend app/console
if [ "${1:0:1}" = '-' ]; then
    set -- php5-fpm "$@"
fi

if [ "$1" = 'console' ]; then
    set -- /data/app/console "${@:2}"
fi

if [ "$1" = 'php5-fpm' ]; then
    # Ensure proper permissions for Symfony
    chown -R www-data:www-data $PROJECT_DIR/app/cache $PROJECT_DIR/app/logs $PROJECT_DIR/app/data/uploads
fi

# Run CMD from docker
exec "$@"
