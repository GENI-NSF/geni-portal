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

# Update user certificate in database
def update_certificate(user_id, user_cert, ma_cert):
    new_cert = user_cert + ma_cert
    sql = "update ma_inside_key set certificate = '%s' where id = %d" % (new_cert, user_id)
    outfile = '/tmp/insert.txt'
    run_sql(sql, outfile)

def main(argv=None):
    if not argv: argv = sys.argv

    options = parse_args(argv)

    old_ma_cert = read_file(options.old_ma_cert)
    new_ma_cert = read_file(options.new_ma_cert)

    sql = "select id from ma_inside_key"
    outfile = "/tmp/user_ids.txt"
    run_sql(sql, outfile)

    user_ids = read_file(outfile).split('\n')
    for user_id in user_ids:
        user_id = user_id.strip()
        if len(user_id) == 0: continue
        user_id = int(user_id)

        sql = "select certificate from ma_inside_key where id = %d" % user_id;
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
            print "Replacing old MA cert with new MA cert: user ID %d" % user_id
            update_certificate(user_id, user_cert, new_ma_cert)
        elif ma_cert == new_ma_cert:
            print "Already associated with new MA cert: user ID %d" % user_id
        else:
            print "MA cert unknown: user ID %d" % user_id

if __name__ == "__main__":
    sys.exit(main())

