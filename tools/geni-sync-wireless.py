#!/usr/bin/env python
# -*- Mode: python -*-
#
#----------------------------------------------------------------------
# Copyright (c) 2015 Raytheon BBN Technologies
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
# Reconcile current list of wimax-enabled projects and members of these
# projects in GENI Clearinghouse with the list of delegated groups/users
# in ORBIT Delegated Account management API
#
# Make take a project argument to only synch that project
# Otherwise, synch all wimax-enabled projects
#----------------------------------------------------------------------


import datetime
import logging
import xml.dom.minidom
import optparse
import sys
import orbit_interface as orb
from sqlalchemy import *
from sqlalchemy.orm import sessionmaker
from sqlalchemy.ext.declarative import declarative_base

def parse_args(argv):
    parser = optparse.OptionParser(usage="Synchronize ORBIT and GENI CH " + 
                                   "sense of projects/groups and members")
    parser.add_option("--debug", action="store_true", default=False,
                       help="enable debugging output")
    parser.add_option("--database_url", help="CH database url") 
    parser.add_option("--holdingpen_group", 
                      help="Name of ORBIT 'holding pen' that is the primary"+\
                      " group for all GENI users in wimax-enabled projects", 
                      default="geni-HOLDINGPEN")
    parser.add_option("--holdingpen_admin", 
                      help="GENI username of admin of ORBIT 'holding pen'",
                      default="seskar")
    parser.add_option("--project", help="specific project name to sync", 
                      default=None)
    parser.add_option("--user", help="specific username to sync", default=None)
    parser.add_option("--cleanup", 
                      help="delete obsolete groups and group memberships", 
                      default=False)
    parser.add_option("-v", "--verbose", help="Print verbose debug info", 
                      dest = 'verbose', action='store_true',
                      default=False)

    options,args = parser.parse_args()
    if not (options.database_url):
        parser.print_usage()
        raise Exception("Missing some required arguments")

    # User and project options are mutually exclusive
    if options.project and options.user:
        print "Only one of --project, --user allowed";
        sys.exit()

    return options,args

