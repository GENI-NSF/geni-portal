#!/bin/bash
# list all accounts of status requested

echo "Requested Accounts:"
echo "select username from account where status = 'requested'" | psql -U portal -h localhost portal


