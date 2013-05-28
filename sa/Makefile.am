svcdatadir = $(pkgdatadir)/sa
svcbindir = $(svcdatadir)/bin

nobase_dist_svcdata_DATA = \
	apache2.conf \
	www/sa_controller.php \
	www/sa.php \
	php/sa_db.php \
	php/sa_settings.php \
	php/sa_utils.php \
	db/postgresql/README.txt \
	db/postgresql/data.sql \
	db/postgresql/schema.sql

dist_svcbin_SCRIPTS = \
	bin/mkslicecert \
	bin/mkcred

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
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(DATA.SQL) $(DB.DB)
