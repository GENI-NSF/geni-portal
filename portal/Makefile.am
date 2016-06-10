# TODO Rename gcf.d/gcf.ini -> gcf.d/example-gcf.ini (see below)

svcdatadir = $(pkgdatadir)/portal
gcfsrcdir = $(svcdatadir)/gcf/src
gcfsrcgenidir = $(gcfsrcdir)/geni
pkgsysconfdir = $(sysconfdir)/geni-ch

# This is silly -- easier to rename the file in git
# TODO: rename the file in git and remove this extraneous target
gcf.d/example-gcf.ini:
	cp gcf.d/gcf.ini gcf.d/example-gcf.ini

# These two must go. Installing in a hardcoded path is bad for
# Automake/Autoconf, and installing in /var/www is bad for Ubuntu.
svcwebtopdir = $(sysconfdir)/../var/www

svcwebdir = $(svcwebtopdir)/secure
svcwebimagesdir = $(svcwebtopdir)/images
svcwebcommondir = $(svcwebtopdir)/common
svcwebcssdir = $(svcwebtopdir)/common/css
svcwebmapdir = $(svcwebtopdir)/common/map
svcwebpolicydir = $(svcwebtopdir)/policy

edit = sed \
	-e 's|@bindir[@]|$(bindir)|g' \
	-e 's|@pkgdatadir[@]|$(pkgdatadir)|g' \
	-e 's|@pkgsysconfdir[@]|$(pkgsysconfdir)|g' \
	-e 's|@prefix[@]|$(prefix)|g'

TEMPLATES = \
	www/amstatus.php

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

nobase_dist_svcdata_DATA = \
	apache2-http.conf \
	db/postgresql/data.sql \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql \
	db/postgresql/update-3.sql \
	db/postgresql/update-4.sql \
	db/postgresql/update-5.sql \
	db/postgresql/update-6.sql \
	db/postgresql/update-7.sql \
	db/postgresql/update-8.sql \
	db/postgresql/update-9.sql \
	db/postgresql/update-10.sql \
	db/postgresql/update-11.sql \
	db/postgresql/update-12.sql \
	gcf.d/example-gcf.ini

#----------------------------------------------------
# GCF related files that overwrite the installed gcf
#----------------------------------------------------
dist_gcfsrc_DATA = \
	gcf.d/src/logging.conf \
	gcf.d/src/stitcher_logging_template.conf
dist_gcfsrc_SCRIPTS = \
	gcf.d/src/omni_php.py \
	gcf.d/src/stitcher_php.py
dist_gcfsrcgeni_DATA =

