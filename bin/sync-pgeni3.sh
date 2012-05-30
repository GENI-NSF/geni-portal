#!/bin/sh

for h in dagoola illyrica marilac sergyar; do
  echo "Pulling from ${h}"
  scp "${h}.gpolab.bbn.com:/usr/share/geni-ch/CA/cacert.pem" cacert-${h}.pem
  scp "${h}.gpolab.bbn.com:/usr/share/geni-ch/ma/ma-cert.pem" ma-cert-${h}.pem
done

cat *.pem > extracerts.bundle
scp extracerts.bundle boss.pgeni3.gpolab.bbn.com:

# echo "Do the following:"
# echo ""
# echo "ssh boss.pgeni3.gpolab.bbn.com"
# echo "sudo mv extracerts.bundle /usr/testbed/etc/extracerts.bundle"
# echo "sudo /usr/testbed/sbin/protogeni/getcacerts"

echo "Moving file into place on pgeni3"
ssh boss.pgeni3.gpolab.bbn.com sudo mv extracerts.bundle /usr/testbed/etc/extracerts.bundle
echo "Invoking getcacerts on pgeni3"
ssh boss.pgeni3.gpolab.bbn.com sudo /usr/testbed/sbin/protogeni/getcacerts
