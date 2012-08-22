<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

require_once('util.php');
require_once('ma_constants.php');
require_once('ma_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('user.php');
require_once('portal.php');

error_log("MA TEST\n");

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

$user = geni_loadUser();


$member_ids = get_member_ids($ma_url, Portal::getInstance());

$member1 = $member_ids[0];
register_ssh_key($ma_url, $user, $member1, 'FILE', 'DESC', 'KEY');

foreach($member_ids as $member_id) {
  error_log("Member_ID " . $member_id);
  $ssh_keys_for_member = lookup_ssh_keys($ma_url, $user, $member_id);
  foreach($ssh_keys_for_member as $ssh_key_for_member) {
    //    error_log("   KEY : " . print_r($ssh_key_for_member, true));
    $filename = $ssh_key_for_member[MA_SSH_KEY_TABLE_FIELDNAME::FILENAME];
    $key = $ssh_key_for_member[MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY];
    error_log("   " . $filename . " " . $key);
  }
}

relative_redirect('debug');

?>

