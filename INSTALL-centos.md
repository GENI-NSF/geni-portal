# Ensure OS is up to date

```bash
sudo yum update -y
```

# Add yum repositories

Add Shibboleth repository:

```bash
wget http://download.opensuse.org/repositories/security://shibboleth/CentOS_7/security:shibboleth.repo
sudo cp security\:shibboleth.repo /etc/yum.repos.d/
```

Add GENI repository:

```bash
wget http://www.gpolab.bbn.com/experiment-support/gposw/centos/geni.repo
sudo cp geni.repo /etc/yum.repos.d/
```

# Install portal

These must be done separately in order to fullfill the geni-portal
dependencies that are in the EPEL repository.

```bash
sudo yum install -y epel-release
sudo yum install -y --nogpgcheck geni-portal

```

-----

Below here needs work. Move this marker down as more of the instructions
get fleshed out.

-----

# If needed, add an entry (CNAME) for the host in /etc/hosts

Explain this. Need a fully qualified domain name.

# 3. InstallSHIB SW [This should be done from an RPM...]
# <*** From development machine ***>
export PORTAL_HOST=aptvm072-1.apt.emulab.net
cd ~/shib
/usr/bin/rsync --delete --delete-excluded -aztv --exclude .git --exclude '*~' \
               --exclude '#*#' --exclude '.#*' ../shib $PORTAL_HOST:


# 3b. Prep shib. No longer need prep-shib-centos.sh
ln -s ~/shib /tmp

# 3c. Install Embedded Discovery Service
cd /tmp
wget https://github.com/GENI-NSF/geni-eds/releases/download/v1.1.0-geni.3/shibboleth-embedded-ds-1.1.0-geni.3.tar.gz
tar xvfz shibboleth-embedded-ds-1.1.0-geni.3.tar.gz
cd shibboleth-embedded-ds-1.1.0-geni.3
sudo mkdir -p /var/www/eds
sudo cp *.css *.js *.html *.gif *.png /var/www/eds

# 4. Set up Variables
sudo cp /usr/share/geni-ch/templates/parameters.json \
        /etc/geni-ch/parameters.json
# Edit parameters.json [Especially note portal_host, ch_host and db_host]
sudo /sbin/geni-portal-install-templates

# 5. Install and run Shib SP
sudo /tmp/install-sp-centos.sh

# 6. Set up SP with IDP
# <*** From Development machine *** >

export IDP_HOST=cetaganda.gpolab.bbn.com
wget https://$PORTAL_HOST/Shibboleth.sso/Metadata --no-check-certificate
scp Metadata $IDP_HOST:/tmp/$PORTAL_HOST-metadata.xml

# <*** From $IDP_HOST ***>

If adding a new server add an entry like this to
/opt/shibboleth-idp/conf/relying-party.xml:

  <metadata:MetadataProvider xsi:type="FilesystemMetadataProvider"
    xmlns="urn:mace:shibboleth:2.0:metadata"
    id="$PORTAL_HOST-metadata"
    metadataFile="/opt/shibboleth-idp/metadata/$PORTAL_HOST-metadata.xml"/>

sudo cp /tmp/$PORTAL_HOST-metadata.xml /opt/shibboleth-idp/metadata
sudo service tomcat6 restart


# Install GENI PORTAL tables
DB_HOST=`geni-portal-install-templates --print_parameter db_host`
DB_USER=`geni-portal-install-templates --print_parameter db_user`
DB_DATABASE=`geni-portal-install-templates --print_parameter db_name`
DB_PASSWORD=`geni-portal-install-templates --print_parameter db_pass`
PSQL="psql -U $DB_USER -h $DB_HOST $DB_DATABASE"
echo "$DB_HOST:*:$DB_DATABASE:$DB_USER:$DB_PASSWORD"  > ~/.pgpass
chmod 0600 ~/.pgpass

$PSQL -f /usr/share/geni-ch/portal/db/postgresql/schema.sql
$PSQL -f /usr/share/geni-ch/km/db/postgresql/schema.sql

# Install km and portal certs from CH machine
CH_HOST=`geni-portal-install-templates --print_parameter ch_host`
scp $CH_HOST:/usr/share/geni-ch/portal/portal-*.pem /tmp
scp $CH_HOST:/usr/share/geni-ch/km/km-*.pem /tmp

sudo cp /tmp/portal-*.pem /usr/share/geni-ch/portal
sudo cp /tmp/km-*.pem /usr/share/geni-ch/km

# Insert portal as recognized CH tool
PORTAL_URN="urn:publicid:IDN+$CH_HOST+authority+portal"
$PSQL -c "insert into ma_client (client_name, client_urn) values ('portal', '$PORTAL_URN')"

sudo systemctl restart httpd.service
