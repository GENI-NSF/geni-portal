
# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

DESTDIR=/usr/share/geni-ch
WWWDIR=$(DESTDIR)/www
LIBDIR=$(DESTDIR)/php
DBDIR=$(DESTDIR)/db
PGDIR=$(DBDIR)/postgresql

# Programs
RSYNC = /usr/bin/rsync
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install syncd syncm synci syncp clean distclean

default:
	@echo "Try make install"

install:
	$(WWWINSTALL) -d $(DESTDIR)
	for d in lib portal sa; do \
	  (cd "$${d}" && $(MAKE) $@) \
	done

syncd:
	$(RSYNC) --exclude .git -aztv ../proto-ch dagoola.gpolab.bbn.com:

syncm:
	$(RSYNC) -aztv ../proto-ch marilac.gpolab.bbn.com:

synci:
	$(RSYNC) -aztv ../proto-ch illyrica.gpolab.bbn.com:

syncp:
	$(RSYNC) -aztv ../proto-ch panther.gpolab.bbn.com:

clean:

distclean:
	find . -name '*~' -exec rm {} \;
