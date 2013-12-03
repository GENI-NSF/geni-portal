import optparse
import sys

class RollbackComputation:
    def __init__(self, new_filename, old_filename, insert_template, delete_template):
        self._new_filename = new_filename
        self._old_filename = old_filename
        self._insert_template = insert_template
        self._delete_template = delete_template

    def _read_entries(self, filename):
        data = open(filename).read()
        lines = data.split('\n')
        entries = []
        for line in lines:
            if len(line) == 0: continue
            entry = [elt.strip() for elt in line.split('|')]
            entries.append(tuple(entry))
        return entries

    def compute(self):
        new_entries = self._read_entries(self._new_filename)
        old_entries = self._read_entries(self._old_filename)

        for new_entry in new_entries:
            if new_entry not in old_entries:
                print self._insert_template % new_entry

        for old_entry in old_entries:
            if old_entry not in new_entries:
                print self._delete_template % old_entry

def main(args):
    parser = optparse.OptionParser()
    parser.add_option("--old_file")
    parser.add_option("--new_file")
    parser.add_option("--insert_template")
    parser.add_option("--delete_template")
    [opts, args] = parser.parse_args(args)

    rc = RollbackComputation(opts.new_file, opts.old_file, opts.insert_template, opts.delete_template)
    rc.compute()


if __name__ == "__main__":
    sys.exit(main(sys.argv))
