#!/usr/bin/env python
# -*- Mode: python -*-
#
# ----------------------------------------------------------------------
# Copyright (c) 2015-2017 Raytheon BBN Technologies
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
# ----------------------------------------------------------------------

# ----------------------------------------------------------------------
# Interface routines for conecting to ORBIT Delegated Account Management API
# ----------------------------------------------------------------------

import io
import os
import subprocess
import sys
from syslog import syslog
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
    ERROR33 = "ERROR 33: Missing user's sshpublickey:"

    ALL = [ERROR1, ERROR2, ERROR3, ERROR4, ERROR5, ERROR5,
           ERROR6, ERROR7, ERROR8, ERROR9, ERROR10, ERROR11,
           ERROR12, ERROR17, ERROR20, ERROR21, ERROR22,
           ERROR30, ERROR31, ERROR32, ERROR33]


#  Class to manage interactions with ORBIT through
#  Delegated Account Management API
class ORBIT_Interface:

    def __init__(self, base_orbit_url):
        self._base_orbit_url = base_orbit_url
        self._DEVNULL = open(os.devnull, 'w')

    MAX_TIMEOUT = "20"  # Number of seconds to wait for a curl command to complete

    # Does given string contain one of the defined errors?
    # If so, return the error, otherwise, return None
    def find_error_code(self, str):
        for cd in CODES.ALL:
            if str.find(cd) >= 0:
                return cd
        return None

    # Return value from CURL on URL
    def get_curl_output(self, query_url):
        raw_curl_output = ""

        try:
            out_file, out_filename = tempfile.mkstemp()
            raw_curl_output = \
                subprocess.check_output(["timeout", self.MAX_TIMEOUT, "curl", "-k", query_url],
                                        stderr=out_file
                                        )
            os.unlink(out_filename)
        except subprocess.CalledProcessError as e:
            error_text = open(out_filename, 'r').read()
            os.unlink(out_filename)
            syslog("Error invoking CURL on %s: Error %s Msg %s" %
                   (query_url, e.returncode, error_text))
            sys.exit(e.returncode)
        return raw_curl_output

    # Invoke an ORBIT REST API call
    # Create url, make call via curl
    #   and exit if an (untolerated) error returned
    def invoke_orbit_method(self, method, args,
                            tolerated_error_codes=[]):
        url = "%s/%s?%s" % (self._base_orbit_url, method, args)
        output = self.get_curl_output(url)
        code = self.find_error_code(output)
        if code and code not in tolerated_error_codes:
            syslog("Error in %s call: %s" % (method, output))
            sys.exit(code)

    # Add ORBIT user to ORBIT group
    # NOTE: No error trying to add a user to a group to which it already exists
    def add_user_to_group(self, group_name, user_name):
        self.invoke_orbit_method("addMemberToGroup",
                                 "groupname=%s&username=%s" %
                                 (group_name, user_name))

    # Remove ORBIT user to ORBIT group
    def remove_user_from_group(self, group_name, user_name):
        self.invoke_orbit_method("removeMemberFromGroup",
                                 "groupname=%s&username=%s" %
                                 (group_name, user_name),
                                 [CODES.ERROR5])

    # Change ORBIT group admin
    def change_group_admin(self, group_name, admin_name):
        self.invoke_orbit_method("changeGroupAdmin",
                                 "groupname=%s&username=%s" %
                                 (group_name, admin_name))

    # Enable ORBIT user
    def enable_user(self, user_name):
        self.invoke_orbit_method("enableUser",
                                 "username=%s" % user_name,
                                 [CODES.ERROR5, CODES.ERROR6])

    # Disable ORBIT user
    def disable_user(self, user_name):
        self.invoke_orbit_method("disableUser",
                                 "username=%s" % user_name,
                                 [CODES.ERROR6, CODES.ERROR5])

    # Delete ORBIT group
    def delete_group(self, group_name):
        self.invoke_orbit_method("deleteGroup", "groupname=%s" % group_name,
                                 [CODES.ERROR7])


