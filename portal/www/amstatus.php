<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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

require_once("user.php");
require_once("header.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("aggstatus.php");

show_html_head('GENI Aggregate Status');
?>

<div id="content-outer" class="one-card">
<div id="content">
<h1>GENI Aggregate Status</h1>
  <section>
    The following is a summary up/down status for each aggregate as reported by
    <a href="https://genimon.uky.edu/login" target="_blank">
        GENI Monitoring</a>.
    For detailed information go to the
    <a href="https://genimon.uky.edu/status" target="_blank">
        GENI monitoring aggregate status page</a> (no login required).
  </section>
  <section>
    <h3>Legend</h3>
    <?php print agg_status_legend(); ?>
  </section>
  <section>
    <h3>Aggregate Status</h3>
    <div class='tablecontainer'>
      <?php print agg_status_table(); ?>
    </div>
  </section>

<script type="text/javascript">
$(document).ready(function () {
    $('#aggtable').DataTable({paging: false});
  });
</script>
</div> <!-- content -->
</div> <!-- content-outer -->
<?php
include("footer.php");
?>
