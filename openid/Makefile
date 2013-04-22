# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

SHARE=$(DESTDIR)/usr/share/geni-ch/openid
ETC=$(DESTDIR)/etc/geni-ch/openid

# Programs
INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

.PHONY: default install clean cleandb distclean
.PHONY: syncm syncd synci syncs syncp

default:
	@echo "Try make install"

all:

install:
	$(INSTALL) -d $(SHARE) $(ETC)
	cp -a src $(SHARE)
	cp -a Auth $(SHARE)
	cp -a doc/* $(SHARE)
	cp -a etc/* $(ETC)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

cleandb:

clean:

distclean:
	find . -name '*~' -exec rm {} \;
