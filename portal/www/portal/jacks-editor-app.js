function JacksEditorApp(jacks, status, buttons, sliceAms, allAms, 
			allRspecs,
			sliceInfo, userInfo, readyCallback) {

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
    this.status = status;
    this.buttons = buttons;
    this.sliceAms = sliceAms;
    this.allAms = allAms;

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

    var that = this;
    var jacksInstance = new window.Jacks({
	    mode: 'editor',
	    source: 'rspec',
	    // This may not need to be hardcoded.
	    size: { x: 791, y: 350},
	    canvasOptions: {
		images: [{
			name: 'foo',
			id: 'bar'
		    },
    {
	name: 'UBUNTU12-64-STD',
	id: 'urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD'
    }]
	    },
	    nodeSelect: false,
	    root: jacks,
	    readyCallback: function (input, output) {
		output.on('fetch-topology', function(rspecs) {
			that.postRspec(rspecs);
		    });
		that.jacksReady(input, output);
		that.initButtons(that.buttons);
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
JacksEditorApp.prototype.PASTE_EVENT_TYPE = "PASTE";
JacksEditorApp.prototype.RESERVE_EVENT_TYPE = "RESERVE";
JacksEditorApp.prototype.SELECT_EVENT_TYPE = "SELECT";

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
    this.input.on(this.PASTE_EVENT_TYPE, this.onEpPaste, this);
    this.input.on(this.RESERVE_EVENT_TYPE, this.onEpReserve, this);
    this.input.on(this.SELECT_EVENT_TYPE, this.onEpSelect, this);
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

    var btn = $('<button type="button">Download RSpec</button>');
    btn.click(function(){ that.handleDownload();});
    $(buttonSelector).append(btn);

    var btn = $('<button type="button">Paste RSpec</button>');
    btn.click(function(){ that.handlePaste();});
    $(buttonSelector).append(btn);

    var text_input = $('<input type="text" id="rspec_paste_text"/>');
    $(buttonSelector).append(text_input);

    var btn = $('<button type="button">Reserve Resoures</button>');
    btn.click(function(){ that.handleReserve();});
    $(buttonSelector).append(btn);

    var agg_selector = that.constructAggregateSelector(); 
    $(buttonSelector).append(agg_selector);
};

JacksEditorApp.prototype.amName = function(am_id) {
    return this.allAms[am_id].name;
};

JacksEditorApp.prototype.constructAggregateSelector = function() {
    var selector_text = "";
    selector_text += 
    '<select name="am_chooser" id="agg_chooser" ">\n';
    $.each(this.allAms, function(am_id, am_info) {
	    debug("AM = " + am_info);
	    var am_url = am_info["url"];
	    var am_name = am_info["name"];
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
 * Handle request to to an RSpec to local file system
 */
    JacksEditorApp.prototype.handleReserve = function() {
	this.jacksInput.trigger('fetch-topology');
    };



//----------------------------------------------------------------------
// Jacks App Events from Jacks
//----------------------------------------------------------------------


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
    var rspec = event.rspec;
    this.jacksInput.trigger('change-topology',
			    [{ rspec: rspec }]);

    $('#rspec_loader').val(null);
    $('#rspec_paste_text').val("");

};

JacksEditorApp.prototype.onEpSelect = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }

    debug("ON_EP_SELECT");
};

JacksEditorApp.prototype.onEpPaste = function(event) {
    if (event.code !== 0) {
	debug("Error retrieving rspec: " + event.output);
	return;
    }
    debug("ON_EP_PASTE");

};

JacksEditorApp.prototype.onEpReserve = function(event) {
    if (event.code !== 0) {
	debug("Error reserving resources: " + event.output);
	return;
    }
    debug("ON_EP_RESERVE");
};

JacksEditorApp.prototype.onEpDownload = function(event) {
    if (event.code !== 0) {
	debug("Error savinging rspec: " + event.output);
	return;
    }
    debug("ON_EP_DOWNLOAD");
};

