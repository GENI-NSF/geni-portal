#!/bin/sh
# Must be run as root
cd /usr/share/geni-ch/portal/gcf
python ./src/gcf-pgch.py -c gcf.ini &>/var/log/gcf-pgch.log &


