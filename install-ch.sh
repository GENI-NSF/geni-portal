#!/bin/bash
# -*- Mode:bash -*-

# Exit on error
set -e
# Echo commands with variables expanded
set -x

# If this script should install config files for a given host, define
# a block for that host.  Define these variables:
# * INSTALL_CONFIG_FILES="yes"
# * CH_EMAIL: e-mail address of the admin for this CH
# * CH_HOST: the FQDN to which this CH should answer
# * PORTAL_PASSWORD: the portal user's psql database password
LOCAL_FQDN=$(/bin/hostname --fqdn)
if [ "$LOCAL_FQDN" = "cascade.gpolab.bbn.com" ]; then
  INSTALL_CONFIG_FILES="yes"
  CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
  CH_HOST=`/bin/hostname --fqdn`
  PORTAL_PASSWORD='portal'
elif [ "$LOCAL_FQDN" = "dagoola.gpolab.bbn.com" ]; then
  INSTALL_CONFIG_FILES="yes"
  CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
  CH_HOST=`/bin/hostname --fqdn`
  PORTAL_PASSWORD='portal'
elif [ "$LOCAL_FQDN" = "illyrica.gpolab.bbn.com" ]; then
  INSTALL_CONFIG_FILES="yes"
  CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
  CH_HOST=`/bin/hostname --fqdn`
  PORTAL_PASSWORD='portal'
elif [ "$LOCAL_FQDN" = "marilac.gpolab.bbn.com" ]; then
  INSTALL_CONFIG_FILES="yes"
  CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
  CH_HOST=`/bin/hostname --fqdn`
  PORTAL_PASSWORD='portal'
elif [ "$LOCAL_FQDN" = "sergyar.gpolab.bbn.com" ]; then
  INSTALL_CONFIG_FILES="yes"
  CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
  CH_HOST=`/bin/hostname --fqdn`
  PORTAL_PASSWORD='portal'
else

  # If not specified, expect some other process to have installed
  # all config files on this node *before* running this script
  INSTALL_CONFIG_FILES="no"

  # Set a default file where we should expect the portal database password
  # to be stored, and a system user who has read access to that file
  PORTAL_PASSWORD_FILE='/usr/sysadmin/etc/portal_password'
  PORTAL_PASSWORD_FILE_USER='www-data'
fi 

GCF_INI=/usr/share/geni-ch/portal/gcf.d/gcf.ini
APACHE_HTTPS_CH=/etc/apache2/sites-available/ch-ssl
APACHE_HTTPS_PORTAL=/etc/apache2/sites-available/portal-ssl
APACHE_HTTP=/etc/apache2/sites-available/default

autoreconf --install
sleep 10
./configure --prefix=/usr --sysconfdir=/etc \
            --bindir=/usr/local/bin --sbindir=/usr/local/sbin
sleep 10
make
sleep 10
sudo make install
sleep 10

if [ "${INSTALL_CONFIG_FILES}" = "yes" ]; then
  sudo cp /etc/geni-ch/example-services.ini /etc/geni-ch/services.ini

  # Modify recommended settings using sed
  sudo sed -i -e "/^email=/s/=.*/=$CH_EMAIL/" /etc/geni-ch/services.ini
  sudo sed -i -e "/^authority=/s/=.*/=$CH_HOST/" /etc/geni-ch/services.ini
  sudo sed -i -e "/^servicehost=/s/=.*/=$CH_HOST/" /etc/geni-ch/services.ini
else
  test -f /etc/geni-ch/services.ini
fi

if [ -f /usr/share/geni-ch/CA/cacert.pem ]; then
  echo "CA certificate already exists - reusing it"
else
  sudo geni-init-ca /etc/geni-ch/services.ini
  sleep 10
fi

sudo geni-init-services /etc/geni-ch/services.ini --sql out.sql
sleep 10

make cleandb
sleep 10

psql -h localhost portal portal -f out.sql
sleep 10


if [ "${INSTALL_CONFIG_FILES}" = "yes" ]; then
  sudo cp /usr/share/geni-ch/portal/gcf.d/example-gcf.ini $GCF_INI
  sudo sed -i -e "/^base_name=/s/=.*/=$CH_HOST/" $GCF_INI
  sudo sed -i -e "s,//localhost,//$CH_HOST,g" $GCF_INI
else
  test -f $GCF_INI
fi

sudo /bin/ln -s /usr/share/geni-ch/CA/cacert.pem /usr/share/geni-ch/portal/gcf.d/trusted_roots/cacert.pem
sudo /bin/ln -s /usr/share/geni-ch/ma/ma-cert.pem /usr/share/geni-ch/portal/gcf.d/trusted_roots/ma-cert.pem
sleep 10

sudo /usr/bin/apt-get install -y --allow-unauthenticated geni-pgch
sleep 10

# This install process always updates the apache config files, regardless
# of whether portal/CH config files are being installed
sudo sed -i -e 's/^#PROTOCH//' $APACHE_HTTPS_CH
sleep 10

sudo sed -i -e '/^<\/VirtualHost>/i\
Include /usr/share/geni-ch/portal/apache2-http.conf' $APACHE_HTTP
sleep 10

sudo rm /var/www/index.html
sleep 10

sudo service apache2 restart
sleep 10

if [ "${INSTALL_CONFIG_FILES}" = "yes" ]; then
  sudo /bin/cp /etc/geni-ch/example-settings.php /etc/geni-ch/settings.php

  sudo sed -i -e "/^\$db_dsn =/s/=.*/= 'pgsql:\/\/portal:$PORTAL_PASSWORD@localhost\/portal';/" /etc/geni-ch/settings.php
  sudo sed -i -e "/^\$portal_admin_email =/s/=.*/= '$CH_EMAIL';/" /etc/geni-ch/settings.php
  sudo sed -i -e "/^\$service_registry_url =/s/=.*/= 'https:\/\/$CH_HOST\/sr\/sr_controller.php';/" /etc/geni-ch/settings.php
else
  test -f /etc/geni-ch/settings.php
fi

# Look in portal-cert.pem for a line like:
#     email:portal-sandbox-admin@gpolab.bbn.com, URI:urn:publicid:IDN+ch5.gpolab.bbn.com+authority+portal, URI:uuid:bb9a5610-eae5-443d-9cfa-c6970af9440c
# and get the URN from that line
portal_urn=$(openssl x509 -text -noout -in /usr/share/geni-ch/portal/portal-cert.pem | grep authority+portal | awk '{print $2}' | awk -F, '{print $1}' | awk -F"URI:" '{print $2}')
test -n "${portal_urn}"
if [ -n "${PORTAL_PASSWORD}" ]; then
  geni-add-trusted-tool -p ${PORTAL_PASSWORD} portal "${portal_urn}"
else
  test -f ${PORTAL_PASSWORD_FILE}
  sudo -u ${PORTAL_PASSWORD_FILE_USER} \
    geni-add-trusted-tool -P ${PORTAL_PASSWORD_FILE} portal "${portal_urn}"
fi
