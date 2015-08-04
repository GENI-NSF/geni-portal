#!/usr/bin/env python
# -*- Mode: python -*-
#
#----------------------------------------------------------------------
# Copyright (c) 2013-2015 Raytheon BBN Technologies
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and/or hardware specification (the "Work") to
# deal in the Work without restriction, including without limitation the
# rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Work, and to permit persons to whom the Work
# is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Work.
#
# THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
# OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
# HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
# WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
# IN THE WORK.
#----------------------------------------------------------------------

#----------------------------------------------------------------------
# Interface routines for conecting to ORBIT Delegated Account Management API
#----------------------------------------------------------------------

import os
import subprocess
import sys
import tempfile
import xml.dom.minidom


# Orbit Delegated Account Management API Error codes
# As of 6/25/2014
class CODES:

    # Means portal tried to add a user that already exists. Handled explicitly.
    ERROR1 = 'ERROR 1: UID and OU and DC match'

    # Means trying to change project. 
    # Shouldn't happen, but code checks for this
    ERROR2 = 'ERROR 2: UID and DC match but OU is different'

    # Member username exists from different authority. 
    # Code tries to pick a different username.
    ERROR3 = 'ERROR 3: UID matches but DC and OU are different'

    # Seems to imply that group ou must be unique for both 
    # local and portal created groups? Huh?
    # FIXME FIXME
    ERROR4 = 'ERROR 4: UID and OU match but DC is different'


    # Handled explicitly when trying to deleteUser, changeLeader, changeProject
    ERROR5 = 'ERROR 5: Unknown username:'

    # Handled explicity in deleteUser
    ERROR6 = 'ERROR 6: Cannot delete user: User is a admin for'

    # Handled explicitly in deleteProject, changeLeader, changeProject
    ERROR7 = 'ERROR 7: Unknown group name'

    # Handled explicitly in deleteProject
    ERROR8 = 'ERROR 8: Group/project not deleted because it contains admin(s):'

    # Theoretically could happen from changeProject if I'm trying to move a 
    # user not created by the portal to a different project. Shouldn't happen.
    ERROR9 = 'ERROR 9: Cannot move users: different DCs'

    # Malformed LDIF
    ERROR10 = 'ERROR 10: Missing OU LDIF entry'

    # Malformed LDIF
    ERROR11 = 'ERROR 11: Missing group name attribute in OU entry'

    # Malformed LDIF
    ERROR12 = 'ERROR 12: Missing objectClass attribute (organizationalUnit/organizationalRole/organizationalUnit) for'

    # Attempt to create a group without an admin, or with unknown admin 
    ERROR17 = 'ERROR 17: Missing PI entry'

    # Tried to create a group that already exists.
    # FIXME: Mostly handled below, but perhaps could be better.
    ERROR20 = 'ERROR 20: Group exists'

    # Malformed LDIF. Note all users must have an email address.
    ERROR21 = 'ERROR 21: Missing PI mail:'

    # Malformed LDIF. Note we explicitly require users have an SSH key.
    ERROR22 = 'ERROR 22: Missing PI ssh public key:'

    # Malformed LDIF.
    ERROR30 = 'ERROR 30: Missing username (UID)'

    # FIXME: I need to handle this (see comments below). 
    # This means I tried to add a user to a group that doesn't exist.
    ERROR31 = 'ERROR 31: Organization does not exist for this user. Missing organization LDIF entry'

    # Malformed LDIF. Not all portal users must have an email address. 
    ERROR32 = 'ERROR 32: Missing users email address'

    # Malformed LDIF. Note we explicitly require users have an SSH key.
    ERROR33 = 'ERROR 33: Missing users ssh public key:'

    ALL = [ERROR1, ERROR2, ERROR3, ERROR4, ERROR5, ERROR5, 
           ERROR6, ERROR7, ERROR8, ERROR9, ERROR10, ERROR11,
           ERROR12, ERROR17, ERROR20, ERROR21, ERROR22, 
           ERROR30, ERROR31, ERROR32, ERROR33]

# Does given string contain one of the defined errors?
# If so, return the error, otherwise, return None
def find_error_code(str):
    for cd in CODES.ALL:
        if str.find(cd) >= 0:
            return cd;
    return None

BASE_ORBIT_URL = "https://www.orbit-lab.org/delegatedAM"

# Return value from CURL on URL
def get_curl_output(query_url):
    DEVNULL = open(os.devnull, 'w')
    raw_curl_output = \
        subprocess.check_output(["curl", "-k", query_url], stderr=DEVNULL)
    return raw_curl_output

# Invoke an ORBIT REST API call
# Create url, make call via curl and exit if an (untolerated) error returned
def invoke_orbit_method(method, args, tolerated_error_codes = []):
    url = "%s/%s?args" % (BASE_ORBIT_URL, method, args)
    output = get_curl_output(url)
    code = find_error_code(output)
    if code and code not in tolerated_error_codes:
        print "Error in %s call: %s" % (method, output)
        sys.exit(0)
    

# Add ORBIT user to ORBIT group
# NOTE: No error trying to add a user to a group to which it already exists
def add_user_to_group(group_name, user_name):
    invoke_orbit_method("addMemberToGroup", 
                        "groupname=%s&username=%s" %  (group_name, user_name))

# Change ORBIT group admin
def change_group_admin(group_name, admin_name):
    invoke_orbit_method("changeGroupAdmin", 
                        "groupname=%s&username=%a" % (group_name, admin_name))

# Enable ORBIT user
def enable_user(user_name):
    invoke_orbit_method("enableUser", 
                        "username=%s" % user_name, [CODES.ERROR6])

