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
# Currently, all known hosts have config files managed out of band,
# so the EXAMPLE_CH_FQDN block has been left in as an example
INSTALL_CONFIG_FILES="yes"
CH_EMAIL='portal-sandbox-admin@gpolab.bbn.com'
CH_HOST='fields.bbn.com'
PORTAL_PASSWORD='portal'
GCF_INI=/usr/share/geni-ch/gcf.d/gcf.ini
APACHE_HTTPS_CH=/etc/httpd/sites-enabled/ch-ssl
APACHE_HTTPS_PORTAL=/etc/httpd/sites-enabled/portal-ssl
APACHE_HTTP=/etc/httpd/sites-enabled/default
SHARE_DIR=/usr/share/geni-ch

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

/usr/bin/sudo /bin/cp -R portal/gcf.d ${SHARE_DIR}

if [ "${INSTALL_CONFIG_FILES}" = "yes" ]; then
  sudo cp /usr/share/geni-ch/gcf.d/example-gcf.ini $GCF_INI
  sudo sed -i -e "/^base_name=/s/=.*/=$CH_HOST/" $GCF_INI
  sudo sed -i -e "s,//localhost,//$CH_HOST,g" $GCF_INI
else
  test -f $GCF_INI
fi

sudo /bin/ln -s /usr/share/geni-ch/CA/cacert.pem /usr/share/geni-ch/portal/gcf.d/trusted_roots/cacert.pem
sudo /bin/ln -s /usr/share/geni-ch/ma/ma-cert.pem /usr/share/geni-ch/portal/gcf.d/trusted_roots/ma-cert.pem
sleep 10

sudo service httpd restart
sleep 10

if [ "${INSTALL_CONFIG_FILES}" = "yes" ]; then
  sudo /bin/cp /etc/geni-ch/example-settings.php /etc/geni-ch/settings.php

  sudo sed -i -e "/^\$db_dsn =/s/=.*/= 'pgsql:\/\/portal:$PORTAL_PASSWORD@localhost\/portal';/" /etc/geni-ch/settings.php
  sudo sed -i -e "/^\$portal_admin_email =/s/=.*/= '$CH_EMAIL';/" /etc/geni-ch/settings.php
  sudo sed -i -e "/^\$service_registry_url =/s/=.*/= 'https:\/\/$CH_HOST:8444\/SR';/" /etc/geni-ch/settings.php
  sudo sed -i -e "/^\$genilib_trusted_host =/s/=.*/= 'https:\/\/$CH_HOST:8444';/" /etc/geni-ch/settings.php
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