dist_svcweb_DATA = \
	www/portal/accept-project-invite.php \
	www/portal/admin.php \
	www/portal/aggregates.php \
	www/portal/amdetails.php \
	www/portal/amstatus.js \
	www/portal/amstatus.php \
	www/portal/ask-for-project.php \
	www/portal/cancel-join-project.php \
	www/portal/cards.js \
	www/portal/certificate.php \
	www/portal/contact-us.php \
	www/portal/confirm-sliverdelete.php \
	www/portal/createimage.php \
	www/portal/createslice.php \
	www/portal/createsliver.php \
	www/portal/dashboard.js \
	www/portal/dashboard.php \
	www/portal/db_error_test.php \
	www/portal/debug_clearcache.php \
	www/portal/deletesliver.php \
	www/portal/deletesshkey.php \
	www/portal/disable-slice.php \
	www/portal/do-accept-project-invite.php \
	www/portal/do-downloadputtykey.php \
	www/portal/do-delete-project-member.php \
	www/portal/do-disable-slice.php \
	www/portal/do-edit-project-member.php \
	www/portal/do-edit-project-membership.php \
	www/portal/do-edit-project.php \
	www/portal/do-edit-slice-member.php \
	www/portal/do-edit-slice.php \
	www/portal/do-handle-lead-request.php \
	www/portal/do-handle-project-request.php \
	www/portal/do-get-logs.php \
	www/portal/do-modify.php \
	www/portal/do-register.php \
	www/portal/do-renew-slice.php \
	www/portal/do-renew.php \
	www/portal/do-slice-search.php \
	www/portal/do-update-keys.php \
	www/portal/do-update-user-preferences.php \
	www/portal/do-upload-project-members.php \
	www/portal/do-user-admin.php \
	www/portal/do-user-search.php \
	www/portal/dologout.php \
	www/portal/downloadkeycert.php \
	www/portal/downloadomnibundle.php \
	www/portal/downloadputtykey.php \
	www/portal/downloadsshkey.php \
	www/portal/downloadsshpublickey.php \
	www/portal/edit-project-member.php \
	www/portal/edit-project.php \
	www/portal/edit-slice-member.php \
	www/portal/edit-slice.php \
	www/portal/env.php \
	www/portal/error-text.php \
	www/portal/future.json \
	www/portal/gemini.php \
	www/portal/gemini_add_global_node.php \
	www/portal/get_omni_invocation_data.php \
	www/portal/irods.php \
	www/portal/generatesshkey.php \
	www/portal/getversion.php \
	www/portal/handle-project-request.php \
	www/portal/help.php \
	www/portal/index.php \
	www/portal/home.php \
	www/portal/image_operations.php \
	www/portal/invite-to-geni.php \
	www/portal/invite-to-project.php \
        www/portal/jacks-app.css \
        www/portal/jacks-app.js \
        www/portal/jacks-app-details.php \
        www/portal/jacks-app-expanded.php \
        www/portal/jacks-app-reserve.php \
        www/portal/jacks-app-status.php \
        www/portal/jacks-editor-app.css \
	www/portal/jacks-editor-app.js \
	www/portal/jacks-editor-app-expanded.php \
	www/portal/jacks-lib.js \
	www/portal/jfed.php \
	www/portal/join-project.php \
	www/portal/join-this-project.php \
	www/portal/listresources.php \
	www/portal/listresources_plain.php \
	www/portal/maintenance_redirect_page.php \
	www/portal/modify.php \
	www/portal/omni-bundle.php \
	www/portal/permission_manager_test.php \
	www/portal/portal_omni_config.php \
	www/portal/portal-jacks-app.js \
	www/portal/portal-jacks-editor-app.js \
	www/portal/preferences.php \
	www/portal/print-text.php \
	www/portal/profile.php \
	www/portal/project-member.php \
	www/portal/project.php \
	www/portal/projects.php \
	www/portal/raw-sliverstatus.php \
	www/portal/renewsliver.php \
	www/portal/request_test.php \
	www/portal/restartsliver.php \
	www/portal/rspecdelete.php \
	www/portal/rspecdownload.php \
	www/portal/rspecs.php \
	www/portal/rspecupdate.php \
	www/portal/rspecupload.php \
	www/portal/rspecview.php \
	www/portal/rspecuploadparser.php \
	www/portal/saverspectoserver.php \
	www/portal/savi.php \
	www/portal/selectrspec.html \
	www/portal/send_bug_report.php \
	www/portal/slice-add-resources.js \
	www/portal/slice-add-resources.php \
	www/portal/slice-add-resources-jacks.css \
	www/portal/slice-add-resources-jacks.js \
	www/portal/slice-add-resources-jacks.php \
	www/portal/slice-map-view.php \
	www/portal/slice-map-data.php \
	www/portal/slice-member.php \
	www/portal/slice.js \
	www/portal/slice.php \
	www/portal/slice-jacks.css \
	www/portal/slice-table.css \
	www/portal/slicecred.php \
	www/portal/sliceresource.php \
	www/portal/slices.php \
	www/portal/sliverdelete.php \
	www/portal/speaks-for.css \
	www/portal/speaks-for.js \
	www/portal/speaks-for.php \
	www/portal/speaks-for-delete.php \
	www/portal/speaks-for-upload.php \
	www/portal/sshkeyedit.php \
	www/portal/status_constants_import.php \
	www/portal/tool-aggwarning.php \
	www/portal/tool-omniconfig.php \
	www/portal/tool-slices.js \
	www/portal/tools-user.js \
	www/portal/updatekeys.js \
	www/portal/updatekeys.php \
	www/portal/upload-file.php \
	www/portal/upload-project-members.php \
	www/portal/uploadsshkey.php \
	www/portal/wimax-enable.php \
	www/portal/wireless_operations.php \
	www/portal/wireless_redirect.php

dist_svcwebimages_DATA = \
	www/images/EG-VM-noTxt-centered.svg \
	www/images/EG-VM-noTxt.svg \
	www/images/EG-VM.svg \
	www/images/geni.png \
	www/images/geni_globe.png \
	www/images/geni_globe_small.png \
	www/images/geni-header-left.png \
	www/images/geni-header-right.png \
	www/images/header-home.jpg \
	www/images/menu.png \
	www/images/openVZvm-noTxt-centered.svg \
	www/images/openVZvm-noTxt.svg \
	www/images/openVZvm.svg \
	www/images/orbit_banner.png \
	www/images/witest-logo-white.png \
	www/images/pin.png \
	www/images/portal.png \
	www/images/portal2.png \
	www/images/RawPC-EG-noTxt-centered.svg \
	www/images/RawPC-EG-noTxt.svg \
	www/images/RawPC-EG.svg \
	www/images/RawPC-IG-noTxt-centered.svg \
	www/images/RawPC-IG-noTxt.svg \
	www/images/RawPC-IG.svg \
	www/images/router.svg \
	www/images/site-icon.png \
	www/images/staticmap.png \
	www/images/Symbols-Tips-icon-clear.png \
	www/images/UseGENI.png \
	www/images/Xen-VM-noTxt-centered.svg \
	www/images/Xen-VM-noTxt.svg \
	www/images/Xen-VM.svg

dist_svcwebcommon_DATA = \
	www/common/dots.gif \
	www/common/nsf1.gif \
	www/common/topbar_gradient.png \
	www/common/topbar_gradient2.png

dist_svcwebcss_DATA =  \
	www/common/css/mobile-portal.css \
	www/common/css/newportal.css \
	www/common/css/portal.css

dist_svcwebmap_DATA =  \
	www/common/map/current.json \
	www/common/map/gmap3.js

dist_svcwebtop_DATA = \
    www/amstatus.php \
	www/index.php \
	www/login-help.php \
	www/favicon.ico

dist_svcwebpolicy_DATA = \
	www/policy/privacy.html

# Use a hook target to create the empty trusted_roots directory
# Note: this requirs AC_PROG_MKDIR_P in configure.ac
install-data-local:
	$(MKDIR_P) $(DESTDIR)/$(svcdatadir)/gcf.d/trusted_roots
