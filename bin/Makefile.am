pkgsysconfdir = $(sysconfdir)/geni-ch

dist_sbin_SCRIPTS = geni-init-ca geni-init-services

dist_bin_SCRIPTS = \
		assert_email.sh \
		geni-add-member-privilege \
		geni-delete-outside-cert \
		geni-add-member-attribute \
		geni-remove-member-attribute \
		geni-add-project-member \
		geni-add-trusted-tool \
		geni-manage-maintenance \
		geni-ops-report \
		geni-remove-project-member \
		geni-revoke-member-privilege \
		geni-enable-user \
		geni-disable-user \
		geni-get-ad-rspecs \
		geni-parse-map-data \
		gmoc_list_contacts \
		gmoc_list_slices \
		report_genich_relations

dist_pkgsysconf_DATA = geni-ch-githash

# GPO Lab
# Install in /usr/local/lib, not /usr/lib
localpythondir = $(subst /usr/lib,/usr/local/lib,$(pythondir))
nobase_dist_localpython_DATA = chapiclient/__init__.py chapiclient/chapi.py

cleandb:
