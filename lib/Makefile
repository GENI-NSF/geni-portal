
# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

DESTDIR=/usr/share/geni-ch/lib
WWWDIR=$(DESTDIR)/www
LIBDIR=$(DESTDIR)/php
DBDIR=$(DESTDIR)/db
PGDIR=$(DBDIR)/postgresql

# Programs
RSYNC = /usr/bin/rsync
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

default:
	echo "try make install"

install:
	$(WWWINSTALL) -d $(DESTDIR)
	$(WWWINSTALL) -d $(WWWDIR)
	$(WWWINSTALL) -d $(LIBDIR)
	$(WWWINSTALL) -d $(DBDIR)
	$(WWWINSTALL) -d $(PGDIR)
	$(WWWINSTALL) -m 0644 php/* $(LIBDIR)
#	$(WWWINSTALL) -m 0644 db/postgresql/* $(PGDIR)
#	$(WWWINSTALL) -m 0644 apache2.conf $(DESTDIR)


syncm:
	$(RSYNC) -aztv ../lib marilac.gpolab.bbn.com:

syncd:
	$(RSYNC) -aztv ../lib dagoola.gpolab.bbn.com:

syncpanther:
	$(RSYNC) -aztv ../lib panther.gpolab.bbn.com:

