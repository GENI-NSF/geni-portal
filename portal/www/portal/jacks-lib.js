//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

// Clean up site IDs in output rspec
// Remove site tag from any bound node
// Change site ID to the site name for remaining sites
function cleanSiteIDsInOutputRSpec(rspec, sites) {
    var doc = jQuery.parseXML(rspec);
    var rspec_root = $(doc).find('rspec')[0];
    var nodes = $(rspec_root).find('node');
    var num_nodes = nodes.length;

    // If a node has a component manager, remove the site tag
    for(var i = 0; i < num_nodes; i++) {
	var node = nodes[i];
	if(node.hasAttribute('component_manager_id')) {
	    $(node).find('site').remove();
	}
    }

    // Are site names unique? Should be...
    var site_names = [];
    var site_ids = [];
    var uniqueNames = true;
    var sites_rspec = $(rspec_root).find('site');
    var numsites = 0;
    if (sites) {
	numsites = sites.length;
    }
    //console.log(sites_rspec.length + " sites remaining in rspec, " + numsites + " given from jacks.currentTopology");
    if (sites_rspec.length > 1 && sites) {
	for (i = 0; i < numsites; i++) {
	    if (! (sites[i].id in site_ids)) {
		//console.log("From Jacks looking at site " + sites[i].id + " with name '" + sites[i].name + "'");
		if (sites[i].name && sites[i].name in site_names) {
		    console.log("Error: non unique site name '" + sites[i].name + "' for site " + sites[i].id + " and one of " + site_ids.toString());
		    uniqueNames = false;
		    break;
		} else if (sites[i].name) {
		    site_names.push(sites[i].name);
		    site_ids.push(sites[i].id);
		}
	    }
	}
    //} else {
	//console.log("Only " + sites_rspec.length + " sites left in rspec");
    }

    // If site names are unique, then replace site IDs with site names
    if (uniqueNames && sites_rspec.length > 0 && sites) {
	//console.log("Site names are unique: replacing site IDs with site names");
	for(var i = 0; i < num_nodes; i++) {
	    var node = nodes[i];
	    if(! node.hasAttribute('component_manager_id')) {
		var thisSite = $(node).find('site');
		//console.log("Node " + node.getAttribute('client_id') + " has site " + thisSite.attr('id'));
		for (var j = 0; j < numsites; j++) {
		    if (sites[j].id == thisSite.attr('id')) {
			//console.log("Found jacks site with same ID");
			if (thisSite.attr('id') != sites[j].name) {
			    //console.log("Replacing site " + thisSite.attr('id') + " on node " + node.getAttribute('client_id') + " name with '" + sites[j].name + "'");
			    thisSite.attr('id', sites[j].name);
			}
			break;
		    }
		}
	    }
	}
    }
    
    var new_rspec = (new XMLSerializer()).serializeToString(doc);

    // Remove xmlns="" which Firefox puts into new elements 
    // but Jacks can't handle
    new_rspec = new_rspec.replace(/xmlns=""/g, '');

    return new_rspec;
}
