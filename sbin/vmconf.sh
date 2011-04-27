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

PKGS="postgresql git-core apache2 python-django"

/usr/bin/apt-get update
check_errs $? "apt-get failed to update"

/usr/bin/apt-get install -y ${PKGS}
check_errs $? "apt-get failed to install packages"

# Set postgres password
echo
echo
echo "@@@@@@@@@@@@@@@@@@@@@"
echo "@"
echo "@ Please enter a database password for the 'postgres' user"
echo "@"
/usr/bin/sudo -u postgres /usr/bin/psql -c \\password

# Enable postgres accessibility in postgres.conf and pg_hba.conf

exit 0
