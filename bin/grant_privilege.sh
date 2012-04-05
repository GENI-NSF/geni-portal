#!/bin/bash
#Grant given privillege to given user
# Usage: grant_privilege.sh user privilege
# Example privileges are 'slice'
if [ $# -ne 2 ]; then
    echo "Usage: grant_privilege.sh username privilege"
    exit
else
    USERNAME=$1
    PRIVILEGE=$2
    echo "insert into account_privilege (account_id, privilege) select account_id, '$PRIVILEGE' from account where username='$USERNAME'" | psql -U portal -h localhost portal
fi
