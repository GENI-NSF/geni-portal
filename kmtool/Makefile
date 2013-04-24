PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
SCHEMA.SQL = db/postgresql/schema.sql

default:
	echo "try make install"

all:

www:
	echo "No www directory present. Exiting."
	/bin/false

/var/www/secure:
	/bin/mkdir /var/www/secure

install: www
	/bin/cp www/kmtool/* /var/www/secure
	/bin/cp -r www/common /var/www

# empty target for compatibility
cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
