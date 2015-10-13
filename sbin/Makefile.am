pkgsysconfdir = $(sysconfdir)/geni-ch
templatesdir = $(pkgdatadir)/templates

edit = sed \
	-e 's|@bindir[@]|$(bindir)|g' \
	-e 's|@pkgdatadir[@]|$(pkgdatadir)|g' \
	-e 's|@pkgsysconfdir[@]|$(pkgsysconfdir)|g' \
	-e 's|@prefix[@]|$(prefix)|g' \
	-e 's|@templatesdir[@]|$(templatesdir)|g'

TEMPLATES = \
	geni-fetch-amdown \
	geni-portal-install-templates

TEMPLATES.IN = $(TEMPLATES:%=%.in)

$(TEMPLATES): Makefile
	rm -f $@ $@.tmp
	srcdir=''; \
		test -f ./$@.in || srcdir=$(srcdir)/; \
		$(edit) $${srcdir}$@.in >$@.tmp
	chmod a-w $@.tmp
	mv $@.tmp $@

%: $(srcdir)/%.in

# Distribute but do not install
EXTRA_DIST = $(TEMPLATES.IN)

CLEANFILES = $(TEMPLATES)

dist_sbin_SCRIPTS = \
	geni-fetch-amdown \
	geni-portal-install-templates \
	import_database.py \
	renew-certs \
	portal-backup \
	update_user_certs.py \
	gen-drop-constraints.sql \
	gen-add-constraints.sql
