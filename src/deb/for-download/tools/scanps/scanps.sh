#!/bin/bash

# run 'crontab -e' and add the following:
# */5 * * * * /home/scanps.sh > /dev/null 2>&1

cd /home
php scanps.php