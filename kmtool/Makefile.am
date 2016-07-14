svcdatadir = $(pkgdatadir)/km

# These two must go. Installing in a hardcoded path is bad for
# Automake/Autoconf, and installing in /var/www is bad for Ubuntu.
svcwebdir = /var/www/secure
svccssdir = /var/www/common/css

nobase_dist_svcdata_DATA = db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql

dist_svcweb_DATA = \
	www/kmtool/kmfooter.php \
	www/kmtool/km_utils.php \
	www/kmtool/kmactivate.php \
	www/kmtool/kmcert.php \
	www/kmtool/kmheader.php \
	www/kmtool/kmhome.php \
	www/kmtool/kmnoemail.php \
	www/kmtool/kmsendemail.php \
	www/kmtool/kmconfirmemail.php \
	www/kmtool/loadcert.js \
	www/kmtool/loadcert.php \
	www/kmtool/renewcert.php

dist_svccss_DATA = www/common/css/kmtool.css
