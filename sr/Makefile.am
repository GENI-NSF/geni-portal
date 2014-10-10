svcdatadir = $(pkgdatadir)/sr

cert_FILES = \
	certs/InCommonServerCA.pem \
	certs/cenic-of.pem \
	certs/clemson-ig-boss.pem \
	certs/clemson-ig-cm.pem \
	certs/clemson-ig-of.pem \
	certs/cornell-ig-boss.pem \
	certs/cornell-ig-cm.pem \
	certs/cornell-ig-of.pem \
	certs/exo-fiu-am.pem \
	certs/exo-nicta-am.pem \
	certs/exo-rci-am.pem \
	certs/exo-uh-am.pem \
	certs/genidesktop.netlab.uky.edu.pem \
        certs/gpo-of.pem \
        certs/gr-bbn1-am.pem \
        certs/gr-bbn1-ch.pem \
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
	certs/illinois-ig-boss.pem \
	certs/illinois-ig-cm.pem \
	certs/illinois-ig-of.pem \
	certs/ion.pem \
	certs/irods.pem \
	certs/irods-test.pem \
	certs/kansas-ig-boss.pem \
	certs/kansas-ig-cm.pem \
	certs/kansas-ig-of.pem \
	certs/kettering-ig-of.pem \
	certs/max.pem \
	certs/max-ig-boss.pem \
	certs/max-ig-cm.pem \
	certs/max-ig-of.pem \
	certs/missouri-ig-boss.pem \
	certs/missouri-ig-cm.pem \
	certs/missouri-ig-of.pem \
	certs/nlr-of.pem \
	certs/nysernet-ig-boss.pem \
	certs/nysernet-ig-cm.pem \
	certs/nysernet-ig-of.pem \
	certs/nysernet-of.pem \
	certs/nyu-ig-boss.pem \
	certs/nyu-ig-cm.pem \
	certs/nyu-ig-of.pem \
	certs/pg-uky-boss.pem \
	certs/pg-uky-cm.pem \
	certs/renci-eg-of.pem \
	certs/sl-of.pem \
	certs/sox-ig-boss.pem \
	certs/sox-ig-cm.pem \
	certs/sox-ig-of.pem \
	certs/sox-of.pem \
	certs/stanford-ig-boss.pem \
	certs/stanford-ig-cm.pem \
	certs/stanford-ig-of.pem \
	certs/uen-of.pem \
	certs/uky-ig-of.pem \
	certs/utah-am.pem \
	certs/wisconsin-ig-boss.pem \
	certs/wisconsin-ig-cm.pem \
	certs/wisconsin-ig-of.pem

db_FILES = \
	db/postgresql/README.txt \
	db/postgresql/data.sql \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql \
	db/postgresql/update-3.sql \
	db/postgresql/update-4.sql


nobase_dist_svcdata_DATA = $(cert_FILES) \
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
