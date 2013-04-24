PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
SCHEMA.SQL = db/postgresql/schema.sql

default:
	echo "try make install"

all:

/var/www/idp:
	/bin/mkdir /var/www/idp

install: /var/www/idp
	/bin/cp www/* /var/www/idp

# empty target for compatibility
cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
