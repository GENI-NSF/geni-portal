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

pkgsysconfdir = $(sysconfdir)/geni-ch

dist_pkgsysconf_DATA = example-services.ini example-settings.php

# Do not distribute ch-services
pkgsysconf_DATA = ch-services.ini

cleandb:
