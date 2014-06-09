#!/bin/sh

if test $(id -u) -eq 0
then
    echo "Cannot run do-make-install.sh as root"
    exit 1
fi
set -e
cd ~/proto-ch
autoreconf --install
./configure --prefix=/usr --sysconfdir=/etc --bindir=/usr/local/bin \
    --sbindir=/usr/local/sbin --mandir=/usr/local/man
make
sudo make install
