/*
#
# Copyright (c) 2014 University of Utah and the Flux Group.
# 
# {{{GENIPUBLIC-LICENSE
# 
# GENI Public License
# 
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and/or hardware specification (the "Work") to
# deal in the Work without restriction, including without limitation the
# rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Work, and to permit persons to whom the Work
# is furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Work.
# 
# THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
# OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
# HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
# WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
# IN THE WORK.
# }}}
#
*/
 
function JacksEditorApp(jacks, status, buttons, sliceAms, allAms, 
			allRspecs,
			sliceInfo, userInfo, enableButtons,
			canvasOptions, constraints,
			readyCallback, fetchTopologyCallback,
			modifiedTopologyCallback) {

    // Map from client_id to am_id
    this.client2am = {};
    // Map from URN (and client_id) to client_id
    this.urn2clientId = {};
    this.selectedElement = null;
    this.input = null;
    this.output = null;
    // Commands going into Jacks.
    this.jacksInput = null;
    // Responses coming out of Jacks.
    this.jacksOutput = null;

    this.jacks = jacks;
    this.jacks_viewer = null;
    this.jacks_viewer_visible = false;

    this.status = status;
    this.buttons = buttons;
    this.sliceAms = sliceAms;
    this.allAms = allAms;

    // Turn {am_id => {name, url}} dictionary into list,
    this.sortedAms = [];
    for(var am_id in allAms) {
	var entry = {
	    am_id : am_id, 
	    name: allAms[am_id]['name'],
	    url : allAms[am_id]['url'],
	    id : allAms[am_id]['urn']
	};
	this.sortedAms.push(entry);
    }

    // sort list
    this.sortedAms.sort(function(am1, am2) {
	    if (am1.name < am2.name)
		return -1;
	    else
		return 1;
	});

    this.allRspecs = allRspecs;

    this.verbose = false; // Print debug messages to console.log

    this.sliceInfo = sliceInfo;
    this.sliceId = sliceInfo.slice_id;
    this.sliceUrn = sliceInfo.slice_urn;
    this.sliceExpiration = sliceInfo.slice_expiration;
    this.sliceName = sliceInfo.slice_name;

    this.downloadingRspec = false;

    this.loginInfo = {};

    this.userInfo = userInfo;
    this.username = userInfo.user_name;

    if (canvasOptions == null)
	canvasOptions = getDefaultCanvasOptions();
    if (constraints == null)
	constraints = getDefaultConstraints();


    canvasOptions.aggregates = this.sortedAms;

    var that = this;
    var jacksInstance = new window.Jacks({
	    mode: 'editor',
	    source: 'rspec',
	    // This may not need to be hardcoded.
	    // size: { x: 791, y: 350},
	    // size: { x: 1400, y: 350},
	    size: 'auto',
	    canvasOptions: canvasOptions,
	    constraints : constraints,
	    nodeSelect: true,
	    multiSite: true,
	    root: jacks,
	    readyCallback: function (input, output) {
		output.on('fetch-topology', fetchTopologyCallback);
		output.on('modified-topology', modifiedTopologyCallback);
		that.jacksReady(input, output);
		if (that.enableButtons) {
		    that.initButtons(that.buttons);
		}
		// Finally, tell our client that we're ready
		readyCallback(that, that.input, that.output);
	    }
	});

}

//----------------------------------------------------------------------
// Jacks App Constants
//----------------------------------------------------------------------

JacksEditorApp.prototype.DOWNLOAD_EVENT_TYPE = "DOWNLOAD";
JacksEditorApp.prototype.LOAD_EVENT_TYPE = "LOAD";
JacksEditorApp.prototype.LOOKUP_EVENT_TYPE = "LOOKUP";
JacksEditorApp.prototype.MANIFEST_EVENT_TYPE = "MANIFEST";
JacksEditorApp.prototype.PASTE_EVENT_TYPE = "PASTE";
JacksEditorApp.prototype.RESERVE_EVENT_TYPE = "RESERVE";
JacksEditorApp.prototype.SELECT_EVENT_TYPE = "SELECT";
JacksEditorApp.prototype.UPLOAD_EVENT_TYPE = "UPLOAD";

//----------------------------------------------------------------------
// Jacks App Methods
//----------------------------------------------------------------------

/**
 * Print to console.log if verbose is set
 */
