svcdatadir = $(pkgdatadir)/cs

nobase_dist_svcdata_DATA = \
	db/postgresql/data.sql \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql \
	db/postgresql/update-3.sql \
	db/postgresql/update-4.sql \
	db/postgresql/update-5.sql \
	db/postgresql/enable_lockdown.sql \
	db/postgresql/disable_lockdown.sql

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
