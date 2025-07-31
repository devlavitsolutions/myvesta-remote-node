#!/bin/bash

###########
# Script that will ensure that Apache2 will always stay in mpm_event mode
###########

switch_to_mpm_event=1

if [ "$1" = 'background' ]; then
    switch_to_mpm_event=0
    check_grep=$(ps -A | grep -c "apt")
    if [ "$check_grep" -eq 0 ]; then
        echo "=== OK, apt is not running"
        echo "=== Cleaning restart.pipe"
        sed -i "/keep_mpm_event/d" /usr/local/vesta/data/queue/restart.pipe
        /usr/local/vesta/bin/v-delete-cron-restart-job
        if [ ! -f "/usr/local/vesta/data/upgrades/keeping-mpm-event-checked-2" ]; then
            touch /usr/local/vesta/data/upgrades/keeping-mpm-event-checked-2
            echo "=== OK, mpm_event is not checked"
            check_grep=$(grep -c "WEB_SYSTEM='apache2'" /usr/local/vesta/conf/vesta.conf)
            if [ "$check_grep" -eq 1 ]; then
                echo "=== OK, we have Apache2"
                release=$(cat /etc/debian_version | tr "." "\n" | head -n1)
                if [ "$release" -ge 10 ]; then
                    echo "=== OK, it's Debian 10 or 11"
                    switch_to_mpm_event=1
                else
                    check_grep=$(/usr/local/vesta/bin/v-list-sys-web-status | grep -c "Server MPM: event")
                    if [ "$check_grep" -eq 1 ]; then
                        echo "=== OK, it's already mpm_event"
                        switch_to_mpm_event=1
                    fi
                fi
            fi
        fi
    fi
else
    echo "=== Script is called by the user"
fi

if [ "$switch_to_mpm_event" -eq 1 ]; then
    echo "=== OK, let's ensure mpm_event"

    a2dismod ruid2 > /dev/null 2>&1
    a2dismod suexec > /dev/null 2>&1
    a2dismod php5.6 > /dev/null 2>&1
    a2dismod php7.0 > /dev/null 2>&1
    a2dismod php7.1 > /dev/null 2>&1
    a2dismod php7.2 > /dev/null 2>&1
    a2dismod php7.3 > /dev/null 2>&1
    a2dismod php7.4 > /dev/null 2>&1
    a2dismod php8.0 > /dev/null 2>&1
    a2dismod php8.1 > /dev/null 2>&1
    a2dismod mpm_prefork > /dev/null 2>&1
    a2enmod mpm_event > /dev/null 2>&1
    apt-get -y remove libapache2-mod-php* > /dev/null 2>&1
    service apache2 restart
    echo "=== Done!"
fi
