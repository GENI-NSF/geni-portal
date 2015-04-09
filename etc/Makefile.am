edit = sed \
	-e 's|@bindir[@]|$(bindir)|g' \
	-e 's|@pkgdatadir[@]|$(pkgdatadir)|g' \
	-e 's|@prefix[@]|$(prefix)|g'

example-services.ini ch-services.ini: Makefile
	rm -f $@ $@.tmp
	srcdir=''; \
	  test -f ./$@.in || srcdir=$(srcdir)/; \
	  $(edit) $${srcdir}$@.in >$@.tmp
	chmod a-w $@.tmp
	mv $@.tmp $@

example-services.ini: $(srcdir)/example-services.ini.in

ch-services.ini: $(srcdir)/ch-services.ini.in

# Distribute but do not install
EXTRA_DIST =  \
	ch-services.ini.in \
	example-services.ini.in

CLEANFILES =  \
	ch-services.ini \
	example-services.ini

pkgsysconfdir = $(sysconfdir)/geni-ch

dist_pkgsysconf_DATA = \
	example-settings.php \
	member-id-columns.dat

# Do not distribute
pkgsysconf_DATA = \
	ch-services.ini \
	example-services.ini

cleandb:
