# XXX Do we still need SocketPool.swf?
# XXX Do we still need playerProductInstall.swf?
# XXX Rename gcf.d/gcf.ini -> gcf.d/example-gcf.ini (see below)
# XXX Do we still need omni.ini (installed in the old Makefile)?

svcdatadir = $(pkgdatadir)/portal
gcfsrcdir = $(svcdatadir)/gcf/src
gcfsrcgenidir = $(gcfsrcdir)/geni

# This is silly -- easier to rename the file in git
# TODO: rename the file in git and remove this extraneous target
gcf.d/example-gcf.ini:
	cp gcf.d/gcf.ini gcf.d/example-gcf.ini

# These two must go. Installing in a hardcoded path is bad for
# Automake/Autoconf, and installing in /var/www is bad for Ubuntu.
svcwebdir = /var/www/secure
svcwebforgedir = $(svcwebdir)/forge
svcwebimagesdir = /var/www/images
svcwebcommondir = /var/www/common
svcwebcssdir = /var/www/common/css
svcwebtopdir = /var/www
svcwebpolicydir = /var/www/policy


nobase_dist_svcdata_DATA = \
	apache2-http.conf \
	db/postgresql/data.sql \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	db/postgresql/update-2.sql \
	db/postgresql/update-3.sql \
	db/postgresql/update-4.sql \
	db/postgresql/update-5.sql \
	gcf.d/example-gcf.ini

#----------------------------------------------------
# GCF related files that overwrite the installed gcf
#----------------------------------------------------
dist_gcfsrc_DATA = \
	gcf.d/src/logging.conf
dist_gcfsrc_SCRIPTS = \
	gcf.d/src/gcf-pgch.py \
	gcf.d/src/omni_php.py
dist_gcfsrcgeni_DATA = \
	gcf.d/src/geni/pgch.py

