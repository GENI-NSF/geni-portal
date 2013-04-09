#!/bin/bash

# Mark a user that requested a GPO IdP account as having been
# created. Select by email address.
# Do this when infra has actually created the account. And then you
# sent the user email.

if [ $# -ne 1 ]; then
    echo "Usage: idp_user_created <email>"
    exit
fi

if [ "$1" = "" ]; then
    echo "Usage: idp_user_created <email>"
    exit
fi

if [ -f $HOME/.pgpass ]; then
    export PGPASSFILE="$HOME/.pgpass"
else
    export PGPASSFILE="/usr/local/etc/psql_password"
fi

echo "update idp_account_request set created_ts=now() where created_ts is null and email='$1'" | psql -U portal -h localhost portal
