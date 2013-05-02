svcdatadir = $(pkgdatadir)/sr

cert_FILES = \
	certs/Thawte_Premium_Server_CA.pem \
	certs/exo-bbn-am.pem \
	certs/exo-rci-am.pem \
	certs/exosm-am.pem \
	certs/ig-gpo-boss.pem \
	certs/ig-gpo-cm.pem \
	certs/ig-utah-boss.pem \
	certs/ig-utah-cm.pem \
	certs/pg-uky-boss.pem \
	certs/pg-uky-cm.pem \
	certs/pgeni3-ca.pem \
	certs/pgeni3.pem \
	certs/utah-am.pem

www_FILES = www/sr_controller.php

apache_FILES = apache2.conf

db_FILES = \
	db/postgresql/README.txt \
	db/postgresql/data.sql \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql


nobase_dist_svcdata_DATA = $(cert_FILES) $(www_FILES) $(apache_FILES) \
				$(db_FILES)

# To do: transition database initialization to a script using
# the installed schema and data SQL files.
PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
SCHEMA.SQL = db/postgresql/schema.sql
DATA.SQL = db/postgresql/data.sql
cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
