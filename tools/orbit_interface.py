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
import xml.dom.minidom

BASE_ORBIT_URL = "https://www.orbit-lab.org/delegatedAM"

# Return value from CURL on URL
def get_curl_output(url):
    DEVNULL = open(os.devnull, 'w')
    raw_curl_output = \
        subprocess.check_output(["curl", orbit_query_url], stderr=DEVNULL)
    return raw_curl_output


# Create ORBIT group
def create_orbit_group(group_name):
    print "CREATE ORBIT GROUP: %s" % group_name

# Delete ORBIT group
def delete_orbit_group(group_name):
    print "DELETE ORBIT GROUP: %s" % group_name

# Create ORBIT user
def create_orbit_user(user_name):
    print "CREATE ORBIT USER %s" % user_name
        
# Add member to ORBIT group
def add_member_to_group(user_name, user_orbit_name, group_name):
    print "ADD MEMBER %s(%s) TO ORBIT GROUP %s" % \
        (user_name, user_orbit_name, group_name)

# Remove member from ORBIT group
def remote_member_from_group(user, group_name):
    print "REMOVE MEMBER %s from ORBIT GROUP %s" %  (user, group_name)


# Get ORBIT group/user info
def get_orbit_groups():
    orbit_query_url = "%s/getGroupsAndUsers" % BASE_ORBIT_URL
    orbit_info_raw = get_curl_output(orbit_query_url)
    orbit_groups = parse_group_user_info(orbit_info_raw)
    return orbit_groups

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
       

