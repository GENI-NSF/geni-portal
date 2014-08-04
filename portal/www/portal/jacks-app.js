var jacksRoot;
var jacksStatus;
var jacksButtons;
var jacksInstance;

// These variables will be used to communicate with Jacks
var jacksInput;
var jacksOutput;

// Initializes the framework for the Jacks App.
function start_jacks_viewer(target, status, buttons) {
  // Save the id for each elements of the Jacks App to update/hook later.
  // Did it this way for a more little freedom into where it goes.
	jacksRoot = target;
  jacksStatus = status;
  jacksButtons = buttons;

  // Initialize the Jacks Viewer instance.
  jacksInstance = new window.Jacks({
                  mode: 'viewer',
                  source: 'rspec',
                  size: { x: 791, y: 350},
                  show: {
                    menu: false,
                    rspec: false,
                    version: false
                  },
                  nodeSelect: false,
                  root: jacksRoot,
                  readyCallback: function (input, output) {
                    jacksInput = input;
                    jacksOutput = output;
                    /*jacksOutput.on('click-event', function (event) { 
                      $('#jacksApp'+ji+' #active').attr('id','');
                      $('#jacksApp'+ji+' #'+event['type']+'-'+event['client_id']).parent().attr('id','active');
                      $('#jacksApp'+ji+' .expandedI').each(function() { $(this).removeClass('expandedI') });
                      $('#jacksApp'+ji+' #list-'+event['client_id']).parent().addClass('expandedI');
                    });*/
                    input.trigger('change-topology',
                                  [{ rspec: '<rspec></rspec>' }]);
                  }
                });
}

// Takes in a list of AMs and makes AJAX calls to get the manifests.
function jacks_get_manifest(amsToPoll, sliceId) {
  $.each(amsToPoll, function(i, v) {
    jacks_update_status('Gathering manifest from '+v+'...');
    $.get("jacks-app-details.php", { am_id:v, slice_id:sliceId }, function(responseTxt,statusTxt,xhr) {
      var rspecManifect = responseTxt;

// NEEDS TO BE CHANGED
      // change-topology removes the current topology.
      // The trigger will need to be updated once an event is implemented
      // that adds the manifest to the current topology.      
      jacksInput.trigger('change-topology', [{ rspec: rspec}]);
    });
  })
}

// Takes in a list of AMs and makes AJAX calls to get the status of
// resources at the specified AMs.
function jacks_poll_status(amsToPoll, sliceId) {
  $.each(amsToPoll, function(i, v) {
    jacks_update_status('Polling resource status from '+v+'...');

  })
}

function jacks_update_status(statusText) {
  $(jacksStatus).prepend('<p class="jacksStatusText">'+statusText+'</p>');
}