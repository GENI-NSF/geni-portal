#!/usr/bin/env python
#----------------------------------------------------------------------
# Copyright (c) 2013-2014 Raytheon BBN Technologies
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

# Take a database dump from one GENI Clearinghouse and import 
# it into another one. Intended for transition from one
# CH (going off line) to another (coming on line)

# The databse on the 'source' machine should be generated as follows:
# pg_dump --clean -U <portal_user> -h localhost <portal_db> > source_dump.sql

# At the end of this script, it will prompt for user password to 
# allow launching a process as another user (www-data)

import optparse
import os
import sys
import subprocess
import tempfile
import time
import uuid

class DatabaseImporter:

    # Constants
    # MA Cert file
    ma_cert_filename = "/usr/share/geni-ch/ma/ma-cert.pem"
    ma_key_filename = "/usr/share/geni-ch/ma/ma-key.pem"

    def __init__(self, argv):
        self._argv = argv;
        self._options = self.parse_args()
        
        self._dump_file = self._options.dump_file
        self._old_hostname= self._options.old_hostname
        self._new_hostname = self._options.new_hostname
        self._old_authority = self._options.old_authority
        self._new_authority = self._options.new_authority
        self._new_portal_urn = "urn:publicid:IDN+%s+authority+portal" \
            % self._new_authority


    def parse_args(self):
        parser = optparse.OptionParser()
        parser.add_option("--dump_file", help="location of db dump to import")
        parser.add_option("--old_hostname", help="name of old CH hostname")
        parser.add_option("--new_hostname", help="name of new CH hostname")
        parser.add_option("--old_authority", help="name of old CH authority")
        parser.add_option("--new_authority", help="name of new CH authority")
        options, args = parser.parse_args(self._argv)

        if not options.dump_file or \
                not options.new_hostname or \
                not options.old_hostname or \
                not options.new_authority or \
                not options.old_authority:
            parser.print_help()
            sys.exit()

        return options

    def execute(self, cmd, as_user=None):
        print "Executing " + " ".join(cmd)
        (fd, filename) = tempfile.mkstemp()
        os.close(fd)
        cmd_file = open(filename, 'w')
        cmd_file.write(" ".join(cmd))
        cmd_file.close()

        try:
            run_cmd = ['/bin/bash', filename]
            if as_user:
                os.chmod(filename, 0777)
                run_cmd = ['sudo',  '-u', as_user, filename]
            subprocess.call(run_cmd)
        except Exception as e:
            print "Error running shell command: " + " ".join(run_cmd)
            print "Error running actual command: " + " ".join(cmd)
            print str(e)
        finally:
            os.remove(filename)


    def translate_member_ids(self, psql_cmd):
        psql = subprocess.Popen(psql_cmd, stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE)
        # Extract all the member ids
        (fd, member_id_file) = tempfile.mkstemp()
        os.close(fd)
        extract_members_sql = \
            "select member_id from ma_member \\g %s\n" % (member_id_file)
        psql.stdin.write(extract_members_sql)

        # Give the db a moment to write the members file
        time.sleep(3)

        # Now read the file
        with open(member_id_file) as f:
            raw = f.readlines()
        member_ids = dict()
        for r in raw:
            r = r.strip()
            try:
                mid = uuid.UUID(r)
                member_ids[mid] = None
            except ValueError:
                pass
        for old in member_ids:
            new = uuid.uuid4()
            while new in member_ids:
                print "COLLISION"
                new = uuid.uuid4()
            member_ids[old] = new

        os.remove(member_id_file)

        drop_sql = 'DROP TABLE IF EXISTS ma_member_id_translation;\n'
        psql.stdin.write(drop_sql)
        create_sql = ('CREATE TABLE ma_member_id_translation ('
                      + ' id SERIAL PRIMARY KEY,'
                      + ' old_id UUID UNIQUE,'
                      + ' new_id UUID UNIQUE'
                      + ');\n')
        psql.stdin.write(create_sql)

        insert_sql = ('INSERT INTO ma_member_id_translation'
                      + "(old_id, new_id) values ('%s', '%s');\n")
        for o,n in member_ids.iteritems():
            psql.stdin.write(insert_sql % (o, n))

        # write an EOF
        (stdoutdata, stderrdata) = psql.communicate(None)
        if False:
            print '------------------------------------------------------------'
            print stdoutdata
            print '------------------------------------------------------------'
            print stderrdata
            print '------------------------------------------------------------'
        return psql.returncode == 0

    def run(self):

        psql_cmd = ['psql', '-U', 'portal', '-h', 'localhost', 'portal']

        # Import the database
        import_db_cmd = psql_cmd + ['<', self._dump_file]
        self.execute(import_db_cmd)

        # Generate new member_id swapping table
        # FIXME FIXME
        print 'Generate new member ID swapping table'
        self.translate_member_ids(psql_cmd)

        # Generate SQL for dropping constraints
        gen_drop_cmd = psql_cmd + ['-q', '-t', '-o', '/tmp/drop-constraints.sql', '<', '/usr/local/sbin/gen-drop-constraints.sql']
        self.execute(gen_drop_cmd)

        # Generate SQL for adding constraints
        gen_add_cmd = psql_cmd + ['-q', '-t', '-o', '/tmp/add-constraints.sql', '<', '/usr/local/sbin/gen-add-constraints.sql']
        self.execute(gen_add_cmd)

        # Drop constraints
        drop_constraints_cmd = psql_cmd + ['<', '/tmp/drop-constraints.sql']
        self.execute(drop_constraints_cmd)

        # Swap member_ids
        print 'Swap member IDs....'
        tcfile = '/etc/geni-ch/member-id-columns.dat'
        with open (tcfile, 'r') as file:
            lines = file.readlines()

        for line in lines:
            (table, column) = line.split(',')
            table = table.strip()
            column = column.strip()
            updatesql = 'update %s set %s = (select T2.new_id from ma_member_id_translation T2 where %s.%s = T2.old_id)' % (table, column, table, column)
            do_update_cmd = psql_cmd + ['-c', '"' + updatesql + '"']
        # FIXME FIXME
            self.execute(do_update_cmd)
            print "Member ID swap: %s" % updatesql

        # Special case handle the table whose column is a string
        updatesql = "update logging_entry_attribute set attribute_value = (select T2.new_id from ma_member_id_translation T2 where logging_entry_attribute.attribute_value::uuid = T2.old_id) where logging_entry_attribute.attribute_name = 'MEMBER'"
        do_update_cmd = psql_cmd + ['-c', '"' + updatesql + '"']
        self.execute(do_update_cmd)
        print "Member ID swap: %s" % updatesql

        # Re-add constraints
        add_constraints_cmd = psql_cmd + ['<', '/tmp/add-constraints.sql']
        self.execute(add_constraints_cmd)

        # Check for errors:

        # Generate SQL for dropping constraints to compare
        gen_drop_cmd = psql_cmd + ['-q', '-t', '-o', '/tmp/drop-constraints2.sql', '<', '/usr/local/sbin/gen-drop-constraints.sql']
        self.execute(gen_drop_cmd)

        if os.path.getsize('/tmp/drop-constraints.sql') != os.path.getsize('/tmp/drop-constraints2.sql'):

            print 'ERROR: some contraints not successfully re-added!'
            run_cmd = ['diff', '/tmp/drop-constraints.sql', '/tmp/drop-constraints2.sql']
            self.execute(run_cmd)
            sys.exit(-1)
        else:
            print "Constraints successfully re-added"

        # Member IDs updated...
        print "Member ID update complete"

        # Change the service registry
        change_sr_sql = \
            "update service_registry set service_url = " \
            + "replace(service_url, '%s', '%s')" \
            % (self._old_hostname, self._new_hostname)
        change_sr_cmd = psql_cmd + ['-c', '"' + change_sr_sql + '"']
        self.execute(change_sr_cmd)

        # Change the MA_client URN names
        change_ma_client_sql = \
            "update ma_client set client_name = 'portal.obsolete' " \
            + "where client_name='portal'; " \
            + "insert into ma_client(client_name, client_urn) " \
            + "values ('portal', '%s')" % self._new_portal_urn
        change_ma_client_cmd = \
            psql_cmd + ['-c', '"' + change_ma_client_sql + '"']
        self.execute(change_ma_client_cmd)

        # Change client_urn in ma_inside_key
        change_ma_inside_key_sql = \
            "update ma_inside_key set client_urn = '%s'" % self._new_portal_urn
        change_ma_inside_key_cmd = \
            psql_cmd + ['-c', '"' + change_ma_inside_key_sql + '"']
        self.execute(change_ma_inside_key_cmd)

        # Delete old portal entry (now that there's no foreign reference)
        delete_obsolete_ma_client_sql = \
            "delete from ma_client where client_name = 'portal.obsolete'"
        delete_obsolete_ma_client_cmd = \
            psql_cmd + ['-c', '"' + delete_obsolete_ma_client_sql + '"']
        self.execute(delete_obsolete_ma_client_cmd)


        # Set the user urn's in ma_member_attribute
        change_ma_member_attribute_sql = \
            "update ma_member_attribute set value = " \
            + "replace(value, '+%s+', '+%s+') where name = 'urn'" \
            % (self._old_authority, self._new_authority)
        change_ma_member_attribute_cmd = \
            psql_cmd + ['-c', '"' + change_ma_member_attribute_sql + '"']
        self.execute(change_ma_member_attribute_cmd)

        # Set all current slices to expired
        expire_slices_sql = "update sa_slice set expired = 't'"
        expire_slices_cmd = psql_cmd + ['-c', '"' + expire_slices_sql + '"']
        self.execute(expire_slices_cmd)

        # Copy the .pgpass file to /tmp so that www-data can use it
        pgpass = ".pgpass"
        pgpass_tmp = "/tmp/" + pgpass
        copy_pgpass_cmd =  ['cp', '~/' + pgpass, '/tmp']
        self.execute(copy_pgpass_cmd)
        self.execute(['chown', 'www-data.www-data', pgpass_tmp], 'root')
        self.execute(['chmod', '0600', pgpass_tmp], 'root')
        self.execute(['mv', pgpass_tmp, '~www-data'], 'root')

        # Run update_user_certs as www-data
        update_user_certs_cmd = \
            ['python', '/usr/local/sbin/update_user_certs.py', 
             '--ma_cert_file', self.ma_cert_filename, 
             '--ma_key_file', self.ma_key_filename, 
             '--old_authority', self._old_authority, 
             '--new_authority', self._new_authority]
        self.execute(update_user_certs_cmd, 'www-data')

        # Remove the .pgpass file from ~www-data
        self.execute(['rm', '~www-data/'+pgpass], 'root')

        # Change ssh public keys that have 'www-data@panther.gpolab.bbn.com'
        # in the comment field to have the username as the comment.
        update_ssh_sql = \
            "update ma_ssh_key set public_key ="\
            + " replace(public_key, 'www-data@panther.gpolab.bbn.com',"\
            + " (select value from ma_member_attribute mma"\
            + " where mma.member_id = ma_ssh_key.member_id"\
            + " and name = 'username'));"
        update_ssh_cmd = psql_cmd + ['-c', '"' + update_ssh_sql + '"']
        self.execute(update_ssh_cmd)
        

if __name__ == "__main__":
    importer = DatabaseImporter(sys.argv)
    importer.run()
