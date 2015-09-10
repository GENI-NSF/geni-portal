# Release 2.34

## Issues Closed

* Jacks app polls indefinitely if no login information is available ([#1439](https://github.com/GENI-NSF/geni-portal/issues/1439))
* Ready resources do not colored green on safari ([#1436](https://github.com/GENI-NSF/geni-portal/issues/1436))
* Redirects on edit-project.php fail to occur after header is printed ([#1434](https://github.com/GENI-NSF/geni-portal/issues/1434))
* jQuery paths undefined in jfed ([#1426](https://github.com/GENI-NSF/geni-portal/issues/1426))
* profile should not say you have no slice or lead requests ([#1425](https://github.com/GENI-NSF/geni-portal/issues/1425))
* Clean up text on do-modify on a duplicate lead request ([#1422](https://github.com/GENI-NSF/geni-portal/issues/1422))
* Put portal update-9.sql in schema.sql ([#1420](https://github.com/GENI-NSF/geni-portal/issues/1420))
* Add isManifest=true to Jacks instance in jacks-app ([#1419](https://github.com/GENI-NSF/geni-portal/issues/1419))
* Createsliver returns error but portal does not realize and says Finished in Green ([#1406](https://github.com/GENI-NSF/geni-portal/issues/1406))
* ExoSM delete from Jacks App doesn't work ([#1401](https://github.com/GENI-NSF/geni-portal/issues/1401))
* SSH doesn't work for ExoSM-allocated Nodes ([#1397](https://github.com/GENI-NSF/geni-portal/issues/1397))
* Map centers at 0,0 on wide screens ([#1390](https://github.com/GENI-NSF/geni-portal/issues/1390))
* Slice expiration can be erroneously displayed in red ([#1387](https://github.com/GENI-NSF/geni-portal/issues/1387))
* Delete on Jacks-App creates exponential manifest calls ([#1364](https://github.com/GENI-NSF/geni-portal/issues/1364))
* Better handling of unknown AMs ([#1346](https://github.com/GENI-NSF/geni-portal/issues/1346))
* load only 1 version of jquery ([#1327](https://github.com/GENI-NSF/geni-portal/issues/1327))
* Delete All in Jacks Add Resources does not clear selection dropdown ([#1246](https://github.com/GENI-NSF/geni-portal/issues/1246))
* Distinguish between 'Finished' and 'Failed' on results page ([#1134](https://github.com/GENI-NSF/geni-portal/issues/1134))
* Add functionality to operator page on portal ([#1059](https://github.com/GENI-NSF/geni-portal/issues/1059))
* add operator helper scripts ([#964](https://github.com/GENI-NSF/geni-portal/issues/964))
* Add Admin page for handling project lead requests ([#731](https://github.com/GENI-NSF/geni-portal/issues/731))
* Create script to readily see oustanding project lead requests ([#404](https://github.com/GENI-NSF/geni-portal/issues/404))
* Profile: Make modify real, request project lead ([#273](https://github.com/GENI-NSF/geni-portal/issues/273))

## Pull Requests Merged

* Fix jacks-app looping (PR [#1442](https://github.com/GENI-NSF/geni-portal/pull/1442))
* Fix redirects when project is successfully created. (PR [#1441](https://github.com/GENI-NSF/geni-portal/pull/1441))
* Fix issue where jquery paths undefined in tool-jfed (PR [#1427](https://github.com/GENI-NSF/geni-portal/pull/1427))
* Use common jquery in KM as well. (PR [#1396](https://github.com/GENI-NSF/geni-portal/pull/1396))
* Use only one version of jQuery (2.1.4) across the portal (#1327) (PR [#1394](https://github.com/GENI-NSF/geni-portal/pull/1394))

# Release 2.33

## Issues Closed

* IRODS VERIFYHOST doesn't work in later versions of CURL ([#1386](https://github.com/GENI-NSF/geni-portal/issues/1386))
* Use portal keys to add / remove member attributes on wireless page ([#1380](https://github.com/GENI-NSF/geni-portal/issues/1380))
* Details page loops indefinitely at AL2S ([#1378](https://github.com/GENI-NSF/geni-portal/issues/1378))
* Show maintenance alerts on all portal pages ([#1375](https://github.com/GENI-NSF/geni-portal/issues/1375))
* listresources.php gets stuck "...refreshing..." when no resources at an aggregate ([#1365](https://github.com/GENI-NSF/geni-portal/issues/1365))
* Summarize slice contents on slices / home pages ([#1354](https://github.com/GENI-NSF/geni-portal/issues/1354))
* Allow retrieving more log messages ([#1318](https://github.com/GENI-NSF/geni-portal/issues/1318))
* Portal page width doesn't scale with browser width ([#768](https://github.com/GENI-NSF/geni-portal/issues/768))

## Pull Requests Merged

* fixed get_urgency_color bug (PR [#1384](https://github.com/GENI-NSF/geni-portal/pull/1384))
* Tkt1318 logs (PR [#1382](https://github.com/GENI-NSF/geni-portal/pull/1382))
* Tkt1354 slicesummary (PR [#1381](https://github.com/GENI-NSF/geni-portal/pull/1381))

# Release 2.32

## Issues Closed

* Remove ION aggregate ([#1377](https://github.com/GENI-NSF/geni-portal/issues/1377))
* add omni stderr to portal_error log ([#1376](https://github.com/GENI-NSF/geni-portal/issues/1376))
* Add SAVI button to portal ([#1355](https://github.com/GENI-NSF/geni-portal/issues/1355))
* loadcert.php delivers expired certificates to xml-signer tool ([#1336](https://github.com/GENI-NSF/geni-portal/issues/1336))
* Certificate management can be broken after speaks-for authorization ([#1335](https://github.com/GENI-NSF/geni-portal/issues/1335))

# Release 2.31

## Issues Closed

* Rename the Wireless button in the tool section ([#1374](https://github.com/GENI-NSF/geni-portal/issues/1374))
* wimax-enable missed an error from the server ([#1373](https://github.com/GENI-NSF/geni-portal/issues/1373))
* Update GENI Desktop link on home page ([#1372](https://github.com/GENI-NSF/geni-portal/issues/1372))
* "Found no UID in SubjectAltNames" error at RENCI ([#1368](https://github.com/GENI-NSF/geni-portal/issues/1368))
* Consider adding an "Add GENI Desktop Global Node" button in Jacks App ([#1351](https://github.com/GENI-NSF/geni-portal/issues/1351))
* Make it easier for Windows users to use keys generated by the Portal. ([#434](https://github.com/GENI-NSF/geni-portal/issues/434))

# Release 2.30

## Issues Closed

* PHP fatal error on double require of logging_client ([#1366](https://github.com/GENI-NSF/geni-portal/issues/1366))
* Add GEE Button to Home page ([#1363](https://github.com/GENI-NSF/geni-portal/issues/1363))
* xml-signer does not work with PKCS#8 private keys ([#1362](https://github.com/GENI-NSF/geni-portal/issues/1362))
* Add new OVS image to Jacks constraints ([#1361](https://github.com/GENI-NSF/geni-portal/issues/1361))
* Rename assert_email.sh to follow convention with "geni-" prefix, add man page ([#1360](https://github.com/GENI-NSF/geni-portal/issues/1360))
* update_user_certs broken with new postgresql ([#1359](https://github.com/GENI-NSF/geni-portal/issues/1359))
* make geni-ch-githash an optional file ([#1358](https://github.com/GENI-NSF/geni-portal/issues/1358))
* tool-slices undefined index ([#1357](https://github.com/GENI-NSF/geni-portal/issues/1357))
* Auto add slice users to existing slivers ([#1353](https://github.com/GENI-NSF/geni-portal/issues/1353))
* Add a json file to the omni bundle to support genilib ([#1352](https://github.com/GENI-NSF/geni-portal/issues/1352))
* typo on jacks add resources results page ([#1348](https://github.com/GENI-NSF/geni-portal/issues/1348))
* jacks-lib handle empty rspec ([#1347](https://github.com/GENI-NSF/geni-portal/issues/1347))
* Remove unused sliverstatus.php ([#1333](https://github.com/GENI-NSF/geni-portal/issues/1333))
* Jacks app double polls status ([#1326](https://github.com/GENI-NSF/geni-portal/issues/1326))
* Decorate Different kinds of links (GRE, EGRE, STITCHED) on Jacks ([#1299](https://github.com/GENI-NSF/geni-portal/issues/1299))
* Make 'stale-omni' an official script ([#1281](https://github.com/GENI-NSF/geni-portal/issues/1281))
* Joint Manifest/Slice page doesn't refresh login info ([#1280](https://github.com/GENI-NSF/geni-portal/issues/1280))
* jacks-editor-app.js hard codes a poor context ([#1193](https://github.com/GENI-NSF/geni-portal/issues/1193))

# Release 2.29

## Issues Closed

* Jacks App SSH button replaces current window ([#1344](https://github.com/GENI-NSF/geni-portal/issues/1344))
* Slow slice jacks blocks other portal pages ([#1343](https://github.com/GENI-NSF/geni-portal/issues/1343))
* Deprecate Flack ([#1219](https://github.com/GENI-NSF/geni-portal/issues/1219))
* Make prototype Jacks pages the default for slice, Add Resources ([#1183](https://github.com/GENI-NSF/geni-portal/issues/1183))
* Warn about illegal RSpecs ([#1101](https://github.com/GENI-NSF/geni-portal/issues/1101))
* Allow binding or rebinding Nodes ([#1100](https://github.com/GENI-NSF/geni-portal/issues/1100))
* Handle partially bound RSpecs ([#1088](https://github.com/GENI-NSF/geni-portal/issues/1088))
* Add a 'Restart' button ([#1044](https://github.com/GENI-NSF/geni-portal/issues/1044))
* Support bound RSpecs ([#389](https://github.com/GENI-NSF/geni-portal/issues/389))

# Release 2.28

## Issues Closed

* Downloaded RSpec is not in pretty format ([#1340](https://github.com/GENI-NSF/geni-portal/issues/1340))
* slice-map-data dies if listresources fails on one AM ([#1334](https://github.com/GENI-NSF/geni-portal/issues/1334))
* Ready fails on slice page ([#1332](https://github.com/GENI-NSF/geni-portal/issues/1332))
* tool-jfed error when no outside key ([#1331](https://github.com/GENI-NSF/geni-portal/issues/1331))
* uncaught type error in jacks-app ([#1330](https://github.com/GENI-NSF/geni-portal/issues/1330))
* Remove unused create-rspec.php ([#1329](https://github.com/GENI-NSF/geni-portal/issues/1329))
* Duplicate nodes doesn't work if site is set ([#1328](https://github.com/GENI-NSF/geni-portal/issues/1328))
* null referent in jacks-app.js ([#1325](https://github.com/GENI-NSF/geni-portal/issues/1325))
* SITE ID shouldn't be changed unless component_manager_id is set ([#1323](https://github.com/GENI-NSF/geni-portal/issues/1323))
* Duplicate AM's given to stitcher ([#1322](https://github.com/GENI-NSF/geni-portal/issues/1322))
* Site names on expanded are white not black ([#1321](https://github.com/GENI-NSF/geni-portal/issues/1321))
* Remove Duplicate nodes/links from jacks editor ([#1320](https://github.com/GENI-NSF/geni-portal/issues/1320))
* auto_ip doesn't remove original IP's ([#1319](https://github.com/GENI-NSF/geni-portal/issues/1319))
* Remove site attribute when component_manager_id is set ([#1317](https://github.com/GENI-NSF/geni-portal/issues/1317))
* Only add compute aggregates to jacks editor list ([#1316](https://github.com/GENI-NSF/geni-portal/issues/1316))
* duplicate node doesn't work ([#1315](https://github.com/GENI-NSF/geni-portal/issues/1315))
* Ready status override by multiple status replies ([#1314](https://github.com/GENI-NSF/geni-portal/issues/1314))
* jacks editor app runs validate rspec file many times ([#1313](https://github.com/GENI-NSF/geni-portal/issues/1313))
* jacks-editor-app loads jacks twice ([#1312](https://github.com/GENI-NSF/geni-portal/issues/1312))
* jacks-app-expanded has Expand button and not Back button ([#1310](https://github.com/GENI-NSF/geni-portal/issues/1310))
* Update Jacks context ([#1298](https://github.com/GENI-NSF/geni-portal/issues/1298))
* Prettify all rspecs shown in the portal ([#1252](https://github.com/GENI-NSF/geni-portal/issues/1252))
* Pass jFed slice_urn ([#1210](https://github.com/GENI-NSF/geni-portal/issues/1210))

# Release 2.27

## Issues Closed

* Changes to attributes erased by auto-ip and duplicate-node features ([#1309](https://github.com/GENI-NSF/geni-portal/issues/1309))
* Remove the warning when pressing slice action buttons on the home page ([#1308](https://github.com/GENI-NSF/geni-portal/issues/1308))
* Sliverstatus indication on the details page ([#1307](https://github.com/GENI-NSF/geni-portal/issues/1307))
* Details button on slice-jacks not filtered by sliver_info? ([#1302](https://github.com/GENI-NSF/geni-portal/issues/1302))
* Context between editor pages doesn't include non-topology changes ([#1301](https://github.com/GENI-NSF/geni-portal/issues/1301))
* Jacks creation must be in document.ready block ([#1300](https://github.com/GENI-NSF/geni-portal/issues/1300))
* Highlight name changes with a different email subject ([#1297](https://github.com/GENI-NSF/geni-portal/issues/1297))
* Infinite loop in duplicate node ([#1296](https://github.com/GENI-NSF/geni-portal/issues/1296))
* Add new copy/paste to expanded jacks editor as well. ([#1295](https://github.com/GENI-NSF/geni-portal/issues/1295))
* jacks_editor_app_expanded loses context from jacks_editor_app ([#1292](https://github.com/GENI-NSF/geni-portal/issues/1292))
* Calls to SCS from portal are delayed by 1 minute ([#1291](https://github.com/GENI-NSF/geni-portal/issues/1291))
* "Raw" manifest text box doesn't format XML ([#1290](https://github.com/GENI-NSF/geni-portal/issues/1290))
* list resources doesn't interleave manifests and status properly ([#1289](https://github.com/GENI-NSF/geni-portal/issues/1289))
* Fix some issues with Jacks viewer/editor and expanded views for 2/4 release ([#1283](https://github.com/GENI-NSF/geni-portal/issues/1283))
* Support copy/paste in jacks-editor-app ([#1278](https://github.com/GENI-NSF/geni-portal/issues/1278))
* Allow pgpass or command line arg for db password in map generation scripts ([#1253](https://github.com/GENI-NSF/geni-portal/issues/1253))
* Set omni2.8 timeout at 45 (minutes) ([#1234](https://github.com/GENI-NSF/geni-portal/issues/1234))

# Release 2.26

## Issues Closed

* Remove get_slice_credential error message from log ([#1288](https://github.com/GENI-NSF/geni-portal/issues/1288))
* Multiple calls to refresh status adds new elements to status list ([#1286](https://github.com/GENI-NSF/geni-portal/issues/1286))
* Limit size of downloaded URL ([#1282](https://github.com/GENI-NSF/geni-portal/issues/1282))
* Error check filenames to upload-file ([#1279](https://github.com/GENI-NSF/geni-portal/issues/1279))
* Leading space causes RSPEC URL Load to fail ([#1277](https://github.com/GENI-NSF/geni-portal/issues/1277))
* Details Status Aggregate table should align status label and status value ([#1276](https://github.com/GENI-NSF/geni-portal/issues/1276))
* Slice details-status with no resources shows no status until details are queried ([#1275](https://github.com/GENI-NSF/geni-portal/issues/1275))
* Should be able to see raw slice status info from details page. ([#1273](https://github.com/GENI-NSF/geni-portal/issues/1273))
* Remove warning if affiliation is not present ([#1269](https://github.com/GENI-NSF/geni-portal/issues/1269))
* Add a Cloud Lab button on home page ([#1267](https://github.com/GENI-NSF/geni-portal/issues/1267))
* Enhancements to Edit RSpec on Profile page ([#1266](https://github.com/GENI-NSF/geni-portal/issues/1266))
* Geo Map should be part of tab view as well as full-screen ([#1264](https://github.com/GENI-NSF/geni-portal/issues/1264))
* Add access to CloudLab from GENI Portal ([#1263](https://github.com/GENI-NSF/geni-portal/issues/1263))
* Missing scrollbar on View RSpec pane in jacks ([#1248](https://github.com/GENI-NSF/geni-portal/issues/1248))
* Add new aggregate categories ([#1239](https://github.com/GENI-NSF/geni-portal/issues/1239))
* Jacks viewer on Manage RSpecs should show menus, sites ([#1237](https://github.com/GENI-NSF/geni-portal/issues/1237))
* Jacks viewer on Details page should show 'menus' and sites ([#1236](https://github.com/GENI-NSF/geni-portal/issues/1236))
* Jacks on results page missing RSpec scrollbar ([#1235](https://github.com/GENI-NSF/geni-portal/issues/1235))
* Allow Jacks app and Jacks editor app to expand with wider browser ([#1233](https://github.com/GENI-NSF/geni-portal/issues/1233))
* slice jacks ready highlighting broken for ExoSM ([#1231](https://github.com/GENI-NSF/geni-portal/issues/1231))
* Show sliver expiration in print-rspec-pretty ([#1107](https://github.com/GENI-NSF/geni-portal/issues/1107))
* Combine the Status and Details pages ([#1043](https://github.com/GENI-NSF/geni-portal/issues/1043))
* Parse "mail" attribute properly ([#740](https://github.com/GENI-NSF/geni-portal/issues/740))

# Release 2.25

## Issues Closed

* Delete on Jacks-App confusion ([#1265](https://github.com/GENI-NSF/geni-portal/issues/1265))
* Update copyright year range to include 2015 ([#1261](https://github.com/GENI-NSF/geni-portal/issues/1261))
* Create GEO MAP view of current slice ([#1260](https://github.com/GENI-NSF/geni-portal/issues/1260))
* Client ID Assumed to be unique in jacks-app ([#1258](https://github.com/GENI-NSF/geni-portal/issues/1258))
* Jacks sshes into wrong node when there are duplicate client_ids in a slice ([#1257](https://github.com/GENI-NSF/geni-portal/issues/1257))
* Name downloaded request rspec with slice name ([#1204](https://github.com/GENI-NSF/geni-portal/issues/1204))
* Add Jacks editor to Manage RSpecs page ([#1143](https://github.com/GENI-NSF/geni-portal/issues/1143))

# Release 2.24

## Issues Closed

* Host Jacks locally optionally ([#1251](https://github.com/GENI-NSF/geni-portal/issues/1251))
* Jacks Add Resources says you have to pick an RSpec ([#1249](https://github.com/GENI-NSF/geni-portal/issues/1249))
* Portal reports "Number of leads for slice must be exactly 1" when adding a member ([#1242](https://github.com/GENI-NSF/geni-portal/issues/1242))

# Release 2.23

## Issues Closed

* Portal crashes looking for GENI_PENDING in get_sliver_status ([#1243](https://github.com/GENI-NSF/geni-portal/issues/1243))
* Jacks-app delete polls indefinitely ([#1241](https://github.com/GENI-NSF/geni-portal/issues/1241))
* Some aggregates appear as 0.0 on the map ([#1240](https://github.com/GENI-NSF/geni-portal/issues/1240))
* Change tab names on slice jacks ([#1238](https://github.com/GENI-NSF/geni-portal/issues/1238))
* Jacks Add Resources uses stale RSPEC ([#1226](https://github.com/GENI-NSF/geni-portal/issues/1226))
* Name request rspec in results page with slice name ([#1205](https://github.com/GENI-NSF/geni-portal/issues/1205))
* Add View RSpec to Jacks Viewer, or equivalent on slice-jacks ([#1202](https://github.com/GENI-NSF/geni-portal/issues/1202))
* Add a 'clear' button in Jacks Add Resources ([#1191](https://github.com/GENI-NSF/geni-portal/issues/1191))
* Highlight selected nodes in Jacks ([#1190](https://github.com/GENI-NSF/geni-portal/issues/1190))
* AM table broken on Slice Jacks ([#1187](https://github.com/GENI-NSF/geni-portal/issues/1187))

# Release 2.22

## Issues Closed

* Change Jacks beta pages to use stable Jacks, not devel ([#1228](https://github.com/GENI-NSF/geni-portal/issues/1228))
* do-edit-slice-members must check args ([#1224](https://github.com/GENI-NSF/geni-portal/issues/1224))
* parse multiple services tags ([#1222](https://github.com/GENI-NSF/geni-portal/issues/1222))
* pending status is unknown ([#1221](https://github.com/GENI-NSF/geni-portal/issues/1221))
* Increase size of URL textbox ([#1218](https://github.com/GENI-NSF/geni-portal/issues/1218))
* Move radio buttons to left side of labels on jacks-add-resources ([#1217](https://github.com/GENI-NSF/geni-portal/issues/1217))
* Update Jacks on sliceresources page ([#1216](https://github.com/GENI-NSF/geni-portal/issues/1216))
* Do not add to config empty keys list ([#1215](https://github.com/GENI-NSF/geni-portal/issues/1215))
* am_map functions should call array_key_exists ([#1212](https://github.com/GENI-NSF/geni-portal/issues/1212))
* Move include files to lib ([#1207](https://github.com/GENI-NSF/geni-portal/issues/1207))
* createsliver causes 'Undefined property' error ([#1206](https://github.com/GENI-NSF/geni-portal/issues/1206))
* Rename select from URL to load from URL ([#1201](https://github.com/GENI-NSF/geni-portal/issues/1201))
* Bug report email mangles AM list ([#1198](https://github.com/GENI-NSF/geni-portal/issues/1198))
* Headers malformed in email on lead request ([#1197](https://github.com/GENI-NSF/geni-portal/issues/1197))
* Editing an ssh key results in odd success message ([#973](https://github.com/GENI-NSF/geni-portal/issues/973))

# Release 2.21.4

## Issues Closed

* Edit text on Jacks Add Resources ([#1223](https://github.com/GENI-NSF/geni-portal/issues/1223))
* "No member URN found for ID: " log messages in KM loadcert ([#1209](https://github.com/GENI-NSF/geni-portal/issues/1209))

# Release 2.21.2

## Issues Closed

* Update the GENI Desktop URLs ([#1199](https://github.com/GENI-NSF/geni-portal/issues/1199))
* Create an index.php ([#1196](https://github.com/GENI-NSF/geni-portal/issues/1196))
* Provide a simple Jacks context ([#1194](https://github.com/GENI-NSF/geni-portal/issues/1194))

# Release 2.21

## Issues Closed

* Log message for allocation is ugly ([#1184](https://github.com/GENI-NSF/geni-portal/issues/1184))
* ReferenceError: Can't find variable: callback when closing rspec viewer ([#1182](https://github.com/GENI-NSF/geni-portal/issues/1182))
* RSpec View button does nothing ([#1181](https://github.com/GENI-NSF/geni-portal/issues/1181))
* parseRequestRSpec notice XML load errors ([#1179](https://github.com/GENI-NSF/geni-portal/issues/1179))
* Protect saverspectoserver ([#1177](https://github.com/GENI-NSF/geni-portal/issues/1177))
* Undefined offset in getBrowser ([#1176](https://github.com/GENI-NSF/geni-portal/issues/1176))
* Verify speaks-for signer URN ([#1175](https://github.com/GENI-NSF/geni-portal/issues/1175))
* Incomplete set of aggregates when selecting slice details from home page ([#1174](https://github.com/GENI-NSF/geni-portal/issues/1174))
* Parse sliver expiration from FOAM, SFA and GRAM AMs ([#1173](https://github.com/GENI-NSF/geni-portal/issues/1173))
* email sent from portal does not preserve international characters ([#1162](https://github.com/GENI-NSF/geni-portal/issues/1162))
* Slice page Jacks prototype ([#1126](https://github.com/GENI-NSF/geni-portal/issues/1126))
* Handle non-stitching, fully-bound, multi-AM RSpecs ([#1087](https://github.com/GENI-NSF/geni-portal/issues/1087))

# Release 2.20

## Issues Closed

* Allocation page has link to list resources to poll all aggregates ([#1171](https://github.com/GENI-NSF/geni-portal/issues/1171))
* bug report attachments are inline in Apple Mail ([#1169](https://github.com/GENI-NSF/geni-portal/issues/1169))
* Rename WiMAX to Wireless ([#1168](https://github.com/GENI-NSF/geni-portal/issues/1168))
* Send iRODS username to Orbit ([#1167](https://github.com/GENI-NSF/geni-portal/issues/1167))
* undefined variable ub in util.php getBrowser ([#1166](https://github.com/GENI-NSF/geni-portal/issues/1166))
* Change slice-wide buttons to poll "this slice" aggregates ([#1165](https://github.com/GENI-NSF/geni-portal/issues/1165))
* Integrate xml-signer 1.0 ([#1164](https://github.com/GENI-NSF/geni-portal/issues/1164))
* Integrate omni/gcf 2.7 ([#1163](https://github.com/GENI-NSF/geni-portal/issues/1163))
* Buttons on per slice rows should act on This Slice ([#1147](https://github.com/GENI-NSF/geni-portal/issues/1147))
* WiMAX: Send iRODS username ([#1095](https://github.com/GENI-NSF/geni-portal/issues/1095))
* Portal allows re-uploading an existing Rspec name and description. ([#784](https://github.com/GENI-NSF/geni-portal/issues/784))

# Release 2.19

## Issues Closed

* Add breadcrumbs to Edit SSH Key ([#1161](https://github.com/GENI-NSF/geni-portal/issues/1161))
* WiMAX: lookup keys using correct user ([#1158](https://github.com/GENI-NSF/geni-portal/issues/1158))
* typo in user.php ([#1157](https://github.com/GENI-NSF/geni-portal/issues/1157))
* suppress jfed warning messages ([#1156](https://github.com/GENI-NSF/geni-portal/issues/1156))
* Renewing at more than 10 aggregates fails ([#1155](https://github.com/GENI-NSF/geni-portal/issues/1155))
* upload bound RSpec and click reserve gives wrong error message ([#1152](https://github.com/GENI-NSF/geni-portal/issues/1152))
* WiMAX: Disallow delete group if you lead another group ([#1151](https://github.com/GENI-NSF/geni-portal/issues/1151))
* properly close img tags ([#1145](https://github.com/GENI-NSF/geni-portal/issues/1145))
* Display project URN on project page ([#1142](https://github.com/GENI-NSF/geni-portal/issues/1142))
* name request and manifest rspec as such in tmp dir ([#1132](https://github.com/GENI-NSF/geni-portal/issues/1132))
* Show the last line of omni stderr to the user ([#1131](https://github.com/GENI-NSF/geni-portal/issues/1131))
* Filter to AM only for non-stitching requests ([#1130](https://github.com/GENI-NSF/geni-portal/issues/1130))
* View RSpecs using Jacks viewer ([#1127](https://github.com/GENI-NSF/geni-portal/issues/1127))
* Remove disabled users from WiMAX groups ([#1124](https://github.com/GENI-NSF/geni-portal/issues/1124))
* Update to latest xml-signer ([#1116](https://github.com/GENI-NSF/geni-portal/issues/1116))
* Fork omni/stitcher processes and modify Add Resources results page ([#1106](https://github.com/GENI-NSF/geni-portal/issues/1106))
* WiMAX: Make new users/groups named geni-foo ([#1094](https://github.com/GENI-NSF/geni-portal/issues/1094))
* WiMAX: Update to new error codes / methods ([#1093](https://github.com/GENI-NSF/geni-portal/issues/1093))
* link to jFed ([#1092](https://github.com/GENI-NSF/geni-portal/issues/1092))
* Support Unicode strings in Portal ([#1069](https://github.com/GENI-NSF/geni-portal/issues/1069))
* Add privilege check when requesting to view private RSpecs ([#1054](https://github.com/GENI-NSF/geni-portal/issues/1054))
* Use style sheet on openid/server.php ([#1007](https://github.com/GENI-NSF/geni-portal/issues/1007))
* Make ajax delete and renew run in parallel ([#998](https://github.com/GENI-NSF/geni-portal/issues/998))
* RSpecs: store all manifests ([#225](https://github.com/GENI-NSF/geni-portal/issues/225))
* Add link to request RSpec on slice page ([#165](https://github.com/GENI-NSF/geni-portal/issues/165))
* Store all request RSpecs ([#164](https://github.com/GENI-NSF/geni-portal/issues/164))

# Release 2.18

## Issues Closed

* Adjust stale-omni to reflect stitcher_php.py ([#1114](https://github.com/GENI-NSF/geni-portal/issues/1114))
* Allow URL specified GENI Desktop site ([#1113](https://github.com/GENI-NSF/geni-portal/issues/1113))
* relative redirect on sign in mangles URL ([#1110](https://github.com/GENI-NSF/geni-portal/issues/1110))
* Add Resources: changing RSpec resets AM ([#1108](https://github.com/GENI-NSF/geni-portal/issues/1108))
* openid wimax attribute legal names changed ([#1097](https://github.com/GENI-NSF/geni-portal/issues/1097))
* Project Join Request email subject is grammatically incorrect ([#1083](https://github.com/GENI-NSF/geni-portal/issues/1083))
* Add several indices and constraints for performance ([#1081](https://github.com/GENI-NSF/geni-portal/issues/1081))
* create slice page should give error message on missing slice name ([#1028](https://github.com/GENI-NSF/geni-portal/issues/1028))
* schemas missing constraints ([#943](https://github.com/GENI-NSF/geni-portal/issues/943))
* It's not clear that the "Raw SliverStatus" link triggers new requests ([#764](https://github.com/GENI-NSF/geni-portal/issues/764))
* pa_project(project_id) should be declared unique ([#657](https://github.com/GENI-NSF/geni-portal/issues/657))
* Stitching Allocate Resources Interface ([#581](https://github.com/GENI-NSF/geni-portal/issues/581))
* Add definition of roles to portal interface ([#558](https://github.com/GENI-NSF/geni-portal/issues/558))
* Detail pages should mention manifest rspec ([#475](https://github.com/GENI-NSF/geni-portal/issues/475))
* Make the list of RSpecs be ordered or organized in some way ([#467](https://github.com/GENI-NSF/geni-portal/issues/467))
* Add new rspec page: explain the fields ([#420](https://github.com/GENI-NSF/geni-portal/issues/420))
* Add "View RSpec" button on the Add Resources page ([#412](https://github.com/GENI-NSF/geni-portal/issues/412))
* RSpecs: search function ([#228](https://github.com/GENI-NSF/geni-portal/issues/228))

# Release 2.17

## Issues Closed

* Remove dependence on "pg_manifest" field of sliver status return ([#1084](https://github.com/GENI-NSF/geni-portal/issues/1084))
* Update to gcf 2.6 ([#1078](https://github.com/GENI-NSF/geni-portal/issues/1078))
* Implement pushstate history on profile tabs ([#1074](https://github.com/GENI-NSF/geni-portal/issues/1074))
* Change LabWiki URL ([#1073](https://github.com/GENI-NSF/geni-portal/issues/1073))
* fix spelling and grammar in "renew your certifcate any time" ([#1072](https://github.com/GENI-NSF/geni-portal/issues/1072))
* WiMAX delete user fails ([#1068](https://github.com/GENI-NSF/geni-portal/issues/1068))
* Omni bundle overwrites public keys with the same file name ([#1063](https://github.com/GENI-NSF/geni-portal/issues/1063))
* PHP date_parse does not have tz_id as a key in the dictionary it returns ([#1061](https://github.com/GENI-NSF/geni-portal/issues/1061))
* Update link to password reset page ([#1056](https://github.com/GENI-NSF/geni-portal/issues/1056))
* Pick your IdP page uses http ([#1025](https://github.com/GENI-NSF/geni-portal/issues/1025))
* Automatically retrieve Ad RSpecs from AMs ([#721](https://github.com/GENI-NSF/geni-portal/issues/721))

# Release 2.16

## Issues Closed

* Slice page does not switch to "This Slice" on Safari ([#1067](https://github.com/GENI-NSF/geni-portal/issues/1067))
* Table-wide renew calendar gets hidden underneath AMs ([#1057](https://github.com/GENI-NSF/geni-portal/issues/1057))
* undefined variable ldif_project_name ([#1053](https://github.com/GENI-NSF/geni-portal/issues/1053))
* Clean up Jacks Viewer implementation ([#1051](https://github.com/GENI-NSF/geni-portal/issues/1051))
* Clarify slice page wording about user keys being loaded on nodes ([#1050](https://github.com/GENI-NSF/geni-portal/issues/1050))
* project names and ids are out of sync on wimax-enable ([#1049](https://github.com/GENI-NSF/geni-portal/issues/1049))
* Add option to Renew Slice and Known Resources on slice page ([#1048](https://github.com/GENI-NSF/geni-portal/issues/1048))
* Minor fixes to the new slice page ([#1047](https://github.com/GENI-NSF/geni-portal/issues/1047))
* Change WiMAX orbit LDAP base url ([#1045](https://github.com/GENI-NSF/geni-portal/issues/1045))
* slice page: add an 'Add' button per AM ([#1042](https://github.com/GENI-NSF/geni-portal/issues/1042))
* update dragon cert ([#1040](https://github.com/GENI-NSF/geni-portal/issues/1040))
* Remove precedence and auto-submitted mail headers ([#1039](https://github.com/GENI-NSF/geni-portal/issues/1039))
* Implement new slice front end ([#1038](https://github.com/GENI-NSF/geni-portal/issues/1038))
* 2 undefined variables on Details ([#1031](https://github.com/GENI-NSF/geni-portal/issues/1031))
* add hide raw resource specification ([#1030](https://github.com/GENI-NSF/geni-portal/issues/1030))
* Profile page uses http for an icon ([#1026](https://github.com/GENI-NSF/geni-portal/issues/1026))
* Enable WiMAX for all portal users ([#1015](https://github.com/GENI-NSF/geni-portal/issues/1015))
* Slice page: actions on selected aggregates only ([#958](https://github.com/GENI-NSF/geni-portal/issues/958))
* Change omni bundle to be called `omni.bundle` ([#929](https://github.com/GENI-NSF/geni-portal/issues/929))
* Would be nice if I could tell the portal where I had resources ([#762](https://github.com/GENI-NSF/geni-portal/issues/762))
* RSpecs: Allow editing properties ([#226](https://github.com/GENI-NSF/geni-portal/issues/226))
* Join a Project table must be sortable, searchable ([#93](https://github.com/GENI-NSF/geni-portal/issues/93))

# Release 2.15

## Issues Closed

* geni-ops-report doesn't handle disabled users ([#1037](https://github.com/GENI-NSF/geni-portal/issues/1037))
* update dragon cert ([#1033](https://github.com/GENI-NSF/geni-portal/issues/1033))
* Labwiki kmactivate.php redirect fails ([#1029](https://github.com/GENI-NSF/geni-portal/issues/1029))
* tool-omniconfig default project doesn't match link ([#1005](https://github.com/GENI-NSF/geni-portal/issues/1005))
* Files are left in /tmp ([#978](https://github.com/GENI-NSF/geni-portal/issues/978))
* New account registration does not honor maintenance outage ([#965](https://github.com/GENI-NSF/geni-portal/issues/965))
* Error for operators during outage with portal de-authorized ([#944](https://github.com/GENI-NSF/geni-portal/issues/944))
* Display OpenID URL on profile page ([#290](https://github.com/GENI-NSF/geni-portal/issues/290))

# Release 2.14

## Issues Closed

* Allow multiple Jacks windows on listresources ([#1027](https://github.com/GENI-NSF/geni-portal/issues/1027))
* Wording modifications for ssh generation page ([#1024](https://github.com/GENI-NSF/geni-portal/issues/1024))
* Wording modifications for the Join a project page ([#1023](https://github.com/GENI-NSF/geni-portal/issues/1023))
* Incorporate xml-signer 0.7 ([#1022](https://github.com/GENI-NSF/geni-portal/issues/1022))
* Remove PUBLIC_KEYs from DETAILS_PUBLIC ([#1021](https://github.com/GENI-NSF/geni-portal/issues/1021))
* Refactor listresources.php page ([#1020](https://github.com/GENI-NSF/geni-portal/issues/1020))
* details page calls listresources twice ([#1017](https://github.com/GENI-NSF/geni-portal/issues/1017))
* AM calls failing with php fatal error ([#1016](https://github.com/GENI-NSF/geni-portal/issues/1016))
* Firefox says "This website does not supply ownership information" for portal.geni.net ([#1014](https://github.com/GENI-NSF/geni-portal/issues/1014))
* Implement Jacks editor onto an unlinked copy of slice-add-resources.php ([#1013](https://github.com/GENI-NSF/geni-portal/issues/1013))
* Update the ION AM SSL certificate ([#1012](https://github.com/GENI-NSF/geni-portal/issues/1012))
* Implement Jacks viewer into listresources.php ([#1011](https://github.com/GENI-NSF/geni-portal/issues/1011))
* Remove dead scripts ([#999](https://github.com/GENI-NSF/geni-portal/issues/999))
* Add new link to the portal landing page ([#994](https://github.com/GENI-NSF/geni-portal/issues/994))
* timeout calls to omni ([#863](https://github.com/GENI-NSF/geni-portal/issues/863))

# Release 2.13

## Issues Closed

* Remove slice expiration as default for sliver renewal ([#1010](https://github.com/GENI-NSF/geni-portal/issues/1010))
* Provide UI for renewing experimenter certificate ([#241](https://github.com/GENI-NSF/geni-portal/issues/241))

# Release 2.12

## Issues Closed

* quote a log message ([#1003](https://github.com/GENI-NSF/geni-portal/issues/1003))
* Omni config with no project gives an error ([#1002](https://github.com/GENI-NSF/geni-portal/issues/1002))
* Update GENI Desktop certificate ([#1001](https://github.com/GENI-NSF/geni-portal/issues/1001))
* Delete geni-renew-slice ([#997](https://github.com/GENI-NSF/geni-portal/issues/997))
* Remove readyToLogin.php ([#996](https://github.com/GENI-NSF/geni-portal/issues/996))
* Represent each GENI site on the map ([#995](https://github.com/GENI-NSF/geni-portal/issues/995))
* Malformed 'From' field in join related emails ([#988](https://github.com/GENI-NSF/geni-portal/issues/988))
* Cleanup passphrase temp files ([#979](https://github.com/GENI-NSF/geni-portal/issues/979))

# Release 2.11

## Issues Closed

* Improve speaks-for flow with popup overlay ([#991](https://github.com/GENI-NSF/geni-portal/issues/991))
* Add 3rd floor Dell rack running GRAM AM into testing infrastructure ([#989](https://github.com/GENI-NSF/geni-portal/issues/989))
* flack.php sometimes missing a value ([#987](https://github.com/GENI-NSF/geni-portal/issues/987))
* Update iRODS server cert ([#986](https://github.com/GENI-NSF/geni-portal/issues/986))
* Add expiration column to support certificate renewal ([#985](https://github.com/GENI-NSF/geni-portal/issues/985))
* Add starter rack running GRAM AM into testing infrastructure ([#984](https://github.com/GENI-NSF/geni-portal/issues/984))
* update activate page for citations ([#983](https://github.com/GENI-NSF/geni-portal/issues/983))
* Flack fails with speaks-for ([#982](https://github.com/GENI-NSF/geni-portal/issues/982))
* Clean up speaks-for logging ([#981](https://github.com/GENI-NSF/geni-portal/issues/981))

# Release 2.10

## Issues Closed

* tool-omniconfigure always produces v2.3.1 omni_config files ([#980](https://github.com/GENI-NSF/geni-portal/issues/980))
* Tell experimenters to use Omni 2.5 and chapi framework ([#975](https://github.com/GENI-NSF/geni-portal/issues/975))
* Change to use omni 2.5 and chapi framework in am calls ([#974](https://github.com/GENI-NSF/geni-portal/issues/974))
* Pass a default MA to the xml-signer tool ([#972](https://github.com/GENI-NSF/geni-portal/issues/972))
* Renew Date needs have explicit TZ ([#971](https://github.com/GENI-NSF/geni-portal/issues/971))
* Portal am_client renew calls should use --alap flag ([#969](https://github.com/GENI-NSF/geni-portal/issues/969))
* Associate attributes with service registry services ([#968](https://github.com/GENI-NSF/geni-portal/issues/968))
* AJAX-ify the _slice_ details page ([#465](https://github.com/GENI-NSF/geni-portal/issues/465))

# Release 2.9

## Issues Closed

* Support Common Federation API v2 ([#967](https://github.com/GENI-NSF/geni-portal/issues/967))
* Pass credentials and options to logging service ([#966](https://github.com/GENI-NSF/geni-portal/issues/966))
* RSpecs: Allow anonymous rspecs ([#957](https://github.com/GENI-NSF/geni-portal/issues/957))
* incommon error redirect should do portal logout ([#939](https://github.com/GENI-NSF/geni-portal/issues/939))
* error-text should have a mailto link for help ([#921](https://github.com/GENI-NSF/geni-portal/issues/921))
* dont hardcode URNs ([#10](https://github.com/GENI-NSF/geni-portal/issues/10))

# Release 2.8

## Issues Closed

* Add confirm dialog box when renewing resources on a slice ([#963](https://github.com/GENI-NSF/geni-portal/issues/963))
* map html files should use SERVER_NAME, not HTTP_HOST ([#962](https://github.com/GENI-NSF/geni-portal/issues/962))
* Show projects and slices in proper case ([#961](https://github.com/GENI-NSF/geni-portal/issues/961))
* missing copyright in ./portal/www/portal/tool-aggwarning.php ([#960](https://github.com/GENI-NSF/geni-portal/issues/960))
* Slice page: warn when acting on all aggregates ([#959](https://github.com/GENI-NSF/geni-portal/issues/959))
* Update flack.php to use new loading mechanism ([#955](https://github.com/GENI-NSF/geni-portal/issues/955))
* Slice and project expiration times ([#953](https://github.com/GENI-NSF/geni-portal/issues/953))

# Release 2.7

## Issues Closed

* ma_client too verbose on empty list of UIDs ([#954](https://github.com/GENI-NSF/geni-portal/issues/954))
* Support change to logging service API ([#951](https://github.com/GENI-NSF/geni-portal/issues/951))
* iRODS server cert expired ([#947](https://github.com/GENI-NSF/geni-portal/issues/947))
* update copyrights for proto-ch to 2014 ([#945](https://github.com/GENI-NSF/geni-portal/issues/945))
* Running scripts as MA causes log guard error ([#942](https://github.com/GENI-NSF/geni-portal/issues/942))
* Pass irods username and zone in Labwiki OpenID payload ([#937](https://github.com/GENI-NSF/geni-portal/issues/937))
* handle-project-request happily re-handles a handled request ([#926](https://github.com/GENI-NSF/geni-portal/issues/926))
* Function get_projects_for_member relies on global $user ([#917](https://github.com/GENI-NSF/geni-portal/issues/917))
* optimize CH queries ([#912](https://github.com/GENI-NSF/geni-portal/issues/912))
* Add a link to IdP password reset page on the IdP login page ([#904](https://github.com/GENI-NSF/geni-portal/issues/904))
* Denying project join request should prompt for email text ([#876](https://github.com/GENI-NSF/geni-portal/issues/876))
* account registration page rejects some passwords ([#756](https://github.com/GENI-NSF/geni-portal/issues/756))
* Can't add email address to a project via bulk upload ([#713](https://github.com/GENI-NSF/geni-portal/issues/713))
* Portal/CH mail should attempt to avoid auto-replies ([#686](https://github.com/GENI-NSF/geni-portal/issues/686))
* portal log_event calls should use $user ([#568](https://github.com/GENI-NSF/geni-portal/issues/568))
* Add a text box to project bulk add page ([#516](https://github.com/GENI-NSF/geni-portal/issues/516))
* Properly handle repeated project join requests ([#410](https://github.com/GENI-NSF/geni-portal/issues/410))
* Handle case where someone invites another person to join a project twice ([#380](https://github.com/GENI-NSF/geni-portal/issues/380))
* Join requests should probably show up in one page so that you can address all of them at once ([#376](https://github.com/GENI-NSF/geni-portal/issues/376))
* Manifest page: tweak wording ([#277](https://github.com/GENI-NSF/geni-portal/issues/277))

# Release 2.6.2

## Issues Closed

* parametrize Flack URL ([#941](https://github.com/GENI-NSF/geni-portal/issues/941))
* Portal fails to handle not authorized ([#940](https://github.com/GENI-NSF/geni-portal/issues/940))
* portal logout message should say more ([#938](https://github.com/GENI-NSF/geni-portal/issues/938))
* handle-project-request should check if request is still pending ([#936](https://github.com/GENI-NSF/geni-portal/issues/936))
* ma_client must not redirect on name lookup errors ([#935](https://github.com/GENI-NSF/geni-portal/issues/935))
* maintenance mode is only checked on home page ([#934](https://github.com/GENI-NSF/geni-portal/issues/934))
* privilege scripts should remind you to restart apache ([#933](https://github.com/GENI-NSF/geni-portal/issues/933))
* chapi client drops client cert chain ([#931](https://github.com/GENI-NSF/geni-portal/issues/931))
* Authority certificates use incorrect URI format for UUID ([#909](https://github.com/GENI-NSF/geni-portal/issues/909))
* Certificate error in Flack from emulab.net ([#905](https://github.com/GENI-NSF/geni-portal/issues/905))
* delete obsolete PHP code ([#898](https://github.com/GENI-NSF/geni-portal/issues/898))
* portal should forbid changing expired project ([#897](https://github.com/GENI-NSF/geni-portal/issues/897))
* portal "project join" requests should not impersonate an experimenter's e-mail address ([#879](https://github.com/GENI-NSF/geni-portal/issues/879))
* Automated emails should set bulk email headers ([#871](https://github.com/GENI-NSF/geni-portal/issues/871))
* Add a way to determine how you created your SSL cert ([#840](https://github.com/GENI-NSF/geni-portal/issues/840))
* Move the slice membership table above the AM list ([#819](https://github.com/GENI-NSF/geni-portal/issues/819))
* Organize list of slices in a project by which are your slices ([#710](https://github.com/GENI-NSF/geni-portal/issues/710))

# Release 2.6.1

## Issues Closed

* geni-enable-user should handle error returns ([#927](https://github.com/GENI-NSF/geni-portal/issues/927))
* Flack on dev servers doesnt trust the server ([#925](https://github.com/GENI-NSF/geni-portal/issues/925))
* install geni-enable/disable scripts ([#924](https://github.com/GENI-NSF/geni-portal/issues/924))
* member attribute scripts should use MA API ([#923](https://github.com/GENI-NSF/geni-portal/issues/923))
* Add ION AM to portal/CH ([#922](https://github.com/GENI-NSF/geni-portal/issues/922))
* no copyright in compute_rollback_sql.py ([#920](https://github.com/GENI-NSF/geni-portal/issues/920))
* clean script usage ([#919](https://github.com/GENI-NSF/geni-portal/issues/919))
* clean up option in geni-enable-user ([#918](https://github.com/GENI-NSF/geni-portal/issues/918))
* handle-project-request fails to get email ([#915](https://github.com/GENI-NSF/geni-portal/issues/915))
* clean up scripts ([#914](https://github.com/GENI-NSF/geni-portal/issues/914))

# Release 2.6

## Issues Closed

* expired projects are in the list for omni default ([#907](https://github.com/GENI-NSF/geni-portal/issues/907))
* geni-ops-report uses ma_member_privilege ([#903](https://github.com/GENI-NSF/geni-portal/issues/903))
* undefined index in tool-slices ([#902](https://github.com/GENI-NSF/geni-portal/issues/902))
* upload-project-members warning ([#901](https://github.com/GENI-NSF/geni-portal/issues/901))
* newch: do-upload-project-members fails ([#900](https://github.com/GENI-NSF/geni-portal/issues/900))
* member privilege scripts fail with import error ([#899](https://github.com/GENI-NSF/geni-portal/issues/899))
* No messages appear on home screen ([#896](https://github.com/GENI-NSF/geni-portal/issues/896))
* aggregate cache doesn't update ([#895](https://github.com/GENI-NSF/geni-portal/issues/895))
* ma_create_certificate is not implemented ([#893](https://github.com/GENI-NSF/geni-portal/issues/893))
* Show pretty name for members on project page ([#892](https://github.com/GENI-NSF/geni-portal/issues/892))
* kettering-ig-of.pem has cruft line ([#890](https://github.com/GENI-NSF/geni-portal/issues/890))
* CHAPI result_handler should trim error string ([#889](https://github.com/GENI-NSF/geni-portal/issues/889))
* compare all booleans using convert_boolean ([#888](https://github.com/GENI-NSF/geni-portal/issues/888))
* do-handle-project-request does not check inputs ([#886](https://github.com/GENI-NSF/geni-portal/issues/886))
* WiMAX: ensure non empty givenname ([#885](https://github.com/GENI-NSF/geni-portal/issues/885))
* Install ca-gpolab.crt in the service_registry ([#884](https://github.com/GENI-NSF/geni-portal/issues/884))
* CHAPI *_client code doesn't handle exceptions ([#883](https://github.com/GENI-NSF/geni-portal/issues/883))
* when portal fails to ask CH whether an account exists, portal assumes the account doesn't exist ([#882](https://github.com/GENI-NSF/geni-portal/issues/882))
* project join approval email cleanup ([#881](https://github.com/GENI-NSF/geni-portal/issues/881))
* OBE: message_handler gives ugly error on non GET request ([#872](https://github.com/GENI-NSF/geni-portal/issues/872))
* OBE: /etc/init.d/geni-pgch should set a umask ([#868](https://github.com/GENI-NSF/geni-portal/issues/868))
* OBE: Remove PA controller from apache config ([#855](https://github.com/GENI-NSF/geni-portal/issues/855))
* OBE: geni-add-project-member doesn't handle lead role properly ([#854](https://github.com/GENI-NSF/geni-portal/issues/854))
* log all API calls ([#782](https://github.com/GENI-NSF/geni-portal/issues/782))
* OBE: message_handler should log on unexpected request type ([#775](https://github.com/GENI-NSF/geni-portal/issues/775))
* OBE: log message registering SSH key should skip quotes? ([#739](https://github.com/GENI-NSF/geni-portal/issues/739))
* OBE: project invite accept log message is misleading ([#726](https://github.com/GENI-NSF/geni-portal/issues/726))
* OBE: traceback in pgch: python bug on failed SSL connections ([#702](https://github.com/GENI-NSF/geni-portal/issues/702))
* OBE: pgch cert cache will get out of date ([#698](https://github.com/GENI-NSF/geni-portal/issues/698))
* OBE: Put pgch behind apache ([#672](https://github.com/GENI-NSF/geni-portal/issues/672))
* OBE: pgch should log source IP ([#651](https://github.com/GENI-NSF/geni-portal/issues/651))
* OBE: Differentiating MA Attributes ([#647](https://github.com/GENI-NSF/geni-portal/issues/647))
* OBE: geni-pgch get_ch_version returns a hardcoded version ([#624](https://github.com/GENI-NSF/geni-portal/issues/624))
* OBE: log pgch to syslog ([#600](https://github.com/GENI-NSF/geni-portal/issues/600))
* OBE: MA errors on new user setup ([#528](https://github.com/GENI-NSF/geni-portal/issues/528))
* OBE: Multiple PA functions unguarded ([#502](https://github.com/GENI-NSF/geni-portal/issues/502))
* OBE: key MA functions unprotected ([#501](https://github.com/GENI-NSF/geni-portal/issues/501))
* syslog failed authorizations ([#491](https://github.com/GENI-NSF/geni-portal/issues/491))
* Create a mechanism to disable accounts of people who are behaving badly ([#486](https://github.com/GENI-NSF/geni-portal/issues/486))
* OBE: pgch shows error messages ([#459](https://github.com/GENI-NSF/geni-portal/issues/459))
* OBE: undefined index on email_address in ma_utils ([#399](https://github.com/GENI-NSF/geni-portal/issues/399))
* OBE: put_message should not do an HTTP redirect ([#370](https://github.com/GENI-NSF/geni-portal/issues/370))
* OBE: request_authorization should return false if action is not authorized ([#369](https://github.com/GENI-NSF/geni-portal/issues/369))
* OBE: Fix permissions for viewing member slices ([#328](https://github.com/GENI-NSF/geni-portal/issues/328))
* OBE: Clearinghouse services should catch exceptions and return proper errors ([#319](https://github.com/GENI-NSF/geni-portal/issues/319))
* OBE: MA must produce user credentials ([#216](https://github.com/GENI-NSF/geni-portal/issues/216))
* OBE: MA use CSRs internally ([#161](https://github.com/GENI-NSF/geni-portal/issues/161))
* OBE: merge Credential Store and Authorization service ([#136](https://github.com/GENI-NSF/geni-portal/issues/136))
* OBE: Make all installs be via packages or config files ([#106](https://github.com/GENI-NSF/geni-portal/issues/106))
* OBE: Cache reserved resources ([#59](https://github.com/GENI-NSF/geni-portal/issues/59))
* OBE: gcf-pgch implement Resolve(user) ([#12](https://github.com/GENI-NSF/geni-portal/issues/12))
* OBE: gcf-pgch check valid uuids ([#11](https://github.com/GENI-NSF/geni-portal/issues/11))
* OBE: put_message handle HTTP header error codes ([#9](https://github.com/GENI-NSF/geni-portal/issues/9))
* OBE: *_controller check arguments ([#6](https://github.com/GENI-NSF/geni-portal/issues/6))

# Release 2.5.1

## Issues Closed

* gemini page sending expired projects ([#887](https://github.com/GENI-NSF/geni-portal/issues/887))

# Release 2.5

## Issues Closed

* Revamp the "Modify Account page" ([#877](https://github.com/GENI-NSF/geni-portal/issues/877))
* add language to stop project doorknob rattling ([#875](https://github.com/GENI-NSF/geni-portal/issues/875))
* Sort project lists ([#873](https://github.com/GENI-NSF/geni-portal/issues/873))
* Undefined index in kmcert ([#870](https://github.com/GENI-NSF/geni-portal/issues/870))
* do-register should check for existing account ([#864](https://github.com/GENI-NSF/geni-portal/issues/864))
* Change omni configuration instructions to say `omni` not `omni.py` etc ([#862](https://github.com/GENI-NSF/geni-portal/issues/862))
* wimax: undefined user attributes ([#859](https://github.com/GENI-NSF/geni-portal/issues/859))
* quiet print-rspec debug ([#858](https://github.com/GENI-NSF/geni-portal/issues/858))
* Fix error reporting of geni-revoke-member-privileges and geni-remove-project-member ([#853](https://github.com/GENI-NSF/geni-portal/issues/853))
* PG insists slice names are unique ([#433](https://github.com/GENI-NSF/geni-portal/issues/433))

# Release 2.4

## Issues Closed

* clearing or extending project expiration does not un-expire the project ([#841](https://github.com/GENI-NSF/geni-portal/issues/841))
* do-renew should forbid times past project expiration ([#839](https://github.com/GENI-NSF/geni-portal/issues/839))
* Add project expiration time in the slice view ([#838](https://github.com/GENI-NSF/geni-portal/issues/838))
* Remove idp account request pages ([#837](https://github.com/GENI-NSF/geni-portal/issues/837))
* print rspec pretty errors in logs ([#836](https://github.com/GENI-NSF/geni-portal/issues/836))
* irods: remove project members from irods group when removed from project ([#832](https://github.com/GENI-NSF/geni-portal/issues/832))
* irods: add project members to irods groups ([#831](https://github.com/GENI-NSF/geni-portal/issues/831))
* irods: create irods group for each project ([#830](https://github.com/GENI-NSF/geni-portal/issues/830))
* irods: refactor basic stuff to an irods_utils ([#829](https://github.com/GENI-NSF/geni-portal/issues/829))
* openid AX to labwiki has an error ([#828](https://github.com/GENI-NSF/geni-portal/issues/828))
* Rationalize the Profile tabs ([#812](https://github.com/GENI-NSF/geni-portal/issues/812))
* Rspecs in user Profile ([#810](https://github.com/GENI-NSF/geni-portal/issues/810))
* clean up geni-revoke-member-permission ([#716](https://github.com/GENI-NSF/geni-portal/issues/716))
* Cert generation and download links outside of Omni ([#708](https://github.com/GENI-NSF/geni-portal/issues/708))
* geni_revoke_member_privilege doesnt for operators ([#668](https://github.com/GENI-NSF/geni-portal/issues/668))
* Make slice member names be mailto links on slice page ([#538](https://github.com/GENI-NSF/geni-portal/issues/538))

# Release 2.3

## Issues Closed

* OpenID: Fatal error calling undefined method Auth_OpenID_AX_Error::iterTypes() ([#827](https://github.com/GENI-NSF/geni-portal/issues/827))
* Add LabWiki button ([#825](https://github.com/GENI-NSF/geni-portal/issues/825))
* Different details view for EG and IG ([#823](https://github.com/GENI-NSF/geni-portal/issues/823))
* Make "Generate SSL cert" button open in a new tab ([#822](https://github.com/GENI-NSF/geni-portal/issues/822))
* Add slice name instructions in the create slice page ([#821](https://github.com/GENI-NSF/geni-portal/issues/821))
* link rspec printing broken ([#818](https://github.com/GENI-NSF/geni-portal/issues/818))
* report_genich_relations reports slice creation and expiration in wrong timezone ([#817](https://github.com/GENI-NSF/geni-portal/issues/817))
* Pass additional info in OpenID data ([#813](https://github.com/GENI-NSF/geni-portal/issues/813))
* Alphabetize members in Project ([#811](https://github.com/GENI-NSF/geni-portal/issues/811))
* WiMAX: Support openid ([#809](https://github.com/GENI-NSF/geni-portal/issues/809))
* WiMAX: Support changing project lead ([#808](https://github.com/GENI-NSF/geni-portal/issues/808))
* WiMAX: Allow project leads to enable multiple WiMAX groups ([#807](https://github.com/GENI-NSF/geni-portal/issues/807))
* WiMAX: Allow deleting groups ([#806](https://github.com/GENI-NSF/geni-portal/issues/806))
* WiMAX: Allow deleting group members ([#805](https://github.com/GENI-NSF/geni-portal/issues/805))
* renew should assume 23:59:59 ([#796](https://github.com/GENI-NSF/geni-portal/issues/796))
* WiMAX: Support error codes ([#777](https://github.com/GENI-NSF/geni-portal/issues/777))
* WiMAX: how do we handle duplicate usernames? ([#776](https://github.com/GENI-NSF/geni-portal/issues/776))
* WiMAX: Support changing projects ([#774](https://github.com/GENI-NSF/geni-portal/issues/774))
* WiMAX: change URL ([#771](https://github.com/GENI-NSF/geni-portal/issues/771))
* remove Add Note function on slice and project pages ([#728](https://github.com/GENI-NSF/geni-portal/issues/728))
* clean up geni-remove-from-project ([#715](https://github.com/GENI-NSF/geni-portal/issues/715))
* Clean up OpenID logging ([#670](https://github.com/GENI-NSF/geni-portal/issues/670))

# Release 2.2

## Issues Closed

* log when printing rspec skips non-local nodes ([#803](https://github.com/GENI-NSF/geni-portal/issues/803))
* upload-project-members must handle empty file ([#802](https://github.com/GENI-NSF/geni-portal/issues/802))
* Comment out ROWS debug log ([#798](https://github.com/GENI-NSF/geni-portal/issues/798))
* Make GENI username more visible ([#797](https://github.com/GENI-NSF/geni-portal/issues/797))
* new code for printing out SSHA hashes for the wiki inserts too much whitespace ([#795](https://github.com/GENI-NSF/geni-portal/issues/795))
* Update portal to use gcf-2.4 final ([#794](https://github.com/GENI-NSF/geni-portal/issues/794))
* GENI Portal has incorrect company name in copyright label ([#793](https://github.com/GENI-NSF/geni-portal/issues/793))
* print-text-helpers php warnings ([#791](https://github.com/GENI-NSF/geni-portal/issues/791))
* PHP warning on error_log in ma_controller ([#790](https://github.com/GENI-NSF/geni-portal/issues/790))
* There is no version information in the GENI portal ([#789](https://github.com/GENI-NSF/geni-portal/issues/789))
* Slice listing needs sorting in portal ([#788](https://github.com/GENI-NSF/geni-portal/issues/788))
* ExoGENI hostname shows empty on details page ([#767](https://github.com/GENI-NSF/geni-portal/issues/767))
* Alphabetize RSpecs and label public and private RSpecs ([#761](https://github.com/GENI-NSF/geni-portal/issues/761))
* Send an email to experimenter when they request to be a project lead ([#760](https://github.com/GENI-NSF/geni-portal/issues/760))
* Rephrase Omni Configuration instructions ([#753](https://github.com/GENI-NSF/geni-portal/issues/753))
* use code format on irods page ([#744](https://github.com/GENI-NSF/geni-portal/issues/744))
* Use the word "portal" on the portal.geni.net landing page ([#733](https://github.com/GENI-NSF/geni-portal/issues/733))
* Lack of component_manager_id causes manifests to not be displayed for ExoSM ([#712](https://github.com/GENI-NSF/geni-portal/issues/712))
* Should be able to see key fingerprints from portal UI ([#695](https://github.com/GENI-NSF/geni-portal/issues/695))
* Status on Expired Slice ([#675](https://github.com/GENI-NSF/geni-portal/issues/675))
* Fix message on the portal when followin an approval link ([#617](https://github.com/GENI-NSF/geni-portal/issues/617))
* Update some links from portal-help to help ([#572](https://github.com/GENI-NSF/geni-portal/issues/572))
* Add the ability to load Project Admins/Members onto slices by default ([#559](https://github.com/GENI-NSF/geni-portal/issues/559))
* Add a refresh button in sliver status page ([#451](https://github.com/GENI-NSF/geni-portal/issues/451))
* Clean up files in /tmp ([#383](https://github.com/GENI-NSF/geni-portal/issues/383))

# Release 2.1

## Issues Closed

* rspec upload errors in logs ([#781](https://github.com/GENI-NSF/geni-portal/issues/781))
* Update Map RSpec data for new RSpecs ([#780](https://github.com/GENI-NSF/geni-portal/issues/780))
* Add IG GPO OF and GPO OF AM install files ([#779](https://github.com/GENI-NSF/geni-portal/issues/779))
* Federated error handling is broken ([#778](https://github.com/GENI-NSF/geni-portal/issues/778))
* undefined HTTP_HOST in map-small.html ([#755](https://github.com/GENI-NSF/geni-portal/issues/755))
* add new GPO sandbox portal/CH nodes to proto-ch git ([#745](https://github.com/GENI-NSF/geni-portal/issues/745))
* Revise handling of Google Analytics ([#742](https://github.com/GENI-NSF/geni-portal/issues/742))
* shib_idp_users should output pw hash in backticks ([#732](https://github.com/GENI-NSF/geni-portal/issues/732))
* non project member can load project page ([#727](https://github.com/GENI-NSF/geni-portal/issues/727))
* RSpec upload page not robust to bad RSpecs ([#720](https://github.com/GENI-NSF/geni-portal/issues/720))
* Undefined variable: ams in amstatus ([#692](https://github.com/GENI-NSF/geni-portal/issues/692))
* download SSH public key from downloaded pair filename should end in .pub ([#681](https://github.com/GENI-NSF/geni-portal/issues/681))
* SA allows members or auditors to edit slice membership ([#533](https://github.com/GENI-NSF/geni-portal/issues/533))
* Make order of aggregates consistent ([#478](https://github.com/GENI-NSF/geni-portal/issues/478))
* Sliver status returns error for AMs with no resources ([#429](https://github.com/GENI-NSF/geni-portal/issues/429))
* Add new rspec page: should new rspecs be public by default? ([#421](https://github.com/GENI-NSF/geni-portal/issues/421))
* Update portal to use gcf-2.4 ([#392](https://github.com/GENI-NSF/geni-portal/issues/392))
* remove pgch once gcf updated ([#347](https://github.com/GENI-NSF/geni-portal/issues/347))
* Fix hardcoded hostname in openid configuration files ([#287](https://github.com/GENI-NSF/geni-portal/issues/287))

# Release 2.0

## Issues Closed

* Fix Google Analytics string to be a global variable ([#743](https://github.com/GENI-NSF/geni-portal/issues/743))
* clean up example admin address used in etc/settings.php ([#741](https://github.com/GENI-NSF/geni-portal/issues/741))
* Add Resources page should include tool-showmessage ([#738](https://github.com/GENI-NSF/geni-portal/issues/738))
* Fix minor bugs in new portal CSS ([#737](https://github.com/GENI-NSF/geni-portal/issues/737))
* Don't install advertisement RSpecs and map.php ([#736](https://github.com/GENI-NSF/geni-portal/issues/736))
* No copyright in './portal/www/portal/map.php' ([#735](https://github.com/GENI-NSF/geni-portal/issues/735))
* Create GENI resource map for monitor display ([#734](https://github.com/GENI-NSF/geni-portal/issues/734))
* Remove fake slice email from slice certificates ([#729](https://github.com/GENI-NSF/geni-portal/issues/729))
* Add Google Analytics functionality ([#718](https://github.com/GENI-NSF/geni-portal/issues/718))
* Update the portal's CSS ([#717](https://github.com/GENI-NSF/geni-portal/issues/717))
* Profile RSpecs section should say RSpec ([#707](https://github.com/GENI-NSF/geni-portal/issues/707))
* add ToC on profile page ([#706](https://github.com/GENI-NSF/geni-portal/issues/706))
* Move glossary from portal to GENI public wiki. ([#703](https://github.com/GENI-NSF/geni-portal/issues/703))
* script to add ma_member_attribute ([#701](https://github.com/GENI-NSF/geni-portal/issues/701))
* write a standalone script to determine which people in a provided list have portal accounts ([#700](https://github.com/GENI-NSF/geni-portal/issues/700))
* enable VERIFYHOST in put_message ([#660](https://github.com/GENI-NSF/geni-portal/issues/660))
* Fix Slice/Project Membership functionality according to recent feedback ([#496](https://github.com/GENI-NSF/geni-portal/issues/496))
* Can't look at a slice I'm not a member of, but can see it from projects page ([#442](https://github.com/GENI-NSF/geni-portal/issues/442))
* Make button-like things be buttons ([#175](https://github.com/GENI-NSF/geni-portal/issues/175))
* Create interactive map of resources ([#28](https://github.com/GENI-NSF/geni-portal/issues/28))
