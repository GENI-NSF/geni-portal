#!/bin/sh
# Must be run as root
cd /usr/share/geni-ch/portal/gcf
pkill -f -u root "python .*gcf-pgch"
python ./src/gcf-pgch.py -c /usr/share/geni-ch/portal/gcf.d/gcf.ini >/var/log/gcf-pgch.log 2>&1 &


