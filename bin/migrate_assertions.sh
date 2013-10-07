#!/bin/bash

# Move all context-free assertions (assertions about users that aren't
# connected to a slice or project context)
# to the MA_MEMBER_ATTRIBUTE table

# There are essentially two cases:
# ATTRIBUTE = 1 (LEAD), CONTEXT_TYPE = 3 (RESOURCE) => "PROJECT_LEAD"
# ATTRIBUTE = 5 (OPERATOR), CONTEXT_TYPE = 3 (RESOURCE) => "OPERATOR"

# MA_MEMBER_ATTRIBUTE
# id : integer
# member_id : uuid
# name : string
# value : string
# self_asserted : Boolean


# Move all PROJECT_LEAD (ATT=1, CT = 3) assertions into MA_MEMBER_ATTRIBUTE
# as 'project_lead: true' attribute
export NOW=`date +%m_%d_%Y+%H_%m_%S`
export FILENAME="/tmp/migrate_$NOW.sql"
echo "insert into ma_member_attribute (member_id, name, value, self_asserted)" > $FILENAME
echo " select principal, 'PROJECT_LEAD', 'true', 'f' from cs_assertion " >>$FILENAME
echo " where context is null and attribute = 1 and context_type = 3;" >>$FILENAME

# Move all OPERATOR (ATT = 5, CT = 3) assertions into MA_MEMBER_ATTRIBUTE
# as 'operator: true' attribute
echo "insert into ma_member_attribute (member_id, name, value, self_asserted)" >> $FILENAME
echo " select principal, 'OPERATOR', 'true', 'f' from cs_assertion " >>$FILENAME
echo " where context is null and attribute = 5 and context_type = 3;" >>$FILENAME
psql -U portal -h localhost portal < $FILENAME

/bin/rm $FILENAME
