#!/bin/bash
#
# This script reads email and password=s in following format:
# email1 pass
# email2 pass
# email3 pass

# The first parameter is the text file from which we read emails and passwords
# The second parameter is SMTP Hostname
# The third parameter is domain if lines contains only username part


host=''
if [ $# -gt 1 ]; then
    host=$2
else
    echo "Usage: ./import-from-file.sh 'FILE' 'SMTPHOST' ['DOMAIN']"
    exit 1;
fi

domain=''
if [ $# -gt 2 ]; then
    domain=$3
fi

end_of_file=0
while [[ $end_of_file == 0 ]]; do

  read -r line
  end_of_file=$?

  if [ "$line" == "" ]; then
    if [[ $end_of_file == 1 ]]; then
      echo "===EOF==="
      break;
    fi
    continue
  fi

  email=$(echo "$line" | awk '{print $1}')
  pass=$(echo "$line" | awk '{print $2}')

  if [[ $email != *"@"* ]]; then
    email="$email@$domain"
  fi

  echo "Extracted: '$email' = '$pass'"

  ./create-mail-sync.sh "$host" "$email" "$pass"

  if [[ $end_of_file == 1 ]]; then
    echo "===EOF==="
    break;
  fi

done < $1
