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
svcwebimagesdir = /var/www/images
svcwebcommondir = /var/www/common
svcwebcssdir = /var/www/common/css
svcwebmapdir = /var/www/common/map
svcwebmapdisplaydir = /var/www/common/map/1080display
svcwebmapoldir = /var/www/common/map/OpenLayers-2.13
svcwebmapolimgdir = /var/www/common/map/OpenLayers-2.13/img
svcwebmapolthemedir = /var/www/common/map/OpenLayers-2.13/theme/default
svcwebmapolthemeimgdir = /var/www/common/map/OpenLayers-2.13/theme/default/img
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
	db/postgresql/update-6.sql \
	db/postgresql/update-7.sql \
	db/postgresql/update-8.sql \
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
	www/portal/SocketPool.swf \
	www/portal/abac-id.php \
	www/portal/abac-key.php \
	www/portal/accept-project-invite.php \
	www/portal/admin.php \
	www/portal/aggregates.php \
	www/portal/amdetails.php \
	www/portal/amstatus.js \
	www/portal/amstatus.php \
	www/portal/approve.php \
	www/portal/ask-for-project.php \
	www/portal/cancel-join-project.php \
	www/portal/certificate.php \
	www/portal/confirm-sliverdelete.php \
	www/portal/create-rspec.php \
	www/portal/createslice.php \
	www/portal/createsliver.php \
	www/portal/db_error_test.php \
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
	www/portal/downloadsshpublickey.php \
	www/portal/edit-project-member.php \
	www/portal/edit-project.php \
	www/portal/edit-slice-member.php \
	www/portal/edit-slice.php \
	www/portal/error-eppn.php \
	www/portal/error-text.php \
	www/portal/flack.css \
	www/portal/flack.php \
	www/portal/dtjava_orig.js \
	www/portal/future.json \
	www/portal/gemini.php \
	www/portal/get_omni_invocation_data.php \
	www/portal/irods.php \
	www/portal/generatesshkey.php \
	www/portal/getversion.php \
	www/portal/handle-project-request.php \
	www/portal/help.php \
	www/portal/index.php \
	www/portal/home.php \
	www/portal/invite-to-geni.php \
	www/portal/invite-to-project.php \
        www/portal/jacks-app.css \
        www/portal/jacks-app.js \
        www/portal/jacks-app-details.php \
        www/portal/jacks-app-reserve.php \
        www/portal/jacks-app-status.php \
        www/portal/jacks-editor-app.css \
	www/portal/jacks-editor-app.js \
	www/portal/join-project.php \
	www/portal/join-this-project.php \
	www/portal/listresources.php \
	www/portal/listresources_plain.php \
	www/portal/maintenance_redirect_page.php \
	www/portal/map.php \
	www/portal/modify.php \
	www/portal/omni-bundle.php \
	www/portal/permission_manager_test.php \
	www/portal/portal_omni_config.php \
	www/portal/portal-jacks-app.js \
	www/portal/portal-jacks-editor-app.js \
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
	www/portal/selectrspec.html \
	www/portal/send_bug_report.php \
	www/portal/slice-add-resources.js \
	www/portal/slice-add-resources.php \
	www/portal/slice-add-resources-jacks.css \
	www/portal/slice-add-resources-jacks.js \
	www/portal/slice-add-resources-jacks.php \
	www/portal/slice-member.php \
	www/portal/slice.php \
	www/portal/slice-jacks.css \
	www/portal/slice-jacks.php \
	www/portal/slice-table.css \
	www/portal/sliceabac.php \
	www/portal/slicecred.php \
	www/portal/sliceresource.php \
	www/portal/slices.php \
	www/portal/sliverdelete.php \
	www/portal/sliverstatus.php \
	www/portal/speaks-for.css \
	www/portal/speaks-for.js \
	www/portal/speaks-for.php \
	www/portal/speaks-for-delete.php \
	www/portal/speaks-for-upload.php \
	www/portal/sshkeyedit.php \
	www/portal/starter-status-bar.php \
	www/portal/status_constants_import.php \
	www/portal/tool-aggwarning.php \
	www/portal/tool-omniconfig.php \
	www/portal/tool-slices.js \
	www/portal/tools-user.js \
	www/portal/upload-file.php \
	www/portal/upload-project-members.php \
	www/portal/uploadsshkey.php \
        www/portal/wimax-enable.php