# Get ORBIT group/user info
def get_orbit_groups_and_users():
    orbit_query_url = "%s/getGroupsAndUsers" % BASE_ORBIT_URL
    orbit_info_raw = get_curl_output(orbit_query_url)
    orbit_groups = parse_group_user_info(orbit_info_raw)
    orbit_users = set()
    for group_name, group_info in orbit_groups.items():
        orbit_group_member_query_url = "%s/getProjectMembers?groupname=%s" %\
            (BASE_ORBIT_URL, group_name)
        orbit_member_info_raw = get_curl_output(orbit_group_member_query_url)
        users = parse_group_members(orbit_member_info_raw)
        for user in users:
            orbit_users.add(user)
        group_info['users'] = users
    return orbit_groups, list(orbit_users)

# Parse results from getGroupsAndUsers call
def parse_group_user_info(orbit_info_raw):
    groups = {}
    orbit_info = xml.dom.minidom.parseString(orbit_info_raw)
    group_nodes = orbit_info.getElementsByTagName('Group')
    user_nodes = orbit_info.getElementsByTagName('User')
    for group_node in group_nodes:
        group_name = group_node.getAttribute('groupname')
        group_admin = group_node.getAttribute('admin')
        groups[group_name] = {'admin' : group_admin, 'users' : []}
    for user_node in user_nodes:
        user_name = user_node.getAttribute('username')
        group_name = user_node.getAttribute('groupname')
        if group_name not in groups:
            print "Group %s for user %s not defined" % \
                (group_name, user_name)
        group = groups[group_name]['users'].append(user_name)
    return groups

# Parse results from getProjectMembers call
def parse_group_members(orbit_info_raw):
    users = []
    orbit_info = xml.dom.minidom.parseString(orbit_info_raw)
    user_nodes = orbit_info.getElementsByTagName("User")
    for user_node in user_nodes:
        user_name = user_node.getAttribute('username')
        users.append(user_name)
    return users

#----------------------------------------------------------------------
# Routines to create LDIF for project/group, user/member and admin
#----------------------------------------------------------------------

# DN base element defining ch.geni.net namespace
def ldif_dn_base():
    return "dc=ch,dc=geni,dc=net"

# DN element for group
def ldif_dn_for_group(group_name):
    return "ou=%s,%s" % (group_name, ldif_dn_base())

# DN element for user
def ldif_dn_for_user(user_name, group_name):
    return "uid=%s,%s" % (user_name, ldif_dn_for_group(group_name))

# LDIF for defining admin role for group
def ldif_for_group_admin(group_name,  lead_name, lead_group_name):
    lead_dn = ldif_dn_for_user(lead_name, lead_group_name)
    project_dn = ldif_dn_for_group(group_name)
    lead_ldif_template = \
        "# LDIF for the project lead\n" + \
        "dn: cn=admin,%s" + \
        "cn: admin\n" + \
        "objectclass: top\n" + \
        "objectclass: organizationalRole\n" + \
        "roleoccupant: %s\n"
    lead_ldif = lead_ldif_template % (project_dn, lead_dn)
    return lead_ldif

# LDIF for defining group
def ldif_for_group(group_name, group_description):
    group_ldif_template = \
        "# LDIF for a project\n" + \
        "dn: %s\n" +\
        "description: %s\n" + \
        "ou: %s\n" + \
        "objectclass: top\n" + \
        "objectclass: organizationalUnit\n"
    group_ldif = group_ldif_template % (ldif_dn_for_group(group_name), 
                                        group_description, group_name)
    return group_ldif

# LDIF for defining user
def ldif_for_user(user_name, group_name, user_prettyname, 
                  user_givenname, user_email, user_sn, user_ssh_keys, 
                  user_group_description, user_irodsname):
    
  irods_entry = ""
  if user_irodsname:
      irods_entry = "iRODSusername: %s\n" % user_irodsname

  ssh_entries = ""
  for i in range(len(user_ssh_keys)):
      prefix = "sshpublickey"
      if i > 0: prefix = "%s%d" % (prefix, i+1)
      ssh_entries = ssh_entries + ("%s: %s\n" % (prefix, user_ssh_keys[i]))

  user_ldif_template = "# LDIF for user %s \n" + \
      "dn: %s\n"  + \
      "cn: %s\n" + \
      "givenname: %s\n" + \
      "mail: %s\n" + \
      "sn: %s\n" + \
      "%s" + \
      "%s" + \
      "uid: %s\n" + \
      "o: %s\n" + \
      "objectclass: top\n" + \
      "objectclass: person\n" + \
      "objectclass: posixAccount\n" + \
      "objectclass: shadowAccount\n" + \
      "objectclass: inetOrgPerson\n" + \
      "objectclass: organizationalPerson\n" + \
      "objectclass: hostObject\n" + \
      "objectclass: ldapPublicKey\n"
  user_ldif = user_ldif_template % \
      (user_name, ldif_dn_for_user(user_name, group_name), 
       user_prettyname, user_givenname, user_email, user_sn,
       irods_entry, ssh_entries,
       user_name, user_group_description)
  return user_ldif

# Save group/admin/user data in LDF
def saveUser(user_ldif_data):
    
    save_user_url = "%s/saveUser" % BASE_ORBIT_URL 

    out_file, out_filename = tempfile.mkstemp()
    os.write(out_file, user_ldif_data)
    os.close(out_file)
    FNULL = open(os.devnull, 'w')

    ldif_arg = "ldif=@%s" % out_filename
    output = subprocess.check_output(["curl", "-PUT", "-H", 
                                      "Content-type: multipart/form-data", 
                                      "-F", ldif_arg, save_user_url],
                                     stderr=FNULL)
    error_code = find_error_code(output)
    if error_code and error_code not in [CODES.ERROR1]:
        print "Error in saveUser call: %s" % output
        sys.exit(0)

    os.unlink(out_filename)
       