JacksEditorApp.prototype.debug = function(msg) {
    if(this.verbose)
	console.log(msg);
}

function getDefaultCanvasOptions()
{
    var defaults = [ 
        {
	    name: 'VM',
	    type: 'default-vm'
	},
        {
	    name: 'Xen VM',
	    type: 'emulab-xen',
	    image: 'urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD'
	},
        {
	    name: 'Raw PC',
	    type: 'emulab-rawpc',
	    image: 'urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD'
        },
        {
	    name: 'Small Exogeni',
	    type: 'm1.small'
	},
        {
	    name: 'Open VSwitch',
	    type: 'emulab-xen',
	    image: 'urn:publicid:IDN+instageni.gpolab.bbn.com+image+emulab-ops:Ubuntu12-64-OVS',
	    icon: 'https://www.emulab.net/protogeni/jacks-stable/images/router.svg'
	}];

  var images = [
        {
	    name: 'UBUNTU12-64-STD',
	    id: 'urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD'
	},
        {
	    name: 'OVS Image',
	    id: 'urn:publicid:IDN+instageni.gpolab.bbn.com+image+emulab-ops:Ubuntu12-64-OVS'
	}
	];

var types =  [
	{
	    name: 'Universal Default VM',
	    id: 'default-vm'
	},
        {
	    name: 'Emulab Xen VM',
	    id: 'emulab-xen'
	      },
        {
	    name: 'Emulab Raw PC',
	    id: 'emulab-rawpc'
	},
        {
	    name: 'ExoGENI Small VM',
	    id: 'm1.small'
	}];

var hardware = [
        {
	    name: 'Dell d710',
	    id: 'd710'
	},
        {
	    name: 'Dell 3Ghz',
	    id: 'pc3000'
	},
        {
	    name: 'Any PC',
	    id: 'pc',
	}];

var linkTypes = [
        {
	    name: 'GRE Tunnel',
	    id: 'gre-tunnel'
	},
        {
	    name: 'EGRE Tunnel',
	    id: 'egre-tunnel'
	},
        {
	    name: 'Ethernet',
	    id: 'lan'
	},
        {
	    name: 'Stitched Ethernet',
	    id: 'stitched'
	}];

var sharedvlans = [];
var canvas_options = { defaults: defaults,
		       images : images,
		       types: types,
		       hardware : hardware,
		       linkTypes : linkTypes,
		       sharedvlans : sharedvlans
		       };
return canvas_options;

}

 function getDefaultConstraints() 
{
var constraints = [
        {
	    node: {
		'hardware': ['d710', 'pc3000'],
		'types': ['emulab-rawpc', 'emulab-xen']
	    }
	},
        {
	    node: {
		'hardware': ['pc3000'],
		'types': ['*']
	    }
	},
        {
	    node: {
		'hardware': ['*'],
		'types': ['default-vm']
	    }
	},
        {
	    node: {
		'hardware': ['pc', 'pc3000'],
		'images': ['urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD']
	    }
	},
        {
	    node: {
		'hardware': ['d710'],
		'images': ['urn:publicid:IDN+instageni.gpolab.bbn.com+image+emulab-ops:Ubuntu12-64-OVS']
	    }
	},
        {
	    node: {
		'hardware': ['*'],
		'images': ['urn:blahblah:any']
	    }
	},
        {
	    node: {
		'hardware': ['pc', 'd710'],
		'types': ['m1.small']
	    }
	},
        {
	    node: {
		'types': ['emulab-xen']
	    },
	    link: {
		'linkTypes': ['egre-tunnel']
	    },
	    node2: {
		'types': ['emulab-xen']
	    }
	},
        {
	    node: {
		'types': ['emulab-rawpc']
	    },
	    link: {
		'linkTypes': ['stitched'],
	    },
	    node2: {
		'types': ['m1.small']
	    }
	}];

return constraints;

}

JacksEditorApp.prototype.setJacksViewer = function(jv) {
    this.jacks_viewer = jv;
    this.jacks_viewer_visible = true;
}

/** 
 * Hide the jacks app pane
 */ 
JacksEditorApp.prototype.hide = function (msg) {
    $(this.jacks).hide();
    $(this.buttons).hide();
    $(this.status).hide();
}

/** 
 * Show the jacks app pane
 */ 
JacksEditorApp.prototype.show = function (msg) {
    $(this.jacks).show();
    $(this.buttons).show();
    $(this.status).show();
}

/**
 * Called when Jacks is ready. 'input' and 'output' are the Jacks
 * input and output event channels.
 */
    JacksEditorApp.prototype.jacksReady = function(input, output) {
	// Once Jacks is ready, we can initialize the
	// JacksEditorApp event channels because Backbone has
	// been loaded.
	this.initEvents();

	// Commands going into Jacks.
	this.jacksInput = input;
	// Responses coming out of Jacks.
	this.jacksOutput = output;

	// Start with a blank topology.
	this.jacksInput.trigger('change-topology',
				[{ rspec: '<rspec></rspec>' }]);

	this.jacksOutput.on('selection', this.onSelectionEvent, this);

    };

JacksEditorApp.prototype.initEvents = function() {
    // Initialize input and output as Backbone.Events
    // See http://backbonejs.org
    this.input = new Object();
    this.output = new Object();
    _.extend(this.input, Backbone.Events);
    _.extend(this.output, Backbone.Events);

    // Debug the event channels
    this.input.on("all", function(eventName) {
	    debug("EP -> JacksEditorApp: " + eventName + " event");
	});
    this.output.on("all", function(eventName) {
	    debug("EP <- JacksEditorApp: " + eventName + " event");
	});

    
    this.input.on(this.DOWNLOAD_EVENT_TYPE, this.onEpDownload, this);
    this.input.on(this.LOAD_EVENT_TYPE, this.onEpLoad, this);
    this.input.on(this.LOOKUP_EVENT_TYPE, this.onEpLookup, this);
    this.input.on(this.MANIFEST_EVENT_TYPE, this.onEpManifest, this);
    this.input.on(this.PASTE_EVENT_TYPE, this.onEpPaste, this);
    this.input.on(this.RESERVE_EVENT_TYPE, this.onEpReserve, this);
    this.input.on(this.SELECT_EVENT_TYPE, this.onEpSelect, this);
    this.input.on(this.UPLOAD_EVENT_TYPE, this.onEpUpload, this);
};

JacksEditorApp.prototype.updateStatus = function(statusText) {
    var statusPane = this.status;
    var html = '<p class="jacksStatusText">' + statusText + '</p>';
    $(statusPane).prepend(html);
};

JacksEditorApp.prototype.rspec_loaded = function(jacks_input) {
    var rspec_loader = $('#rspec_loader');
    var rspec_file = rspec_loader.get(0).files[0];
    var reader = new FileReader();
    reader.onload = function(evt) { 
	var contents = evt.target.result;
	jacks_input.trigger('change-topology', 
					    [{rspec : contents}]);
	$('#rspec_chooser').val("0");
	$('#rspec_paste_text').val("");
	$('#rspec_url_text').val("");
    }
    reader.readAsText(rspec_file);
}

JacksEditorApp.prototype.initButtons = function(buttonSelector) {
    var that = this;
    var jacks_input = this.jacksInput;

    //    var btn = $('<button type="button">Load RSpec</button>');
    //    btn.click(function(){ that.handleLoad();});
    //    $(buttonSelector).append(btn);

    var rspec_load_label = $('<label for "rspec_loader">Load Rspec: </label>');
    $(buttonSelector).append(rspec_load_label);

    rspec_loader_onchange = function() {
	that.rspec_loaded(jacks_input);
    };
    var rspec_loader = $('<input type="file" id="rspec_loader" onchange="rspec_loader_onchange();" />');
    $(buttonSelector).append(rspec_loader);

    var rspec_select_label = $('<label for "rspec_chooser">Select Rspec: </label>');
    $(buttonSelector).append(rspec_select_label);
    
    var rspec_selector = that.constructRspecSelector();
    $(buttonSelector).append(rspec_selector);

    var btn = $('<button type="button">Upload URL</button>');
    btn.click(function() {
	    that.handleUpload();
	});
    $(buttonSelector).append(btn);

    var rspec_url_text_input = $('<input type="text" id="rspec_url_text"/>');
    $(buttonSelector).append(rspec_url_text_input);

    var btn = $('<button type="button">Paste RSpec</button>');
    btn.click(function(){ that.handlePaste();});
    $(buttonSelector).append(btn);

    var text_input = $('<input type="text" id="rspec_paste_text"/>');
    $(buttonSelector).append(text_input);

    var btn = $('<button type="button">Download RSpec</button>');
    btn.click(function(){ that.handleDownload();});
    $(buttonSelector).append(btn);

    var btn = $('<button type="button">Reserve Resoures</button>');
    btn.click(function(){ that.handleReserve();});
    $(buttonSelector).append(btn);

    var agg_selector = that.constructAggregateSelector(); 
    $(buttonSelector).append(agg_selector);

    /*
    btn = $('<button type="button">VIEWER</BUTTON>');
    btn.click(function() {
	    //	    console.log("HIDE " + that.jacks_viewer);
	    if(that.jacks_viewer != null) {
		if (that.jacks_viewer_visible) {
		    that.jacks_viewer.hide();
		    that.jacks_viewer_visible = false;
		} else {
		    that.jacks_viewer.show();
		    that.jacks_viewer_visible = true;
		}
		//		je.style.visibility='hidden';
	    }
	});
    $(buttonSelector).append(btn);
    */

};

