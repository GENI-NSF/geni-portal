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
# Update the user certs in the database ma_inside_keys tables to reflect the current ma_cert

import optparse
import os
import sys
import subprocess
import tempfile
import datetime
import OpenSSL

# Helper functions
# Get contents of file
def read_file(filename):
    f = open(filename, 'r')
    contents = f.read()
    f.close()
    return contents

# Run SQL query and dump output into given file
def run_sql_to_file(sql, outfile):
    cmd = ['psql', 'portal', '-U',  'portal', '-h', 'localhost', '-c', sql, '-o', outfile, '-t' , '-q']
    output = subprocess.call(cmd);

def run_sql(sql):
    file = get_tempfile()
    run_sql_to_file(sql, file)
    contents = read_file(file)
    os.remove(file)
    return contents

# Create a tempfile ready for writing to
def get_tempfile():
    (fd, filename) = tempfile.mkstemp()
    os.close(fd)
    return filename

# Strip leading spaces and trailing blank lines from key file
def fix_keyfile(keyfile):
    key_data = read_file(keyfile);
    key_data_lines = key_data.split('\n');
    new_key_data_lines = [line.strip() for line in key_data_lines if len(line.strip()) > 0]
    new_key_data = "\n".join(new_key_data_lines) + "\n"
    file = open(keyfile, 'w');
    file.write(new_key_data);
    file.close()

class UserCertGenerator:
    def __init__(self):
        pass

    # Constants
    ca_config = '/usr/share/geni-ch/CA/openssl.cnf'
    extname = 'v3_user'

    def create_csr_for_user_key(self, user_key_file):
    
        csr_file = get_tempfile()

        # Create the csr (certificate signing request)
        csr_cmd = ['openssl',  'req', \
                       '-new', \
                       '-key', user_key_file, \
                       '-batch', \
                       '-out', csr_file]
#        print "CMD = " + " ".join(csr_cmd)
        subprocess.call(csr_cmd)
        return csr_file

    # Write the extension file
    # Return 0 if no error
    def write_ext_file(self, user_urn, user_uuid, user_email):
        extdata_template = "[ %s ]\n" \
            + "subjectKeyIdentifier=hash\n" \
            + "authorityKeyIdentifier=keyid:always,issuer:always\n" \
            + "basicConstraints = CA:FALSE\n" \
            + "subjectAltName=email:copy,URI:%s,URI:urn:uuid:%s\n"
        extdata = extdata_template % (self.extname, user_urn, user_uuid);

        retval = 0
        ext_file = ""
        try:
            ext_file = get_tempfile()
            f = open(ext_file, 'w');
            f.write(extdata);
            f.close()
        except Exception, e:
            print "WEF %s" % str(e)
            retval = 1

        return ext_file, retval 

# Sign the Certificate signing request, writing file to given filename
    def sign_csr(self, csr_file, 
                 cert_file, signer_cert_file, signer_key_file,
                 subject, user_urn, user_uuid, user_email):

        (ext_file, ext_failure) = self.write_ext_file(user_urn, user_uuid, user_email)
        if ext_failure:
            print "Returning from error in write_ext_file %d" % ext_failure
            return 1

        sign_cmd = ['openssl', 'ca', \
                        '-config', self.ca_config, \
                        '-policy', 'policy_anything', \
                        '-in', csr_file, \
                        '-out', cert_file, \
                        '-extfile', ext_file, \
                        '-extensions', self.extname,  \
                        '-batch', \
                        '-notext', \
                        '-cert', signer_cert_file, \
                        '-keyfile', signer_key_file, \
                        '-subj', subject]
#        print "CMD = " + " ".join(sign_cmd)
        retcode = subprocess.call(sign_cmd)
        if retcode == 0:
            os.remove(ext_file)
        else:
            print "sign command failed. ext file is %s" % (ext_file)

    # Create a cert with the user's URN, UUID and email signed by
    # Signed by the user's private key and then certified by signer's signature
    def create_cert_for_user_key(self, signer_cert_file, signer_key_file, \
                                     user_urn, user_uuid, user_email, \
                                     user_key_file, cert_file):

        csr_file = self.create_csr_for_user_key(user_key_file)
        
        subject = "/CN=%s/emailAddress=%s" % (user_uuid, user_email)
        
        self.sign_csr(csr_file, cert_file, \
                          signer_cert_file, signer_key_file, \
                          subject, user_urn, user_uuid, user_email)

        os.remove(csr_file)


def cert_expiration(pemcert):
    cert = OpenSSL.crypto.load_certificate(OpenSSL.crypto.FILETYPE_PEM,
                                           pemcert)
    not_after = cert.get_notAfter()
    expires = datetime.datetime.strptime(not_after, '%Y%m%d%H%M%SZ')
    return expires

# Update user certificate in database table
def update_certificate(user_id, table_name, user_cert, ma_cert):
    new_cert = user_cert + ma_cert
    expiration = cert_expiration(user_cert)
    sql = ("update %s set certificate = '%s', expiration = '%s'"
           + " where member_id = '%s'")
    sql = sql % (table_name, new_cert, expiration, user_id)
    run_sql(sql)

# Class to update all certificates in a given database in ma_inside_key and ma_outside_cert tables
class UserCertificateUpdater:
    def __init__(self, argv):
        self._argv = argv
        self._options = self.parse_args()

        self._ma_cert_file = self._options.ma_cert_file
        self._ma_key_file = self._options.ma_key_file
        self._ma_cert = read_file(self._options.ma_cert_file)
        self._old_authority= self._options.old_authority
        self._new_authority= self._options.new_authority

    def parse_args(self):
        parser = optparse.OptionParser()
        parser.add_option("--ma_cert_file", help="location of MA cert")
        parser.add_option("--ma_key_file", help="location of MA private key")
        parser.add_option("--old_authority", help="name of old MA authority")
        parser.add_option("--new_authority", help="name of new MA authority")

        options, args = parser.parse_args(self._argv)

        if not options.ma_cert_file \
                or not options.ma_key_file \
                or not options.old_authority \
                or not options.new_authority:
            parser.print_help()
            sys.exit()

        return options


#    def update_certs_in_table(self, tablename):
#        sql = "select member_id from %s" % tablename
#        user_ids = run_sql(sql).split('\n')
#
#        for user_id in user_ids:
#            user_id = user_id.strip()
#            if len(user_id) == 0: continue
#            user_id = int(user_id)
#
#            sql = "select certificate from %s where member_id = %d" % (tablename, user_id);
#            cert = run_sql(sql)

#            # Need to split into lines, take off the leading space and rejoin
#            lines = cert.split('\n')
#            trimmed_lines = [line[1:] for line in lines]
#            cert = '\n'.join(trimmed_lines)
#            end_certificate = 'END CERTIFICATE-----\n'
#            cert_pieces = cert.split(end_certificate);
#            user_cert = cert_pieces[0] + end_certificate
#            ma_cert = cert_pieces[1] + end_certificate
#
#        if ma_cert == self._old_ma_cert:
#            print "Replacing old MA cert with new MA cert: user ID %d table %s" % (user_id, tablename)
#            update_certificate(user_id, tablename, user_cert, self._new_ma_cert)
#        elif ma_cert == self._new_ma_cert:
#            print "Already associated with new MA cert: user ID %d table %s" % (user_id, tablename)
#        else:
#            print "MA cert unknown: user ID %d table %s" % (user_id, tablename)

    def create_certs_in_table(self, tablename, send_email_if_no_private_key):
        sql = "select member_id from %s" % tablename
        user_uuids = run_sql(sql).split('\n')
        uus = [uu.strip() for uu in user_uuids if len(uu.strip()) > 0]
        user_uuids = uus

        sql = "select member_id from %s where private_key is null and certificate is not null" % tablename;
        user_uuids_no_key = run_sql(sql).split('\n')
        uunks = [uunk.strip() for uunk in user_uuids_no_key if len(uunk.strip()) > 0]
        user_uuids_no_key = uunks
        user_emails_no_key = []
        user_emails_with_key = []

        sql = "select member_id from %s where private_key is null and certificate is null" % tablename;
        user_uuids_no_key_no_cert = run_sql(sql).split('\n')
        uunkncs = [uunknc.strip() for uunknc in user_uuids_no_key_no_cert if len(uunknc.strip()) > 0]
        user_uuids_no_key_no_cert = uunkncs

        if not send_email_if_no_private_key and len(user_uuids_no_key):
            print "Error: table with no private keys not allowed: %s" % tablename
            sys.exit(-1)

        for user_uuid in user_uuids:

            sql = "select value from ma_member_attribute where member_id = '%s' and name = 'email_address'" % user_uuid
            addresses = run_sql(sql).split('\n')
            user_email = addresses[0].strip()
            if not user_email:
                continue

            sql = "select value from ma_member_attribute where member_id = '%s' and name = 'urn'" \
                % user_uuid
            urns = run_sql(sql).split('\n')
            old_user_urn = urns[0].strip()
            user_urn = old_user_urn.replace(self._old_authority, self._new_authority)
#            print "Old urn = %s New urn = %s" % (old_user_urn, user_urn)

            full_user_uuid = "urn:uuid:%s" % user_uuid

            if user_uuid in user_uuids_no_key_no_cert:
                print "User has no private key or cert: %s" % user_uuid
                sql = "delete from %s where member_id = '%s'" % (tablename, user_uuid)
                run_sql(sql)

            elif user_uuid in user_uuids_no_key:
                # No private key: Send an email to generate a new CSR
                print "User has no private key: %s" % user_uuid

                if send_email_if_no_private_key:
                    # Note this in the DB
                    sql = "insert into ma_member_attribute (member_id, name, value, self_asserted) values ('%s', 'panther_outside_cert', 'no key', false)" % (user_uuid)
                    run_sql(sql)

                    # Delete the old outside cert, so they are forced to re generate
                    sql = "delete from %s where member_id = '%s'" % (tablename, user_uuid)
                    run_sql(sql)

                    # Record their email in a file so we can email all these people
                    user_emails_no_key.append(user_email)
                
            else:
                print "User has private key: %s" % user_uuid

                if send_email_if_no_private_key:
                    # Record their email in a file so we can email all these people
                    user_emails_with_key.append(user_email)

                    # Note this in the DB
                    sql = "insert into ma_member_attribute (member_id, name, value, self_asserted) values ('%s', 'panther_outside_cert', 'had key', false)" % (user_uuid)
                    run_sql(sql)

                # Re-generate their cert
                sql = "select private_key from %s where member_id='%s'" % (tablename, user_uuid)
                user_key_file = get_tempfile()
                run_sql_to_file(sql, user_key_file)
                fix_keyfile(user_key_file)
                cert_file = "/tmp/cert-%s.pem" % user_uuid
                cert_generator = UserCertGenerator()
                cert_generator.create_cert_for_user_key(self._ma_cert_file, self._ma_key_file,
                                                        user_urn, user_uuid, user_email,
                                                        user_key_file, cert_file)

                user_cert = read_file(cert_file)
                update_certificate(user_uuid, tablename, user_cert, self._ma_cert)
                os.remove(user_key_file)
                os.remove(cert_file)
        # End of loop over user_uuids

        if send_email_if_no_private_key:
            # Record file of people who had outside cert no private key (did CSR)
            if len(user_emails_no_key):
                fname = "/tmp/%s-user-emails-no-key.txt" % tablename
                with open(fname, 'w') as file:
                    for email in user_emails_no_key:
                        file.write(email)
                        file.write('\n')
                        
            # Record file of people who had outside cert with private key (need to re-download)
            if len(user_emails_with_key):
                fname = "/tmp/%s-user-emails-with-key.txt" % tablename
                with open(fname, 'w') as file:
                    for email in user_emails_with_key:
                        file.write(email);
                        file.write('\n')
            
    # end of create_certs_in_table


    def run(self):
        self.create_certs_in_table('ma_inside_key', False)
        self.create_certs_in_table('ma_outside_cert', True)


def main(argv=None):
    if not argv: argv = sys.argv

    updater = UserCertificateUpdater(argv)
    updater.run()

if __name__ == "__main__":
    sys.exit(main())

