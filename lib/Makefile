
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

.PHONY: default install
.PHONY: syncm syncd synci syncs syncp

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

cleandb:

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

