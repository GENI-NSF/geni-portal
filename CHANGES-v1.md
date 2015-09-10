# Release 1.9.6

## Issues Closed

* Add ExoGENI FIU and UH racks to service registry ([#714](https://github.com/GENI-NSF/geni-portal/issues/714))

# Release 1.9.5

## Issues Closed

* pgch should log on every call ([#697](https://github.com/GENI-NSF/geni-portal/issues/697))
* wimax-enable.php should not check for certificates ([#689](https://github.com/GENI-NSF/geni-portal/issues/689))
* Undefined variable in tool-expired-projects ([#687](https://github.com/GENI-NSF/geni-portal/issues/687))
* Fix tracebacks in omni_php.py when creating a sliver fails ([#649](https://github.com/GENI-NSF/geni-portal/issues/649))
* slice expiration datepicker shouldn't let you pick a date past the project expiration ([#614](https://github.com/GENI-NSF/geni-portal/issues/614))
* Handle error return from omni on create sliver ([#321](https://github.com/GENI-NSF/geni-portal/issues/321))

# Release 1.9.4

## Issues Closed

* log if someone tries to download their outside cert ([#699](https://github.com/GENI-NSF/geni-portal/issues/699))

# Release 1.9.3

## Issues Closed

* pgch is not threadsafe ([#690](https://github.com/GENI-NSF/geni-portal/issues/690))

# Release 1.9.1

## Issues Closed

* produce firstname and lastname fields for GMOC monitoring when IdP does not provide them ([#688](https://github.com/GENI-NSF/geni-portal/issues/688))

# Release 1.9

## Issues Closed

* WiMAX page shows success as failure ([#685](https://github.com/GENI-NSF/geni-portal/issues/685))
* WiMAX must send 1st ssh key as `sshpublickey` ([#684](https://github.com/GENI-NSF/geni-portal/issues/684))
* Only show current AM on createsliver page ([#683](https://github.com/GENI-NSF/geni-portal/issues/683))
* show links for the current aggregate on the Details page ([#682](https://github.com/GENI-NSF/geni-portal/issues/682))
* rspecview logs the whole rspec ([#680](https://github.com/GENI-NSF/geni-portal/issues/680))
* delete ssh key result message is ugly ([#679](https://github.com/GENI-NSF/geni-portal/issues/679))
* update ssh key result message broken ([#678](https://github.com/GENI-NSF/geni-portal/issues/678))
* valid_expiration undefined ([#677](https://github.com/GENI-NSF/geni-portal/issues/677))
* pgch createslice fails ([#676](https://github.com/GENI-NSF/geni-portal/issues/676))
* gemini.php: fails to send private ssh keys ([#674](https://github.com/GENI-NSF/geni-portal/issues/674))
* add a help link to the irods page ([#673](https://github.com/GENI-NSF/geni-portal/issues/673))
* repair bitrot in report_genich_relations ([#669](https://github.com/GENI-NSF/geni-portal/issues/669))
* do-edit-project should check permissions ([#666](https://github.com/GENI-NSF/geni-portal/issues/666))
* Fix call(s) to modify_project_membership in pa_client.php ([#665](https://github.com/GENI-NSF/geni-portal/issues/665))
* debug log in rspecupload ([#663](https://github.com/GENI-NSF/geni-portal/issues/663))
* GIMI OpenID client does not receive response from server ([#661](https://github.com/GENI-NSF/geni-portal/issues/661))
* Add pa_project_attribute table ([#656](https://github.com/GENI-NSF/geni-portal/issues/656))
* Allow for download public SSH key ([#655](https://github.com/GENI-NSF/geni-portal/issues/655))
* update the privacy policy to cover sharing of data with operators via GMOC ([#654](https://github.com/GENI-NSF/geni-portal/issues/654))
* change links from SignMeUpPortal to SignMeUp ([#653](https://github.com/GENI-NSF/geni-portal/issues/653))
* pgch needs load balancing ([#650](https://github.com/GENI-NSF/geni-portal/issues/650))
* undefined variables in amstatus ([#648](https://github.com/GENI-NSF/geni-portal/issues/648))
* e-mail to identify users in bulk upload should not be case sensitive ([#646](https://github.com/GENI-NSF/geni-portal/issues/646))
* Login information is wrong in multi-user slices ([#645](https://github.com/GENI-NSF/geni-portal/issues/645))
* GENI User should be more restrictive ([#644](https://github.com/GENI-NSF/geni-portal/issues/644))
* Fix sa_url in tool-omniconfig.php ([#643](https://github.com/GENI-NSF/geni-portal/issues/643))
* Move cainfo.html to ch area ([#642](https://github.com/GENI-NSF/geni-portal/issues/642))
* Fix warnings in gemini.php ([#641](https://github.com/GENI-NSF/geni-portal/issues/641))
* create iRODS accounts ([#640](https://github.com/GENI-NSF/geni-portal/issues/640))
* Default slice expirations ([#636](https://github.com/GENI-NSF/geni-portal/issues/636))
* Add text next to the request to be a project lead button ([#630](https://github.com/GENI-NSF/geni-portal/issues/630))
* "Join Project" request can lead to bounced messages ([#629](https://github.com/GENI-NSF/geni-portal/issues/629))
* Add the email field to the lead request email notification ([#626](https://github.com/GENI-NSF/geni-portal/issues/626))
* Cleanup `Details` page to only show nodes actually reserved at a particular aggregate ([#605](https://github.com/GENI-NSF/geni-portal/issues/605))
* Handle DOS et al newlines in bulk add page ([#584](https://github.com/GENI-NSF/geni-portal/issues/584))
* Merge PA and SA ([#540](https://github.com/GENI-NSF/geni-portal/issues/540))
* Clean up bulk invite email ([#529](https://github.com/GENI-NSF/geni-portal/issues/529))
* Support WiMAX sites ([#484](https://github.com/GENI-NSF/geni-portal/issues/484))
* save error Omni results ([#444](https://github.com/GENI-NSF/geni-portal/issues/444))
* Handle mistyped email address in Project Join Invitations ([#409](https://github.com/GENI-NSF/geni-portal/issues/409))
* On text box to invite project members, clarify if text box takes newlines, commas, etc ([#405](https://github.com/GENI-NSF/geni-portal/issues/405))
* getslicecred GENI AM API call should log something when invoked by an operator ([#397](https://github.com/GENI-NSF/geni-portal/issues/397))
* Verify OpenID username against clearinghouse data ([#289](https://github.com/GENI-NSF/geni-portal/issues/289))
* The OpenID login sequence is messy ([#288](https://github.com/GENI-NSF/geni-portal/issues/288))

# Release 1.8

## Issues Closed

* KMTool can't handle capitalized EPPN's that are different from EPPN's in database ([#635](https://github.com/GENI-NSF/geni-portal/issues/635))
* Portal website refers to incorrect 'footer.php' file ([#633](https://github.com/GENI-NSF/geni-portal/issues/633))
* import_database should use a tempfile for list of members ([#632](https://github.com/GENI-NSF/geni-portal/issues/632))
* Update_User_Certs.py does not handle no-key/no-cert case ([#631](https://github.com/GENI-NSF/geni-portal/issues/631))
* SSH private keys can be duplicated in GEMINI data ([#628](https://github.com/GENI-NSF/geni-portal/issues/628))
* Conditionalize display of the generate ssh key button on uploadsshkey.php ([#625](https://github.com/GENI-NSF/geni-portal/issues/625))
* Update GEMINI integration ([#623](https://github.com/GENI-NSF/geni-portal/issues/623))
* gemini.php is issuing PHP warnings ([#619](https://github.com/GENI-NSF/geni-portal/issues/619))
* OpenID cannot talk to service registry ([#618](https://github.com/GENI-NSF/geni-portal/issues/618))
* Modify the "Join the project" default email text ([#616](https://github.com/GENI-NSF/geni-portal/issues/616))
* handle project request sends email to malformed address ([#611](https://github.com/GENI-NSF/geni-portal/issues/611))
* ch.geni.net apache config should block portal access ([#590](https://github.com/GENI-NSF/geni-portal/issues/590))
* tools-admin.php is fake ([#539](https://github.com/GENI-NSF/geni-portal/issues/539))
* Augment "Details" page with login info for multiple users ([#510](https://github.com/GENI-NSF/geni-portal/issues/510))

# Release 1.7.1

## Issues Closed

* Month expiration date should be incremented by one ([#610](https://github.com/GENI-NSF/geni-portal/issues/610))
* Add `do-handle-project-request.php` to Makefile.am ([#609](https://github.com/GENI-NSF/geni-portal/issues/609))

# Release 1.7

## Issues Closed

* update_user_certs not robust to user with no portal key ([#606](https://github.com/GENI-NSF/geni-portal/issues/606))
* Send SSH keys to GEMINI ([#604](https://github.com/GENI-NSF/geni-portal/issues/604))
* Revamp slice and sliver renewal ([#603](https://github.com/GENI-NSF/geni-portal/issues/603))
* Add GEMINI button(s) to home page ([#602](https://github.com/GENI-NSF/geni-portal/issues/602))
* script to reset outside cert ([#601](https://github.com/GENI-NSF/geni-portal/issues/601))
* Add a note that slice member keys/account will only be adding via the native portal resource reservation mechanism ([#596](https://github.com/GENI-NSF/geni-portal/issues/596))
* Add an index page for a clearinghouse host ([#595](https://github.com/GENI-NSF/geni-portal/issues/595))
* Update OpenId for portal.geni.net ([#594](https://github.com/GENI-NSF/geni-portal/issues/594))
* emails from services have wrong hostname ([#593](https://github.com/GENI-NSF/geni-portal/issues/593))
* Call setClientCert with an array instead of a string ([#592](https://github.com/GENI-NSF/geni-portal/issues/592))
* Remove create_standard_services script ([#587](https://github.com/GENI-NSF/geni-portal/issues/587))
* Add project info to GEMINI data ([#585](https://github.com/GENI-NSF/geni-portal/issues/585))
* List of project in download omni bundle should only include active projects ([#554](https://github.com/GENI-NSF/geni-portal/issues/554))
* Add a "delete" ssh key button to Profile page ([#542](https://github.com/GENI-NSF/geni-portal/issues/542))
* project lead email has empty addressee in body ([#521](https://github.com/GENI-NSF/geni-portal/issues/521))
* Have raw manifest appear in a window ([#520](https://github.com/GENI-NSF/geni-portal/issues/520))
* new project page: note lead name is public ([#469](https://github.com/GENI-NSF/geni-portal/issues/469))
* geni-pgch.log should have timestamps in it ([#398](https://github.com/GENI-NSF/geni-portal/issues/398))
* Renew slivers when you renew slice ([#126](https://github.com/GENI-NSF/geni-portal/issues/126))
* Flack does not handle a client certificate chain ([#16](https://github.com/GENI-NSF/geni-portal/issues/16))

# Release 1.6.2

## Issues Closed

* Handle EPPN as case-insensitive ([#597](https://github.com/GENI-NSF/geni-portal/issues/597))

# Release 1.6

## Issues Closed

* import_db clobbers non member logging_entries ([#589](https://github.com/GENI-NSF/geni-portal/issues/589))
* 2 files not being installed in cs/db ([#586](https://github.com/GENI-NSF/geni-portal/issues/586))
* notify users to download a new version of omni ([#583](https://github.com/GENI-NSF/geni-portal/issues/583))
* accept project invite breadcrumbs typo ([#582](https://github.com/GENI-NSF/geni-portal/issues/582))
* In maintenance mode, allow operators to do more ([#579](https://github.com/GENI-NSF/geni-portal/issues/579))
* Errors in ch_error.log about /var/www/cainfo.html ([#578](https://github.com/GENI-NSF/geni-portal/issues/578))
* on DB change, must change member_ids ([#577](https://github.com/GENI-NSF/geni-portal/issues/577))
* sbin/import_database missing copyright ([#576](https://github.com/GENI-NSF/geni-portal/issues/576))
* create post-5th intro page for panther ([#575](https://github.com/GENI-NSF/geni-portal/issues/575))
* Add more scripts to bin install ([#569](https://github.com/GENI-NSF/geni-portal/issues/569))
* Manage ma_outside_cert table for transition ([#564](https://github.com/GENI-NSF/geni-portal/issues/564))
* do-register creates ABAC entries that are unused ([#556](https://github.com/GENI-NSF/geni-portal/issues/556))

# Release 1.5

## Issues Closed

* Clarify wording on user cert generation page ([#567](https://github.com/GENI-NSF/geni-portal/issues/567))
* Make omni-bundle download be the same regardless of the number of projects ([#566](https://github.com/GENI-NSF/geni-portal/issues/566))
* Split out sundown/lockdown in a branch off of develop ([#565](https://github.com/GENI-NSF/geni-portal/issues/565))
* Add S/MIME signing to GEMINI data handoff ([#560](https://github.com/GENI-NSF/geni-portal/issues/560))
* pgch fails to handle renewslice error ([#555](https://github.com/GENI-NSF/geni-portal/issues/555))
* Update certificate creation instructions to match omni-configure ([#552](https://github.com/GENI-NSF/geni-portal/issues/552))
* Add `authority` field to omni-bundle and template omni_config ([#551](https://github.com/GENI-NSF/geni-portal/issues/551))
* Limit max-expiration in preparation for lockdown mode ([#548](https://github.com/GENI-NSF/geni-portal/issues/548))
* Create and support Portal/CH Lockdown Mode ([#547](https://github.com/GENI-NSF/geni-portal/issues/547))
* Accept Project Invite page is messy ([#530](https://github.com/GENI-NSF/geni-portal/issues/530))
* geni-add-trusted-tool has stack trace on failed db authentication ([#505](https://github.com/GENI-NSF/geni-portal/issues/505))
* install scripts that portal operators run in /usr/local/bin on portals ([#499](https://github.com/GENI-NSF/geni-portal/issues/499))
* Clean up make install ([#488](https://github.com/GENI-NSF/geni-portal/issues/488))
* install and shell scripts should get service hostnames from a config file ([#354](https://github.com/GENI-NSF/geni-portal/issues/354))
* Put SR url in settings.php and use it ([#352](https://github.com/GENI-NSF/geni-portal/issues/352))
* Eliminate GeniUser->urn() ([#351](https://github.com/GENI-NSF/geni-portal/issues/351))

# Release 1.4

## Issues Closed

* Allow taking away project lead or operator privilege ([#550](https://github.com/GENI-NSF/geni-portal/issues/550))
* Add a script to revoke privileges ([#549](https://github.com/GENI-NSF/geni-portal/issues/549))
* copyright missing from tools/stitch_utils.py ([#544](https://github.com/GENI-NSF/geni-portal/issues/544))
* Slice owner is not updated when Slice Lead is changed ([#537](https://github.com/GENI-NSF/geni-portal/issues/537))
* Remove debug printouts ([#531](https://github.com/GENI-NSF/geni-portal/issues/531))
* Project Lead shown on home page (and project page?) is actually the project creator ([#512](https://github.com/GENI-NSF/geni-portal/issues/512))
* Refresh authority certificates ([#507](https://github.com/GENI-NSF/geni-portal/issues/507))
* Add a button for GENI Desktop to slice page ([#503](https://github.com/GENI-NSF/geni-portal/issues/503))
* maintenance mode ([#495](https://github.com/GENI-NSF/geni-portal/issues/495))
* pgch: implement getversion ([#492](https://github.com/GENI-NSF/geni-portal/issues/492))
* On portal login page, add link to GPO IdP account request form ([#487](https://github.com/GENI-NSF/geni-portal/issues/487))
* Implement user lockout for maintenance ([#485](https://github.com/GENI-NSF/geni-portal/issues/485))
* GENI IdP login formats strange on GEC16 tutorial VM ([#452](https://github.com/GENI-NSF/geni-portal/issues/452))

# Release 1.3

## Issues Closed

* Lead cannot change membership ([#532](https://github.com/GENI-NSF/geni-portal/issues/532))
* Displayed expired slices on project page but hidden by a button ([#523](https://github.com/GENI-NSF/geni-portal/issues/523))
* clean up privilege table ([#493](https://github.com/GENI-NSF/geni-portal/issues/493))
* Start a CHANGELOG ([#489](https://github.com/GENI-NSF/geni-portal/issues/489))
* Clean up the labels on the aggregate buttons in Flack ([#470](https://github.com/GENI-NSF/geni-portal/issues/470))
* Undefined HTTP_REFERER in sshkeyedit.php ([#438](https://github.com/GENI-NSF/geni-portal/issues/438))
* Errors in error.log for new users ([#435](https://github.com/GENI-NSF/geni-portal/issues/435))
* Sliverstatus error in ready sliver ([#431](https://github.com/GENI-NSF/geni-portal/issues/431))
* SSH public key identifier says www-data ([#427](https://github.com/GENI-NSF/geni-portal/issues/427))
* proto-ch make install should put some useful /bin/ scripts into central bin directories ([#426](https://github.com/GENI-NSF/geni-portal/issues/426))
* Create omni config bundle ([#424](https://github.com/GENI-NSF/geni-portal/issues/424))
* sa_controller redundancy ([#423](https://github.com/GENI-NSF/geni-portal/issues/423))
* give AMs consistent short names ([#402](https://github.com/GENI-NSF/geni-portal/issues/402))
* OpenID authentication results in a connection timed out error ([#396](https://github.com/GENI-NSF/geni-portal/issues/396))
* slice expiration uses localtime instead of UTC ([#386](https://github.com/GENI-NSF/geni-portal/issues/386))
* geni-pgch is not logging ([#385](https://github.com/GENI-NSF/geni-portal/issues/385))
* Slice creation and expiration times are identical ([#384](https://github.com/GENI-NSF/geni-portal/issues/384))
* Create admin scripts to alter project membership ([#368](https://github.com/GENI-NSF/geni-portal/issues/368))
* Add project expiration ([#367](https://github.com/GENI-NSF/geni-portal/issues/367))
* Undefined variables in log file ([#366](https://github.com/GENI-NSF/geni-portal/issues/366))
* Renewing individual sliver gives error "No new sliver expiration time specified." ([#364](https://github.com/GENI-NSF/geni-portal/issues/364))
* Update portal_omni_config once gcf-2.2 is release ([#363](https://github.com/GENI-NSF/geni-portal/issues/363))
* Renew Sliver expiration wrong ([#361](https://github.com/GENI-NSF/geni-portal/issues/361))
* Make Delete Resources page not be slow ([#358](https://github.com/GENI-NSF/geni-portal/issues/358))
* Slice status for individual aggregate not updating ([#357](https://github.com/GENI-NSF/geni-portal/issues/357))
* Does slice page show expired slices? should it? ([#346](https://github.com/GENI-NSF/geni-portal/issues/346))
* Record portal user last seen timestamp ([#330](https://github.com/GENI-NSF/geni-portal/issues/330))
* pgch is not restarted on machine reboot ([#326](https://github.com/GENI-NSF/geni-portal/issues/326))
* Add resources: include all Slice member ssh keys ([#248](https://github.com/GENI-NSF/geni-portal/issues/248))
* Flack: update to latest ([#233](https://github.com/GENI-NSF/geni-portal/issues/233))
* support tutorial accounts ([#200](https://github.com/GENI-NSF/geni-portal/issues/200))
* slice credentials for GMOC staff ([#178](https://github.com/GENI-NSF/geni-portal/issues/178))
* bulk add to project ([#171](https://github.com/GENI-NSF/geni-portal/issues/171))
* allow removing members from projects, slices ([#153](https://github.com/GENI-NSF/geni-portal/issues/153))
* Allow seeing/downloading request RSpecs ([#104](https://github.com/GENI-NSF/geni-portal/issues/104))
* Allow adding known people to projects/slices ([#86](https://github.com/GENI-NSF/geni-portal/issues/86))
* Explore shibboleth logout capabilities ([#64](https://github.com/GENI-NSF/geni-portal/issues/64))
* Construct join/invite pages for slices changes ([#58](https://github.com/GENI-NSF/geni-portal/issues/58))
* turn off indexing ([#1](https://github.com/GENI-NSF/geni-portal/issues/1))

# Release 1.2

## Issues Closed

* AJAX requests are not handled in parallel due to session management ([#356](https://github.com/GENI-NSF/geni-portal/issues/356))
* Speed up SliverStatus page ([#355](https://github.com/GENI-NSF/geni-portal/issues/355))
* Add "use omni" button to slice page ([#345](https://github.com/GENI-NSF/geni-portal/issues/345))
* If user requests to join one project, the project owner sees requests for the user to join each project ([#344](https://github.com/GENI-NSF/geni-portal/issues/344))
* Handle users whose IdP does not share an email address ([#341](https://github.com/GENI-NSF/geni-portal/issues/341))
* Project join email should include url ([#337](https://github.com/GENI-NSF/geni-portal/issues/337))
* Google Chrome shows HTTPS warning on portal pages ([#335](https://github.com/GENI-NSF/geni-portal/issues/335))
* Modify portal to support download of `omni_config` with CH ([#334](https://github.com/GENI-NSF/geni-portal/issues/334))
* e-mail notification of user approval should say who approved the user ([#333](https://github.com/GENI-NSF/geni-portal/issues/333))
* Change "manifest" to "details" on button label ([#332](https://github.com/GENI-NSF/geni-portal/issues/332))
* Disable reserve button to prevent multiple presses ([#324](https://github.com/GENI-NSF/geni-portal/issues/324))
* Use the federated error handler if mail attribute is not available from IdP. ([#318](https://github.com/GENI-NSF/geni-portal/issues/318))
* Make `pgch` support `listmyslices` ([#317](https://github.com/GENI-NSF/geni-portal/issues/317))
* Make `pgch` support `RenewSlice` ([#315](https://github.com/GENI-NSF/geni-portal/issues/315))
* Generate an `omni_config` to use omni with the CH ([#314](https://github.com/GENI-NSF/geni-portal/issues/314))
* send portal-dev-admin email when user's IdP doesn't share enough ([#308](https://github.com/GENI-NSF/geni-portal/issues/308))
* log more project changes ([#297](https://github.com/GENI-NSF/geni-portal/issues/297))
* check project names and slice names for valid URN chars ([#285](https://github.com/GENI-NSF/geni-portal/issues/285))
* Add Resources page is slow ([#250](https://github.com/GENI-NSF/geni-portal/issues/250))
* On slice.php Sliver Status for all AMs is slow ([#245](https://github.com/GENI-NSF/geni-portal/issues/245))
* RSpecs: allow online viewing ([#227](https://github.com/GENI-NSF/geni-portal/issues/227))
* Add outside key support to KM ([#202](https://github.com/GENI-NSF/geni-portal/issues/202))
* address coding sprint critiques ([#180](https://github.com/GENI-NSF/geni-portal/issues/180))
* Sort home page log messages by time ([#119](https://github.com/GENI-NSF/geni-portal/issues/119))
* Download slice credential page and link ([#85](https://github.com/GENI-NSF/geni-portal/issues/85))
* Add buttons (and modify pages) to generate createsliver, manifest, and delete pages for one AM ([#81](https://github.com/GENI-NSF/geni-portal/issues/81))

# Release 1.1

## Issues Closed

* Flack fails getting slice credential ([#325](https://github.com/GENI-NSF/geni-portal/issues/325))
* Internal error on renew slice ([#320](https://github.com/GENI-NSF/geni-portal/issues/320))
* Project lead email is missing user name ([#316](https://github.com/GENI-NSF/geni-portal/issues/316))
* Princeton users see a portal error when logging in via InCommon ([#311](https://github.com/GENI-NSF/geni-portal/issues/311))
* kmactivate page should have a link to the InCommon privacy policy ([#307](https://github.com/GENI-NSF/geni-portal/issues/307))
* Add resources: awhile to a while ([#305](https://github.com/GENI-NSF/geni-portal/issues/305))
* modify account: 2 periods ([#304](https://github.com/GENI-NSF/geni-portal/issues/304))
* Be consistent about project lead request text ([#303](https://github.com/GENI-NSF/geni-portal/issues/303))
* Delete Resources page has button called Delete Slivers ([#301](https://github.com/GENI-NSF/geni-portal/issues/301))
* button on Create Project page should say Create ([#300](https://github.com/GENI-NSF/geni-portal/issues/300))
* Clean up request logic ([#296](https://github.com/GENI-NSF/geni-portal/issues/296))
* protect handle-project-request ([#295](https://github.com/GENI-NSF/geni-portal/issues/295))
* omni 2.0 version cache fails on portal ([#203](https://github.com/GENI-NSF/geni-portal/issues/203))
