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
#----------------------------------------------------------------------


import logging
import xml.dom.minidom
import optparse
import os
import subprocess
import sys
from gcf.omnilib.frameworks.framework_base import Framework_Base
from gcf.omnilib.util.dossl import _do_ssl


def parse_args(argv):
    parser = optparse.OptionParser(usage="Add given attribute with optional specific value to the given member if not already present")
    parser.add_option("--debug", action="store_true", default=False,
                       help="enable debugging output")
    parser.add_option("-k", "--key", metavar="FILE",
                      help="Invoker's private key")
    parser.add_option("-c", "--cert", metavar="FILE",
                      help="Invoker's GENI certificate")
    parser.add_option("-u", "--url", help="CH URL prefix")
    parser.add_option("-p", "--project", help="project name", default=None)
    parser.add_option("-v", "--verbose", help="Print verbose debug info", default=False)

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

# Manaager to manage synchronize between ORBIT groups/users and GENI CH wimax-enabled
# projects and members
class WirelessProjectManager:

    BASE_ORBIT_URL = "https://www.orbit-lab.org/delegatedAM"


    def __init__(self, options):
        self._options = options


        
        # Set up XMLRPC clients for MA and SA
        self._ma_url = "%s/MA" % self._options.url
        self._sa_url = "%s/SA" % self._options.url
        self._suppress_errors = None
        self._reason = "Testing"
        self._config = {'cert' : self._options.cert, 'key' : self._options.key}

        self._framework = MAClientFramework(self._config, {})
        self._sa_client = self._framework.make_client(self._sa_url, 
                                                self._options.key, self._options.cert,
                                                allow_none=True,
                                                verbose=False)
        self._ma_client = self._framework.make_client(self._ma_url, 
                                                self._options.key, self._options.cert,
                                                allow_none=True,
                                                verbose=False)

    # Print error and exit
    def error(self, msg): print msg; sys.exit()

    # Make GENI Clearinghouse call
    def ch_call(self, method, *args):
        (result, msg) = \
            _do_ssl(self._framework, self._suppress_errors, self._reason, method, *args)
        if (self._options.verbose):
            print "RESULT = %s" % result
        if result['code'] != 0:
            self.error("Error from CHAPI call: %s" % result)
        return result['value']

    def is_wimax_enabled(self, attrs):
        is_enabled = False
        for attr in attrs:
            if attr['name'] == 'enable_wimax':
                is_enabled = True
                break
        return is_enabled

    def synchronize(self):

        # Grab project info for project of given name
        wimax_projects = {}
        
        opts = {}
        if self._options.project:
            opts = {"match" : {"PROJECT_NAME" : self._options.project}}
        projects_info = self.ch_call(self._sa_client.lookup, "PROJECT", [], opts)
        if  len(projects_info) == 0:
            self.error("No such project found: %s" % self._options.project)
            
        for project_urn in projects_info.keys():

            project_info = projects_info[project_urn]
            project_admin_uid = project_info["_GENI_PROJECT_OWNER"]
            project_members = []

            # Get project attributes
            project_attrs = self.ch_call(self._sa_client.lookup_project_attributes, 
                                         project_urn, [], {})
            if (self.is_wimax_enabled(project_attrs) == False):
                continue

            # Grab members of project
            project_members = self.ch_call(self._sa_client.lookup_members, 
                                           'PROJECT', project_urn, [], {})

            # Grab info for members
            member_urns = [pm['PROJECT_MEMBER'] for pm in project_members]
            opts = {"match" : {"MEMBER_URN" : member_urns}}
            project_member_info = self.ch_call(self._ma_client.lookup, 'MEMBER', [], opts)

            project_membership = {}
            project_admin = None

            for pm in project_members:
                member_urn = pm['PROJECT_MEMBER']
                project_membership[member_urn] = \
                    {'member_uid' : pm['PROJECT_MEMBER_UID'], 
                     'member_urn' : pm['PROJECT_MEMBER'], 
                     'username' : project_member_info[member_urn]['MEMBER_USERNAME'],
                     'wimax_username' : project_member_info[member_urn]['_GENI_WIMAX_USERNAME'],
                     'role' : pm['PROJECT_ROLE']}

                if pm['PROJECT_MEMBER_UID'] == project_admin_uid:
                    project_admin = pm['PROJECT_MEMBER']

            wimax_projects[project_urn] = {'admin' : project_admin, 
                                           'users' : project_membership }

        print "PROJECTS = %s" % wimax_projects

        # Get the ORBIT list of groups and members
        orbit_query_url = "%s/getGroupsAndUsers" % self.BASE_ORBIT_URL
        DEVNULL = open(os.devnull, 'w')
        orbit_info_raw = subprocess.check_output(["curl", orbit_query_url], stderr=DEVNULL)
        if (self._options.verbose):
            print "ORBIT = %s" % orbit_info_raw
        wimax_groups = self.parse_group_user_info(orbit_info_raw)
        print "WIMAX_GROUPS = %s" % wimax_groups

    # Parse results from getGroupsAndUsers call
    def parse_group_user_info(self, orbit_info_raw):
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
                self.error("Group %s for user %s not defined" % (group_name, user_name))
            group = groups[group_name]['users'].append(user_name)
        return groups

def main():

    options, args = parse_args(sys.argv)

    wpm = WirelessProjectManager(options)
    wpm.synchronize()


if __name__ == "__main__":
    sys.exit(main())
