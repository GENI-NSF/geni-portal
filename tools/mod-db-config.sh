#!/bin/sh

# script to ensure DB config changes for postgres are applied to turn
# off ssl and turn on utf8 encoding
# requires local postgresql.conf.patch file

# Exit on error
set -e
# Echo commands with variables expanded
set -x

if [ -f postgresql.conf.patch ]; then
  echo "Applying DB config from local postgresql.conf.patch"
else
  echo "Missing local postgresql.conf.patch"
  exit -1
fi

sudo /usr/bin/patch --backup -N -p 0 /etc/postgresql/8.4/main/postgresql.conf < postgresql.conf.patch
sudo service postgresql-8.4 restart
sudo service apache2 restart
