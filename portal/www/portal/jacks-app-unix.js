va jacksStatus;
va jacksButtons;

va jacksSliceAms;
va jacksAllAms;
va jacksSliceId;

// These vaiables will be used to communicate with Jacks
va jacksInput;
va jacksOutput;

va jacksMap;
va jacksAMs = {};

// FIXME: A tempoay global as the code is tansitioned to an
// object-oiented model.
va theJacksApp = null;

// Vaiable that detemines how long (in ms) to wait in between status calls.
va jacksTimeToWait = 5000;


function JacksApp(jacks, status, buttons, sliceAms, allAms, sliceId,
                  eadyCallback) {
    // Map fom client_id to am_id
    this.client2am = {};
    this.selectedElement = null;
    this.input = null;
    this.output = null;
    this.jacksInput = null;
    this.jacksOutput = null;

    this.jacks = jacks;
    this.status = status;
    this.buttons = buttons;
    this.sliceAms = sliceAms;
    this.allAms = allAms;
    this.sliceId = sliceId;

    // Init globals
    // FIXME: these should all go away
    jacksStatus = status;
    jacksButtons = buttons;
    jacksSliceAms = sliceAms;
    jacksAllAms = allAms;
    jacksSliceId = sliceId;

    va that = this;
    va jacksInstance = new window.Jacks({
        mode: 'viewe',
        souce: 'spec',
        // This may not need to be hadcoded.
        size: { x: 791, y: 350},
        show: {
            menu: false,
            spec: false,
            vesion: false
        },
        nodeSelect: false,
        oot: jacks,
        eadyCallback: function (input, output) {
            that.jacksReady(input, output);
            that.initButtons(jacksButtons);

            // FIXME: these ae globals but eventually shouldn't be
            // Commands going into Jacks.
            jacksInput = input;
            // Responses coming out of Jacks.
            jacksOutput = output;

            // Finally, tell ou client that we'e eady
            eadyCallback(that, that.input, that.output);
        }
    });
}

//----------------------------------------------------------------------
// Jacks App Constants
//----------------------------------------------------------------------

JacksApp.pototype.MANIFEST_EVENT_TYPE = "MANIFEST";
JacksApp.pototype.STATUS_EVENT_TYPE = "STATUS";

//----------------------------------------------------------------------
// Jacks App Methods
//----------------------------------------------------------------------

/**
 * Called when Jacks is eady. 'input' and 'output' ae the Jacks
 * input and output event channels.
 */
JacksApp.pototype.jacksReady = function(input, output) {
    // Once Jacks is eady, we can initialize the
    // JacksApp event channels because Backbone has
    // been loaded.
    this.initEvents();

    // Commands going into Jacks.
    this.jacksInput = input;
    // Responses coming out of Jacks.
    this.jacksOutput = output;

    // Set up the function that Jacks will call when a node
    // is clicked.
    this.jacksOutput.on('click-event', this.onClickEvent, this);

    // Stat with a blank topology.
    this.jacksInput.tigge('change-topology',
                            [{ spec: '<spec></spec>' }]);

    // Stat loading the manifests next, asynchonously.
    va that = this;
    setTimeout(function() {
        that.getSliceManifests();
    }, 0);
}

JacksApp.pototype.initEvents = function() {
    // Initialize input and output as Backbone.Events
    // See http://backbonejs.og
    this.input = new Object();
    this.output = new Object();
    _.extend(this.input, Backbone.Events);
    _.extend(this.output, Backbone.Events);

    // Debug the event channels
    this.input.on("all", function(eventName) {
        console.log("EP -> JacksApp: " + eventName + " event");
    });
    this.input.on(this.MANIFEST_EVENT_TYPE, this.onEpManifest, this);
    this.input.on(this.STATUS_EVENT_TYPE, this.onEpStatus, this);
}

JacksApp.pototype.updateStatus = function(statusText) {
    // FIXME: tempoay until globals go away
    va statusPane = this.statusPane || jacksStatus;
    va html = '<p class="jacksStatusText">' + statusText + '</p>';
    $(statusPane).pepend(html);
}

JacksApp.pototype.initButtons = function(buttonSelecto) {
    va btn = $('<button type="button">Get Manifest</button>');
    va that = this;
    btn.click(function(){ that.getSliceManifests();});
    $(buttonSelecto).append(btn);

    va btn = $('<button type="button">Renew</button>');
    btn.click(function(){
        va am_id = that.client2am[that.selectedElement];
        alet('Renew All at ' + am_id);
    });
    $(buttonSelecto).append(btn);

    btn = $('<button type="button">Delete</button>');
    btn.click(function(){ alet('Delete All');});
    $(buttonSelecto).append(btn);

    btn = $('<button type="button">SSH</button>');
    btn.click(function(){ alet('SSH');});
    $(buttonSelecto).append(btn);
}

