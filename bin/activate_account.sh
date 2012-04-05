#!/bin/bash
# Activate an account of given user
#
# Usage: activate_account.sh username

if [ $# -ne 1 ]; then
    echo "Usage: activate_account.sh username"
    exit
else
    USERNAME=$1
    echo "update account set status = 'active' where username = '$USERNAME'" | psql -U portal -h localhost portal
fi