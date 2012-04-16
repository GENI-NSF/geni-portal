#!/bin/bash

# List assertions for given user
# Usage: list_assertions.sh username

if [ $# -lt 1 ]; then
    echo "Usage: list_assertions.sh person_name"
exit
fi

PERSON=$1
FILENAME="/tmp/la.$USER.sql";

# List assertions with no context
echo "Context-free Assertions:"
echo "select account.username, cs_attribute.name, cs_context_type.name " > $FILENAME
echo " from account, cs_attribute, cs_context_type, cs_assertion " >> $FILENAME
echo " where account.username = '$PERSON'" >> $FILENAME
echo " and account.account_id = cs_assertion.principal " >> $FILENAME
echo " and cs_attribute.id = cs_assertion.attribute " >> $FILENAME
echo " and cs_context_type.id = cs_assertion.context_type" >> $FILENAME
echo " and cs_assertion.context_type > 2" >> $FILENAME
psql -U portal -h localhost < $FILENAME

# List project assertions
echo ""
echo "Project assertions"
echo "select account.username, cs_attribute.name, cs_context_type.name, pa_project.project_name " > $FILENAME
echo " from account, cs_attribute, cs_context_type, cs_assertion, pa_project " >> $FILENAME
echo " where account.username = '$PERSON'" >> $FILENAME
echo " and cs_context_type.id = cs_assertion.context_type" >> $FILENAME
echo " and account.account_id = cs_assertion.principal " >> $FILENAME
echo " and cs_attribute.id = cs_assertion.attribute " >> $FILENAME
echo " and cs_assertion.context_type = 1 " >> $FILENAME
echo " and pa_project.project_id = cs_assertion.context " >> $FILENAME
psql -U portal -h localhost < $FILENAME

# List slice  assertions
echo ""
echo "Slice assertionss"
echo "select account.username, cs_attribute.name, cs_context_type.name, sa_slice.slice_name " > $FILENAME
echo " from account, cs_attribute, cs_context_type, cs_assertion, sa_slice " >> $FILENAME
echo " where account.username = '$PERSON'" >> $FILENAME
echo " and account.account_id = cs_assertion.principal " >> $FILENAME
echo " and cs_context_type.id = cs_assertion.context_type" >> $FILENAME
echo " and cs_attribute.id = cs_assertion.attribute " >> $FILENAME
echo " and cs_assertion.context_type = 2 " >> $FILENAME
echo " and sa_slice.slice_id = cs_assertion.context " >> $FILENAME
psql -U portal -h localhost < $FILENAME

#rm $FILENAME


