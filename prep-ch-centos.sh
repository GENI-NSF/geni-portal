#!/bin/sh

# A script to configure a GPO lab VM with the right stuff
# to allow the prototype clearinghouse to run.

set -x 
set -e 

SFA_TMP_FILES="/tmp/sfa.log /tmp/sfa_import.log"
/usr/bin/sudo /bin/touch ${SFA_TMP_FILES}
/usr/bin/sudo /bin/chmod 777 ${SFA_TMP_FILES}


#create a .pgpass
if [ ! -f ${HOME}/.pgpass ]
then
    echo "localhost:*:portal:portal:portal" > ${HOME}/.pgpass
    chmod 600 ${HOME}/.pgpass
fi


PVERSION=$(/usr/bin/psql --version | awk '/psql/ {print $3}')
echo ${PVERSION}
if [[ "${PVERSION}" = "8.4.20" ]]
then
  echo "Should be running postgres version 8.4.20"
else
  echo "You are not running postgres 8.4.20.  You need to edit postgresql.conf and pg_hba.conf for your new version."
  echo "This script relies on it being postgres 8.4.20."
  exit
fi


# Set postgres password
/usr/bin/sudo service postgresql initdb
/usr/bin/sudo service postgresql start
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
/usr/bin/sudo -u postgres /bin/cp postgres/8.4.20/postgresql.conf /var/lib/pgsql/data

#----------------------------------------------------------------------
# Patch pg_hba.conf to allow md5 authentication from anywhere
#----------------------------------------------------------------------
/usr/bin/sudo -u postgres /bin/cp postgres/8.4.20/pg_hba.conf /var/lib/pgsql/data

#----------------------------------------------------------------------
# Restart PostgreSQL
#----------------------------------------------------------------------
/usr/bin/sudo /sbin/service postgresql restart

#----------------------------------------------------------------------
# Create the portal user and db
#----------------------------------------------------------------------
/usr/bin/sudo -u postgres /usr/bin/createuser -S -D -R portal

echo
echo
echo "@@@@@@@@@@@@@@@@@@@@@"
echo "@"
echo "@ Please enter a new database password for the 'portal' user"
echo "@"
/usr/bin/sudo -u postgres /usr/bin/psql -c "\\password portal"

/usr/bin/sudo -u postgres /usr/bin/createdb portal

#----------------------------------------------------------------------
# All done.
#----------------------------------------------------------------------
exit 0
