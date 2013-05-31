#!/usr/bin/env python

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


    def run(self):

        psql_cmd = ['psql', '-U', 'portal', '-h', 'localhost', 'portal']

        # Import the database
        import_db_cmd = psql_cmd + ['<', self._dump_file]
        self.execute(import_db_cmd)

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
