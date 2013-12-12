#!/bin/sh

# Script to remove vestiges of the old clearinghouse
# Run with sudo

# Exit on error
set -e
# Echo commands with variables expanded
set -x

rm -rf /usr/share/geni-ch/authz
rm -rf /usr/share/geni-ch/cs/www
rm /usr/share/geni-ch/cs/apache2.conf
rm /usr/share/geni-ch/logging/apache2.conf
rm -rf /usr/share/geni-ch/logging/www
# uninstall geni-pgch service / package
apt-get remove geni-pgch
rm /usr/share/geni-ch/ma/apache2.conf
rm -rf /usr/share/geni-ch/ma/php
rm -rf /usr/share/geni-ch/ma/www
rm /usr/share/geni-ch/pa/apache2.conf
rm /usr/share/geni-ch/sa/apache2.conf
rm -rf /usr/share/geni-ch/sa/php
rm -rf /usr/share/geni-ch/sa/www
rm -rf /usr/share/geni-ch/sa/bin
rm /usr/share/geni-ch/sr/apache2.conf
rm -rf /usr/share/geni-ch/sr/www
