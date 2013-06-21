#!/bin/sh

set -e
cd ~/proto-ch
autoreconf --install
./configure --prefix=/usr --sysconfdir=/etc --bindir=/usr/local/bin --sbindir=/usr/local/sbin
make
sudo make install