JacksEditorApp.prototype.amName = function(am_id) {
    return this.allAms[am_id].name;
};

JacksEditorApp.prototype.constructAggregateSelector = function() {
    var that = this;
    var selector_text = "";
    selector_text += 
    '<select name="am_chooser" id="agg_chooser" ">\n';
    $.each(that.sortedAms, function(am_index) {
	    var am_entry = that.sortedAms[am_index];
	    var am_id = am_entry.am_id;
	    var am_url = am_entry.url;
	    var am_name = am_entry.name;
	    selector_text += '<option value="' + am_id + '">' + am_name + '</option>\n';
	});
    selector_text += "</select>\n";
    
    return selector_text;
};

JacksEditorApp.prototype.constructRspecSelector = function() {

    var that = this;
    rspec_selector_on_change = function() {
	that.handleSelect();
    };
    var selector_text = "";
    selector_text += 
    '<select name="rspec_chooser" id="rspec_chooser" onchange="rspec_selector_on_change();">\n';
    selector_text += ' <option value="0"/>'; // Empty first entry

    $.each(this.allRspecs, function(i, rspec_info) {
	    var rspec_id = rspec_info['id'];
	    var rspec_name = rspec_info['name'];
	    var bound = rspec_info['bound'];
	    var stitch = rspec_info['stitch'];
	    var visibility = rspec_info['visbiilty'];
	    var rspec_text = rspec_name;
	    if (visibility =="private" ) rspec_text += " [PRIVATE]";
	    if (bound == "t") rspec_text += " [BOUND]";
	    if (stitch == "t") rspec_text += " [STITCH]";
	    selector_text += '<option value="' + rspec_id + '">' + 
		rspec_text + '</option>\n';
	});

    selector_text += "</select>\n";
    
    return selector_text;
};



//----------------------------------------------------------------------
// Jacks App Events to Embedding Page
//----------------------------------------------------------------------

/**
 * Handle request to select an RSpec from local file system
 */
JacksEditorApp.prototype.handleLoad = function() {
    this.output.trigger(this.LOAD_EVENT_TYPE, { name : this.LOAD_EVENT_TYPE, 
						client_data : {}, slice_id: this.sliceId,
						callback: this.input}
	);
};

/**
 * Handle request to select an RSpec from local file system
 */
JacksEditorApp.prototype.handleUpload = function(url) {
    var url = $('#rspec_url_text').val();
    this.output.trigger(this.UPLOAD_EVENT_TYPE, { name : this.UPLOAD_EVENT_TYPE, 
						  client_data : {}, 
						  url: url,
						  callback: this.input}
	);
};

/**
 * Hanlde request to select an RSpec from embedding page's list
 */
JacksEditorApp.prototype.handleSelect = function() {
    // Load the selected rspec into jacks
    var selector_parent = $('#rspec_chooser');
    var selector = selector_parent[0];
    var selected_index = selector.selectedIndex;
    var selected_option = selector.options[selected_index];
    var rspec_id = selected_option['value'];

    this.output.trigger(this.LOOKUP_EVENT_TYPE, {name : this.LOOKUP_EVENT_TYPE,
		rspec_id : rspec_id,
		client_data : {},
		callback : this.input});
    
};

/**
 * Handle request to an RSpec to local file system
 */
