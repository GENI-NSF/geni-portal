#!/bin/bash
# Activate account of given user as a potential proejct lead
#
# Usage: activate_project_lead.sh username

if [ $# -ne 1 ]; then
    echo "Usage: activate_project_lead.sh member_id"
    exit
fi

signer_cert=/usr/share/geni-ch/ma/ma-cert.pem
signer_key=/usr/share/geni-ch/ma/ma-key.pem
ma_host=`hostname --fqdn`
url=https://$ma_host/ma/ma_controller.php
plain_file=`mktemp`
signed_file=`mktemp`
result_file=`mktemp`

msg="{\"operation\":\"add_member_privilege\",\"member_id\":\"$1\",\"privilege_id\":1}"
echo $msg > $plain_file

openssl smime -sign -signer $signer_cert -inkey $signer_key \
  -in $plain_file -out $signed_file

curl -i -X PUT --data-binary @$signed_file -o $result_file $url

cat $result_file
rm $plain_file
rm $signed_file
rm $result_file
exit 0