# Get ORBIT group/user info
    def get_orbit_groups_and_users(self):
        orbit_query_url = "%s/getGroupsAndUsers" % self._base_orbit_url
        orbit_info_raw = self.get_curl_output(orbit_query_url)
        orbit_groups, orbit_users = self.parse_group_user_info(orbit_info_raw)
        for group_name, group_info in orbit_groups.items():
            orbit_group_member_query_url = \
                "%s/getProjectMembers?groupname=%s" % \
                (self._base_orbit_url, group_name)
            orbit_member_info_raw = \
                self.get_curl_output(orbit_group_member_query_url)
            users = self.parse_group_members(orbit_member_info_raw)
            group_info['users'] = users
        return orbit_groups, orbit_users

    # Parse results from getGroupsAndUsers call
    def parse_group_user_info(self, orbit_info_raw):
        groups = {}
        users = []
        orbit_info = xml.dom.minidom.parseString(orbit_info_raw)
        group_nodes = orbit_info.getElementsByTagName('Group')
        user_nodes = orbit_info.getElementsByTagName('User')
        for group_node in group_nodes:
            group_name = group_node.getAttribute('groupname')
            group_admin = group_node.getAttribute('admin')
            groups[group_name] = {'admin': group_admin, 'users': []}
        for user_node in user_nodes:
            user_name = user_node.getAttribute('username')
            users.append(user_name)
        return groups, users

    # Parse results from getProjectMembers call
    def parse_group_members(self, orbit_info_raw):
        users = []
        orbit_info = xml.dom.minidom.parseString(orbit_info_raw)
        user_nodes = orbit_info.getElementsByTagName("User")
        for user_node in user_nodes:
            user_name = user_node.getAttribute('username')
            users.append(user_name)
        return users

    # Save group/admin/user data in LDIF
    def saveUser(self, user_ldif_data):

        save_user_url = "%s/saveUser" % self._base_orbit_url

        out_file, out_filename = tempfile.mkstemp()
        utf8_file = io.open(out_file, mode='w', encoding='utf8')
        utf8_file.write(user_ldif_data.decode('utf-8'))
        utf8_file.close()

        ldif_arg = "ldif=@%s" % out_filename
        output = ""
        try:
            err_file, err_filename = tempfile.mkstemp()
            output = subprocess.check_output(["timeout", self.MAX_TIMEOUT,
                                              "curl", "-k", "-PUT", "-H",
                                              "Content-type: multipart/form-data",
                                              "-F", ldif_arg, save_user_url],
                                             stderr=err_file)
            os.unlink(err_filename)
        except subprocess.CalledProcessError as e:
            error_text = open(err_filename, 'r'). read()
            syslog("Error invoking curl LDIF saveUser command: %s: Error %s Msg %s"
                   (save_user_url, e.returncode, error_text))
            os.unlink(err_fileame)
            sys.exit(e.returncode)

#        print "OUTPUT = %s" % output
        error_code = self.find_error_code(output)
        if error_code and error_code not in [CODES.ERROR1]:
            syslog("Error in saveUser call: %s" % output)
            sys.exit(error_code)

            os.unlink(out_filename)

# ----------------------------------------------------------------------
# Routines to create LDIF for project/group, user/member and admin
# ----------------------------------------------------------------------

    # DN base element defining ch.geni.net namespace
    def ldif_dn_base(self):
        return "dc=ch,dc=geni,dc=net"

    # DN element for group
    def ldif_dn_for_group(self, group_name):
        return "ou=%s,%s" % (group_name, self.ldif_dn_base())

    # DN element for user
    def ldif_dn_for_user(self, user_name, group_name):
        return "uid=%s,%s" % (user_name, self.ldif_dn_for_group(group_name))

    # LDIF for defining admin role for group
    def ldif_for_group_admin(self, group_name, lead_name, lead_group_name):
        lead_dn = self.ldif_dn_for_user(lead_name, lead_group_name)
        project_dn = self.ldif_dn_for_group(group_name)
        lead_ldif_template = \
            "# LDIF for the project lead\n" + \
            "dn: cn=admin,%s\n" + \
            "cn: admin\n" + \
            "objectclass: top\n" + \
            "objectclass: organizationalRole\n" + \
            "roleoccupant: %s\n"
        lead_ldif = lead_ldif_template % (project_dn, lead_dn)
        return lead_ldif

    # LDIF for defining group
    def ldif_for_group(self, group_name, group_description):
        group_ldif_template = \
            "# LDIF for a project\n" + \
            "dn: %s\n" +\
            "description: %s\n" + \
            "ou: %s\n" + \
            "objectclass: top\n" + \
            "objectclass: organizationalUnit\n"
        group_ldif = group_ldif_template % \
            (self.ldif_dn_for_group(group_name), group_description, group_name)
        return group_ldif

    # LDIF for defining user
    def ldif_for_user(self, user_name, group_name, user_prettyname,
                      user_givenname, user_email, user_sn, user_ssh_keys,
                      user_group_description, user_irodsname):

        irods_entry = ""
        if user_irodsname:
            irods_entry = "iRODSusername: %s\n" % user_irodsname

        ssh_entries = ""
        for i in range(len(user_ssh_keys)):
            prefix = "sshpublickey"
            if i > 0:
                prefix = "%s%d" % (prefix, i+1)
            ssh_entries = ssh_entries + \
                ("%s: %s\n" % (prefix, user_ssh_keys[i]))

        user_ldif_template = "# LDIF for user %s \n" + \
            "dn: %s\n" + \
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
            (user_name, self.ldif_dn_for_user(user_name, group_name),
             user_prettyname, user_givenname, user_email, user_sn,
             irods_entry, ssh_entries,
             user_name, user_group_description)
        return user_ldif
