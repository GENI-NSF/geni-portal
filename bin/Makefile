# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

SRC=$(DESTDIR)/usr/share/geni-ch/sbin
DEST=$(DESTDIR)/usr/share/geni-ch/sbin
ETC=/usr/local/etc

# Programs
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install clean cleandb
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

install:
	$(INSTALL) geni-ch-githash $(ETC)
# NOTE: the three scripts in the bin directory are NOT installed with
# this script anymore.  Instead they are managed by Puppet.
#	/bin/cp gmoc_list_contacts $(DEST)
#	/bin/cp gmoc_list_slices $(DEST)
#	/bin/cp report_genich_relations $(DEST)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

cleandb:

clean:
