#!/bin/bash
# Script to enter a portal/clearinghouse into 'sundown mode'
# Sundown mode means that new or existing slices (and thus slivers)
# cannot have expiration times that go beyond a specified date.

# The supplied datetime must be in the specified format, and is specified in local time

if [ $# -ne 1 ]
then
    echo "Usage: enable_sundown.sh \"yyyy-mm-dd hh:mm:ss\" (24 hour time in local timezone)"
    exit -1
fi

export sundown_time=$1

tz=`date +%Z`
export sundown_msg="This GENI Portal and Clearinghouse is being transitioned to portal.geni.net. Expirations for new or renewed slices or slivers can be no later than: $sundown_time $tz."

sudo ~/proto-ch/bin/geni-manage-maintenance --set-sundown "$sundown_msg" "$sundown_time"
