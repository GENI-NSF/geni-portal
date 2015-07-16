#!/bin/bash
# Disable an account of given user
#
# Usage: disable_account.sh username

if [ $# -ne 1 ]; then
    echo "Usage: disable_account.sh username"
    exit
else
    USERNAME=$1
    echo "update account set status = 'disabled' where username = '$USERNAME'" | psql -U portal -h localhost portal
fi