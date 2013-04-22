# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

SRC=$(DESTDIR)/usr/share/geni-ch/sbin
DEST=$(DESTDIR)/usr/share/geni-ch/sbin
ETC=$(DESTDIR)/usr/local/etc
BIN=$(DESTDIR)/usr/local/bin

# Programs
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install clean cleandb
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

all:

# To date, these are the scripts to install, and it is just
# a subset of all the scripts that exist. If we need to
# install more, just add another line.
install:
	$(INSTALL) geni-ch-githash $(ETC)
	$(INSTALL) -d $(BIN)
	$(INSTALL) geni-ops-report $(BIN)
	$(INSTALL) report_genich_relations $(BIN)
	$(INSTALL) gmoc_list_contacts $(BIN)
	$(INSTALL) gmoc_list_slices $(BIN)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

cleandb:

clean:
