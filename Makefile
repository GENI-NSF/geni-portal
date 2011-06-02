
.PHONY = default install cleandb syncm

RSYNC = /usr/bin/rsync
PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
CLEANDB.SQL = db/portal-schema.sql db/portal-data.sql

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

cleandb:
	cat $(CLEANDB.SQL) | $(PSQL) -U $(DB.USER) -h $(DB.HOST) $(DB.DB)

syncm:
	$(RSYNC) -aztv ../proto-ch marilac.gpolab:

syncpanther:
	$(RSYNC) -aztv ../proto-ch panther.gpolab:
