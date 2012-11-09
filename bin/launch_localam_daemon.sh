#!/bin/sh
# Must be run as root
cd /usr/share/geni-ch/portal/gcf
pkill -f -u root "python .*gcf-am"
ARGS="-c /usr/share/geni-ch/portal/gcf.d/gcf.ini --api-version 2"

#ARGS="$ARGS -p 8443"
#ARGS="$ARGS --debug"
python ./src/gcf-am.py $ARGS >/var/log/gcf-am.log 2>&1 &


