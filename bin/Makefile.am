edit = sed \
	-e 's|@bindir[@]|$(bindir)|g' \
	-e 's|@pkgdatadir[@]|$(pkgdatadir)|g' \
	-e 's|@prefix[@]|$(prefix)|g'

geni-get-ad-rspecs: Makefile
	rm -f $@ $@.tmp
	srcdir=''; \
	  test -f ./$@.in || srcdir=$(srcdir)/; \
	  $(edit) $${srcdir}$@.in >$@.tmp
	chmod a-w $@.tmp
	mv $@.tmp $@

geni-get-ad-rspecs: $(srcdir)/geni-get-ad-rspecs.in

# Distribute but do not install
EXTRA_DIST =  \
	geni-get-ad-rspecs.in

CLEANFILES =  \
	geni-get-ad-rspecs

pkgsysconfdir = $(sysconfdir)/geni-ch

dist_bin_SCRIPTS = \
		geni-manage-maintenance \
		geni-parse-map-data \
		geni-sync-wireless \
		geni-watch-omni

bin_SCRIPTS = \
	geni-get-ad-rspecs

if INSTALL_GITHASH
dist_pkgsysconf_DATA = geni-ch-githash
endif

localpythondir = $(subst /usr/lib,/usr/local/lib,$(pythondir))
nobase_dist_localpython_DATA = portal_utils/__init__.py portal_utils/orbit_interface.py

cleandb:
