#----------------------------------------------------------------------
# Copyright (c) 2013 Raytheon BBN Technologies
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
import sys
import subprocess

def parse_args(argv):
    parser = optparse.OptionParser()
    parser.add_option("--old_ma_cert", help="location of old MA cert")
    parser.add_option("--new_ma_cert", help="location of new MA cert")

    options, args = parser.parse_args(argv)

    if not options.old_ma_cert or not options.new_ma_cert:
        parser.print_help()
        sys.exit()

    return options

# Get contents of file
def read_file(filename):
    f = open(filename, 'r')
    contents = f.read()
    f.close()
    return contents

# Run SQL query and dump output into given file
def run_sql(sql, outfile):
    cmd = ['psql', 'portal', '-U',  'portal', '-h', 'localhost', '-c', sql, '-o', outfile, '-t' , '-q']
    output = subprocess.call(cmd);

# Update user certificate in database table
def update_certificate(user_id, table_name, user_cert, ma_cert):
    new_cert = user_cert + ma_cert
    sql = "update %s set certificate = '%s' where id = %d" % (table_name, new_cert, user_id)
    outfile = '/tmp/insert.txt'
    run_sql(sql, outfile)

def update_certs_in_table(tablename, old_ma_cert, new_ma_cert):
    sql = "select id from %s" % tablename
    outfile = "/tmp/user_ids.txt"
    run_sql(sql, outfile)

    user_ids = read_file(outfile).split('\n')
    for user_id in user_ids:
        user_id = user_id.strip()
        if len(user_id) == 0: continue
        user_id = int(user_id)

        sql = "select certificate from %s where id = %d" % (tablename, user_id);
        outfile = "/tmp/cert-%d.txt" % user_id
        run_sql(sql, outfile)
        cert = read_file(outfile)

        # Need to split into lines, take off the leading space and rejoin
        lines = cert.split('\n')
        trimmed_lines = [line[1:] for line in lines]
        cert = '\n'.join(trimmed_lines)
        end_certificate = 'END CERTIFICATE-----\n'
        cert_pieces = cert.split(end_certificate);
        user_cert = cert_pieces[0] + end_certificate
        ma_cert = cert_pieces[1] + end_certificate

        if ma_cert == old_ma_cert:
            print "Replacing old MA cert with new MA cert: user ID %d table %s" % (user_id, tablename)
            update_certificate(user_id, tablename, user_cert, new_ma_cert)
        elif ma_cert == new_ma_cert:
            print "Already associated with new MA cert: user ID %d table %s" % (user_id, tablename)
        else:
            print "MA cert unknown: user ID %d table %s" % (user_id, tablename)


def main(argv=None):
    if not argv: argv = sys.argv

    options = parse_args(argv)

    old_ma_cert = read_file(options.old_ma_cert)
    new_ma_cert = read_file(options.new_ma_cert)

    update_certs_in_table('ma_inside_key', old_ma_cert, new_ma_cert)
    update_certs_in_table('ma_outside_cert', old_ma_cert, new_ma_cert)

if __name__ == "__main__":
    sys.exit(main())

