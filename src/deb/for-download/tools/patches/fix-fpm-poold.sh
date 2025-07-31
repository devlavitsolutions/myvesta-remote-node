#!/bin/bash

if [ -d "/etc/php" ]; then
    OLDVAL='php_admin_value\[upload_max_filesize\] = 80M'
    NEWVAL='php_admin_value\[upload_max_filesize\] = 800M'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"

    OLDVAL='php_admin_value\[post_max_size\] = 80M'
    NEWVAL='php_admin_value\[post_max_size\] = 800M'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"

    OLDVAL='php_admin_value\[memory_limit\] = 256M'
    NEWVAL='php_admin_value\[memory_limit\] = 512M'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL|$NEWVAL|g"

    OLDVAL='pm.max_children = '
    NEWVAL='pm.max_children = 3'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"

    OLDVAL='request_terminate_timeout = '
    NEWVAL='request_terminate_timeout = 360s'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"

    OLDVAL='php_admin_value\[max_execution_time\] = '
    NEWVAL='php_admin_value\[max_execution_time\] = 300'
    find /etc/php/*/fpm/pool.d/ -name "*.conf" -type f -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"
    find /usr/local/vesta/data/templates/web/apache2/ -type f -name "*.sh" -exec grep -l "$OLDVAL" {} \; | xargs sed -i "s|$OLDVAL.*|$NEWVAL|g"

    systemctl --full --type service --all | grep "php...-fpm" | awk '{print $1}' | xargs systemctl restart
fi

if [ -f "/etc/apache2/mods-enabled/mpm_event.conf" ]; then
    rm /etc/apache2/mods-enabled/mpm_event.conf
    ln -s /etc/apache2/mods-available/mpm_event.conf /etc/apache2/mods-enabled/mpm_event.conf
    sed -i "s#MaxRequestWorkers.*#MaxRequestWorkers        200#g" /etc/apache2/mods-available/mpm_event.conf
    systemctl restart apache2
fi
