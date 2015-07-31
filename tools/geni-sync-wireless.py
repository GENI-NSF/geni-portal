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
# Reconcile current list of wimax-enabled projects and members of these
# projects in GENI Clearinghouse with the list of delegated groups/users
# in ORBIT Delegated Account management API
#
# Make take a project argument to only synch that project
# Otherwise, synch all wimax-enabled projects
#----------------------------------------------------------------------


import logging
import xml.dom.minidom
import optparse
import sys
import orbit_interface as orb
from gcf.omnilib.frameworks.framework_base import Framework_Base
from gcf.omnilib.util.dossl import _do_ssl


def parse_args(argv):
    parser = optparse.OptionParser(usage="Synchronize ORBIT and GENI CH " + 
                                   "sense of projects/groups and members")
    parser.add_option("--debug", action="store_true", default=False,
                       help="enable debugging output")
    parser.add_option("-k", "--key", metavar="FILE",
                      help="Invoker's private key")
    parser.add_option("-c", "--cert", metavar="FILE",
                      help="Invoker's GENI certificate")
    parser.add_option("-u", "--url", help="CH URL prefix")
    parser.add_option("-p", "--project", help="project name", default=None)
    parser.add_option("-v", "--verbose", help="Print verbose debug info", 
                      default=False)

    options,args = parser.parse_args()
    if not (options.key and options.cert and options.url):
        parser.print_usage()
        raise Exception("Missing some required arguments")
    return options,args

# Class to manage XMLRPC calls to GENI Clearninghouse
class MAClientFramework(Framework_Base):
    def __init__(self, config, opts):
        Framework_Base.__init__(self, config)
        self.config = config
        self.fwtype = "MA Ciient"
        self.logger = logging.getLogger('client')
        self.opts = opts

# Manaager to manage synchronize between ORBIT groups/users and GENI 
# CH wimax-enabled projects and members
class WirelessProjectManager:

    def __init__(self, options):
        self._options = options
        self._project = self._options.project
        
        # Set up XMLRPC clients for MA and SA
        self._ma_url = "%s/MA" % self._options.url
        self._sa_url = "%s/SA" % self._options.url
        self._suppress_errors = None
        self._reason = "Testing"
        self._config = {'cert' : self._options.cert, 'key' : self._options.key}

        self._framework = MAClientFramework(self._config, {})
        self._sa_client = self._framework.make_client(self._sa_url, 
                                                      self._options.key, 
                                                      self._options.cert,
                                                      allow_none=True,
                                                      verbose=False)
        self._ma_client = self._framework.make_client(self._ma_url, 
                                                      self._options.key, 
                                                      self._options.cert,
                                                      allow_none=True,
                                                      verbose=False)

    # Print error and exit
    def error(self, msg): print msg; sys.exit()

    # Make GENI Clearinghouse call
    def ch_call(self, method, *args):
        (result, msg) = \
            _do_ssl(self._framework, self._suppress_errors, self._reason, 
                    method, *args)
        if (self._options.verbose):
            print "RESULT = %s" % result
        if result['code'] != 0:
            self.error("Error from CHAPI call: %s" % result)
        return result['value']

    # Lookup attribute
    def lookup_attribute(self, attrs, name):
        for attr in attrs:
            if attr['name'] == name:
                return attr['value']
        return None

    def is_wimax_enabled(self, attrs):
        return self.lookup_attribute(attrs, 'enable_wimax') != None

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
    def synchronize(self):

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

    # Grab project/member info for all wimax-enabled project
    def get_orbit_projects(self):
        opts = {}
        if self._project:
            opts = {"match" : {"PROJECT_NAME" : self._options.project}}
        projects_info = self.ch_call(self._sa_client.lookup, "PROJECT", 
                                     [], opts)
        if  len(projects_info) == 0:
            self.error("No such project found: %s" % self._options.project)
            
        orbit_projects = {}
        for project_urn in projects_info.keys():

            project_info = projects_info[project_urn]
            project_admin_uid = project_info["_GENI_PROJECT_OWNER"]
            project_members = []

            # Get project attributes
            project_attrs = \
                self.ch_call(self._sa_client.lookup_project_attributes, 
                             project_urn, [], {})
            if (self.is_wimax_enabled(project_attrs) == False):
                continue
            wimax_group_name = self.lookup_attribute(project_attrs, 
                                                     'wimax_group_name')

            # Grab members of project
            project_members = self.ch_call(self._sa_client.lookup_members, 
                                           'PROJECT', project_urn, [], {})

            # Grab info for members
            member_urns = [pm['PROJECT_MEMBER'] for pm in project_members]
            opts = {"match" : {"MEMBER_URN" : member_urns}}
            project_member_info = self.ch_call(self._ma_client.lookup, 
                                               'MEMBER', [], opts)

            project_membership = {}
            project_admin = None

            for pm in project_members:
                member_urn = pm['PROJECT_MEMBER']
                project_membership[member_urn] = \
                    {'member_uid' : pm['PROJECT_MEMBER_UID'], 
                     'member_urn' : pm['PROJECT_MEMBER'], 
                     'username' : \
                         project_member_info[member_urn]['MEMBER_USERNAME'],
                     'wimax_username' : \
                         project_member_info[member_urn]['_GENI_WIMAX_USERNAME'],
                     'role' : pm['PROJECT_ROLE']}

                if pm['PROJECT_MEMBER_UID'] == project_admin_uid:
                    project_admin = pm['PROJECT_MEMBER']

            orbit_projects[project_urn] = {'admin' : project_admin, 
                                           'wimax_group_name' : 
                                           wimax_group_name,
                                           'users' : project_membership }
        return orbit_projects

def main():

    options, args = parse_args(sys.argv)

    wpm = WirelessProjectManager(options)
    wpm.synchronize()


if __name__ == "__main__":
    sys.exit(main())
