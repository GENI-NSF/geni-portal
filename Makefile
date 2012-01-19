
.PHONY = default install cleandb syncm abac

RSYNC = /usr/bin/rsync
PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
CLEANDB.SQL = db/portal-schema.sql db/portal-data.sql
LIB.DIR = /usr/share/geni-portal
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
	@echo
	@echo "*** Remember to check www/portal/settings.php! ***"
	@echo

cleandb:
	cat $(CLEANDB.SQL) | $(PSQL) -U $(DB.USER) -h $(DB.HOST) $(DB.DB)

syncm:
	$(RSYNC) -aztv ../proto-ch marilac.gpolab.bbn.com:

syncd:
	$(RSYNC) -aztv ../proto-ch dagoola.gpolab.bbn.com:

syncpanther:
	$(RSYNC) -aztv ../proto-ch panther.gpolab.bbn.com:

$(ABAC.DIR):
	sudo mkdir -p $(ABAC.DIR)


$(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem: $(ABAC.DIR)
	/usr/local/bin/creddy --generate --cn GeniPortal
	/usr/bin/sudo /bin/mv GeniPortal_ID.pem GeniPortal_private.pem $(ABAC.DIR)
	/usr/bin/sudo /bin/chown www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem
	/usr/bin/sudo /bin/chgrp www-data $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem

abac: $(ABAC.DIR)/GeniPortal_ID.pem $(ABAC.DIR)/GeniPortal_private.pem
