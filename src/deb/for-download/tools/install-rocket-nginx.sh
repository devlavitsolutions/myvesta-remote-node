#!/bin/bash

wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-force-https.tpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-force-https.tpl
wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-force-https.stpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-force-https.stpl
wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-hosting.tpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-hosting.tpl
wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-hosting.stpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-hosting.stpl
wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-webp-express-force-https.tpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-webp-express-force-https.tpl
wget -nv -O /usr/local/vesta/data/templates/web/nginx/wprocket-webp-express-force-https.stpl https://c.myvestacp.com/tools/rocket-nginx-templates/wprocket-webp-express-force-https.stpl

echo "Updating apt, please wait..."
apt-get update > /dev/null 2>&1

apt-get -y install git > /dev/null 2>&1

if [ ! -d "/etc/nginx/rocket-nginx" ]; then
    cd /etc/nginx
    git clone https://github.com/satellitewp/rocket-nginx.git
else
    cd /etc/nginx/rocket-nginx
    git pull
    cd ..
fi

cd rocket-nginx
cp rocket-nginx.ini.disabled rocket-nginx.ini
php rocket-parser.php
if [ -f "/etc/nginx/rocket-nginx/conf.d/default.conf" ]; then
    /usr/local/vesta/bin/v-php-func 'strip_once_in_file_between_including_borders' '/etc/nginx/rocket-nginx/conf.d/default.conf' '# BROWSER MEDIA CACHE' '}'
fi
