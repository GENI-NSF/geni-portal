svcdatadir = $(pkgdatadir)/km

# These two must go. Installing in a hardcoded path is bad for
# Automake/Autoconf, and installing in /var/www is bad for Ubuntu.
svcwebdir = /var/www/secure
svccssdir = /var/www/common/css

nobase_dist_svcdata_DATA = db/postgresql/schema.sql

dist_svcweb_DATA = \
	www/kmtool/kmfooter.php \
	www/kmtool/km_utils.php \
	www/kmtool/kmactivate.php \
	www/kmtool/kmcert.php \
	www/kmtool/kmheader.php \
	www/kmtool/kmhome.php \
	www/kmtool/kmnoemail.php

dist_svccss_DATA = www/common/css/kmtool.css

# To do: transition database initialization to a script using
# the installed schema and data SQL files.
PSQL = /usr/bin/psql
DB.USER = portal
DB.HOST = localhost
DB.DB = portal
SCHEMA.SQL = db/postgresql/schema.sql

cleandb:
	$(PSQL) -U $(DB.USER) -h $(DB.HOST) -f $(SCHEMA.SQL) $(DB.DB)
