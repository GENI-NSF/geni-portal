# Ubuntu settings
WWW_OWNER=www-data
WWW_GROUP=www-data

DESTDIR ?= /usr

CATOP    = $(DESTDIR)/share/geni-ch/CA
CASERIAL = $(CATOP)/serial
CAINDEX  = $(CATOP)/index.txt
CACONF   = $(CATOP)/openssl.cnf

INSTALL ?= /usr/bin/install
WWWINSTALL = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)

install: $(CATOP) $(CASERIAL) $(CAINDEX)
	$(WWWINSTALL) -d $(CATOP)/certs
	$(WWWINSTALL) -d $(CATOP)/crl
	$(WWWINSTALL) -d $(CATOP)/newcerts
	$(WWWINSTALL) -d $(CATOP)/private
	$(WWWINSTALL) -m 0644 openssl.cnf $(CACONF)

$(CATOP):
	$(WWWINSTALL) -d $(CATOP)

# Create an empty file with the right permissions, then initialize it
$(CASERIAL):
	$(WWWINSTALL) -m 0644 /dev/null $(CASERIAL)
	/bin/echo "00" > $(CASERIAL)

$(CAINDEX):
	$(WWWINSTALL) -m 0644 /dev/null $(CAINDEX)

uninstall:
	$(RM) -r $(CATOP)
