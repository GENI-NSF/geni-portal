#!/bin/sh
# Must be run as root
cd /usr/share/geni-ch/portal/gcf
pkill -f -u root "python .*gcf-pgch"
ARGS="-c /usr/share/geni-ch/portal/gcf.d/gcf.ini"
ARGS="$ARGS -p 8443"
#ARGS="$ARGS --debug"
python ./src/gcf-pgch.py $ARGS >/var/log/gcf-pgch.log 2>&1 &


