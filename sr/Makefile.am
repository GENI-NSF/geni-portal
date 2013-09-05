svcdatadir = $(pkgdatadir)/sr

cert_FILES = \
	certs/Thawte_Premium_Server_CA.pem \
	certs/exo-gpo-of.pem \
	certs/exo-bbn-am.pem \
	certs/exo-fiu-am.pem \
	certs/exo-nicta-am.pem \
	certs/exo-rci-am.pem \
	certs/exo-uh-am.pem \
	certs/exosm-am.pem \
	certs/genidesktop.netlab.uky.edu.pem \
        certs/gpo-of.pem \
	certs/i2-of.pem \
	certs/ig-gatech-boss.pem \
	certs/ig-gatech-cm.pem \
	certs/ig-gatech-of.pem \
	certs/ig-gpo-boss.pem \
	certs/ig-gpo-cm.pem \
        certs/ig-gpo-of.pem \
	certs/ig-kettering-boss.pem \
	certs/ig-kettering-cm.pem \
	certs/ig-ky-boss.pem \
	certs/ig-ky-cm.pem \
	certs/ig-northwestern-boss.pem \
	certs/ig-northwestern-cm.pem \
	certs/ig-northwestern-of.pem \
	certs/ig-utah-boss.pem \
	certs/ig-utah-cm.pem \
	certs/ig-utah-of.pem \
	certs/ig-utahddc-boss.pem \
	certs/ig-utahddc-cm.pem \
	certs/ig-utahddc-of.pem \
	certs/nlr-of.pem \
	certs/pg-uky-boss.pem \
	certs/pg-uky-cm.pem \
	certs/pgeni3-ca.pem \
	certs/pgeni3.pem \
	certs/uen-of.pem \
	certs/utah-am.pem \
	certs/irods.pem \
	certs/irods-test.pem

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
