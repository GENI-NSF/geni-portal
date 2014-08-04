#!/bin/sh

# A script to configure a GPO lab VM with the right stuff
# to allow the prototype clearinghouse to run.

set -x 
set -e 
#
# gcf installation
#
SHARE_DIR=/usr/share/geni-ch

# Make a directory for gcf to live in
if [ ! -d "${SHARE_DIR}" ]; then
  /usr/bin/sudo /bin/mkdir -p "${SHARE_DIR}"
fi

GCF=gcf-2.5
GCF_PKG=${GCF}.tar.gz
/usr/bin/wget http://www.gpolab.bbn.com/internal/projects/proto-ch/${GCF_PKG}
/usr/bin/sudo /bin/tar xzfC "${GCF_PKG}" "${SHARE_DIR}"
/usr/bin/sudo /bin/ln -s -f ${SHARE_DIR}/${GCF} ${SHARE_DIR}/gcf

exit 0
