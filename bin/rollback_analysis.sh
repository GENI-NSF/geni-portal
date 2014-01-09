#!/bin/bash


#----------------------------------------------------------------------
# OPERATORS
#----------------------------------------------------------------------

#Select the distinct operator privileges in MA_MEMBER_ATTRIBUTE table

echo "select distinct member_id from ma_member_attribute where name = 'OPERATOR' and value = 'true'" | psql -t -U portal -h localhost portal | sort > /tmp/all_operators_maa.txt

# Select the distinct operator privileges in CS_ASSERTION table

echo "select distinct principal from cs_assertion  where attribute = 5" | psql -U portal -t -h localhost portal | sort >  /tmp/all_operators_cs.txt

echo "-- Changes in Operators between ma_member_attribute and cs_assertion"

python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 5, 1);" \
   --delete_template "delete from cs_assertion where principal = '%s' and attribute = 5 and context_type = 1;"
python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 5, 2);" \
   --delete_template "delete from cs_assertion where principal = '%s' and attribute = 5 and context_type = 2;"
python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 5, 3);" \
   --delete_template "delete from cs_assertion where principal = '%s' and attribute = 5 and context_type = 3;"
python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 5, 4);" \
   --delete_template "delete from cs_assertion where principal = '%s' and attribute = 5 and context_type = 4;"
python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 5, 5);" \
   --delete_template "delete from cs_assertion where principal = '%s' and attribute = 5 and context_type = 5;"

# Fix up ma_member_attribute table for operators
python compute_rollback_sql.py \
   --new_file /tmp/all_operators_maa.txt \
   --old_file /tmp/all_operators_cs.txt \
   --insert_template "insert into ma_member_privilege (member_id, privilege_id) values ('%s', 2);" \
   --delete_template "delete from ma_member_privilege where member_id = '%s' and privilege_id = 2;"


#----------------------------------------------------------------------
# PROJECT LEADS
#----------------------------------------------------------------------

echo "select distinct member_id from ma_member_attribute where name = 'PROJECT_LEAD' and value = 'true'" | psql -t -U portal -h localhost portal | sort > /tmp/all_leads_maa.txt

# Select the distinct operator privileges in CS_ASSERTION table

echo "select distinct principal from cs_assertion  where attribute = 1 and context_type = 3" | psql -U portal -t -h localhost portal | sort >  /tmp/all_leads_cs.txt

echo "-- Changes in Project leads between ma_member_attribute and cs_assertion"

python compute_rollback_sql.py  --new_file /tmp/all_leads_maa.txt --old_file /tmp/all_leads_cs.txt  --insert_template "insert into cs_assertion (principal, attribute, context_type) values ('%s', 1, 3);" --delete_template "delete from cs_assertion where principal = '%s' and attribute = 1 and context_type = 3;"

# Fix up ma_member_attribute table for project leads
python compute_rollback_sql.py \
   --new_file /tmp/all_leads_maa.txt \
   --old_file /tmp/all_leads_cs.txt \
   --insert_template "insert into ma_member_privilege (member_id, privilege_id) values ('%s', 1);" \
   --delete_template "delete from ma_member_privilege where member_id = '%s' and privilege_id = 1;"


#----------------------------------------------------------------------
# PROJECT MEMBERSHIP
#----------------------------------------------------------------------

echo "select member_id, project_id, role from pa_project_member where member_id is not null and project_id is not null" | psql -U portal -t -h localhost portal | sort > /tmp/all_project_members_maa.txt

echo "select principal, context, attribute from cs_assertion where context_type = 1 and principal is not null and context is not null" | psql -U portal -t -h localhost portal | sort > /tmp/all_project_members_cs.txt

echo "-- Changes in Project membership between pa_project_member and cs_assertion"

python compute_rollback_sql.py  --new_file /tmp/all_project_members_maa.txt --old_file /tmp/all_project_members_cs.txt  --insert_template "insert into cs_assertion (principal, context, attribute, context_type) values ('%s', '%s', '%s', 1);" --delete_template "delete from cs_assertion where principal = '%s' and context = '%s' and attribute = '%s' and context_type = 1;"


#----------------------------------------------------------------------
# SLICE MEMBERSHIP
#----------------------------------------------------------------------

echo "select member_id, slice_id, role from sa_slice_member where member_id is not null and slice_id is not null" | psql -U portal -t -h localhost portal | sort > /tmp/all_slice_members_maa.txt

echo "select principal, context, attribute from cs_assertion where context_type = 2 and principal is not null and context is not null" | psql -U portal -t -h localhost portal | sort > /tmp/all_slice_members_cs.txt

echo "-- Changes in Slice membership between sa_slice_member and cs_assertion"

python compute_rollback_sql.py  --new_file /tmp/all_slice_members_maa.txt --old_file /tmp/all_slice_members_cs.txt  --insert_template "insert into cs_assertion (principal, context, attribute, context_type) values ('%s', '%s', '%s', 2);" --delete_template "delete from cs_assertion where principal = '%s' and context = '%s' and attribute = '%s' and context_type = 2;"










