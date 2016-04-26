#!/bin/bash

set -eo pipefail

PROJECT_DIR=/data
CONF_FILE=/etc/nginx/conf.d/examdb.conf

envsubst '$APP_BACKEND' < $PROJECT_DIR/docker/nginx/upstream.conf > $CONF_FILE
envsubst '$NGINX_PORT $NGINX_HOST' < $PROJECT_DIR/docker/nginx/examdb.$ENVIRONMENT.conf >> $CONF_FILE

exec "$@"
