#!/bin/bash
# Activate account of given user as a potential proejct lead
#
# Usage: activate_project_lead.sh username

if [ $# -ne 1 ]; then
    echo "Usage: activate_project_lead.sh username"
    exit
fi

export p=`dirname $0`

$p/activate_account.sh $1
$p/create_assertion.sh $1 lead resource

exit
