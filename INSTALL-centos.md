# Introduction

For installing the GENI Portal Software, shell windows on three servers are required:
 * The Portal host 
 * The IdP host
 * The development host (from which the user can scp from/to the other hosts)

Unless specified otherwise, all commands are to be done on the Portal host.

In addition, these environment variables must be defined on the appropriate windows referring to the addresses of the given host:
 * $PORTAL_HOST : the address of the host on which we're installing the GENI Portal
 * $CH_HOST : the address of the GENI Clearinghouse to which the Portal is being associated
 * $IDP_HOST : The address of the IdP (Identity Provider) to which the Portal is being associated

# Install Portal Software

Ensure OS is up to date

```bash
sudo yum update -y
```

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

Install GENI portal software

These must be done separately in order to fullfill the geni-portal
dependencies that are in the EPEL repository.

```bash
sudo yum install -y epel-release
sudo yum install -y --nogpgcheck geni-portal

```

# If using an APT Centos7 image, do this:
```bash
sudo yum reinstall polkit\* power

sudo reboot
```


Map public facing IP address to fully-qualified domain name:

```bash
cp /etc/hosts /tmp/hosts
echo "`hostname -i`  `hostname -f`" >> /tmp/hosts
sudo cp /tmp/hosts /etc/hosts
```


# 3. Install Shibboleth Software [This should be done from an RPM...]
<*** From development machine ***>
For now, we're not copying, just seeing what we need in subsequent steps.
```bash
export PORTAL_HOST=`hostname -f`
cd ~/shib
/usr/bin/rsync --delete --delete-excluded -aztv --exclude .git --exclude '*~' \
               --exclude '#*#' --exclude '.#*' ../shib $PORTAL_HOST:
```

# 3a. Edit shibboleth attribute-map.xml
Edit /etc/shibboleth/attribute-map.xml and uncomment the block of <Attribute> entries
below the "<!-- Examples of LDAP-based attributes, uncomment to use these ... -->

# 3b. Prep shib. No longer need prep-shib-centos.sh
ln -s ~/shib /tmp

# 3c. Install Embedded Discovery Service
```bash
cd /tmp
wget https://github.com/GENI-NSF/geni-eds/releases/download/v1.1.0-geni.3/shibboleth-embedded-ds-1.1.0-geni.3.tar.gz
tar xvfz shibboleth-embedded-ds-1.1.0-geni.3.tar.gz
cd shibboleth-embedded-ds-1.1.0-geni.3
sudo mkdir -p /var/www/eds
sudo cp *.css *.js *.html *.gif *.png /var/www/eds

```

# 4. Set up Variables
```bash
sudo cp /usr/share/geni-ch/templates/parameters.json \
        /etc/geni-ch/parameters.json
# Edit /etc/geni-ch/parameters.json [Especially note portal_host, ch_host and db_host]
sudo /sbin/geni-portal-install-templates
```

# 5. Install and run Shib SP
```bash
sudo /tmp/install-sp-centos.sh
```

# 6. Set up SP with IDP

```bash
# On development host:
export IDP_HOST=cetaganda.gpolab.bbn.com
wget https://$PORTAL_HOST/Shibboleth.sso/Metadata --no-check-certificate
scp Metadata $IDP_HOST:/tmp/$PORTAL_HOST-metadata.xml
```

On IDP host:
If adding a new server, add an entry like this to
/opt/shibboleth-idp/conf/relying-party.xml:

```
  <metadata:MetadataProvider xsi:type="FilesystemMetadataProvider"
    xmlns="urn:mace:shibboleth:2.0:metadata"
    id="$PORTAL_HOST-metadata"
    metadataFile="/opt/shibboleth-idp/metadata/$PORTAL_HOST-metadata.xml"/>
```

```bash
sudo cp /tmp/$PORTAL_HOST-metadata.xml /opt/shibboleth-idp/metadata
sudo service tomcat6 restart
```

```
# On portal host:
scp $IDP_HOST:/opt/shibboleth-idp/metadata/idp-metadata.xml  /etc/shibboleth/$IDP_HOST-idp-metadata.xml 
```

Edit /etc/shibboleth/shibboleth2.xml to add <MetadataProvider> data for IDP:

```
       <!-- trust the identity provider at $IDP_HOST -->
        <MetadataProvider type="XML"
                          file="idp-metadata-$IDP_HOST.xml"/>
```



# 7. Install GENI PORTAL tables
```bash
DB_HOST=`geni-portal-install-templates --print_parameter db_host`
DB_USER=`geni-portal-install-templates --print_parameter db_user`
DB_DATABASE=`geni-portal-install-templates --print_parameter db_name`
DB_PASSWORD=`geni-portal-install-templates --print_parameter db_pass`
PSQL="psql -U $DB_USER -h $DB_HOST $DB_DATABASE"
echo "$DB_HOST:*:$DB_DATABASE:$DB_USER:$DB_PASSWORD"  > ~/.pgpass
chmod 0600 ~/.pgpass

$PSQL -f /usr/share/geni-ch/portal/db/postgresql/schema.sql
$PSQL -f /usr/share/geni-ch/km/db/postgresql/schema.sql
```

# 8. Install km and portal certs from CH machine
```bash
# On Development Host:
scp $CH_HOST:/usr/share/geni-ch/portal/portal-*.pem /tmp
scp $CH_HOST:/usr/share/geni-ch/km/km-*.pem /tmp
scp /tmp/portal-*.pem /tmp/km-*.pem $PORTAL_HOST:/tmp

# On Portal Host:
sudo cp /tmp/portal-*.pem /usr/share/geni-ch/portal
sudo cp /tmp/km-*.pem /usr/share/geni-ch/km
```

# 9. Insert portal as recognized CH tool
```bash
PORTAL_URN="urn:publicid:IDN+$CH_HOST+authority+portal"
$PSQL -c "insert into ma_client (client_name, client_urn) values ('portal', '$PORTAL_URN')"
```

# 10. Restart HTTPD service
```bash
sudo systemctl restart httpd.service
```
