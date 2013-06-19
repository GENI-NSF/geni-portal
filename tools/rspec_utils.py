#----------------------------------------------------------------------
# Copyright (c) 2013 Raytheon BBN Technologies
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
#----------------------------------------------------------------------
# Routines to create RSPECs from a set of nodes and links
import json
import optparse
import sys
from xml.dom.minidom import *

# Static helper utils


# Change the value of a given name to the namespace denoted by a label
# i.e. A:name for A being the label (typically the aggregate nickname)
def tag_name(label, name):
  return "%s:%s" % (label, name)

# Take prety xml and get rid of superflous linefeeds
def cleanXML(doc):
    xml = doc.toprettyxml(indent = '    ')
    clean_xml = ""
    for line in xml.split('\n'):
        if line.strip():
            clean_xml += line + '\n'
    return clean_xml

def create_node(doc, element_label, attribute_name, attribute_value):
  node = doc.createElement(element_label)
  if attribute_name:
    node.setAttribute(attribute_name, attribute_value)
  return node

# Set up and return request RSPEC header
def setup_request_header(root):
  request = root.createElement('rspec')
  request.setAttribute('type', 'request')
  root.appendChild(request)

  namespaces = {"xmlns" : "http://www.geni.net/resources/rspec/3",
       "xmlns:xsi" : "http://www.w3.org/2001/XMLSchema-instance",
       "xmlns:stitch" : "http://hpn.east.isi.edu/rspec/ext/stitch/0.1/",
       "xsi:schemaLocation" : "http://hpn.east.isi.edu/rspec/ext/stitch/0.1/"+
                " http://hpn.east.isi.edu/rspec/ext/stitch/0.1/stitch-schema.xsd" +
                " http://www.geni.net/resources/rspec/3 " +
                " http://www.geni.net/resources/rspec/3/request.xsd"}
  for ns in namespaces.keys():
    ns_value = namespaces[ns];
    request.setAttribute(ns, ns_value)
  return request

