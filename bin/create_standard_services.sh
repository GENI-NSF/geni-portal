#/bin/bash

FILENAME="/tmp/standard_services.$USER"
#echo $FILENAME

BASEDIR=/usr/share/geni-ch

FQDN=`hostname -f`

# SR
./mk-auth-req "${BASEDIR}/sr/sr-key.pem" "${BASEDIR}/sr/sr-req.pem" sr portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/sr/sr-req.pem" "${BASEDIR}/sr/sr-cert.pem" sr

# Add the root cert location
echo "insert into service_registry (service_type, service_url, service_cert) values (7, '', '${BASEDIR}/CA/cacert.pem');" > $FILENAME

# SA
./mk-auth-req "${BASEDIR}/sa/sa-key.pem" "${BASEDIR}/sa/sa-req.pem" sa portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/sa/sa-req.pem" "${BASEDIR}/sa/sa-cert.pem" sa CA
echo "insert into service_registry (service_type, service_url, service_cert) values (1, 'https://${FQDN}/sa/sa_controller.php', '${BASEDIR}/sa/sa-cert.pem');" >> $FILENAME


# PA
./mk-auth-req "${BASEDIR}/pa/pa-key.pem" "${BASEDIR}/pa/pa-req.pem" pa portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/pa/pa-req.pem" "${BASEDIR}/pa/pa-cert.pem" pa
echo "insert into service_registry (service_type, service_url, service_cert) values (2, 'https://${FQDN}/pa/pa_controller.php', '${BASEDIR}/pa/pa-cert.pem');" >> $FILENAME


# MA
./mk-auth-req "${BASEDIR}/ma/ma-key.pem" "${BASEDIR}/ma/ma-req.pem" ma portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/ma/ma-req.pem" "${BASEDIR}/ma/ma-cert.pem" ma CA
echo "insert into service_registry (service_type, service_url, service_cert) values (3, 'https://${FQDN}/ma/ma_controller.php', '${BASEDIR}/ma/ma-cert.pem');" >> $FILENAME
# Copy this over to be trusted by pgch
/bin/ln -s ${BASEDIR}/ma-cert.pem ${BASEDIR}/portal/gcf.d/trusted_roots/ma-cert.pem


# LOGGING
./mk-auth-req "${BASEDIR}/logging/logging-key.pem" "${BASEDIR}/logging/logging-req.pem" logging portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/logging/logging-req.pem" "${BASEDIR}/logging/logging-cert.pem" logging
echo "insert into service_registry (service_type, service_url, service_cert) values (5, 'https://${FQDN}/logging/logging_controller.php', '${BASEDIR}/logging/logging-cert.pem');" >> $FILENAME


# CS
./mk-auth-req "${BASEDIR}/cs/cs-key.pem" "${BASEDIR}/cs/cs-req.pem" cs portal-dev-admin@gpolab.bbn.com
./sign-auth-req "${BASEDIR}/cs/cs-req.pem" "${BASEDIR}/cs/cs-cert.pem" cs
echo "insert into service_registry (service_type, service_url, service_cert) values (6, 'https://${FQDN}/cs/cs_controller.php', '${BASEDIR}/cs/cs-cert.pem');" >> $FILENAME


# A local aggregate manager
echo "insert into service_registry (service_type, service_url, service_name, service_description) values (0, 'https://localhost:8001/', 'Local gcf AM', 'Empty AM');" >> $FILENAME

# Write the accumulated SQL to the database
sudo -u $SUDO_USER psql -U portal -h localhost portal < $FILENAME

# Delete the temp file
rm $FILENAME

echo ""
echo " *** Remember to email Tom to ask him to install the new MA and CA certs on pgeni3 *** "
