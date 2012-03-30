
.PHONY = default install cleandb syncm abac

RSYNC = /usr/bin/rsync
PSQL = /usr/bin/psql
INSTALL = /usr/bin/install
INSTALL.WWW = /usr/bin/install -o www-data -g www-data
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
CLEANDB.SQL = db/portal-schema.sql db/portal-data.sql
LIB.DIR = /usr/share/geni-ch/portal
ABAC.DIR = $(LIB.DIR)/abac

default:
	echo "try make install"

www:
	echo "No www directory present. Exiting."
	/bin/false

/var/www/secure:
	sudo /bin/mkdir /var/www/secure

/var/www/images:
	sudo /bin/mkdir /var/www/images

install: www
	sudo /bin/cp www/portal/* /var/www/secure
	sudo /bin/cp www/images/* /var/www/images
	sudo /bin/cp -r www/common /var/www/
	sudo $(INSTALL.WWW) -m 755 -d /var/www/policy
	sudo $(INSTALL.WWW) -m 644 www/policy/* /var/www/policy
	@echo
	@echo "*** Remember to check www/portal/settings.php! ***"
	@echo

cleandb:
	cat $(CLEANDB.SQL) | $(PSQL) -U $(DB.USER) -h $(DB.HOST) $(DB.DB)

syncm:
	$(RSYNC) -aztv ../portal marilac.gpolab.bbn.com:

syncd:
	$(RSYNC) -aztv ../portal dagoola.gpolab.bbn.com:

syncpanther:
	$(RSYNC) -aztv ../portal panther.gpolab.bbn.com:

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