# Take a JSON configuration and create a (potentiall) stitched request rspec
# Instantiate a request RSPEC for stitching across aggregates
# aggregates is a list of {'urn', 'agg'} for all aggregates involved in topology
# nodes is a list of {'agg', 'type', 'name'} for all created compute nodes
# links is a list of {'from_node', 'to_node'} for all link interfaces
#   between nodes (internal and stitched)
class RSpecComposer:

  # Parse command args
  def parse_args(self, argv=None):
    if argv == None: argv = sys.argv

    parser = optparse.OptionParser()

    parser.add_option("--json_filename", \
                        help="Name of JSON filename containing aggregates [cmid, agg_name], internal [agg_name rspec] and external [agg1_name, agg1_if, agg2_name, agg2_if] tuples", \
                        default=None, dest="json_filename")

    opts, args = parser.parse_args(argv)

    if opts.json_filename is None:
      parser.print_help()
      sys.exit(0)

    return opts

  def __init__(self):
    self._opts = self.parse_args(sys.argv)

    json_filename = self._opts.json_filename
    self._json_data = open(json_filename, 'r').read()
    self._data = json.loads(self._json_data)
    # print str(self._data)

    self._aggregates = self._data['aggregates']
    self._nodes = self._data['nodes']
    self._links = self._data['links']

    self._aggregate_urns_by_name = {}
    for agg in self._aggregates:
      agg_urn = agg['urn']
      agg_name = agg['agg']
      self._aggregate_urns_by_name[agg_name] = agg_urn

    self._nodes_by_name = {}

    self._node_elements_by_name = {}

    self._interfaces_by_node = {}

    self._ip_template = "10.10.0.%d"
    self._ip_netmask = "255.255.255.0"
    self._next_ip_address = 10;

    self._root = Document()
    self._request = setup_request_header(self._root)

  # Hierarchically add a value into an XML tree 
  # indicated by node => value where value could be another node => value
  def add_children(self, node_elt, key, value):
    if isinstance(value, basestring):
      node_elt.setAttribute(key, value)
    else:
      child_elt = create_node(self._root, key, None, None)
      node_elt.appendChild(child_elt)
      print "VALUE = " + str(value) + " " + str(type(value))
      for sub_key in value.keys():
        sub_value = value[sub_key]
        self.add_children(child_elt, sub_key, sub_value)

  def create_compute_node(self, node_agg, 
                          node_name, agg_urn, node):
    node_elt = create_node(self._root, 'node', 'client_id', node_name)
    node_elt.setAttribute('component_manager_id', agg_urn)

    # Add in all optional keys
    for key in node.keys():
      if key == 'agg' or key == 'name': continue
      self.add_children(node_elt, key, node[key])

    self._node_elements_by_name[node_name] = node_elt
    self._request.appendChild(node_elt)

  def create_interface(self, agg, node):
    node_elt = self._node_elements_by_name[node]
    node_interfaces = node_elt.getElementsByTagName('interface')
    num_interfaces = len(node_interfaces)
    if_name = "%s:%d" % (node, num_interfaces)
    interface = create_node(self._root, 'interface', 'client_id', if_name)
    node_elt.appendChild(interface)

    address = self._ip_template % self._next_ip_address
    self._next_ip_address = self._next_ip_address + 1
    ip_address = create_node(self._root, 'ip', 'address', address)
    ip_address.setAttribute('netmask', self._ip_netmask)
    ip_address.setAttribute('type', 'ipv4');
    interface.appendChild(ip_address)

    return if_name

  def create_stitched_link(self, from_agg,  link_from_node, 
                                  to_agg, link_to_node):

    # create an interface on each node
    from_interface = self.create_interface(from_agg, link_from_node)
    to_interface = self.create_interface(to_agg, link_to_node)

    link_name = "link-%s-%s" % (link_from_node, link_to_node)
    link = create_node(self._root, 'link', 'client_id', link_name)

    from_agg_urn = self._aggregate_urns_by_name[from_agg]
    from_cm = create_node(self._root,'component_manager', 'name', from_agg_urn)
    link.appendChild(from_cm)

                          
    from_iface_ref = create_node(self._root, 'interface_ref', \
                                   'client_id', from_interface)
    link.appendChild(from_iface_ref)

    to_agg_urn = self._aggregate_urns_by_name[to_agg]
    to_cm = create_node(self._root,'component_manager', 'name', to_agg_urn)
    link.appendChild(to_cm)

    to_iface_ref = create_node(self._root, 'interface_ref', \
                                   'client_id', to_interface)
    link.appendChild(to_iface_ref)

    self._request.appendChild(link)

  def create_simple_link(self, agg, from_node, to_node):
    # create an interface on each node
    from_interface = self.create_interface(agg, from_node)
    to_interface = self.create_interface(agg, to_node)

    # create a link between these interfaces
    link = create_node(self._root, 'link', 'client_id', 
                       tag_name(from_node, 'lan'))

    from_ifref = create_node(self._root, 'interface_ref', 'client_id', \
                             from_interface)
    link.appendChild(from_ifref)

    to_ifref = create_node(self._root, 'interface_ref', 'client_id', \
                             to_interface)
    link.appendChild(to_ifref)

    property_from_to = create_node(self._root, 'property', 
                                   'source_id', to_interface)
    property_from_to.setAttribute('dest_id', from_interface)

    link.appendChild(property_from_to)

    property_to_from = create_node(self._root, 'property', 
                                   'source_id', from_interface)
    property_to_from.setAttribute('dest_id', to_interface)
    link.appendChild(property_to_from)

    link_type = create_node(self._root, 'link_type', 'name', 'lan')
    link.appendChild(link_type)

    self._request.appendChild(link)
    
    pass

  def create_rspec(self):

    # Loop over all nodes, creating node elements
    for node in self._nodes:
      node_agg = node['agg']
      node_name = node['name']
    # Validate: All aggs in nodes must be in list of aggregates
      if node_agg not in self._aggregate_urns_by_name.keys():
        print "Unknown aggregate: %s" % node_agg
        sys.exit(0)
      agg_urn = self._aggregate_urns_by_name[node_agg]
      self._nodes_by_name[node_name] = node
      self.create_compute_node(node_agg, 
                          node_name, agg_urn, node)

      # Loop over all links
      # Create an interface on each node being linked
      # If intra-aggregate create a non-stitching link
      # If cross-aggregate create a stitching link
    for link in self._links:
      link_from_node = link['from_node']
      link_to_node = link['to_node']
      if link_from_node not in self._nodes_by_name.keys():
        print "Unknown node : %s" % link_from_node
      if link_to_node not in self._nodes_by_name.keys():
        print "Unknown node : %s" % link_to_node

      from_agg = self._nodes_by_name[link_from_node]['agg']
      from_agg_urn = self._aggregate_urns_by_name[from_agg]
      to_agg = self._nodes_by_name[link_to_node]['agg']
      to_agg_urn = self._aggregate_urns_by_name[to_agg]

      if (from_agg == to_agg):
        self.create_simple_link(from_agg, link_from_node, link_to_node)
#        print "Generate non-stitch link and interfaces %s %s %s" % \
#            (from_agg, link_from_node, link_to_node)
      else:
        self.create_stitched_link(from_agg,  link_from_node, 
                                  to_agg, link_to_node)
#        print "Generate stitch link and interfaces %s %s %s %s" % \
#            (from_agg, link_from_node, to_agg, link_to_node)

    return self._root

# Parse a JSON file and create an rspec from its 
# specified aggregates, nodes, links
if __name__ == "__main__":
  composer = RSpecComposer()
  doc = composer.create_rspec()
  print cleanXML(doc)

