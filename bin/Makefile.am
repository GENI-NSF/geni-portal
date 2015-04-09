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

dist_sbin_SCRIPTS = geni-init-ca geni-init-services

dist_bin_SCRIPTS = \
		assert_email.sh \
		geni-add-member-attribute \
		geni-add-project-member \
		geni-add-trusted-tool \
		geni-delete-outside-cert \
		geni-disable-user \
		geni-enable-user \
		geni-manage-maintenance \
		geni-ops-report \
		geni-parse-map-data \
		geni-remove-member-attribute \
		geni-remove-project-member \
		geni-watch-omni \
		gmoc_list_contacts \
		gmoc_list_slices \
		report_genich_relations

bin_SCRIPTS = \
	geni-get-ad-rspecs

if INSTALL_GITHASH
dist_pkgsysconf_DATA = geni-ch-githash
endif

cleandb:
