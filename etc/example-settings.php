<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

/*
 * The location of the database (DSN = "data source name").  This one
 * is user "scott" with password "tiger" connecting to database "portal"
 * on host "localhost".
 *
 * See http://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php
 */
$db_dsn = 'pgsql://scott:tiger@localhost/portal';

/*
 * Where to send administrative email.
 */
$portal_admin_email = 'portal-admin@example.com';

/*
 * Bootstrap the service registry. All other services are discovered
 * via the service registry.
 */
$service_registry_url = 'https://ch.example.com:8444/SR';

/*
 * User/PW for an admin account on the iRODS Test server for use by
 * the portal in creating iRODS accounts
 */
$portal_irods_user = 'rods';
$portal_irods_pw = 'rods';

/*
 * Enable google analytics when set to true.
 */
$portal_analytics_enable = true;

/*
 * The google analytics tracking ID.
 */
$portal_analytics_string = "ga('create', 'UA-000000-01', 'example.com');";

/*
 * Base URL for xml-signer tool genilib JavaScript code.
 */
$genilib_trusted_host = 'https://ch.example.com:8444'

?>
