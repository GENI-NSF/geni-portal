#!/bin/sh

# A script to configure a GPO lab VM with the right stuff
# to allow the prototype clearinghouse to run.

check_errs()
{
  # Function. Parameter 1 is the return code
  # Para. 2 is text to display on failure.
  if [ "${1}" -ne "0" ]; then
    echo "ERROR # ${1} : ${2}"
    # as a bonus, make our script exit with the right error code.
    exit ${1}
  fi
}

PKGS="postgresql git-core apache2 php5-pgsql php-mdb2-driver-pgsql php5-uuid"

# Packages for gcf/omni
PKGS="$PKGS python-m2crypto python-dateutil python-pyopenssl"
PKGS="$PKGS libxmlsec1 xmlsec1 libxmlsec1-openssl libxmlsec1-dev"


/usr/bin/sudo /usr/bin/apt-get update
check_errs $? "apt-get failed to update"

/usr/bin/sudo /usr/bin/apt-get -y dist-upgrade
check_errs $? "apt-get failed to dist-upgrade"

/usr/bin/sudo /usr/bin/apt-get install -y ${PKGS}
check_errs $? "apt-get failed to install packages"

#
# gcf installation
#
SHARE_DIR=/usr/share/geni-portal

# Make a directory for gcf to live in
/usr/bin/sudo /bin/mkdir "${SHARE_DIR}"

GCF=gcf-1.3-rc1
GCF_PKG=${GCF}.tar.gz
/usr/bin/wget http://www.gpolab.bbn.com/internal/projects/proto-ch/${GCF_PKG}
/usr/bin/sudo /bin/tar xzfC "${GCF_PKG}" "${SHARE_DIR}"
/usr/bin/sudo /bin/ln -s ${SHARE_DIR}/${GCF} ${SHARE_DIR}/gcf

SFA_TMP_FILES="/tmp/sfa.log /tmp/sfa_import.log"
/usr/bin/touch ${SFA_TMP_FILES}
/bin/chmod 777 ${SFA_TMP_FILES}

/usr/bin/sudo /bin/cp -R gcf.d ${SHARE_DIR}

#
# Restart Apache to find the new php packages.
#
/usr/bin/sudo /usr/sbin/service apache2 restart

# Set postgres password
echo
echo
echo "@@@@@@@@@@@@@@@@@@@@@"
echo "@"
echo "@ Please enter a database password for the 'postgres' user"
echo "@"
/usr/bin/sudo -u postgres /usr/bin/psql -c \\password

# Enable postgres accessibility in postgres.conf and pg_hba.conf
#----------------------------------------------------------------------
# Patch postgresql.conf to listen for network connections
#----------------------------------------------------------------------
/usr/bin/sudo /usr/bin/patch --backup -p 0 /etc/postgresql/8.4/main/postgresql.conf <<EOF
--- postgresql.conf.orig	2011-01-07 13:20:50.840787089 -0500
+++ postgresql.conf	2011-01-07 13:23:10.984792098 -0500
@@ -60,6 +60,7 @@
 					# comma-separated list of addresses;
 					# defaults to 'localhost', '*' = all
 					# (change requires restart)
+listen_addresses = '*'
 port = 5432				# (change requires restart)
 max_connections = 100			# (change requires restart)
 # Note:  Increasing max_connections costs ~400 bytes of shared memory per 
EOF
check_errs $? "failed to patch postgresql.conf"

#----------------------------------------------------------------------
# Patch pg_hba.conf to allow md5 authentication from anywhere
#----------------------------------------------------------------------
/usr/bin/sudo /usr/bin/patch --backup -p 0 /etc/postgresql/8.4/main/pg_hba.conf <<EOF
--- pg_hba.conf.orig	2011-01-07 13:21:05.936789520 -0500
+++ pg_hba.conf	2011-01-07 13:31:12.880789053 -0500
@@ -84,3 +84,4 @@
 host    all         all         127.0.0.1/32          md5
 # IPv6 local connections:
 host    all         all         ::1/128               md5
+host    all         all         0.0.0.0/0             md5
EOF
check_errs $? "failed to patch pg_hba.conf"

#----------------------------------------------------------------------
# Restart PostgreSQL
#----------------------------------------------------------------------
/usr/bin/sudo /usr/sbin/service postgresql-8.4 restart
check_errs $? "failed to restart postgresql-8.4"

#----------------------------------------------------------------------
# Create the portal user and db
#----------------------------------------------------------------------
/usr/bin/sudo -u postgres /usr/bin/createuser -S -D -R portal
check_errs $? "failed to create user portal"

echo
echo
echo "@@@@@@@@@@@@@@@@@@@@@"
echo "@"
echo "@ Please enter a new database password for the 'portal' user"
echo "@"
/usr/bin/sudo -u postgres /usr/bin/psql -c "\\password portal"
check_errs $? "failed to set the password for portal user"

/usr/bin/sudo -u postgres /usr/bin/createdb portal
check_errs $? "failed to create the portal database"

#----------------------------------------------------------------------
# All done.
#----------------------------------------------------------------------
exit 0