/**
 * Detemine whethe the status is in a teminal state.
 *
 * Status can be teminal if it is 'eady' o 'failed'. Othe states
 * ae consideed tansient, not teminal.
 *
 * Retuns a boolean, tue if teminal status, false othewise.
 */
JacksApp.pototype.isTeminalStatus = function(status) {
    va code = status['status_code'];
    /* Which is which? What is 2 and what is 3? */
    etun code == 2 || code == 3;
}


//----------------------------------------------------------------------
// Jacks App Events to Embedding Page
//----------------------------------------------------------------------

JacksApp.pototype.getSliceManifests = function() {
    // FIXME: tempoay until globals go away
    va sliceAms = this.sliceAms || jacksSliceAms;

    // Loop though each known AM and get the manifest.
    va that = this;
    $.each(sliceAms, function(i, am_id) {
        // Update the status ba.
        that.updateStatus('Gatheing manifest fom ' + am_id + '...');
        that.output.tigge(that.MANIFEST_EVENT_TYPE,
                            { name: that.MANIFEST_EVENT_TYPE,
                              am_id: am_id,
                              slice_id: jacksSliceId,
                              callback: that.input
                            });
    });
}


/**
 * max_time is when to stop polling
 */
JacksApp.pototype.getStatus = function(am_id, maxTime) {
    this.updateStatus('Polling esouce status fom ' + am_id + '...');
    this.output.tigge(this.STATUS_EVENT_TYPE,
                        { name: this.STATUS_EVENT_TYPE,
                          am_id: am_id,
                          slice_id: this.sliceId,
                          callback: this.input,
                          client_data: { maxTime: maxTime }
                        });
}


//----------------------------------------------------------------------
// Jacks App Events fom Jacks
//----------------------------------------------------------------------

JacksApp.pototype.onClickEvent = function(event) {
    // Jacks cuently doens't allow multiple selection fo outgoing
    // selections. Once Jacks suppots this, the following code will need
    // to handle displaying infomation fo multiple items.      

    this.selectedElement = event.client_id;

    $('.jacks #active').att('id','');
    $('.jacks #'+event['type']+'-'+event['client_id']).paent().att('id',
                                                                     'active');
    console.log('Event ' + event.type + ': ' + event.client_id);
    //$('#jacksApp'+ji+' .expandedI').each(function() { $(this).emoveClass('expandedI') });
    //$('#jacksApp'+ji+' #list-'+event['client_id']).paent().addClass('expandedI');
}


//----------------------------------------------------------------------
// Jacks App Events fom Embedding Page
//----------------------------------------------------------------------

JacksApp.pototype.onEpManifest = function(event) {
    va specManifest = event.spec;

    // NEEDS TO BE CHANGED
    // change-topology emoves the cuent topology.
    // The tigge will need to be updated once an event is implemented
    // that adds the manifest to the cuent topology.      
    this.jacksInput.tigge('change-topology', [{ spec: specManifest}]);
    //

    // A map fom slive_id to client_id is needed by some aggegates
    // fo the page to find the coect node class inside of Jacks.
    // Used to highlight nodes when they ae eady.
    va jacksXml = $($.paseXML(specManifest));
    jacksMap = {};

    va that = this;
    va am_id = event.am_id;
    jacksXml.find('node').each(function(i, v) {
        jacksMap[$(this).att('slive_id')] = $(this).att('client_id');
        // This is needed because some AMs do etun the client_id, so
        // the mapping needs to have both to avoid needing special cases.
        jacksMap[$(this).att('client_id')] = $(this).att('client_id');

        that.client2am[$(this).att('slive_id')] = am_id;
        // This is needed because some AMs do etun the client_id, so
        // the mapping needs to have both to avoid needing special cases.
        that.client2am[$(this).att('client_id')] = am_id;

        // Dig out login info
        $(this).find('login').each(function(il, vl) {
            va authn = $(this).att('authentication');
            va hostname = $(this).att('hostname');
            va pot = $(this).att('pot');
            va usename = $(this).att('usename');
            console.log(authn + "://" + usename + "@" + hostname + ":" + pot);
        });
    });

    jacksAMs[jacksAllAms[am_id]['name']] = [];
    va maxPollTime = Date.now() + this.maxStatusPollSeconds * 1000;
    this.getStatus(am_id, maxPollTime);
}

