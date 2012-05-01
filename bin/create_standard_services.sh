#/bin/bash

FILENAME="/tmp/standard_services.$USER"
#echo $FILENAME

BASEDIR=/usr/share/geni-ch

FQDN=`hostname -f`

# SR
./mk-auth-req sr "${BASEDIR}/sr/sr-key.pem" "${BASEDIR}/sr/sr-req.pem"
./sign-auth-req "${BASEDIR}/sr/sr-req.pem" "${BASEDIR}/sr/sr-cert.pem"

# SA
./mk-auth-req sa "${BASEDIR}/sa/sa-key.pem" "${BASEDIR}/sa/sa-req.pem"
./sign-auth-req "${BASEDIR}/sa/sa-req.pem" "${BASEDIR}/sa/sa-cert.pem" CA
CERT=`openssl x509 -in "${BASEDIR}/sa/sa-cert.pem"`
echo "insert into service_registry (service_type, service_url, service_cert) values (1, 'https://${FQDN}/sa/sa_controller.php', '${CERT}');" > $FILENAME


# PA
./mk-auth-req pa "${BASEDIR}/pa/pa-key.pem" "${BASEDIR}/pa/pa-req.pem"
./sign-auth-req "${BASEDIR}/pa/pa-req.pem" "${BASEDIR}/pa/pa-cert.pem"
CERT=`openssl x509 -in "${BASEDIR}/pa/pa-cert.pem"`
echo "insert into service_registry (service_type, service_url, service_cert) values (2, 'https://${FQDN}/pa/pa_controller.php', '${CERT}');" >> $FILENAME


# MA
./mk-auth-req ma "${BASEDIR}/ma/ma-key.pem" "${BASEDIR}/ma/ma-req.pem"
./sign-auth-req "${BASEDIR}/ma/ma-req.pem" "${BASEDIR}/ma/ma-cert.pem" CA
CERT=`openssl x509 -in "${BASEDIR}/ma/ma-cert.pem"`
echo "insert into service_registry (service_type, service_url, service_cert) values (3, 'https://${FQDN}/ma/ma_controller.php', '${CERT}');" >> $FILENAME


# LOGGING
./mk-auth-req logging "${BASEDIR}/logging/logging-key.pem" "${BASEDIR}/logging/logging-req.pem"
./sign-auth-req "${BASEDIR}/logging/logging-req.pem" "${BASEDIR}/logging/logging-cert.pem"
CERT=`openssl x509 -in "${BASEDIR}/logging/logging-cert.pem"`
echo "insert into service_registry (service_type, service_url, service_cert) values (5, 'https://${FQDN}/logging/logging_controller.php', '${CERT}');" >> $FILENAME


# CS
./mk-auth-req cs "${BASEDIR}/cs/cs-key.pem" "${BASEDIR}/cs/cs-req.pem"
./sign-auth-req "${BASEDIR}/cs/cs-req.pem" "${BASEDIR}/cs/cs-cert.pem"
CERT=`openssl x509 -in "${BASEDIR}/cs/cs-cert.pem"`
echo "insert into service_registry (service_type, service_url, service_cert) values (6, 'https://${FQDN}/cs/cs_controller.php', '${CERT}');" >> $FILENAME


# A local aggregate manager
echo "insert into service_registry (service_type, service_url, service_name, service_description) values (0, 'https://localhost:8001/', 'Local gcf AM', 'Empty AM');" >> $FILENAME

# Write the accumulated SQL to the database
sudo -u $SUDO_USER psql -U portal -h localhost portal < $FILENAME

# Delete the temp file
rm $FILENAME
