#!/bin/bash

USER='webmail'
DOMAIN='' # enter domain or subdomain

VERSION='1.6.6'
DOWNLOAD="https://github.com/roundcube/roundcubemail/releases/download/$VERSION/roundcubemail-$VERSION-complete.tar.gz"

LOGINMESSAGE1='Click here for NEW Webmail'
LOGINMESSAGE2='(it is adapted for mobile phones too)'

DATABASE_NAME_WITHOUT_PREFIX="roundcube"

#############################################################################################################################

if [ $# -gt 0 ]; then
    USER=$1
fi
if [ $# -gt 1 ]; then
    DOMAIN=$2
fi

DATABASE_NAME="${USER}_${DATABASE_NAME_WITHOUT_PREFIX}"

if [ -z "$USER" ] || [ -z "$DOMAIN" ]; then
    echo "Usage:"
    echo "bash install-new-roundcube.sh VESTAUSER YOURDOMAIN.com"
    exit 1
fi

source /usr/local/vesta/func/main.sh
source /usr/local/vesta/func/db.sh

# Defining password-gen function
gen_pass() {
    MATRIX='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
    LENGTH=32
    while [ ${n:=1} -le $LENGTH ]; do
        PASS="$PASS${MATRIX:$(($RANDOM%${#MATRIX})):1}"
        let n+=1
    done
    echo "$PASS"
}
DATABASE_PASSWORD=$(gen_pass)

fix_ownership() {
    chown -R $USER:$USER /home/$USER/web/$DOMAIN/public_html/
    find /home/$USER/web/$DOMAIN/public_html/ -type d -exec chmod 755 {} +
    find /home/$USER/web/$DOMAIN/public_html/ -type f -exec chmod 644 {} +
}


if [ ! -d "/home/$USER" ]; then
    echo "== Creating user: $USER"
    USER_PASSWORD=$(gen_pass)
    /usr/local/vesta/bin/v-add-user "$USER" "$USER_PASSWORD" "admin@$DOMAIN" 'default' 'Webmail' 'Roundcube'
    /usr/local/vesta/bin/v-change-user-language "$USER" 'en'
fi

if [ ! -d "/home/$USER/web/$DOMAIN" ]; then
    echo "== Adding domain: $DOMAIN"
    /usr/local/vesta/bin/v-add-domain "$USER" "$DOMAIN" "" "yes"
    if [ ! -d "/home/$USER/web/$DOMAIN" ]; then
        echo "ERROR: Can't create domain $DOMAIN, maybe it's already created under the other user?"
        exit 1
    fi
    if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-74.tpl" ]; then
        /usr/local/vesta/bin/v-change-web-domain-tpl "$USER" "$DOMAIN" 'PHP-FPM-74' 'yes'
    fi
fi

pub_ip=$(curl -4 -s https://scripts.myvestacp.com/ip.php)
domain_host_ip=$(host $DOMAIN | head -n 1 | awk '{print $NF}')
if [ "$pub_ip" != "$domain_host_ip" ]; then
    echo "ERROR:"
    echo "$DOMAIN is not pointing to $pub_ip"
    echo "I see it is pointing to $domain_host_ip"
    read -p "Are you sure you want to continue? (y/n)" answer
    if [ "$answer" = "y" ] || [ "$answer" = "Y" ]; then
        echo "Okay, we will continue."
    else
        echo "OK, good bye!"
        exit 1
    fi
fi

number_of_files=$(ls /home/$USER/web/$DOMAIN/public_html | wc -l)
if [ $number_of_files -ne 0 ]; then
    if [ $number_of_files -eq 2 ] && [ -f "/home/$USER/web/$DOMAIN/public_html/index.html" ] && [ -f "/home/$USER/web/$DOMAIN/public_html/robots.txt" ]; then
        rm /home/$USER/web/$DOMAIN/public_html/index.html
        rm /home/$USER/web/$DOMAIN/public_html/robots.txt
    else
        echo "== public_html folder is not empty, aborting."
        exit 1
    fi
fi

if [ ! -f "/home/$USER/conf/web/ssl.$DOMAIN.ca" ]; then
    www_host="www.$DOMAIN"
    www_host_ip=$(host $www_host | head -n 1 | awk '{print $NF}')
    if [ "$www_host_ip" != "$domain_host_ip" ]; then
        echo "=== Deleting www"
        /usr/local/vesta/bin/v-delete-web-domain-alias "$USER" "$DOMAIN" "$www_host" 'no'
        /usr/local/vesta/bin/v-delete-dns-on-web-alias "$USER" "$DOMAIN" "$www_host" 'no'
        www_host=""
    fi
    echo "== Installing LetsEncrypt SSL, please wait..."
    /usr/local/vesta/bin/v-add-letsencrypt-domain "$USER" "$DOMAIN" "$www_host" 'yes'
    /usr/local/vesta/bin/v-change-web-domain-proxy-tpl "$USER" "$DOMAIN" 'force-https' 'jpg,jpeg,gif,png,ico,svg,css,zip,tgz,gz,rar,bz2,doc,xls,exe,pdf,ppt,txt,odt,ods,odp,odf,tar,wav,bmp,rtf,js,mp3,avi,mpeg,flv,woff,woff2' 'yes'
fi

echo "== Downloading Roundcube..."
wget -nv "$DOWNLOAD" -O /root/roundcubemail.tar.gz

echo "== Extracting Roundcube..."
tar --directory /home/$USER/web/$DOMAIN/public_html --strip-components=1 -xzf /root/roundcubemail.tar.gz roundcubemail-$VERSION/

fix_ownership

DB_EXISTS=$(check_if_database_exists "$USER" "$DATABASE_NAME")
if [ "$DB_EXISTS" = "no" ]; then
    echo "== Creating database: $DATABASE_NAME"
    /usr/local/vesta/bin/v-add-database "$USER" "$DATABASE_NAME_WITHOUT_PREFIX" "$DATABASE_NAME_WITHOUT_PREFIX" "$DATABASE_PASSWORD" 'mysql' 'localhost' 'utf8'
else
    echo "== Database $DATABASE_NAME already exists, and it's maybe used by another site. I will not continue. Please edit this script and enter other database name."
    exit 1
fi

if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-73.tpl" ]; then
    echo "================================"
    echo "== Installing php7.3-imap module"
    apt update
    apt install -y php7.3-imap
    echo "================================"
fi
if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-74.tpl" ]; then
    echo "================================"
    echo "== Installing php7.4-imap module"
    apt update
    apt install -y php7.4-imap
    echo "================================"
fi
if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-80.tpl" ]; then
    echo "================================"
    echo "== Installing php8.0-imap module"
    apt update
    apt install -y php8.0-imap
    echo "================================"
fi
if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-81.tpl" ]; then
    echo "================================"
    echo "== Installing php8.1-imap module"
    apt update
    apt install -y php8.1-imap
    echo "================================"
fi
if [ -f "/usr/local/vesta/data/templates/web/apache2/PHP-FPM-82.tpl" ]; then
    echo "================================"
    echo "== Installing php8.2-imap module"
    apt update
    apt install -y php8.2-imap
    echo "================================"
fi

echo "-------------------------------------"
echo "Go to:"
echo "https://${DOMAIN}/installer/"
echo "... and finish the Roundcube installation."
echo ""
echo "You will be asked for:"
echo "Database host: localhost"
echo "Database user: $DATABASE_NAME"
echo "Database name: $DATABASE_NAME"
echo "Database pass: $DATABASE_PASSWORD"
echo ""
echo "Suggestion: plugins to be enabled: new_user_dialog, password"
echo "-------------------------------------"
echo ""
echo "=== When you click 'Initialize database' button (and see DB Write: OK), you can consider the installation as done."
read -p "=== Then press Enter here, and this script will remove /home/$USER/web/$DOMAIN/public_html/installer folder ==="

if [ ! -f "/home/$USER/web/$DOMAIN/public_html/config/config.inc.php" ]; then
    echo "=== ERROR: You didn't finish the installation."
    echo "=== Please read carefully what is written above."
    read -p "=== Then press Enter when you finish installation ==="
fi
if [ ! -f "/home/$USER/web/$DOMAIN/public_html/config/config.inc.php" ]; then
    echo "=== ERROR: You didn't finish the installation."
    echo "=== Sorry, the script will exit now."
    exit 1;
fi

rm -rf /home/$USER/web/$DOMAIN/public_html/installer

if [ -d "/home/$USER/web/$DOMAIN/public_html/plugins/password" ]; then
    cp /usr/share/roundcube/plugins/password/config.inc.php /home/$USER/web/$DOMAIN/public_html/plugins/password/config.inc.php
    cp /usr/share/roundcube/plugins/password/drivers/vesta.php /home/$USER/web/$DOMAIN/public_html/plugins/password/drivers/vesta.php
fi

# wget -nv https://c.myvestacp.com/tools/roundcube-filters.tgz -O /root/roundcube-filters.tgz
# tar --directory /home/$USER/web/$DOMAIN/public_html/plugins -xzf /root/roundcube-filters.tgz
# sed -i "s/\$config\['plugins'\] = \[/\$config['plugins'] = ['filters', /g" /home/$USER/web/$DOMAIN/public_html/config/config.inc.php

echo "\$config['session_lifetime'] = 1080;" >> /home/$USER/web/$DOMAIN/public_html/config/config.inc.php

fix_ownership

if [ -f "/usr/share/roundcube/skins/larry/templates/login.html" ]; then
    check_grep=$(grep -c 'color: white; font-size: 12pt' /usr/share/roundcube/skins/larry/templates/login.html)
    if [ "$check_grep" -eq 0 ]; then
        sed -i "s|<roundcube:form name=\"form\" method=\"post\">|<br /><br /><center><a href=\"https://$DOMAIN/\" style=\"color: white; font-size: 12pt;\">$LOGINMESSAGE1</a><br /><span style=\"color: white; font-size: 8pt;\">$LOGINMESSAGE2</span></center><br /><br />\n\n<roundcube:form name=\"form\" method=\"post\">|g" /usr/share/roundcube/skins/larry/templates/login.html
    fi
fi

check_grep=$(grep -c 'MAIL_URL=' /usr/local/vesta/conf/vesta.conf)
if [ "$check_grep" -eq 0 ]; then
    echo "MAIL_URL='https://$DOMAIN/'" >> /usr/local/vesta/conf/vesta.conf
fi


echo "-------------------------------------"
echo "Roundcube installed!"
echo "Go to:"
echo "https://${DOMAIN}/"
echo "-------------------------------------"
