
DESTDIR ?= /usr

.PHONY = default install cleandb abac gcf
.PHONY: syncm syncd synci syncs syncp

RSYNC = /usr/bin/rsync
PSQL = /usr/bin/psql
INSTALL = /usr/bin/install
INSTALL.WWW = /usr/bin/install -o www-data -g www-data
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
CLEANDB.SQL = db/postgresql/schema.sql db/postgresql/data.sql
LIB.DIR = $(DESTDIR)/share/geni-ch/portal
CA.DIR = $(DESTDIR)/share/geni-ch/CA
MA.DIR = $(DESTDIR)/share/geni-ch/ma
ABAC.DIR = $(LIB.DIR)/abac
GCF.D = $(LIB.DIR)/gcf.d
GCF.DIR = $(LIB.DIR)/gcf
FQDN = $(shell /bin/hostname -f)
SHORT_HOST = $(shell /bin/hostname -s)
SED = /bin/sed

default:
	echo "try make install"

www:
	echo "No www directory present. Exiting."
	/bin/false

/var/www/secure:
	sudo /bin/mkdir /var/www/secure

/var/www/images:
	sudo /bin/mkdir /var/www/images

$(LIB.DIR):
	$(INSTALL.WWW) -d $(LIB.DIR)

$(GCF.D):
	$(INSTALL.WWW) -d $(GCF.D)

# Perform some replacements when install gcf.ini
$(GCF.D)/gcf.ini: gcf.d/gcf.ini
	$(SED) -e 's/localhost/$(FQDN)/g' < $^ \
	  | $(SED) -e 's/base_name=.*/base_name=$(SHORT_HOST)/' > $@
	chown www-data.www-data $@
	chmod 0644 $@

$(GCF.D)/omni.ini: gcf.d/omni.ini
	$(INSTALL.WWW) -m 644 $^ $@

$(GCF.D)/trusted_roots:
	$(INSTALL.WWW) -d $@

# pgch must trust the cacert
$(GCF.D)/trusted_roots/cacert.pem:
	/bin/ln -s $(CA.DIR)/cacert.pem $@

# pgch must also trust the ma-cert
$(GCF.D)/trusted_roots/ma-cert.pem:
	/bin/ln -s $(MA.DIR)/ma-cert.pem $@

$(GCF.DIR)/src/gcf-pgch.py: gcf.d/src/gcf-pgch.py
	$(INSTALL.WWW) $^ $@

$(GCF.DIR)/src/geni/pgch.py: gcf.d/src/geni/pgch.py
	$(INSTALL.WWW) -m 644 $^ $@

$(GCF.DIR)/src/omni_php.py: gcf.d/src/omni_php.py
	$(INSTALL.WWW) $^ $@

GCF_DEPS = 	$(GCF.D) \
		$(GCF.D)/gcf.ini \
		$(GCF.D)/omni.ini \
		$(GCF.D)/trusted_roots \
		$(GCF.D)/trusted_roots/cacert.pem \
		$(GCF.D)/trusted_roots/ma-cert.pem \
		$(GCF.DIR)/src/gcf-pgch.py \
		$(GCF.DIR)/src/geni/pgch.py \
		$(GCF.DIR)/src/omni_php.py

install-gcf: $(GCF_DEPS)


install: $(LIB.DIR) www install-gcf
	sudo /bin/cp www/portal/* /var/www/secure
	sudo /bin/cp -r flack/* /var/www/secure
	sudo /bin/cp www/images/* /var/www/images
	sudo /bin/cp -r www/common /var/www/
	sudo /bin/cp www/*.php /var/www/
	sudo $(INSTALL.WWW) -m 755 -d /var/www/policy
	sudo $(INSTALL.WWW) -m 644 www/policy/* /var/www/policy
	sudo $(INSTALL.WWW) -m 644 apache2-http.conf $(LIB.DIR)
	@echo
	@echo "*** Remember to check www/portal/settings.php! ***"
	@echo

cleandb:
	cat $(CLEANDB.SQL) | $(PSQL) -U $(DB.USER) -h $(DB.HOST) $(DB.DB)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

$(ABAC.DIR):
	sudo mkdir -p $(ABAC.DIR)


$(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem: $(ABAC.DIR)
	/usr/local/bin/creddy --generate --cn GeniPortal
	/usr/bin/sudo /bin/mv GeniPortal_ID.pem GeniPortal_private.pem $(ABAC.DIR)
	/usr/bin/sudo /bin/chown www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem
	/usr/bin/sudo /bin/chgrp www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem

abac: $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem

gcf: 
	sudo /bin/cp -r gcf.d $(LIB.DIR)
