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
	sudo /bin/cp geni-ch-githash $(ETC)/geni-ch-githash
#	sudo /bin/cp gmoc_list_contacts $(DEST)
#	sudo /bin/cp gmoc_list_slices $(DEST)
#	sudo /bin/cp report_genich_relations $(DEST)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

cleandb:

clean:
