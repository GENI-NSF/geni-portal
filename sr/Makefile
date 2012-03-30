
# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

DESTDIR=/usr/share/geni-ch/sr
WWWDIR=$(DESTDIR)/www
LIBDIR=$(DESTDIR)/php
DBDIR=$(DESTDIR)/db
PGDIR=$(DBDIR)/postgresql

PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
CLEANDB.SQL = db/postgresql/schema.sql db/postgresql/data.sql


# Programs
RSYNC = /usr/bin/rsync
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install clean distclean
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

install:
	$(WWWINSTALL) -d $(DESTDIR)
	$(WWWINSTALL) -d $(WWWDIR)
	$(WWWINSTALL) -d $(LIBDIR)
	$(WWWINSTALL) -d $(DBDIR)
	$(WWWINSTALL) -d $(PGDIR)
	$(WWWINSTALL) -m 0644 www/* $(WWWDIR)
	$(WWWINSTALL) -m 0644 php/* $(LIBDIR)
	$(WWWINSTALL) -m 0644 db/postgresql/* $(PGDIR)
	$(WWWINSTALL) -m 0644 apache2.conf $(DESTDIR)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

cleandb:
	cat $(CLEANDB.SQL) | $(PSQL) -U $(DB.USER) -h $(DB.HOST) $(DB.DB)

clean:

distclean:
	find . -name '*~' -exec rm {} \;