dist_svcwebimages_DATA = \
	www/images/EG-VM-noTxt-centered.svg \
	www/images/EG-VM-noTxt.svg \
	www/images/EG-VM.svg \
	www/images/geni.png \
	www/images/geni_globe.png \
	www/images/geni_globe_small.png \
	www/images/header-home.jpg \
	www/images/openVZvm-noTxt-centered.svg \
	www/images/openVZvm-noTxt.svg \
	www/images/openVZvm.svg \
	www/images/orbit_banner.png \
	www/images/witest-logo-white.png \
	www/images/pgfc-screenshot.jpg \
	www/images/portal.png \
	www/images/portal2.png \
	www/images/RawPC-EG-noTxt-centered.svg \
	www/images/RawPC-EG-noTxt.svg \
	www/images/RawPC-EG.svg \
	www/images/RawPC-IG-noTxt-centered.svg \
	www/images/RawPC-IG-noTxt.svg \
	www/images/RawPC-IG.svg \
	www/images/site-icon.png \
	www/images/staticmap.png \
	www/images/Symbols-Tips-icon-clear.png \
	www/images/UseGENI.png \
	www/images/Xen-VM-noTxt-centered.svg \
	www/images/Xen-VM-noTxt.svg \
	www/images/Xen-VM.svg

dist_svcwebcommon_DATA = \
	www/common/InC_Participant.png \
	www/common/README-incommon-logo.txt \
	www/common/dots.gif \
	www/common/nsf1.gif \
	www/common/logos.html \
	www/common/topbar_gradient.png \
	www/common/topbar_gradient2.png

dist_svcwebcss_DATA =  \
	www/common/css/portal.css

dist_svcwebmap_DATA =  \
	www/common/map/current.json \
	www/common/map/map-small.html \
	www/common/map/rspec-dragon-maxgigapop-net.xml \
	www/common/map/rspec-emulab-net.xml \
	www/common/map/rspec-exogeni-net-bbnvmsite.xml \
	www/common/map/rspec-exogeni-net-fiuvmsite.xml \
	www/common/map/rspec-exogeni-net-rcivmsite.xml \
	www/common/map/rspec-exogeni-net-uhvmsite.xml \
	www/common/map/rspec-exogeni-net.xml \
	www/common/map/rspec-geni-it-cornell-edu.xml \
	www/common/map/rspec-geni-kettering-edu.xml \
	www/common/map/rspec-genirack-nyu-edu.xml \
	www/common/map/rspec-instageni-clemson-edu.xml \
	www/common/map/rspec-instageni-gpolab-bbn-com.xml \
	www/common/map/rspec-instageni-illinois-edu.xml \
	www/common/map/rspec-instageni-ku-gpeni-net.xml \
	www/common/map/rspec-instageni-maxgigapop-net.xml \
	www/common/map/rspec-instageni-northwestern-edu.xml \
	www/common/map/rspec-instageni-nysernet-org.xml \
	www/common/map/rspec-instageni-rnet-missouri-edu.xml \
	www/common/map/rspec-instageni-rnoc-gatech-edu.xml \
	www/common/map/rspec-instageni-sox-net.xml \
	www/common/map/rspec-instageni-stanford-edu.xml \
	www/common/map/rspec-instageni-wisc-edu.xml \
	www/common/map/rspec-ion-internet2-edu.xml \
	www/common/map/rspec-lan-sdn-uky-edu.xml \
	www/common/map/rspec-openflow-foam-bbn-hn-exogeni-gpolab-bbn-com.xml \
	www/common/map/rspec-openflow-foam-foam-cenic-net.xml \
	www/common/map/rspec-openflow-foam-foam-geni-it-cornell-edu.xml \
	www/common/map/rspec-openflow-foam-foam-geni-kettering-edu.xml \
	www/common/map/rspec-openflow-foam-foam-genirack-nyu-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-clemson-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-gpolab-bbn-com.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-illinois-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-ku-gpeni-net.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-maxgigapop-net.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-northwestern-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-nysernet-org.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-rnet-missouri-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-sox-net.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-stanford-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-wisc-edu.xml \
	www/common/map/rspec-openflow-foam-foam-instageni-rnoc-gatech-edu.xml \
	www/common/map/rspec-openflow-foam-foam-lan-sdn-uky-edu.xml \
	www/common/map/rspec-openflow-foam-foam-nysernet-org.xml \
	www/common/map/rspec-openflow-foam-foam-sox-net.xml \
	www/common/map/rspec-openflow-foam-foam-utahddc-geniracks-net.xml \
	www/common/map/rspec-openflow-foam-ig-utah.xml \
	www/common/map/rspec-openflow-foam-rci-hn-exogeni-gpolab-bbn-com.xml \
	www/common/map/rspec-openflow-foam-sl-geni-northwestern-edu.xml \
	www/common/map/rspec-uky-emulab-net.xml \
	www/common/map/rspec-utah-geniracks-net.xml \
	www/common/map/rspec-utahddc-geniracks-net.xml