dist_svcweb_DATA = \
	www/portal/abac-id.php \
	www/portal/abac-key.php \
	www/portal/abac.php \
	www/portal/accept-project-invite.php \
	www/portal/add-project-note.php \
	www/portal/add-slice-note.php \
	www/portal/admin.php \
	www/portal/aggregates.php \
	www/portal/am_map.php \
	www/portal/amstatus.js \
	www/portal/amstatus.php \
	www/portal/approve.php \
	www/portal/ask-for-project.php \
	www/portal/cancel-join-project.php \
	www/portal/certificate.php \
	www/portal/confirm-sliverdelete.php \
	www/portal/createslice.php \
	www/portal/createsliver.php \
	www/portal/cs_controller_test.php \
	www/portal/db-util.php \
	www/portal/db_error_test.php \
	www/portal/debug.php \
	www/portal/debug_clearcache.php \
	www/portal/deletesliver.php \
	www/portal/deletesshkey.php \
	www/portal/disable-slice.php \
	www/portal/do-accept-project-invite.php \
	www/portal/do-delete-project-member.php \
	www/portal/do-disable-slice.php \
	www/portal/do-edit-project-member.php \
	www/portal/do-edit-project-membership.php \
	www/portal/do-edit-project.php \
	www/portal/do-edit-slice-member.php \
	www/portal/do-edit-slice.php \
	www/portal/do-handle-project-request.php \
	www/portal/do-modify.php \
	www/portal/do-register.php \
	www/portal/do-renew-slice.php \
	www/portal/do-renew.php \
	www/portal/do-upload-project-members.php \
	www/portal/dologout.php \
	www/portal/downloadkeycert.php \
	www/portal/downloadomnibundle.php \
	www/portal/downloadsshkey.php \
	www/portal/edit-project-member.php \
	www/portal/edit-project.php \
	www/portal/edit-slice-member.php \
	www/portal/edit-slice.php \
	www/portal/error-eppn.php \
	www/portal/error-text.php \
	www/portal/flack.php \
	www/portal/flack_orig.html \
	www/portal/flack_test.php \
	www/portal/flackportal-1.html \
	www/portal/flackportal-2.html \
	www/portal/flackportal-22.html \
	www/portal/flackportal-3.html \
	www/portal/flackportal-A.html \
	www/portal/flackportal-B.html \
	www/portal/flackportal-C.html \
	www/portal/footer.php \
	www/portal/gemini.php \
	www/portal/generatesshkey.php \
	www/portal/getversion.php \
	www/portal/glossary.html \
	www/portal/handle-project-request.php \
	www/portal/header.php \
	www/portal/help.php \
	www/portal/home-active-tabs.php \
	www/portal/home-active.php \
	www/portal/home-requested.php \
	www/portal/home.php \
	www/portal/invite-to-geni.php \
	www/portal/invite-to-project.php \
	www/portal/join-project.php \
	www/portal/join-this-project.php \
	www/portal/listresources.php \
	www/portal/listresources_plain.php \
	www/portal/logging_test.php \
	www/portal/ma_controller_test.php \
	www/portal/maintenance_redirect_page.php \
	www/portal/modify.php \
	www/portal/omni-bundle.php \
	www/portal/pa_controller_test.php \
	www/portal/permission_manager_test.php \
	www/portal/portal.php \
	www/portal/portal_omni_config.php \
	www/portal/print-text-helpers.php \
	www/portal/print-text.php \
	www/portal/profile.php \
	www/portal/project-member.php \
	www/portal/project.php \
	www/portal/projects.php \
	www/portal/query-sliverstatus.php \
	www/portal/raw-sliverstatus.php \
	www/portal/readyToLogin.php \
	www/portal/renewsliver.php \
	www/portal/request_test.php \
	www/portal/rspecdelete.php \
	www/portal/rspecdownload.php \
	www/portal/rspecs.php \
	www/portal/rspecupload.php \
	www/portal/rspecview.php \
	www/portal/sa_controller_test.php \
	www/portal/services.php \
	www/portal/settings.php \
	www/portal/slice-add-resources.js \
	www/portal/slice-add-resources.php \
	www/portal/slice-member.php \
	www/portal/slice.php \
	www/portal/sliceabac.php \
	www/portal/slicecred.php \
	www/portal/sliceresource.php \
	www/portal/slices.php \
	www/portal/sliverdelete.php \
	www/portal/sliverstatus.php \
	www/portal/sr_controller_test.php \
	www/portal/sshkeyedit.php \
	www/portal/starter-status-bar.php \
	www/portal/status_constants.php \
	www/portal/status_constants_import.php \
	www/portal/tool-breadcrumbs.php \
	www/portal/tool-expired-projects.php \
	www/portal/tool-expired-slices.php \
	www/portal/tool-lookupids.php \
	www/portal/tool-omniconfig.php \
	www/portal/tool-projects.php \
	www/portal/tool-showmessage.php \
	www/portal/tool-slices.php \
	www/portal/tools-admin.php \
	www/portal/tools-user.php \
	www/portal/upload-project-members.php \
	www/portal/uploadsshkey.html \
	www/portal/uploadsshkey.php \
	www/portal/user.php \
	flack/playerProductInstall.swf


dist_svcwebforge_DATA = flack/forge/SocketPool.swf

dist_svcwebimages_DATA = \
	www/images/UseGENI.png \
	www/images/geni.png \
	www/images/pgfc-screenshot.jpg \
	www/images/portal.png \
	www/images/portal2.png \
	www/images/staticmap.png \
	www/images/header-home.jpg

dist_svcwebcommon_DATA = \
	www/common/InC_Participant.png \
	www/common/README-incommon-logo.txt \
	www/common/dots.gif \
	www/common/logos.html \
	www/common/topbar_gradient.png \
	www/common/topbar_gradient2.png

dist_svcwebcss_DATA =  \
	www/common/css/portal.css

dist_svcwebtop_DATA = \
	www/index.php \
	www/login-help.php \
	www/favicon.ico \
	www/cainfo.html

dist_svcwebpolicy_DATA = \
	www/policy/privacy.html

# Use a hook target to create the empty trusted_roots directory
# Note: this requirs AC_PROG_MKDIR_P in configure.ac
install-data-local:
	$(MKDIR_P) $(svcdatadir)/gcf.d/trusted_roots


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
