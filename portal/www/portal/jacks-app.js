var jacksRoot;
var jacksStatus;
var jacksButtons;
var jacksInstance;

var jacksSliceAms;
var jacksAllAms;
var jacksSliceId;

// These variables will be used to communicate with Jacks
var jacksInput;
var jacksOutput;

var jacksMap;
var jacksAMs = {};

// Initializes the framework for the Jacks App.
function start_jacks_viewer(target, status, buttons, sliceAms, allAms, sliceId) {
  // Save the id for each elements of the Jacks App to update/hook later.
  // Did it this way for a more little freedom into where it goes.
	jacksRoot = target;
  jacksStatus = status;
  jacksButtons = buttons;

  jacksSliceAms = sliceAms;
  jacksAllAms = allAms;
  jacksSliceId = sliceId;

  // Initialize the Jacks Viewer instance.
  jacksInstance = new window.Jacks({
                  mode: 'viewer',
                  source: 'rspec',
                  size: { x: 791, y: 350}, // This may not need to be hardcoded.
                  show: {
                    menu: false,
                    rspec: false,
                    version: false
                  },
                  nodeSelect: false,
                  root: jacksRoot,
                  readyCallback: function (input, output) {
                    // Commands going into Jacks.
                    jacksInput = input;
                    // Responses coming out of Jacks.
                    jacksOutput = output;

                    // Set up the function that Jacks will call when a node
                    // is clicked.
                    jacksOutput.on('click-event', function (event) { 
// NEEDS TO CHANGE
                    // Jacks currently doens't allow multiple selection for outgoing
                    // selections. Once Jacks supports this, the following code will need
                    // to handle displaying information for multiple items.      
                      $('.jacks #active').attr('id','');
                      $('.jacks #'+event['type']+'-'+event['client_id']).parent().attr('id','active');
                      //$('#jacksApp'+ji+' .expandedI').each(function() { $(this).removeClass('expandedI') });
                      //$('#jacksApp'+ji+' #list-'+event['client_id']).parent().addClass('expandedI');
                    });

                    // Start with a blank topology.
                    input.trigger('change-topology',
                                  [{ rspec: '<rspec></rspec>' }]);
                  }
                });

  setTimeout(function() {
    jacks_get_manifest();
  }, 5000);
}

// Takes in a list of AMs and makes AJAX calls to get the manifests.
function jacks_get_manifest() {
  // Loop through each known AM and get the manifest.
  $.each(jacksSliceAms, function(i, v) {
    // Update the status bar.
    jacks_update_status('Gathering manifest from '+v+'...');
    // Ajax call for the manifest.
    $.get("jacks-app-details.php", { am_id:v, slice_id:jacksSliceId }, function(responseTxt,statusTxt,xhr) {
      var rspecManifest = responseTxt;

// NEEDS TO BE CHANGED
      // change-topology removes the current topology.
      // The trigger will need to be updated once an event is implemented
      // that adds the manifest to the current topology.      
      jacksInput.trigger('change-topology', [{ rspec: rspecManifest}]);
//

      // A map from sliver_id to client_id is needed by some aggregates
      // for the page to find the correct node class inside of Jacks.
      // Used to highlight nodes when they are ready.
      var jacksXml = $($.parseXML(rspecManifest));
      jacksMap = {};

      jacksXml.find('node').each(function(i, v) {
        jacksMap[$(this).attr('sliver_id')] = $(this).attr('client_id');
        // This is needed because some AMs do return the client_id, so
        // the mapping needs to have both to avoid needing special cases.
        jacksMap[$(this).attr('client_id')] = $(this).attr('client_id');
      });

      jacksAMs[jacksAllAms[v]['name']] = [];
      jacks_poll_status(v);
    });
  })
}

// Takes in a list of AMs and makes AJAX calls to get the status of
// resources at the specified AMs.
function jacks_poll_status(amToPoll) {
  // Variable that determines how long (in ms) to wait in between status calls.
  var jacksTimeToWait = 5000;
  // Update the status bar.
  jacks_update_status('Polling resource status from '+amToPoll+'...');
  // Ajax call for the status.
  $.getJSON("amstatus.php", { am_id:amToPoll, slice_id:jacksSliceId },function(responseTxt,statusTxt,xhr){
    $.each(responseTxt, function(i, v) {

// SHOULD PROBABLY CHANGE
      // This only looks for READY and FAILED. There may be other cases to look for.
      // Probably shouldn't poll infinitely.
      if (v['status_code'] != 2 && v['status_code'] != 3) {
        jacks_update_status('Resources on '+v['am_name']+' are '+v['geni_status']+'. Polling again in '+jacksTimeToWait/1000+' seconds.');
        // Begin waiting for the next call.
        setTimeout(function() {
          jacks_poll_status(amToPoll);
        }, jacksTimeToWait);
      }
      else if (v['geni_status'] == 'ready') {
        jacks_update_status('Resources on '+v['am_name']+' are ready.');
      }
      else if (v['geni_status'] == 'failed') {
        jacks_update_status('Resources on '+v['am_name']+' have failed.');
      }

// SHOULD PROBABLY CHANGE
      // This section is for coloring the nodes that are ready.
      // At the moment there is no coloring for failed nodes, etc.
      if (v.hasOwnProperty('resources')) {
      $.each(v['resources'], function(ii, vi) {
        if (vi['geni_status'] == 'ready') {

// NEEDS TO CHANGE
          // The classes that are targeted will likely need to change once
          // the restriction of unique client name is changed to unique
          // client name per aggregate.
          // 
          // There has also been talk about Jacks supporting the page telling it
          // what to highlight, which would make this less hack-ey.
          $('.jacks #node-'+jacksMap[vi['geni_urn']]).parent().find('.checkbox').attr('id','ready');
          $('.jacks #link-'+jacksMap[vi['geni_urn']]).parent().find('.checkbox').attr('id','ready');
          $('.jacks #list-'+jacksMap[vi['geni_urn']]).parent().find('.itemID').addClass('resourcesReady');

          jacksAMs[v['am_name']].push(jacksMap[vi['geni_urn']]);
          console.log(jacksAMs[v['am_name']]);
        }
      });
    }
    });
  });
}

function jacks_update_status(statusText) {
  $(jacksStatus).prepend('<p class="jacksStatusText">'+statusText+'</p>');
}