dist_svcwebmapdisplay_DATA =  \
  www/common/map/1080display/index.php \
  www/common/map/1080display/map-big.html
  
dist_svcwebmapol_DATA =  \
  www/common/map/OpenLayers-2.13/OpenLayers.js \
  www/common/map/OpenLayers-2.13/readme.md

dist_svcwebmapolimg_DATA =  \
  www/common/map/OpenLayers-2.13/img/blank.gif \
  www/common/map/OpenLayers-2.13/img/cloud-popup-relative.png \
  www/common/map/OpenLayers-2.13/img/drag-rectangle-off.png \
  www/common/map/OpenLayers-2.13/img/drag-rectangle-on.png \
  www/common/map/OpenLayers-2.13/img/east-mini.png \
  www/common/map/OpenLayers-2.13/img/layer-switcher-maximize.png \
  www/common/map/OpenLayers-2.13/img/layer-switcher-minimize.png \
  www/common/map/OpenLayers-2.13/img/marker-blue.png \
  www/common/map/OpenLayers-2.13/img/marker-gold.png \
  www/common/map/OpenLayers-2.13/img/marker-green.png \
  www/common/map/OpenLayers-2.13/img/marker.png \
  www/common/map/OpenLayers-2.13/img/measuring-stick-off.png \
  www/common/map/OpenLayers-2.13/img/measuring-stick-on.png \
  www/common/map/OpenLayers-2.13/img/north-mini.png \
  www/common/map/OpenLayers-2.13/img/panning-hand-off.png \
  www/common/map/OpenLayers-2.13/img/panning-hand-on.png \
  www/common/map/OpenLayers-2.13/img/slider.png \
  www/common/map/OpenLayers-2.13/img/south-mini.png \
  www/common/map/OpenLayers-2.13/img/west-mini.png \
  www/common/map/OpenLayers-2.13/img/zoombar.png \
  www/common/map/OpenLayers-2.13/img/zoom-minus-mini.png \
  www/common/map/OpenLayers-2.13/img/zoom-plus-mini.png \
  www/common/map/OpenLayers-2.13/img/zoom-world-mini.png
  
dist_svcwebmapoltheme_DATA =  \
  www/common/map/OpenLayers-2.13/theme/default/google.css \
  www/common/map/OpenLayers-2.13/theme/default/google.tidy.css \
  www/common/map/OpenLayers-2.13/theme/default/ie6-style.css \
  www/common/map/OpenLayers-2.13/theme/default/ie6-style.tidy.css \
  www/common/map/OpenLayers-2.13/theme/default/style.css \
  www/common/map/OpenLayers-2.13/theme/default/style.mobile.css \
  www/common/map/OpenLayers-2.13/theme/default/style.mobile.tidy.css \
  www/common/map/OpenLayers-2.13/theme/default/style.tidy.css
  
dist_svcwebmapolthemeimg_DATA =  \
  www/common/map/OpenLayers-2.13/theme/default/img/add_point_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/add_point_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/blank.gif \
  www/common/map/OpenLayers-2.13/theme/default/img/close.gif \
  www/common/map/OpenLayers-2.13/theme/default/img/drag-rectangle-off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/drag-rectangle-on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_line_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_line_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_point_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_point_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_polygon_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/draw_polygon_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/editing_tool_bar.png \
  www/common/map/OpenLayers-2.13/theme/default/img/move_feature_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/move_feature_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/navigation_history.png \
  www/common/map/OpenLayers-2.13/theme/default/img/overview_replacement.gif \
  www/common/map/OpenLayers-2.13/theme/default/img/panning-hand-off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/panning-hand-on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/pan_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/pan_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/pan-panel-NOALPHA.png \
  www/common/map/OpenLayers-2.13/theme/default/img/pan-panel.png \
  www/common/map/OpenLayers-2.13/theme/default/img/remove_point_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/remove_point_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/ruler.png \
  www/common/map/OpenLayers-2.13/theme/default/img/save_features_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/save_features_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/view_next_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/view_next_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/view_previous_off.png \
  www/common/map/OpenLayers-2.13/theme/default/img/view_previous_on.png \
  www/common/map/OpenLayers-2.13/theme/default/img/zoom-panel-NOALPHA.png \
  www/common/map/OpenLayers-2.13/theme/default/img/zoom-panel.png

dist_svcwebtop_DATA = \
	www/index.php \
	www/login-help.php \
	www/favicon.ico

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
