# Release 3.4

## Issues Closed

* Add ability to set default homepage tab ([#1579](https://github.com/GENI-NSF/geni-portal/issues/1579))
* Users get stuck in confusing state if they're in no active projects. ([#1567](https://github.com/GENI-NSF/geni-portal/issues/1567))
* Add ability to clear "last message" ([#1565](https://github.com/GENI-NSF/geni-portal/issues/1565))
* Add version to local storage  ([#1479](https://github.com/GENI-NSF/geni-portal/issues/1479))
* On admin page slice search, show member usernames ([#1432](https://github.com/GENI-NSF/geni-portal/issues/1432))
* Some error messages are page redirects on the admin page ([#1417](https://github.com/GENI-NSF/geni-portal/issues/1417))

## Pull Requests Merged

* Add geni-sync-wireless to doc (PR [#1586](https://github.com/GENI-NSF/geni-portal/pull/1586))
* Improve error handling for failed slice searches on admin page. (PR [#1585](https://github.com/GENI-NSF/geni-portal/pull/1585))
* Move man page to geni-ch (PR [#1584](https://github.com/GENI-NSF/geni-portal/pull/1584))
* Add user preference for picking default homepage tab. (PR [#1583](https://github.com/GENI-NSF/geni-portal/pull/1583))
* Show name and username when listing slice members on slice search. (PR [#1582](https://github.com/GENI-NSF/geni-portal/pull/1582))
* Change what displays when a user has no active projects. (PR [#1581](https://github.com/GENI-NSF/geni-portal/pull/1581))
* Add x button to clear alerts set with $_SESSION['lastmessage'] (PR [#1578](https://github.com/GENI-NSF/geni-portal/pull/1578))
* Add version number to local storage and clear Ls when this changes. (PR [#1577](https://github.com/GENI-NSF/geni-portal/pull/1577))
* Bump version to 3.4 (PR [#1575](https://github.com/GENI-NSF/geni-portal/pull/1575))
* Migrate scripts to geni-ch (PR [#1413](https://github.com/GENI-NSF/geni-portal/pull/1413))

# Release 3.3

## Issues Closed

* Add a secondary sort for slices ([#1560](https://github.com/GENI-NSF/geni-portal/issues/1560))
* Footer is different width than the normal content. ([#1555](https://github.com/GENI-NSF/geni-portal/issues/1555))
* Add user preferences ([#1526](https://github.com/GENI-NSF/geni-portal/issues/1526))
* Check portalIsAuthorized earlier in dashboard.php ([#1506](https://github.com/GENI-NSF/geni-portal/issues/1506))
* Bring back the slice list ([#1498](https://github.com/GENI-NSF/geni-portal/issues/1498))
* Would like an option for default slice view ([#1482](https://github.com/GENI-NSF/geni-portal/issues/1482))
* dashboard storage is confused on shared computers ([#1477](https://github.com/GENI-NSF/geni-portal/issues/1477))
* TZ on lead requests is wrong ([#1446](https://github.com/GENI-NSF/geni-portal/issues/1446))
* Support new model for wireless accounts ([#1392](https://github.com/GENI-NSF/geni-portal/issues/1392))
* WiMAX: forbid delete your group when you lead another group ([#1136](https://github.com/GENI-NSF/geni-portal/issues/1136))
* WiMAX: Add state sync functions ([#1076](https://github.com/GENI-NSF/geni-portal/issues/1076))
* WiMAX: Inconsistent DB state ([#1058](https://github.com/GENI-NSF/geni-portal/issues/1058))
* WiMAX: Support multiple wimax sites ([#773](https://github.com/GENI-NSF/geni-portal/issues/773))
* WiMAX: Put URL in service registry ([#772](https://github.com/GENI-NSF/geni-portal/issues/772))

## Pull Requests Merged

* Clear local storage when different user logs into portal on same pc. (PR [#1574](https://github.com/GENI-NSF/geni-portal/pull/1574))
* Use slice/project name to break ties when sorting. (PR [#1573](https://github.com/GENI-NSF/geni-portal/pull/1573))
* Dashboard code cleanup (PR [#1572](https://github.com/GENI-NSF/geni-portal/pull/1572))
* Make footer width match width of other cards (PR [#1571](https://github.com/GENI-NSF/geni-portal/pull/1571))
* Silence SQL notices. (PR [#1570](https://github.com/GENI-NSF/geni-portal/pull/1570))
* Add preference for default tab on the slice page.  (PR [#1568](https://github.com/GENI-NSF/geni-portal/pull/1568))
* Update slice table view (PR [#1566](https://github.com/GENI-NSF/geni-portal/pull/1566))
* Use UTC for database timestamps (PR [#1564](https://github.com/GENI-NSF/geni-portal/pull/1564))
* Update Jacks context file (PR [#1563](https://github.com/GENI-NSF/geni-portal/pull/1563))
* bump to 3.3 (PR [#1559](https://github.com/GENI-NSF/geni-portal/pull/1559))
* Add user preferences to portal. (PR [#1556](https://github.com/GENI-NSF/geni-portal/pull/1556))
* Wireless integration between GENI and ORBIT (PR [#1532](https://github.com/GENI-NSF/geni-portal/pull/1532))

# Release 3.2

## Issues Closed

* Logs on slice and project page can timeout ([#1557](https://github.com/GENI-NSF/geni-portal/issues/1557))
* Lots of dead code in header.php ([#1553](https://github.com/GENI-NSF/geni-portal/issues/1553))
* last_seen table is not being populated ([#1550](https://github.com/GENI-NSF/geni-portal/issues/1550))
* Renew doesn't work on (new) slice page ([#1549](https://github.com/GENI-NSF/geni-portal/issues/1549))
* Allow for switching between arbitrary cards in cards.js ([#1545](https://github.com/GENI-NSF/geni-portal/issues/1545))
* Cards file has hardcoded reference to map tab for map initialization ([#1544](https://github.com/GENI-NSF/geni-portal/issues/1544))
* PHP warning in logs for admin page ([#1535](https://github.com/GENI-NSF/geni-portal/issues/1535))
* Add link to projects tab from new slice page when not in any projects ([#1524](https://github.com/GENI-NSF/geni-portal/issues/1524))
* Replace user name with "User" when load_user is false in show_header ([#1519](https://github.com/GENI-NSF/geni-portal/issues/1519))
* Aggregate view on slice page has lost its styling ([#1518](https://github.com/GENI-NSF/geni-portal/issues/1518))
* Alerts look bad with new header design ([#1516](https://github.com/GENI-NSF/geni-portal/issues/1516))
* Update project page to match dashboard ([#1514](https://github.com/GENI-NSF/geni-portal/issues/1514))
* Update profile page to match dashboard ([#1513](https://github.com/GENI-NSF/geni-portal/issues/1513))
* Make project name on slice card a link to the project ([#1512](https://github.com/GENI-NSF/geni-portal/issues/1512))
* Make "N slices" on project card be a link to those slices ([#1511](https://github.com/GENI-NSF/geni-portal/issues/1511))
* Update slice page to match dashboard ([#1509](https://github.com/GENI-NSF/geni-portal/issues/1509))
* Need to be able to sort slices by project ([#1497](https://github.com/GENI-NSF/geni-portal/issues/1497))
* Logs pane embeds login page if session times out ([#1460](https://github.com/GENI-NSF/geni-portal/issues/1460))

## Pull Requests Merged

* Fix bug where login page gets embedded on slices & projects log tabs on session timeout (PR [#1558](https://github.com/GENI-NSF/geni-portal/pull/1558))
* Clean up code in header.php, remove references to old header tabs (PR [#1554](https://github.com/GENI-NSF/geni-portal/pull/1554))
* Fix issue where "last_seen" table was not being populated (PR [#1552](https://github.com/GENI-NSF/geni-portal/pull/1552))
* Fixed broken renew button on the (new) slice page (PR [#1551](https://github.com/GENI-NSF/geni-portal/pull/1551))
* Make "has n slices" a link to the slice card showing those slices (PR [#1547](https://github.com/GENI-NSF/geni-portal/pull/1547))
* Clean up cards.js code, add ability to switch to arbitrary card (PR [#1546](https://github.com/GENI-NSF/geni-portal/pull/1546))
* Fix bug where login screen gets embedded in logs card on session timeout (PR [#1543](https://github.com/GENI-NSF/geni-portal/pull/1543))
* Show links to profile pages even when $load_user is false (PR [#1542](https://github.com/GENI-NSF/geni-portal/pull/1542))
* Add link to projects tab from new slice page when not in any projects. (PR [#1541](https://github.com/GENI-NSF/geni-portal/pull/1541))
* Make project names on slice cards a link to that project (PR [#1540](https://github.com/GENI-NSF/geni-portal/pull/1540))
* Fix formatting of the aggregate view on the slice page (PR [#1539](https://github.com/GENI-NSF/geni-portal/pull/1539))
* Fix php warnings about undefined indices on admin page (PR [#1538](https://github.com/GENI-NSF/geni-portal/pull/1538))
* Allow for sorting of slices by project name on dashboard. (PR [#1536](https://github.com/GENI-NSF/geni-portal/pull/1536))
* Update project page to use new tab/cards style  (PR [#1534](https://github.com/GENI-NSF/geni-portal/pull/1534))
* Update slice page to match dashboard tab navigation (PR [#1533](https://github.com/GENI-NSF/geni-portal/pull/1533))
* Bump version to 3.2 (PR [#1530](https://github.com/GENI-NSF/geni-portal/pull/1530))
* Make tabs on the profile page match those on the dashboard. (PR [#1528](https://github.com/GENI-NSF/geni-portal/pull/1528))
* Make maintenance alerts appear as their own card above content. (PR [#1520](https://github.com/GENI-NSF/geni-portal/pull/1520))
* Migrate aggregates to geni-ch (PR [#1412](https://github.com/GENI-NSF/geni-portal/pull/1412))

# Release 3.1.1

## Issues Closed

* Deleted files appear in Makefiles ([#1531](https://github.com/GENI-NSF/geni-portal/issues/1531))

# Release 3.1

## Issues Closed

* Add a GENI bibliography link on the Help menu  ([#1510](https://github.com/GENI-NSF/geni-portal/issues/1510))
* Add google analytics to flack page ([#1504](https://github.com/GENI-NSF/geni-portal/issues/1504))
* Hamburger menu pops up on mobile on page resize ([#1500](https://github.com/GENI-NSF/geni-portal/issues/1500))
* Add a link to geni-lib under Tools menu ([#1491](https://github.com/GENI-NSF/geni-portal/issues/1491))
* Add google analytics to "expanded view" pages. ([#1488](https://github.com/GENI-NSF/geni-portal/issues/1488))
* Make projects.php redirect to projects tab on the dashboard. ([#1487](https://github.com/GENI-NSF/geni-portal/issues/1487))
* Add "contact us" page and link to it under "Help" dropdown. ([#1486](https://github.com/GENI-NSF/geni-portal/issues/1486))
* Indenting/ CSS of SSH key instructions on SSH tab is confusing ([#1485](https://github.com/GENI-NSF/geni-portal/issues/1485))
* Project cards grow on text overflow, making weird gaps on project tab. ([#1484](https://github.com/GENI-NSF/geni-portal/issues/1484))
* Renew slice button on dashboard pops up incorrect warning about too many aggregates ([#1481](https://github.com/GENI-NSF/geni-portal/issues/1481))
* Fix styling on expanded Jacks pages ([#1458](https://github.com/GENI-NSF/geni-portal/issues/1458))
* Add an Other Tools page ([#1457](https://github.com/GENI-NSF/geni-portal/issues/1457))
* GEO View only shows half on first display ([#1272](https://github.com/GENI-NSF/geni-portal/issues/1272))

## Pull Requests Merged

* Add link to GENI Bibliography (PR [#1515](https://github.com/GENI-NSF/geni-portal/pull/1515))
* Add google analytics to flack page (PR [#1505](https://github.com/GENI-NSF/geni-portal/pull/1505))
* Fix bug where hamburger menu would expand on every page resize. (PR [#1503](https://github.com/GENI-NSF/geni-portal/pull/1503))
* Fix styling of Jacks expanded views. (PR [#1502](https://github.com/GENI-NSF/geni-portal/pull/1502))
* Update welcome page to use new portal CSS styles. (PR [#1501](https://github.com/GENI-NSF/geni-portal/pull/1501))
* Add Google analytics (via adding header) to jacks-expanded views. (PR [#1499](https://github.com/GENI-NSF/geni-portal/pull/1499))
* Fix formatting on the SSH tab of the profile page to be clearer (PR [#1496](https://github.com/GENI-NSF/geni-portal/pull/1496))
* Add "Contact Us" page to the portal, and add link to it in help dropdown  (PR [#1495](https://github.com/GENI-NSF/geni-portal/pull/1495))
* Make projects.php redirect to projects tab on the dashboard. (PR [#1494](https://github.com/GENI-NSF/geni-portal/pull/1494))
* Handle text overflow on project and slice cards on dashboard. (PR [#1493](https://github.com/GENI-NSF/geni-portal/pull/1493))
* Fix erroneous popup warning when "Renew slice" clicked. (PR [#1490](https://github.com/GENI-NSF/geni-portal/pull/1490))
* Add link to 'Other Tools' wiki page listing omni, etc. to header (PR [#1489](https://github.com/GENI-NSF/geni-portal/pull/1489))
* Make maps on the portal Google maps (PR [#1428](https://github.com/GENI-NSF/geni-portal/pull/1428))
* Migrate some db schemas to geni-ch (PR [#1411](https://github.com/GENI-NSF/geni-portal/pull/1411))

# Release 3.0

## Issues Closed

* 'New Slice' from project card gives page where you cannot create a slice ([#1478](https://github.com/GENI-NSF/geni-portal/issues/1478))
* Renew resources (7 days) on slice with faraway expiration causes an error.  ([#1473](https://github.com/GENI-NSF/geni-portal/issues/1473))
* Renew resources (X days) in the slice dropdown has the wrong value for X. ([#1471](https://github.com/GENI-NSF/geni-portal/issues/1471))
* Undefined variable log_msg in db_utils.php on line 103 ([#1467](https://github.com/GENI-NSF/geni-portal/issues/1467))
* wimax-enable.php fails with too many redirects for users with no projects ([#1464](https://github.com/GENI-NSF/geni-portal/issues/1464))
* account changes always look like lead requests ([#1463](https://github.com/GENI-NSF/geni-portal/issues/1463))
* Link to SAVI testbed ([#1455](https://github.com/GENI-NSF/geni-portal/issues/1455))
* Remove Flack from Home and Slice Pages ([#1451](https://github.com/GENI-NSF/geni-portal/issues/1451))
* Manage Resources button on slice page doesn't work ([#1448](https://github.com/GENI-NSF/geni-portal/issues/1448))
* Add a Create Image button on the Jacks app ([#1389](https://github.com/GENI-NSF/geni-portal/issues/1389))
* Account changes posts telephone changes when it is unchanged ([#1339](https://github.com/GENI-NSF/geni-portal/issues/1339))
* Move or duplicate Wimax button ([#1262](https://github.com/GENI-NSF/geni-portal/issues/1262))
* Redesign the home page ([#1146](https://github.com/GENI-NSF/geni-portal/issues/1146))
* Add Renew button to the per slice row ([#711](https://github.com/GENI-NSF/geni-portal/issues/711))
* note on home.php and slice.php if there is imminent expiration of a slice (and slivers where possible) ([#613](https://github.com/GENI-NSF/geni-portal/issues/613))
* Remove "account" and "identity" tables ([#299](https://github.com/GENI-NSF/geni-portal/issues/299))

## Pull Requests Merged

* Fix issue where create slice button was disabled when project_id was given in URL params (PR [#1480](https://github.com/GENI-NSF/geni-portal/pull/1480))
* Fix issue where renew button in slice dropdown would try and shorten slice lifetime (PR [#1476](https://github.com/GENI-NSF/geni-portal/pull/1476))
* Bump major version (PR [#1474](https://github.com/GENI-NSF/geni-portal/pull/1474))
* Don't store all profile changes as lead requests (PR [#1466](https://github.com/GENI-NSF/geni-portal/pull/1466))
* Tkt1389 createimage (PR [#1452](https://github.com/GENI-NSF/geni-portal/pull/1452))
* Restore manage resources functionality (PR [#1450](https://github.com/GENI-NSF/geni-portal/pull/1450))
* Remove account and identity tables (issue 299) (PR [#1415](https://github.com/GENI-NSF/geni-portal/pull/1415))