JacksEditorApp.prototype.handleDownload = function() {
    this.downloadingRspec = true;
    this.jacksInput.trigger('fetch-topology');
};


/**
 * Handle request to paste an RSpec to the portal for use by Jacks
 */
JacksEditorApp.prototype.handlePaste = function() {
    var rspec_paste_input = $('#rspec_paste_text');
    var current_rspec = rspec_paste_input.val();
    this.jacksInput.trigger('change-topology', [{rspec : current_rspec}]);

    $('#rspec_chooser').val("0");
    $('#rspec_loader').val(null);
    $('#rspec_url_text').val("");


};

JacksEditorApp.prototype.postRspec = function(rspecs) 
{
    if (rspecs.length == 0 || (!rspecs[0].rspec)) 
	return;

    rspec = rspecs[0].rspec;

    if (this.downloadingRspec) {
	this.output.trigger(this.DOWNLOAD_EVENT_TYPE, {
		name : this.DOWNLOAD_EVENT_TYPE,
		    rspec : rspec,
		    client_data : {},
		    callback : this.input
		    }
	    );
	this.downloadingRspec = false;
    } else {
	this.updateStatus("Processing reservation request...");
	var selector_parent = $('#agg_chooser');
	var selector = selector_parent[0];
	var selected_index = selector.selectedIndex;
	var selected_option = selector.options[selected_index];
	var am_id = selected_option['value'];
	var am_name = selected_option['label'];
	this.output.trigger(this.RESERVE_EVENT_TYPE, { 
		name : this.RESERVE_EVENT_TYPE, 
		    rspec : rspec,
		    am_id : am_id, 
		    client_data : {}, 
		    slice_id: this.sliceId,
		    callback: this.input}
	    );
    }
}


/**
 * Handle request to allocate resources
 */
    JacksEditorApp.prototype.handleReserve = function() {
	this.jacksInput.trigger('fetch-topology');
    };



//----------------------------------------------------------------------
// Jacks App Events from Jacks
//----------------------------------------------------------------------

JacksEditorApp.prototype.onSelectionEvent = function(event) {
    debug("JE : " + event);
}


//----------------------------------------------------------------------
// Jacks App Events from Embedding Page
//----------------------------------------------------------------------

JacksEditorApp.prototype.onEpLoad = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }
};

JacksEditorApp.prototype.onEpLookup = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }
    debug("onEpLookup: " + event);
    this.jacksInput.trigger('change-topology',
			    [{ rspec: event.rspec }]);

    $('#rspec_loader').val(null);
    $('#rspec_paste_text').val("");
    $('#rspec_url_text').val("");

};

JacksEditorApp.prototype.onEpSelect = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }

    debug("ON_EP_SELECT");
};

JacksEditorApp.prototype.onEpUpload = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }

    debug("ON_EP_UPLOAD");
    this.jacksInput.trigger('change-topology',
			    [{ rspec: event.rspec }]);

    $('#rspec_chooser').val("0");
    $('#rspec_loader').val(null);
    $('#rspec_paste_text').val("");

};

JacksEditorApp.prototype.onEpPaste = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }
    debug("ON_EP_PASTE");

};

JacksEditorApp.prototype.onEpManifest = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }
    debug("ON_EP_Manifest");

};

JacksEditorApp.prototype.onEpReserve = function(event) {
    if (event.code !== 0) {
	debug("Error reserving resources: " + event.output);
	return;
    }
    debug("ON_EP_RESERVE");

    if (this.jacks_viewer != null) {
	this.jacksInput.trigger('change-topology',
				[{ rspec: '<rspec></rspec>' }]);
	this.updateStatus("Reservation request completed.");
	var am_id = event.am_id;
	if (this.jacks_viewer.sliceAms.indexOf(am_id) < 0) {
	    this.jacks_viewer.sliceAms.push(am_id);
	}
	this.hide();
	$('#rspec_chooser').val("0");
	$('#rspec_paste_text').val("");
	$('#rspec_loader').val(null);
	$('#rspec_url_text').val("");


	this.jacks_viewer.show();
	this.jacks_viewer.getSliceManifests();
    }
};

JacksEditorApp.prototype.onEpDownload = function(event) {
    if (event.code !== 0) {
	debug("Error savinging rspec: " + event.output);
	return;
    }
    debug("ON_EP_DOWNLOAD");
};