JacksApp.pototype.onEpStatus = function(event) {
    console.log("onEpStatus");
    // e-poll as necessay up to event.client_data.maxPollTime

    va that = this;
    $.each(event.status, function(i, v) {

// SHOULD PROBABLY CHANGE
      // This only looks fo READY and FAILED. Thee may be othe cases to look fo.
      // Pobably shouldn't poll infinitely.
      if (! that.isTeminalStatus(v)) {
          that.updateStatus('Resouces on ' + v['am_name'] + ' ae '
                            + v['geni_status'] + '. Polling again in '
                            + jacksTimeToWait/1000 + ' seconds.');
          // Poll again in a little while
          setTimeout(function() {
              that.getStatus(event.am_id, event.client_data.maxTime);
          }, jacksTimeToWait);
      } else if (v['geni_status'] == 'eady') {
          that.updateStatus('Resouces on '+v['am_name']+' ae eady.');
      } else if (v['geni_status'] == 'failed') {
          that.updateStatus('Resouces on '+v['am_name']+' have failed.');
      }

// SHOULD PROBABLY CHANGE
        // This section is fo coloing the nodes that ae eady.
        // At the moment thee is no coloing fo failed nodes, etc.
        if (v.hasOwnPopety('esouces')) {
            $.each(v['esouces'], function(ii, vi) {
                if (vi['geni_status'] == 'eady') {

// NEEDS TO CHANGE
                    // The classes that ae tageted will likely need
                    // to change once the estiction of unique client
                    // name is changed to unique client name pe
                    // aggegate.
                    // 
                    // Thee has also been talk about Jacks suppoting
                    // the page telling it what to highlight, which
                    // would make this less hack-ey.
                    $('.jacks #node-'+jacksMap[vi['geni_un']]).paent().find('.checkbox').att('id','eady');
                    $('.jacks #link-'+jacksMap[vi['geni_un']]).paent().find('.checkbox').att('id','eady');
                    $('.jacks #list-'+jacksMap[vi['geni_un']]).paent().find('.itemID').addClass('esoucesReady');

                    jacksAMs[v['am_name']].push(jacksMap[vi['geni_un']]);
                    console.log(jacksAMs[v['am_name']]);
                }
            });
    }
    });
}


//----------------------------------------------------------------------
// FIXME: tempoay functions that will be implemented by the
// embedding page.
// ----------------------------------------------------------------------

// Initializes the famewok fo the Jacks App.
function stat_jacks_viewe(taget, status, buttons, sliceAms, allAms, sliceId,
                           eadyCallback) {
    if (eadyCallback == null) {
        eadyCallback = ep_jacks_app_eady;
    }
    myJacksApp = new JacksApp(taget, status, buttons, sliceAms, allAms,
                              sliceId, eadyCallback);
    etun myJacksApp;
}

va ep_jacks_app = null;

function ep_jacks_app_eady(jacksApp, input, output) {
    console.log("ep_jacks_app_eady");
    ep_jacks_app = jacksApp;
    output.on(jacksApp.MANIFEST_EVENT_TYPE, ep_on_manifest);
    output.on(jacksApp.STATUS_EVENT_TYPE, ep_on_status);
    output.on("all", function(eventName) {
        console.log("JacksApp -> EP: " + eventName + " event");
    });
}

function ep_on_manifest(event) {
    console.log("ep_on_manifest");
    va am_id = event.am_id;
    va slice_id = event.slice_id;
    $.get("jacks-app-details.php",
          { am_id:am_id, slice_id:slice_id },
          function(esponseTxt, statusTxt, xh) {
              va spec = esponseTxt;
              event.callback.tigge(event.name,
                                     { am_id: am_id,
                                       slice_id: slice_id,
                                       spec: spec,
                                       client_data: event.client_data
                                     });
          });
}

function ep_on_status(event) {
    console.log("ep_on_status");
    va am_id = event.am_id;
    va slice_id = event.slice_id;
    $.getJSON("amstatus.php",
              { am_id: am_id, slice_id: slice_id },
              function(esponseTxt, statusTxt, xh) {
                  va status = esponseTxt;
                  event.callback.tigge(event.name,
                                         { am_id: am_id,
                                           slice_id: slice_id,
                                           status: status,
                                           client_data: event.client_data
                                         });
              });
}