# Manaager to manage synchronize between ORBIT groups/users and GENI 
# CH wimax-enabled projects and members
class WirelessProjectManager:

    def __init__(self, options):
        self._options = options

        self._project = self._options.project
        self._cleanup = self._options.cleanup
        self._user = self._options.user

        self._db_url = self._options.database_url
        self._db = create_engine(self._db_url)
        self._session_class = sessionmaker(bind=self._db)
        self._metadata = MetaData(self._db)
        base = declarative_base()
        base.metadata.create_all(self._db)
        self._session = self._session_class()

        self.PROJECT_TABLE = Table('pa_project', self._metadata, autoload=True)
        self.PROJECT_ATTRIBUTE_TABLE = Table('pa_project_attribute', 
                                             self._metadata, autoload=True)
        self.PROJECT_MEMBER_TABLE = Table('pa_project_member', 
                                          self._metadata, autoload=True)
        self.MEMBER_ATTRIBUTE_TABLE = Table('ma_member_attribute',
                                          self._metadata, autoload=True)
        self.SSH_KEY_TABLE = Table('ma_ssh_key',
                                   self._metadata, autoload=True)

        self.holdingpen_group_description = "GENI ORBIT MEMBER HOLDINGPEN"

        # These are instance variables filled in during synchronize

        # GENI wimax-enabled projects and members of these projects
        self._geni_projects = {}
        self._geni_members = {}

        # ORBIT groups and users
        self._orbit_groups = {}
        self._orbit_users = {}

        
    # Print error and exit
    def error(self, msg): print msg; sys.exit()

    # Lookup attribute
    def lookup_attribute(self, attrs, name):
        for attr in attrs:
            if attr['name'] == name:
                return attr['value']
        return None

    # Make sure 'enable_wimax' is in list of attributes (for project)
    def is_wimax_enabled(self, attrs):
        return self.lookup_attribute(attrs, 'enable_wimax') != None

    # Get pretty name from member data
    def get_pretty_name(self, member_info):
        if 'displayName' in member_info:
            return member_info['displayName']
        else:
            return "%s %s" % (member_info['first_name'], 
                              member_info['last_name'])

    # Turn GENI name to ORBIT name
    def to_orbit_name(self, name): return "geni-%s" % name

    # Top level synchronization function
    # Gather GENI clearinghouse sense of projects/members
    #    Possibly limited to specific project or user
    # Gather ORBIT sense of groups/users
    # Make sure the 'holding pen' group exists
    def synchronize(self):

        now = datetime.datetime.now()
        print "Synchronizing GENI wimax-enabled projects/users with ORBIT: %s"\
            % datetime.datetime.strftime(now, '%Y-%m-%d %H:%M:%S')

        # Grab project info for GENI wimax-enabled projects
        # Filtering to given project if set with --project
        self.get_geni_projects()

        # Grab members in wimax-enabled projects
        self.get_geni_members()

        # Get the ORBIT list of groups and admins
        self._orbit_groups, self._orbit_users = \
            orb.get_orbit_groups_and_users()
        if self._options.verbose:
            print "GENI PROJECTS = %s" % self._geni_projects
            print "GENI MEMBERS = %s" % self._geni_members
            print "ORBIT GROUPS = %s" % self._orbit_groups
            print "ORBIT USERS = %s" % self._orbit_users

        # Make sure the holdingpen gorup and admin exist
        self.ensure_holdingpen_group_and_admin()

        # Make sure all members of wimax-enabled projects exist as orbit users
        # Make sure they are enabled
        self.ensure_project_members_exist()

        # Make sure all wimax-enabled projects exist as orbit groups
        self.ensure_projects_exist()

        # Make sure all orbit users are in proper wimax group
        self.ensure_project_members_in_groups()

        # Make sure the admins of orbit groups match the leads of GENI projects
        self.ensure_project_leads_are_group_admins()

        # If we're doing cleanup, 
        #   delete group members who aren't project members
        #   delete groups that aren't GENI projects
        #   disable any users not in any GENI project
        if self._cleanup:
            self.delete_group_members_not_in_project()
            self.delete_groups_without_projects()
            self.disable_users_in_no_project()

    # Make sure that the holdingpen group exists
    def ensure_holdingpen_group_and_admin(self):

        # Find the holdingpen admin among the GENI members read
        holdingpen_admin_info = None
        for member_id, member_info in self._geni_members.items():
            if member_info['username'] == self._options.holdingpen_admin:
                holdingpen_admin_info = member_info
                break

        if not holdingpen_admin_info:
            self.error("Holdingpen admin not in GENI: %s" % \
                           self._options.holdingpen_admin)

        # Grab 'pretty name' for holdingpen admin
        admin_pretty_name = self.get_pretty_name(holdingpen_admin_info)
        holdingpen_admin_username = \
            self.to_orbit_name(self._options.holdingpen_admin)
        holdingpen_admin_ssh_keys = holdingpen_admin_info['ssh_keys']

        ldif_text = ""
        if self._options.holdingpen_group not in self._orbit_groups:
            ldif_text = ldif_text + \
                orb.ldif_for_group(self._options.holdingpen_group,
                                   self.holdingpen_group_description)
            ldif_text = ldif_text + \
                orb.ldif_for_group_admin(self._options.holdingpen_group,
                                         holdingpen_admin_username,
                                         self._options.holdingpen_group)
            print "Creating holdingpen group: %s" % \
                self._options.holdingpen_group

        if holdingpen_admin_username not in self._orbit_users:
            ldif_text = ldif_text + \
                orb.ldif_for_user(holdingpen_admin_username,
                                  self._options.holdingpen_group,
                                  admin_pretty_name,
                                  holdingpen_admin_info['first_name'],
                                  holdingpen_admin_info['email_address'],
                                  holdingpen_admin_info['last_name'],
                                  holdingpen_admin_ssh_keys,
                                  self.holdingpen_group_description, None)
            print "Creating holdingpen admin: %s" % \
                holdingpen_admin_username

        if ldif_text != "":
            orb.saveUser(ldif_text)

    # Make sure that all members of wimax-enabled projects exist in orbit
    # If not, create and place in holdingpen group as their primary group
    # The holdingpen admin is in the list of geni members, but don't need
    #   to create his account: should already be there
    def ensure_project_members_exist(self): 
        for member_id, member_info in self._geni_members.items():
            username = member_info['username']
            if username == self._options.holdingpen_admin: continue
            orbit_username = self.to_orbit_name(username)
            if orbit_username not in self._orbit_users:
                print "Creating ORBIT user: %s" % orbit_username
                member_pretty_name = self.get_pretty_name(member_info)
                member_ssh_keys = member_info['ssh_keys']
                ldif_text = \
                    orb.ldif_for_user(orbit_username,
                                      self._options.holdingpen_group,
                                      member_pretty_name,
                                      member_info['first_name'],
                                      member_info['email_address'],
                                      member_info['last_name'],
                                      member_ssh_keys,
                                      self.holdingpen_group_description, None)
                orb.saveUser(ldif_text)

    
    # Make sure all wimax-enabled GENI projects have a corresponding 
    # ORBIT group
    def ensure_projects_exist(self): 
        for project_id, project_info in self._geni_projects.items():
            project_name = project_info['project_name']
            project_description = project_info['project_description']
            orbit_group_name = self.to_orbit_name(project_name)
            if orbit_group_name not in self._orbit_groups:
                print "Creating ORBIT group: %s" % orbit_group_name
                lead_id = project_info['lead_id']
                lead_username = self._geni_members[lead_id]['username']
                orbit_lead_username = self.to_orbit_name(lead_username)
                ldif_text = orb.ldif_for_group(orbit_group_name, 
                                               project_description)
                ldif_text = ldif_text + \
                    orb.ldif_for_group_admin(orbit_group_name, 
                                             orbit_lead_username,
                                             self._options.holdingpen_group)
                orb.saveUser(ldif_text)

                # Add new group to self._orbit_groups structure
                # Leave users blank so we'll re-create them later
                orbit_group_info = {'admin' : orbit_lead_username,
                                    'users' : []}
                self._orbit_groups[orbit_group_name] = orbit_group_info

    # Make sure all members of wimax-enabledf GENI projects are membes
    # of the corresponding ORBIT group
    # Enable all users that are members of a non-holdingpen group
    def ensure_project_members_in_groups(self): 
        users_to_enable = set()
        for project_id, project_info in self._geni_projects.items():
            project_name = project_info['project_name']
            orbit_group_name = self.to_orbit_name(project_name)
            group_info = self._orbit_groups[orbit_group_name]
            for member_id in project_info['members']:
                member_info = self._geni_members[member_id]
                geni_username = member_info['username']
                orbit_username = self.to_orbit_name(geni_username)
                if orbit_username not in group_info['users']:
                    print "Adding user %s to group %s" % (orbit_username, 
                                                          orbit_group_name)
                    orb.add_user_to_group(orbit_group_name, orbit_username)
                    users_to_enable.add(orbit_username)

        # Enable all users that have been added to groups
        for user_to_enable in users_to_enable:
            print "Enabling user: %s" % user_to_enable
            orb.enable_user(user_to_enable)

    # Make sure the lead of the project is the corresponding group admin
    def ensure_project_leads_are_group_admins(self): 
        for project_id, project_info in self._geni_projects.items():
            project_name = project_info['project_name']
            orbit_group_name = self.to_orbit_name(project_name)
            lead_id = project_info['lead_id']
            lead_username = self._geni_members[lead_id]['username']
            orbit_lead_username = self.to_orbit_name(lead_username)
            orbit_group_admin = self._orbit_groups[orbit_group_name]['admin']
            if orbit_group_admin != orbit_lead_username:
                print "Change admin of group %s from %s to %s" % \
                    (orbit_group_name, orbit_group_admin, orbit_lead_username)
                orb.change_group_admin(orbit_group_name, orbit_lead_username)


    # WRITE ME
    def delete_group_members_not_in_project(self): pass
    def delete_groups_without_projects(self): pass
    def disable_users_in_no_project(self): pass

    # Top level synchronization function
    # Gather GENI clearinghouse sense of projects/members
    # Gather ORBIT sense of groups/users
    # Make appropriate calls to ORBIT to make its sense reflect that of GENI CH
    #
    # For every GENI project that is not an ORBIT group, create group
    # For every ORBIT group that is not a GENI project, delete group
    # For every user in a GENI project that is not an ORBIT user,
    #    create user
    # For every member of GENI project that is not user of 
    #    corresponding ORBIT group, add user to ORBIT group
    # For every user of ORBIT group that is not a member of 
    #    corresponding GENI project, remove user from ORBIT group
    def synchronizeOLD(self):

        # Grab project info for project of given name
        orbit_projects = self.get_orbit_projects()
        print "PROJECTS = %s" % orbit_projects

        # Get the ORBIT list of groups and members
        orbit_groups = orb.get_orbit_groups()
        print "GROUPS = %s" % orbit_groups

        # For every GENI project that is not an ORBIT group, create group
        for project_urn, project_info in orbit_projects.items():
            wimax_group_name = project_info['wimax_group_name']
            if wimax_group_name not in orbit_groups:
                orb.create_orbit_group(wimax_group_name)

        # For every ORBIT group that is not a GENI project, delete group
        # Only for full synch: Don't bother if only synching for one project
        if self._project == None:
            for group_name, group_info in orbit_groups.items():
                project_urn = \
                    self.lookup_project_for_wimax_group_name(orbit_projects,
                                                             group_name)
                if project_urn == None:
                    orb.delete_orbit_group(group_name)

        # For every user in a GENI project that is not an ORBIT user,
        #    create user
        for project_urn, project_info in orbit_projects.items():
            group_name = project_info['wimax_group_name']
            group = orbit_groups[group_name]
            for user_urn, user_info in project_info['users'].items():
                user_name = user_info['username']
                user_orbit_name = user_info['wimax_username']
                if user_orbit_name == None:
                    orb.create_orbit_user(user_name)

        # For every member of a GENI project that is not a member of
        # the corresponding ORBIT group, add member to group
        for project_urn, project_info in orbit_projects.items():
            group_name = project_info['wimax_group_name']
            group = orbit_groups[group_name]
            for user_urn, user_info in project_info['users'].items():
                user_name = user_info['username']
                user_orbit_name = user_info['wimax_username']
                if user_orbit_name not in group['users']:
                    orb.add_member_to_group(user_name, user_orbit_name, 
                                           group_name)

        # For every member of orbit group that is not a member of 
        # the corresponding GENI project, remove member from group
        for group_name, group_info in orbit_groups.items():
            project_urn = self.lookup_project_for_wimax_group_name(
                orbit_projects, group_name)
            if not project_urn: continue
            project_members = orbit_projects[project_urn]['users']
            for user in group_info['users']:
                member_urn = self.lookup_member_for_wimax_user_name(
                    project_members, user)
                if member_urn == None:
                    orb.remove_member_from_group(user, group_name)

    # Lookup project from given table whose wimax_group_name matches given 
    def lookup_project_for_wimax_group_name(self, orbit_projects, group_name):
        for project_urn, project_info in orbit_projects.items():
            if project_info['wimax_group_name'] == group_name:
                return project_urn
        return None

    # Lookup member from given table whose wimax_name matches given 
    def lookup_member_for_wimax_user_name(self, project_members, user_name):
        for member_urn, member_info in project_members.items():
            if member_info['wimax_username'] == user_name:
                return member_urn
        return None

    # Grab project info [indexed by project id] for all wimax-enabled projects
    # Only single project for --project option
    # Only projects to which given users belongs for --user option
    def get_geni_projects(self):
        projects = {}

        # Get all the WIMAX-enabled projects
        query = self._session.query(self.PROJECT_TABLE.c.lead_id, 
                                    self.PROJECT_TABLE.c.project_name, 
                                    self.PROJECT_TABLE.c.project_id, 
                                    self.PROJECT_TABLE.c.project_purpose) 
        query = query.filter(self.PROJECT_TABLE.c.project_id == \
                                 self.PROJECT_ATTRIBUTE_TABLE.c.project_id)
        query = query.filter(self.PROJECT_ATTRIBUTE_TABLE.c.name == \
                                 'enable_wimax')
        if (self._project):
            query = query.filter(self.PROJECT_TABLE.c.project_name == \
                                     self._project)
        project_rows = query.all()
        project_ids = []


        for row in project_rows:
            project_ids.append(row.project_id)
            projects[row.project_id] = {
                'lead_id' : row.lead_id,
                'lead_id' : row.lead_id,
                'project_name' : row.project_name,
                'project_description' : row.project_purpose,
                'members' : []
                }

        # Get all members of WIMAX-enabled projects
        query = self._session.query(self.PROJECT_MEMBER_TABLE.c.member_id,
                                    self.PROJECT_MEMBER_TABLE.c.project_id)
        query = query.filter(self.PROJECT_MEMBER_TABLE.c.project_id.in_(\
                project_ids))
        if self._user:
            query = query.filter(self.PROJECT_MEMBER_TABLE.c.member_id == \
                                     self.MEMBER_ATTRIBUTE_TABLE.c.member_id)
            query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.name == \
                                     'username')
            query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.value == \
                                     self._user)
        member_rows = query.all()
        for row in member_rows:
            projects[row.project_id]['members'].append(row.member_id)

        # Don't return any projects with no members 
        # (if we're filtering by user)
        for project_id, project_info in projects.items():
            if len(project_info['members']) == 0:
                del projects[project_id]

        self._geni_projects = projects

    # Grab info about all people in wimax projects
    def get_geni_members(self):

        projects = self._geni_projects

        members = {}

        # Get unique list of all member_ids over all projects
        member_ids =  set()
        for proj_id, project_info in projects.items():
            for member_id in project_info['members']:
                member_ids.add(member_id)

        # add the holdingpen admin, who may not be the member of any project
        query = self._session.query(self.MEMBER_ATTRIBUTE_TABLE.c.member_id)
        query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.name == 'username')
        query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.value == \
                                 self._options.holdingpen_admin)
        holdingpen_admin_rows = query.all()
        for row in holdingpen_admin_rows:
            member_ids.add(row.member_id)

        # Turn set back into list, to grab all users with these member ID's
        member_ids = list(member_ids)

        query = self._session.query(self.MEMBER_ATTRIBUTE_TABLE)
        query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.member_id.in_(\
                member_ids))
        query = query.filter(self.MEMBER_ATTRIBUTE_TABLE.c.name.in_(\
                ["username", "first_name", "email_address", 
                 "last_name", "displayName"]))
        
        member_rows = query.all()
        for row in member_rows:
            if row.member_id not in members: 
                members[row.member_id] = {}
            members[row.member_id][row.name] = row.value


        # Grab SSH keys for all members
        for member_id, member_info in members.items(): 
            member_info['ssh_keys']=[]
        query = self._session.query(self.SSH_KEY_TABLE)
        query = query.filter(self.SSH_KEY_TABLE.c.member_id.in_(\
                member_ids))
        key_rows = query.all()
        for row in key_rows:
            members[row.member_id]['ssh_keys'].append(row.public_key)

        self._geni_members = members


def main():

    options, args = parse_args(sys.argv)

    wpm = WirelessProjectManager(options)
    wpm.synchronize()


if __name__ == "__main__":
    sys.exit(main())
