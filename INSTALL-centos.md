# Installation on CentOS 7

# Introduction

For installing the GENI Portal Software, shell windows on three servers are required:

 * The Portal host
 * The IdP host
 * The development host (from which the user can scp from/to the other hosts)

Unless specified otherwise, all commands are to be done on the Portal host.

In addition, these environment variables must be defined on the appropriate windows referring to the addresses of the given hosts:

 * $PORTAL_HOST : the address of the host on which we're installing the GENI Portal
 * $CH_HOST : the address of the GENI Clearinghouse to which the Portal is being associated
 * $IDP_HOST : The address of the IdP (Identity Provider) to which the Portal is being associated

# Install Portal Software

Ensure OS is up to date

```bash
sudo yum update -y
```

Check the status of SELinux:

```Shell
$ sestatus
SELinux status:                 disabled
```

If SELinux is enabled, do this:
```Shell
sudo sed -i -e "s/SELINUX=enforcing/SELINUX=disabled/g" /etc/selinux/config
sudo reboot
```
Install NTP:

```bash
sudo yum install ntp -y
```
Enable and start NTP

```bash
sudo systemctl enable ntpd
sudo systemctl start ntpd
```
Test it out
```bash
ntpq -p
```

Add Shibboleth repository:

```bash
wget http://download.opensuse.org/repositories/security://shibboleth/CentOS_7/security:shibboleth.repo
sudo cp security\:shibboleth.repo /etc/yum.repos.d/
```

Install the EPEL release

The GENI software depends on
[Fedora Extra Packages for Enterprise Linux (EPEL)](https://fedoraproject.org/wiki/EPEL)
packages. To install EPEL:

```bash
sudo yum install -y epel-release
```

Install GENI Tools

GENI Tools RPMs are available on [GitHub](https://github.com).
`yum` can download and install these RPMs.

_N.B. The link in the example below may not be the latest RPM.
You can find the URL of the latest RPM at_
https://github.com/GENI-NSF/geni-tools/releases/latest

```Shell
sudo yum install -y \
    https://github.com/GENI-NSF/geni-tools/releases/download/v2.9/geni-tools-2.9-1.el7.centos.noarch.rpm
```

Install GENI Portal software

GENI Portal RPMs are available on [GitHub](https://github.com).
`yum` can download and install these RPMs.

_N.B. The link in the example below may not be the latest RPM.
You can find the URL of the latest RPM at_
https://github.com/GENI-NSF/geni-portal/releases/latest

```Shell
sudo yum install -y \
    https://github.com/GENI-NSF/geni-portal/releases/download/v3.26/geni-portal-3.26-1.el7.centos.noarch.rpm
```


```bash
# If there are updates on a development machine not in the RPM, do this:

# On development machine:
rsync --delete --delete-excluded -aztv --exclude .git --exclude '*~' --exclude '#*#' \
--exclude '.#*' ~/geni-portal $PORTAL_HOST:

# On portal host:
sudo yum install -y texinfo # One-time
ln -s ~/geni-portal ~/proto-ch # One-time
 ~/geni-portal/bin/do-make-install.sh
```


```bash
# *** BBN ONLY ***
# IF using an APT Centos7 image, do this:
sudo yum reinstall -y polkit\* power
sudo reboot
```



Map public facing IP address to fully-qualified domain name:

```bash
cp /etc/hosts /tmp/hosts
echo "`hostname -i`  `hostname -f`" >> /tmp/hosts
sudo cp /tmp/hosts /etc/hosts
```


# 3. Install Shibboleth Software

## 3a. Edit shibboleth attribute-map.xml

Edit `/etc/shibboleth/attribute-map.xml` and uncomment the block
of <Attribute> entries below the following line:

    <!-- Examples of LDAP-based attributes, uncomment to use these ... -->

## 3b. Install Embedded Discovery Service (EDS)
```bash
sudo yum install -y shibboleth-embedded-ds
```

## 3c. Edit Shibboleth EDS Apache configuration

There is a bug in the Shibboleth EDS configuration file for Apache on
CentOS 7. In `/etc/httpd/conf.d/shibboleth-ds.conf`, change the line:

    Allow from all

To:

    Require all granted

## 3d. Edit Shibboleth EDS config file

Edit the file `/etc/shibboleth-ds/idpselect_config.js` and set the
`helpURL` to a valid web page or email link.

# 4. Set up Variables
```bash
sudo cp /usr/share/geni-ch/templates/parameters.json \
        /etc/geni-ch/parameters.json
# Edit /etc/geni-ch/parameters.json [Especially note portal_host, ch_host, db_host and idp_host]
sudo /sbin/geni-portal-install-templates
```

# 5. Install and run Shib SP
```bash
sudo /tmp/install-sp-centos.sh
```

# 6. Set up SP with IDP

```bash
# On development host:
wget -O /tmp/Metadata https://$PORTAL_HOST/Shibboleth.sso/Metadata --no-check-certificate
scp /tmp/Metadata $IDP_HOST:/tmp/$PORTAL_HOST-metadata.xml
```

```
# On IDP host:
# If adding a new server, add an entry like this to
# /opt/shibboleth-idp/conf/relying-party.xml:

  <metadata:MetadataProvider xsi:type="FilesystemMetadataProvider"
    xmlns="urn:mace:shibboleth:2.0:metadata"
    id="$PORTAL_HOST-metadata"
    metadataFile="/opt/shibboleth-idp/metadata/$PORTAL_HOST-metadata.xml"/>


sudo cp /tmp/$PORTAL_HOST-metadata.xml /opt/shibboleth-idp/metadata
sudo service tomcat6 restart
```

```
# On development host:
scp $IDP_HOST:/opt/shibboleth-idp/metadata/idp-metadata.xml  /tmp/idp-metadata-$IDP_HOST.xml
scp /tmp/idp-metadata-$IDP_HOST.xml $PORTAL_HOST:/tmp
```

```
# On portal host:
# Add host-specific extensions to IDP metadata for GENI logo, name, etc.
sed -e "/<Extensions>/r /tmp/idp-metadata-extension.xml" /tmp/idp-metadata-$IDP_HOST.xml > /tmp/idp-metadata-$IDP_HOST.extended.xml
sudo cp /tmp/idp-metadata-$IDP_HOST.extended.xml /etc/shibboleth/idp-metadata-$IDP_HOST.xml
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

# 9. Disable HTTPD private tmp directory

The portal uses /tmp to communicate between the portal and launched
omni/stitcher commands. Depending on the installation, CentOS may enable
a private /tmp directory for httpd which will hide the necessary files
from launched omni/stitcher processes.

To disable private tmp directory for httpd, edit the file:

    /etc/systemd/system/multi-user.target.wants/httpd.service

and set `PrivateTmp` to false:

````
PrivateTmp=false
````

# 10. Enable HTTPD and SHIBD services to start at boot time

The following commands will enable the services to start at boot time:

````sh
sudo systemctl enable httpd.service

sudo systemctl enable shibd.service
````

The following commands will verify that the services are set to start
at boot time. These should report "enabled".

````sh
sudo systemctl is-enabled httpd.service

sudo systemctl is-enabled shibd.service
````

# 11. Restart HTTPD service

```sh
sudo systemctl restart httpd.service
```
