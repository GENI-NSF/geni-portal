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

# Caution: assumes destination directories exist
if [ -d www ]; then
  for d in www/*; do
    dst=`basename ${d}`
    if [ ${dst} = "portal" ]; then
      # portal gets copied to secure
      dst=secure
    fi
    dst=/var/www/${dst}
    if [ ! -d ${dst} ]; then
      sudo /bin/mkdir ${dst}
    fi
    echo "Copying ${d} to ${dst}"
    sudo /bin/cp ${d}/* ${dst}
  done
fi
#check_errs $? "apt-get failed to update"

#----------------------------------------------------------------------
# Create and initialize the database
#----------------------------------------------------------------------

# CAUTION: This is a big hammer.
#sudo -u postgres dropdb portal

#sudo -u postgres createdb portal
#check_errs $? "createdb failed to create database portal"

psql -U portal -h localhost portal < db/portal-schema.sql
check_errs $? "schema portal-schema failed"

psql -U portal -h localhost portal < db/portal-data.sql
check_errs $? "data portal-data failed"

exit 0
