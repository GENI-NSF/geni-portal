# GENI Portal Release Notes

# [Release 3.22](https://github.com/GENI-NSF/geni-portal/milestones/3.22)

## Changes

* Use a placeholder ssh key in geni-sync-wireless
  ([#1726](https://github.com/GENI-NSF/geni-portal/issues/1726))
* Fix redirect on upload ssh key
  ([#1778](https://github.com/GENI-NSF/geni-portal/issues/1778))

## Installation Notes

* None

# [Release 3.21](https://github.com/GENI-NSF/geni-portal/milestones/3.21)

## Changes

* Set envelope sender to the portal's own email address
  ([#1774](https://github.com/GENI-NSF/geni-portal/issues/1774))

## Installation Notes

* None

# [Release 3.20](https://github.com/GENI-NSF/geni-portal/milestones/3.20)

## Changes

* Update packaging and cron documentation
  ([#1765](https://github.com/GENI-NSF/geni-portal/issues/1765))
* Switch to GitHub Flow
  ([#1766](https://github.com/GENI-NSF/geni-portal/issues/1766))
* Add cert and key to geni-sync-wireless call to sync one project
  ([#1767](https://github.com/GENI-NSF/geni-portal/issues/1767))
* Add from email address to settings instead of hard coded
  ([#1769](https://github.com/GENI-NSF/geni-portal/issues/1769))

## Installation Notes

* Add `$portal_from_email` to `/etc/geni-ch/settings.php`

# [Release 3.19](https://github.com/GENI-NSF/geni-portal/milestones/3.19)

## Changes

* Add CentOS 7 httpd config for OpenID
  ([#1755](https://github.com/GENI-NSF/geni-portal/issues/1755))
* Install Jacks icon file VM-noTxt-centered
  ([#1756](https://github.com/GENI-NSF/geni-portal/issues/1756))
* Make geni-fetch-aggmon request more data
  ([#1762](https://github.com/GENI-NSF/geni-portal/issues/1762))

## Installation Notes

* None

# [Release 3.18](https://github.com/GENI-NSF/geni-portal/milestones/3.18)

## Changes

* Rework the 'Join A Project' page for better privacy
  ([#1743](https://github.com/GENI-NSF/geni-portal/issues/1743))
* Remove cc to admins on self-asserted email address
  ([#1744](https://github.com/GENI-NSF/geni-portal/issues/1744))
* Use GENI Monitoring for Jacks AM status
  ([#1747](https://github.com/GENI-NSF/geni-portal/issues/1747))
* Change the Disk Image JSON URL for the Genidesktop
  ([#1748](https://github.com/GENI-NSF/geni-portal/issues/1748))
* Add Links to GENI monitoring on aggregate status pages
  ([#1751](https://github.com/GENI-NSF/geni-portal/issues/1751))
* Adopt unaffiliated identity provider for login
  ([#1753](https://github.com/GENI-NSF/geni-portal/issues/1753))

## Installation Notes

* None

# [Release 3.17](https://github.com/GENI-NSF/geni-portal/milestones/3.17)

## Changes

* Fix quotes inside examples in texinfo document
  ([#1731](https://github.com/GENI-NSF/geni-portal/issues/1731))
* Change /usr/local to /usr in doc to match RPM installation
  ([#1732](https://github.com/GENI-NSF/geni-portal/issues/1732))
* Use clearinghouse APIs for geni-sync-wireless
  ([#1733](https://github.com/GENI-NSF/geni-portal/issues/1733))
* geni-fetch-amdown fails outside GPO Lab
  ([#1734](https://github.com/GENI-NSF/geni-portal/issues/1734))
* Streamline and unify edit membership and edit project paths
  ([#1739](https://github.com/GENI-NSF/geni-portal/issues/1739))

## Installation Notes

* None

# [Release 3.16](https://github.com/GENI-NSF/geni-portal/milestones/3.16)

## Changes

* Handle error / string return from `restart_sliver` in
  `restartsliver.php`.
  ([#1724](https://github.com/GENI-NSF/geni-portal/issues/1724))
* Check that image name to `Create Image` is alphanumeric, and
  handle error returns from Omni on createimage.
  ([#1694](https://github.com/GENI-NSF/geni-portal/issues/1694))
* Show specific shared attributes on OpenID trust page.
  ([#1697](https://github.com/GENI-NSF/geni-portal/issues/1697))
* Fix adding global node to work on first page load
  ([#1718](https://github.com/GENI-NSF/geni-portal/issues/1718))
* Fix Jacks hang when displayed in expanded view
  ([#1720](https://github.com/GENI-NSF/geni-portal/issues/1720))
* Update Jacks context to use Ubuntu 14 as default InstaGENI Image
  ([#1729](https://github.com/GENI-NSF/geni-portal/issues/1729))

## Installation Notes

* None

# [Release 3.15](https://github.com/GENI-NSF/geni-portal/milestones/3.15)

## Changes

* Allow self service assertion of email address
  ([#1673](https://github.com/GENI-NSF/geni-portal/issues/1673))
* Provide a way to update SSH keys on existing resources
  ([#1393](https://github.com/GENI-NSF/geni-portal/issues/1393))
* Add some notes to geni-fetch-aggmon for testing
  ([#1703](https://github.com/GENI-NSF/geni-portal/issues/1703))
* Update the Jacks URL per Emulab's request
  ([#1704](https://github.com/GENI-NSF/geni-portal/issues/1704))
* Remove InstaGENI FOAM aggregates
  ([#1705](https://github.com/GENI-NSF/geni-portal/issues/1705))
* Handle large advertisements in geni-parse-map-data
  ([#1707](https://github.com/GENI-NSF/geni-portal/issues/1707))
* Remove hardcoded help email from code
  ([#1709](https://github.com/GENI-NSF/geni-portal/issues/1709))
* Add VTS aggregates to map
  ([#1714](https://github.com/GENI-NSF/geni-portal/issues/1714))

## Installation Notes

* Update KM database to add new table for email address self-service:

    ```shell
    psql -U portal -h localhost portal < /usr/share/geni-ch/km/db/postgresql/update-2.sql
    ```

# [Release 3.14](https://github.com/GENI-NSF/geni-portal/milestones/3.14)

## Changes

* Standardize python shebang declaration
  ([#1367](https://github.com/GENI-NSF/geni-portal/issues/1367))
* Delete obsolete files
  ([#1701](https://github.com/GENI-NSF/geni-portal/issues/1701))

## Installation Notes

* Delete unused files from installation:

    ```
    sudo rm /var/www/secure/map.php
    sudo rm /var/www/secure/SocketPool.swf
    sudo rm /var/www/common/map/*.rspec
    sudo rm /var/www/common/map/rspec-*.xml
    sudo rm -r /var/www/common/map/1080display
    sudo rm -r /var/www/common/map/OpenLayers-2.13
    sudo rm /var/www/common/InC_Participant.png
    sudo rm /var/www/common/README-incommon-logo.txt
    sudo rm /var/www/common/logos.html
    ```

# [Release 3.13](https://github.com/GENI-NSF/geni-portal/milestones/3.13)

## Changes

* Confirm before deleting RSpecs
  ([#1691](https://github.com/GENI-NSF/geni-portal/issues/1691))
* Handle logout when viewing RSpecs
  ([#1696](https://github.com/GENI-NSF/geni-portal/issues/1696))

## Installation Notes

* None

# [Release 3.12](https://github.com/GENI-NSF/geni-portal/milestones/3.12)

## Installation Notes

* None

## Changes

* Update project name constraints to match clearinghouse
  ([#1689](https://github.com/GENI-NSF/geni-portal/issues/1689))

# [Release 3.11](https://github.com/GENI-NSF/geni-portal/milestones/3.11)

## Installation Notes

* Add a cron job to fetch aggregate status from GENI Monitoring (adjust
 path to `geni-fetch-aggmon` as necessary):

    ```
    # Fetch aggregate data hourly from GENI Monitoring
    0 */1 * * * /usr/sbin/geni-fetch-aggmon
    ```

## Changes

* Add aggregate status tab to dashboard
  ([#1629](https://github.com/GENI-NSF/geni-portal/issues/1629))
* Support Adding an RSPEC from a File to an existing Jacks topology
  ([#1660](https://github.com/GENI-NSF/geni-portal/issues/1660))
* Remove Flack
  ([#1676](https://github.com/GENI-NSF/geni-portal/issues/1676))
* Add aggregate status from GENI Monitoring
  ([#1681](https://github.com/GENI-NSF/geni-portal/issues/1681))


# [Release 3.10](https://github.com/GENI-NSF/geni-portal/milestones/3.10)

* Show open project lead requests on profile
  ([#1424](https://github.com/GENI-NSF/geni-portal/issues/1424))
* Remove project lead checkbox from modify page for project leads
  ([#1431](https://github.com/GENI-NSF/geni-portal/issues/1431))
* Fix PHP warning on admin page
  ([#1430](https://github.com/GENI-NSF/geni-portal/issues/1430))
* Fix bug where log and map tabs wouldn't show up if table view
  was preffered view on the dashboard.
  ([#1653](https://github.com/GENI-NSF/geni-portal/issues/1653))
* Confine jFed launch to a single page
  ([#1663](https://github.com/GENI-NSF/geni-portal/issues/1663))

# [Release 3.9](https://github.com/GENI-NSF/geni-portal/milestones/3.9)

* Add salist.json for xml-signer tool
  ([#1268](https://github.com/GENI-NSF/geni-portal/issues/1268))
* Add "New Project" button to project table view.
  ([#1650](https://github.com/GENI-NSF/geni-portal/issues/1650))
* Make project table view show expired projects.
  ([#1651](https://github.com/GENI-NSF/geni-portal/issues/1651))

# [Release 3.8](https://github.com/GENI-NSF/geni-portal/milestones/3.8)

* Join this project must correctly check return indicating
  an existing request to join this project.
  ([#1637](https://github.com/GENI-NSF/geni-portal/issues/1637))
* `get_project` uses wrong variable name for result. `lookup_project_details`
  should bail when given empty list of projet UIDs.
  ([#1639](https://github.com/GENI-NSF/geni-portal/issues/1639))
* Support properly importing the `lead_request` table into a new database.
  ([#1643](https://github.com/GENI-NSF/geni-portal/issues/1643))
* Skip re-generating user certs on DB import by default.
  Use `--regen_certs` for old behavior.
  ([#1644](https://github.com/GENI-NSF/geni-portal/issues/1644))

# [Release 3.7](https://github.com/GENI-NSF/geni-portal/milestones/3.7)

* "Add Global Node" gets "URI too long" for large topology
  ([#1622](https://github.com/GENI-NSF/geni-portal/issues/1622))
* Force aggregate status page in a tab
  ([#1624](https://github.com/GENI-NSF/geni-portal/issues/1624))
* Add an ansible inventory to the details page
  ([#1630](https://github.com/GENI-NSF/geni-portal/issues/1630))

# [Release 3.6](https://github.com/GENI-NSF/geni-portal/milestones/3.6)

* Generate agg_nick_cache from SR data
  ([#1408](https://github.com/GENI-NSF/geni-portal/issues/1408))
* Check errors when loading am status data
  ([#1614](https://github.com/GENI-NSF/geni-portal/issues/1614))
* Fix a SQL syntax error in the portal schema
  ([#1615](https://github.com/GENI-NSF/geni-portal/issues/1615))
* Move iRODS certs to clearinghouse
  ([#1616](https://github.com/GENI-NSF/geni-portal/issues/1616))
* Exclude VTS aggregates from portal
  ([#1619](https://github.com/GENI-NSF/geni-portal/issues/1619))

# [Release 3.5](https://github.com/GENI-NSF/geni-portal/milestones/3.5)

* Fix a renew bug on Firefox
  ([#1590](https://github.com/GENI-NSF/geni-portal/issues/1590))
* Remove debug printouts
  ([#1598](https://github.com/GENI-NSF/geni-portal/issues/1598))
* Provide aggregate status to Jacks
  ([#1600](https://github.com/GENI-NSF/geni-portal/issues/1600))
* Add an aggregate status page
  ([#1601](https://github.com/GENI-NSF/geni-portal/issues/1601))
* Fix referer redirect when portal is not authorized
  ([#1604](https://github.com/GENI-NSF/geni-portal/issues/1604))
* Make AM status page publicly accessible
  ([#1609](https://github.com/GENI-NSF/geni-portal/issues/1609))

# [Release 3.4](https://github.com/GENI-NSF/geni-portal/milestones/3.4)

* Improve error handling for failed slice searches on admin page
  ([#1417](https://github.com/GENI-NSF/geni-portal/issues/1417))
* Show slice member usernames on slice search
  ([#1432](https://github.com/GENI-NSF/geni-portal/issues/1432))
* Add version number to localStorage
  ([#1479](https://github.com/GENI-NSF/geni-portal/issues/1479))
* Add ability to clear success/failure/error messages on pages
  ([#1565](https://github.com/GENI-NSF/geni-portal/issues/1565))
* Provide a useful message when a user is in no active projects
  ([#1567](https://github.com/GENI-NSF/geni-portal/issues/1567))
* Migrate management scripts from geni-portal to geni-ch
  ([geni-ch #101](https://github.com/GENI-NSF/geni-ch/issues/101))
* Allow users to pick default homepage tab
  ([#1579](https://github.com/GENI-NSF/geni-portal/issues/1579))

# [Release 3.3](https://github.com/GENI-NSF/geni-portal/milestones/3.3)

* Deploy GENI/ORBIT sync service (geni-sync-wireless.py) and change
  Wireless Account Management page (wimax-enable.php) accordingly
  ([#1392](https://github.com/GENI-NSF/geni-portal/issues/1392))
* Changes for Wireless management cover previous wimax tickets
  ([#772](https://github.com/GENI-NSF/geni-portal/issues/772),
   [#773](https://github.com/GENI-NSF/geni-portal/issues/773),
   [#1058](https://github.com/GENI-NSF/geni-portal/issues/1058),
   [#1076](https://github.com/GENI-NSF/geni-portal/issues/1076),
   [#1136](https://github.com/GENI-NSF/geni-portal/issues/1136))
* Store timestamps in UTC for last_seen, lead requests, and KM asserted
  attributes
  ([#1446](https://github.com/GENI-NSF/geni-portal/issues/1446))
* Update slice table view to match dashboard style
* Store username with saved filters/sorts to fix confusion on shared computers
  ([#1477](https://github.com/GENI-NSF/geni-portal/issues/1477))
* Add user preferences to the portal
  ([#1526](https://github.com/GENI-NSF/geni-portal/issues/1526))
 * Allow users to pick a default slice view
   ([#1482](https://github.com/GENI-NSF/geni-portal/issues/1482))
 * Allow users to pick between default homepage view: cards or table
   ([#1498](https://github.com/GENI-NSF/geni-portal/issues/1498))
* Cleanup dashboard code
  ([#1506](https://github.com/GENI-NSF/geni-portal/issues/1506))
* Fix width of footer to match main cards
  ([#1555](https://github.com/GENI-NSF/geni-portal/issues/1555))
* Add secondary sort for slices
  ([#1560](https://github.com/GENI-NSF/geni-portal/issues/1560))

# [Release 3.2](https://github.com/GENI-NSF/geni-portal/milestones/3.2)

* Move SR certs from geni-portal to geni-ch
  ([geni-ch #102](https://github.com/GENI-NSF/geni-ch/issues/102))
* Fix bug where login screen gets embedded in logs card on session timeout
  ([#1460](https://github.com/GENI-NSF/geni-portal/issues/1460))
* Allow for sorting of slices by project name on dashboard
  ([#1497](https://github.com/GENI-NSF/geni-portal/issues/1497))
* Update slice page to use new tab/cards style
  ([#1509](https://github.com/GENI-NSF/geni-portal/issues/1509))
* Make "has n slices" a link to the slice card showing those slices
  ([#1511](https://github.com/GENI-NSF/geni-portal/issues/1511))
* Make project names on slice cards a link to that project
  ([#1512](https://github.com/GENI-NSF/geni-portal/issues/1512))
* Update profile page to use new tab/cards style
  ([#1513](https://github.com/GENI-NSF/geni-portal/issues/1513))
* Update project page to use new tab/cards style
  ([#1514](https://github.com/GENI-NSF/geni-portal/issues/1514))
* Move alerts above main content, below header
  ([#1516](https://github.com/GENI-NSF/geni-portal/issues/1516))
* Fix formatting of the aggregate view on the slice page
  ([#1518](https://github.com/GENI-NSF/geni-portal/issues/1518))
* Show links to profile pages in header even when "$load_user" false
  ([#1519](https://github.com/GENI-NSF/geni-portal/issues/1519))
* Add link to projects tab from new slice page when not in any projects
  ([#1524](https://github.com/GENI-NSF/geni-portal/issues/1524))
* Fix php warnings about undefined indices on admin page
  ([#1535](https://github.com/GENI-NSF/geni-portal/issues/1535))
* Remove references to map initialization code in cards.js
  ([#1544](https://github.com/GENI-NSF/geni-portal/issues/1544))
* Allow for switching between arbitrary cards in cards.js
  ([#1545](https://github.com/GENI-NSF/geni-portal/issues/1545))
* Fixed broken renew button on the (new) slice page
  ([#1549](https://github.com/GENI-NSF/geni-portal/issues/1549))
* Fix issue where the "last_seen" table was not being populated
  ([#1550](https://github.com/GENI-NSF/geni-portal/issues/1550))
* Clean up code in header.php, remove references to old header tabs
  ([#1553](https://github.com/GENI-NSF/geni-portal/issues/1553))
* Fix bug where login screen gets embedded in slices and projects logs cards
  on session timeout
  ([#1557](https://github.com/GENI-NSF/geni-portal/issues/1557))

# [Release 3.1.1](https://github.com/GENI-NSF/geni-portal/milestones/3.1.1)
* Remove obsolete files (from Makefiles too)


# [Release 3.1](https://github.com/GENI-NSF/geni-portal/milestones/3.1)

* Migrate schema files for CH tables to geni-ch
  ([geni-ch #103](https://github.com/GENI-NSF/geni-ch/issues/103))
* Add link to "Other Tools" wiki page listing Omni, VTS, geni-lib, etc.
  ([#1457](https://github.com/GENI-NSF/geni-portal/issues/1457),
   [#1491](https://github.com/GENI-NSF/geni-portal/issues/1491))
* Fix CSS on Jacks expanded views
  ([#1458](https://github.com/GENI-NSF/geni-portal/issues/1458))
* Fix erroneous warning when renewing slices on the dashboard
  ([#1481](https://github.com/GENI-NSF/geni-portal/issues/1481))
* Handle text overflow for project names and lead names on dashboard
  ([#1484](https://github.com/GENI-NSF/geni-portal/issues/1484))
* Replace OpenLayers maps on the portal with Google maps
  ([#1428](https://github.com/GENI-NSF/geni-portal/issues/1428))
 * Includes dashboard "Map" tab, the small welcome page map, and both slice
   geo views ("Geographic View" and "Geo Map")
 * This fixes the half loaded map in the "Geographic View" slice tab
   ([#1272](https://github.com/GENI-NSF/geni-portal/issues/1272))
* Add "Contact Us" page to the portal, and add link to it in help dropdown
  ([#1486](https://github.com/GENI-NSF/geni-portal/issues/1486))
* Fix formatting on the SSH tab of the profile page to be clearer
  ([#1485](https://github.com/GENI-NSF/geni-portal/issues/1485))
* Update welcome page to use new portal CSS styles
  ([#1501](https://github.com/GENI-NSF/geni-portal/issues/1501))
* Make projects.php redirect to projects tab on the dashbaord
  ([#1487](https://github.com/GENI-NSF/geni-portal/issues/1487))
* Add Google Analytics (via adding header) to jacks expanded views
  ([#1488](https://github.com/GENI-NSF/geni-portal/issues/1488))
* Fix bug where hamburger menu would expand on every page resize
  ([#1500](https://github.com/GENI-NSF/geni-portal/issues/1500))
* Add google analytics to flack page
  ([#1504](https://github.com/GENI-NSF/geni-portal/issues/1504))
* Add link to GENI Bibliography
  ([#1510](https://github.com/GENI-NSF/geni-portal/issues/1510))

# [Release 3.0](https://github.com/GENI-NSF/geni-portal/milestones/3.0)

* Add create image function for PG/IG racks within jacks-app
  ([#1389](https://github.com/GENI-NSF/geni-portal/issues/1389))
* Remove account and identity (and associated) tables, scripts and clients
  ([#299](https://github.com/GENI-NSF/geni-portal/issues/299))
* Restore 'Manage Resources' functionality
  ([#1448](https://github.com/GENI-NSF/geni-portal/issues/1448))
* Update Portal with new look and feel. Some key features:
 * The home page has been re-designed using tabs, and 'cards' for each
   project or slice
   ([#1146](https://github.com/GENI-NSF/geni-portal/issues/1146))
 * To act on a slice or project, click '...'
 * 'Projects' and 'Slices' pages are tabs on the home page, reachable from
   the header menus under 'Home'
 * Links from the 'Tools' section of the old home page are now largely under
   'Partners' or 'Tools' in the header, available on every page.
 * To launch a tool in a slice context (jFed, GENI Desktop, LabWiki), use
   the button on the slice page.
 * You can now renew slice resources directly from the home page
   ([#711](https://github.com/GENI-NSF/geni-portal/issues/711))
 * A Slice card indicates (using colors and icons) if the slice or
   resources expire soon
   ([#613](https://github.com/GENI-NSF/geni-portal/issues/613))
 * Flack has been removed
   ([#1451](https://github.com/GENI-NSF/geni-portal/issues/1451))
 * Added a link to the page to request a SAVI account
   ([#1455](https://github.com/GENI-NSF/geni-portal/issues/1455))
 * The GENI Wireless button is now under 'Manage Accounts' on the profile,
   with direct links to wireless testbeds under 'Partners'
   ([#1262](https://github.com/GENI-NSF/geni-portal/issues/1262))
* Avoid always storing profile changes as lead requests
  ([#1463](https://github.com/GENI-NSF/geni-portal/issues/1463))
* Avoid listing all profile edits as having changed the telephone number
  ([#1339](https://github.com/GENI-NSF/geni-portal/issues/1339))
* Fix undefined variable in log_db_error
  ([#1467](https://github.com/GENI-NSF/geni-portal/issues/1467))
* Fix infinite redirect on wimax-enable with no projects
  ([#1464](https://github.com/GENI-NSF/geni-portal/issues/1464))
* Fix issue where length of renewal wrong in slice actions dropdown
  ([#1471](https://github.com/GENI-NSF/geni-portal/issues/1471))
* Fix issue where renew button in slice dropdown would try and shorten slice
  lifetime
  ([#1473](https://github.com/GENI-NSF/geni-portal/issues/1473))
* Fix issue where create slice button was disabled when project_id was given
  in URL params
  ([#1478](https://github.com/GENI-NSF/geni-portal/issues/1478))
