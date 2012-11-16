
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

.PHONY: default install clean distclean
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

#install:
#	$(WWWINSTALL) -d $(DESTDIR)
#	for d in lib portal sa sr authz cs ma pa logging; do \
#	  (cd "$${d}" && $(MAKE) $@) \
#	done

bin/geni-ch-githash: .git
	git rev-parse HEAD > bin/geni-ch-githash

syncd: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch dagoola.gpolab.bbn.com:

syncm: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch marilac.gpolab.bbn.com:

synci: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch illyrica.gpolab.bbn.com:

syncs: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch sergyar.gpolab.bbn.com:

synct: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch tau-ceti.gpolab.bbn.com:

syncp: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch panther.gpolab.bbn.com:

syncc: bin/geni-ch-githash
	$(RSYNC) --exclude .git -aztv ../proto-ch cascade.gpolab.bbn.com:

clean:

distclean:
	find . -name '*~' -exec rm {} \;


# Thanks to http://highlandsun.com/hyc/GNUYou.htm
SUBDIRS = lib portal kmtool sa sr authz cs ma pa logging ca openid bin
install cleandb:
	@$(MAKE) $(SUBDIRS) TARG=$@
$(SUBDIRS)::
	@cd $@; echo making $(TARG) in $@...; \
	$(MAKE) $(TARG)
.PHONY: $(SUBDIRS)
