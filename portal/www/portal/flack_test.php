<?php
require_once('flack.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('sa_constants.php');
require_once('sa_client.php');
require_once('user.php');

$sr_url = get_sr_url();
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$user = geni_loadUser();
$slices = lookup_slices($sa_url, $user, null, $user->account_id);
$slice = $slices[0];
$slice_urn = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_URN];

error_log("SLICE_URN = " . $slice_urn);

$original = $_GET["orig"] == "true";
error_log("ORIGINAL = " . $original);

$contents = generate_flack_page($slice_urn, $original);
print $contents;
?>
