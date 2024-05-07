#!/usr/bin/env sh

set -e

APP_DIR=/usr/local/share/cypht
cd ${APP_DIR}

# TODO: validate env var values here, perhaps in php or in Hm_Site_Config_File()

# TODO: source these defaults from an .env file or some other place?
USER_CONFIG_TYPE="${USER_CONFIG_TYPE:-file}"
USER_SETTINGS_DIR="${USER_SETTINGS_DIR:-/var/lib/hm3/users}"
ATTACHMENT_DIR="${ATTACHMENT_DIR:-/var/lib/hm3/attachments}"
APP_DATA_DIR="${APP_DATA_DIR:-/var/lib/hm3/app_data}"


# Wait for database to be ready then setup tables for sessions, authentication, and settings as needed
./scripts/setup_database.php

./scripts/setup_system.sh

# Generate the run-time configuration
php ./scripts/config_gen.php


# Enable the program in the web-server

if [ "${USER_CONFIG_TYPE}" = "file" ]
then
    chown www-data:www-data ${USER_SETTINGS_DIR}
fi

chown www-data:www-data ${ATTACHMENT_DIR}
chown -R www-data:www-data /var/lib/nginx
chown www-data:www-data ${APP_DATA_DIR}

rm -r /var/www
ln -s $(pwd)/site /var/www

# Start services
/usr/bin/supervisord -c /etc/supervisord.conf

# exec "$@"   # TODO: what is this for?
