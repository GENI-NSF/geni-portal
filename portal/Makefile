
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
LIB.DIR = $(DESTDIR)/share/geni-ch/portal
CA.DIR = $(DESTDIR)/share/geni-ch/CA
CA.CERT = $(CA.DIR)/cacert.pem
MA.DIR = $(DESTDIR)/share/geni-ch/ma
MA.CERT = $(CA.DIR)/ma-cert.pem
ABAC.DIR = $(LIB.DIR)/abac
GCF.D = $(LIB.DIR)/gcf.d
GCF.DIR = $(LIB.DIR)/gcf
# Replace with @sysconfdir@/geni-ch in automake
SYSCONFDIR = $(DESTDIR)/etc/geni-ch
SED = /bin/sed

SCHEMA.SQL = db/postgresql/schema.sql
DATA.SQL = db/postgresql/data.sql

default:
	echo "try make install"

all:

www:
	echo "No www directory present. Exiting."
	/bin/false

/var/www/secure:
	/bin/mkdir /var/www/secure

/var/www/images:
	/bin/mkdir /var/www/images

$(LIB.DIR):
	$(INSTALL.WWW) -d $(LIB.DIR)

$(GCF.D):
	$(INSTALL.WWW) -d $(GCF.D)

# Install gcf.ini as an example in the sys conf dir (usually "etc")
$(GCF.D)/gcf.ini: gcf.d/gcf.ini
	$(INSTALL) -m 0644 gcf.d/gcf.ini $(SYSCONFDIR)/example-gcf.ini

$(GCF.D)/omni.ini: gcf.d/omni.ini
	$(INSTALL.WWW) -m 644 $^ $@

$(GCF.D)/trusted_roots:
	$(INSTALL.WWW) -d $@

# pgch must trust the cacert
$(GCF.D)/trusted_roots/cacert.pem:
	if [ -f $(CA.CERT) ]; then \
	  /bin/rm -f $@; \
	  /bin/ln -s $(CA.CERT) $@; \
	else \
	  echo "$(CA.CERT) does not exist. Not linking to $@."; \
	fi

# pgch must also trust the ma-cert
$(GCF.D)/trusted_roots/ma-cert.pem:
	if [ -f $(MA.CERT) ]; then \
	  /bin/rm -f $@; \
	  /bin/ln -s $(MA.CERT) $@; \
	else \
	  echo "$(MA.CERT) does not exist. Not linking to $@."; \
	fi

$(GCF.DIR):
	$(error Directory $(GCF.DIR) does not exist. Please install gcf.)

$(GCF.DIR)/src/gcf-pgch.py: gcf.d/src/gcf-pgch.py
	$(INSTALL.WWW) $^ $@

$(GCF.DIR)/src/geni/pgch.py: gcf.d/src/geni/pgch.py
	$(INSTALL.WWW) -m 644 $^ $@

$(GCF.DIR)/src/omni_php.py: gcf.d/src/omni_php.py
	$(INSTALL.WWW) $^ $@

$(GCF.DIR)/src/logging.conf: gcf.d/src/logging.conf
	$(INSTALL.WWW) $^ $@

GCF_DEPS = 	$(GCF.D) \
		$(GCF.D)/gcf.ini \
		$(GCF.D)/omni.ini \
		$(GCF.D)/trusted_roots \
		$(GCF.D)/trusted_roots/cacert.pem \
		$(GCF.D)/trusted_roots/ma-cert.pem \
		$(GCF.DIR) \
		$(GCF.DIR)/src/gcf-pgch.py \
		$(GCF.DIR)/src/geni/pgch.py \
		$(GCF.DIR)/src/omni_php.py \
		$(GCF.DIR)/src/logging.conf

install-gcf: $(GCF_DEPS)


install: $(LIB.DIR) www install-gcf /var/www/secure /var/www/images
	/bin/cp www/portal/* /var/www/secure
	/bin/cp -r flack/* /var/www/secure
	/bin/cp www/images/* /var/www/images
	/bin/cp -r www/common /var/www/
	/bin/cp www/*.php /var/www/
	/bin/cp www/favicon.ico /var/www/favicon.ico
	$(INSTALL.WWW) -m 755 -d /var/www/policy
	$(INSTALL.WWW) -m 644 www/policy/* /var/www/policy
	$(INSTALL.WWW) -m 644 apache2-http.conf $(LIB.DIR)
	@echo
	@echo "*** Remember to check www/portal/settings.php! ***"
	@echo

cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(DATA.SQL) $(DB.DB)

syncm syncd synci syncs syncp:
	(cd .. && $(MAKE) $@)

$(ABAC.DIR):
	mkdir -p $(ABAC.DIR)


$(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem: $(ABAC.DIR)
	/usr/local/bin/creddy --generate --cn GeniPortal
	/bin/mv GeniPortal_ID.pem GeniPortal_private.pem $(ABAC.DIR)
	/bin/chown www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem
	/bin/chgrp www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem

abac: $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem

gcf: 
	/bin/cp -r gcf.d $(LIB.DIR)
