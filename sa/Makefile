
# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

DESTDIR=/usr/share/geni-ch/sa
WWWDIR=$(DESTDIR)/www
LIBDIR=$(DESTDIR)/php
DBDIR=$(DESTDIR)/db
BINDIR=$(DESTDIR)/bin
PGDIR=$(DBDIR)/postgresql

PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
SCHEMA.SQL = db/postgresql/schema.sql
DATA.SQL = db/postgresql/data.sql

# Programs
RSYNC = /usr/bin/rsync
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install clean distclean
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

all:

install:
	$(WWWINSTALL) -d $(DESTDIR)
	$(WWWINSTALL) -d $(WWWDIR)
	$(WWWINSTALL) -d $(LIBDIR)
	$(WWWINSTALL) -d $(DBDIR)
	$(WWWINSTALL) -d $(BINDIR)
	$(WWWINSTALL) -d $(PGDIR)
	$(WWWINSTALL) bin/mkslicecert $(BINDIR)/mkslicecert
	$(WWWINSTALL) bin/mkcred $(BINDIR)/mkcred
	$(WWWINSTALL) -m 0644 www/* $(WWWDIR)
	$(WWWINSTALL) -m 0644 php/* $(LIBDIR)
	$(WWWINSTALL) -m 0644 db/postgresql/* $(PGDIR)
	$(WWWINSTALL) -m 0644 apache2.conf $(DESTDIR)

cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(DATA.SQL) $(DB.DB)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

clean:

distclean:
	find . -name '*~' -exec rm {} \;
