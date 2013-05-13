# Routines to manipulate RSPEC templates to produce stitching-ready rspecs
import json
import optparse
import sys
from xml.dom.minidom import *

# Parse command args
def parse_args(argv=None):
  if argv == None: argv = sys.argv

  parser = optparse.OptionParser()

  parser.add_option("--json_filename", \
                      help="Name of JSON filename containing aggregates [cmid, agg_name], internal [agg_name rspec] and external [agg1_name, agg1_if, agg2_name, agg2_if] tuples", \
                      default=None, dest="json_filename")

  opts, args = parser.parse_args(argv)
  return opts

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

def copy_attributes(to_node, from_node, label, attributes_to_tag):
  for attr_name, attr_value in from_node.attributes.items():
    if attr_name in attributes_to_tag:
      attr_value = tag_name(label, attr_value)
    to_node.setAttribute(attr_name, attr_value)


def modify_node(element, node_hierarchy, attr_name, \
                  attr_value, prepend, prev_hierarchy = []):
#  print "MN: " + str(element) + " " + str(node_hierarchy) + " " + attr_name + " " \
#      + attr_value + " " + str(prepend) + " " + str(prev_hierarchy);
  if element.nodeType != Node.ELEMENT_NODE: return
  current_hierarchy = prev_hierarchy[:]
  current_hierarchy.append(element.nodeName)
  if current_hierarchy == node_hierarchy:
    new_attr_value = attr_value
    if element.hasAttribute(attr_name):
      if prepend: 
        current_attr_value = element.getAttribute(attr_name)
        new_attr_value = tag_name(attr_value, current_attr_value)
      element.setAttribute(attr_name, new_attr_value)
  for child in element.childNodes:
    if child.nodeType != Node.ELEMENT_NODE: continue
    modify_node(child, node_hierarchy, attr_name, attr_value, \
                  prepend, current_hierarchy)


# Clone the template element to the request element
# (recursively, all attributes and children)
# The mapping is a dictionary
# Attribute => {value, prepend}
# Where attribute is hierarchical (e.g. 'node.interface_ref.client_id')
# and 'prepend' is a boolean indicating whether to replace the value with
# 'value' or to replace value with 'value' + original_value
# If the template doesn't have the given attribute, 
#     add it if 'prepend' is false
def clone(doc, request, template, mapping):

  # Deep clone the template children into the request
  for child in template.childNodes:
    new_child = doc.importNode(child, True)
    for map in mapping.keys():
      node_hierarchy = map.split(".")
      attr_name = node_hierarchy[len(node_hierarchy)-1]
      node_hierarchy = node_hierarchy[:-1]
      map_attributes = mapping[map]
      attr_value = map_attributes[0]
      map_prepend = map_attributes[1]
      modify_node(new_child, node_hierarchy, attr_name, \
                    attr_value, map_prepend)
    request.appendChild(new_child)


def copy_from_template(doc, request, template, agg_urn, agg_label):
  mapping = {
    'node.client_id' : [agg_label, True],
    'node.component_manager_id' : [agg_urn, False],
    'node.interface.client_id' : [agg_label, True],
    'link.client_id': [agg_label, True],
    'link.interface_ref.client_id' : [agg_label, True],
    'link.property.source_id' : [agg_label, True],
    'link.property.dest_id' : [agg_label, True]
    }
  clone(doc, request, template, mapping)

def create_node(doc, element_label, attribute_name, attribute_value):
  node = doc.createElement(element_label)
  node.setAttribute(attribute_name, attribute_value)
  return node

# Add the stitching link to the request
# Representing link between interfaces on two different aggregates
def add_stitching_link(doc, request,  agg1_urn, agg1_label, if1, \
                         agg2_urn, agg2_label, if2):

  link = doc.createElement('link')
  link_id = 'link-%s-%s' % (agg1_label, agg2_label)
  link.setAttribute('client_id', link_id)

  cm1 = create_node(doc, 'component_manager', 'name', agg1_urn)
  link.appendChild(cm1)
  if_ref1 = create_node(doc, 'interface_ref', 'client_id', \
                          tag_name(agg1_label, if1))
  link.appendChild(if_ref1)

  cm2 = create_node(doc, 'component_manager', 'name', agg2_urn)
  link.appendChild(cm2)
  if_ref2 = create_node(doc, 'interface_ref', 'client_id', \
                          tag_name(agg2_label, if2))
  link.appendChild(if_ref2);

#  link.appendChild(property_1_2)
#  link.appendChild(property_2_1)

  link_type = doc.createElement('link_type');
  link_type.setAttribute('name', 'lan');
  link.appendChild(link_type)

  request.appendChild(link)

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

# Create a request rspec that:
#   Instantiates template1 on agg1 (URN)
#   Instantiates template2 on agg2 (URN)
#   Builds a stitching link between the two on described interfaces
# def instantiate_stitching_rspec_orig(template1, agg1_urn, link1, \
#                                   template2, agg2_urn, if2):
# #  print "ISR %s %s %s %s %s %s" % (template1, agg1_urn, if1, \
# #                                     template2, agg2_urn, if2)

#   template1_doc = parse(open(template1, 'r'))
#   template1_node = template1_doc.childNodes[0]
# #  print template1_doc.toxml()

#   template2_doc = parse(open(template2, 'r'))
#   template2_node = template2_doc.childNodes[0]
# #  print template2_doc.toxml()

#   root = Document()
#   request = setup_request_header(root)
#   agg1_label = "A"
#   agg2_label = "B"
#   copy_from_template(root, request, template1_node, agg1_urn, agg1_label)
#   copy_from_template(root, request, template2_node, agg2_urn, agg2_label)
#   add_stitching_link(root, request, agg1_urn, agg1_label, if1, \
#                        agg2_urn, agg2_label, if2)

#   return root

def find_agg_node(request, agg_node_name):
  for nd in request.childNodes:
    if nd.nodeType != Node.ELEMENT_NODE: continue
    nd_client_id = nd.getAttribute('client_id')
    if nd_client_id == agg_node_name:
      return nd
  print "Aggregate/Node not found : " + agg_node_name
  sys.exit(0)

# Validate that a given aggregate has been defined
def validate_aggregate_name(agg_name, aggregate_urns):
    if agg_name not in aggregate_urns:
      print "Undefined aggregate : " + agg_name
      sys.exit(0)

# Insert an interface node into the document as a child of the specified node
# representing the topology for the given aggregate
def insert_interface(doc, request, agg, node, aggregate_stitched_interfaces):
  if agg in aggregate_stitched_interfaces:
    count = aggregate_stitched_interfaces[agg]
  else:
    count = 0
  aggregate_stitched_interfaces[agg] = count + 1
  interface_name = "stitch:" + str(count)
  tagged_interface_name = tag_name(agg, interface_name)
  
  agg_node = find_agg_node(request, agg + ":" + node)

  interface_child = create_node(doc, 'interface', 'client_id', tagged_interface_name)
  agg_node.appendChild(interface_child)
  return interface_name

# Find all interfaces that don't already have IP addresses dedicated. 
# Then assign unique addresses to them.
def assign_ip_addresses(root, request):
  ip_template = "10.10.0.%d"
  ip_netmask = "255.255.255.0"
  next_ip = 100
  for child in request.childNodes:
    if child.nodeType != Node.ELEMENT_NODE: 
      continue
    for interface in child.childNodes:
      if interface.nodeType != Node.ELEMENT_NODE or interface.nodeName != "interface":
        continue
      # We have an interface node. Does it have an IP child?
      ip_children = interface.getElementsByTagName('ip')
      if len(ip_children) == 0:
        ip_address = ip_template % next_ip
        next_ip = next_ip + 1
        ip_node = create_node(root, 'ip', 'address', ip_address);
        ip_node.setAttribute('netmask', ip_netmask)
        ip_node.setAttribute('type', 'ipv4')
        interface.appendChild(ip_node)


# Instantiate a request RSPEC for stitching across aggregates
# aggregates is a list of {'urn', 'agg'} for all aggregates involved in topology
# internal is a list of {'agg', 'rspec'} indicating which internal topologies 
#    (specified by rspec)are to be created on which aggregates 
# external is a list of {'from_agg', 'from_node', 'to_agg', 'to_node'} 
#    indicating which aggregates (and instantiated nodes) participate in a 
#    dedicated cross-aggregate interfaces 
def instantiate_stitching_rspec(aggregates, internal_topologies, external_links):

  # Validate arguments
  # All aggs in internal and external must be listed in aggregates
  # ***

  root = Document()
  request = setup_request_header(root)

  # Maintain a list of aggregate URNs by name
  aggregate_urns = {}
  for agg in aggregates:
    agg_urn = agg['urn']
    agg_name = agg['agg']
    aggregate_urns[agg_name] = agg_urn

  # Maintain a list of stitched interfaces for each aggregate by name
  aggregate_stitched_interfaces = {}

  # Instantiate internal topologies at each aggregate
  # Changing the names to be in the namespace of the aggregate (to avoid overlap)
  for internal_topology in internal_topologies:
    agg_name = internal_topology['agg']
    agg_rspec = internal_topology['rspec']
    validate_aggregate_name(agg_name, aggregate_urns)
    agg_urn = aggregate_urns[agg_name]
    template_doc = parse(open(agg_rspec, 'r'))
    template_node = template_doc.childNodes[0]
    copy_from_template(root, request, template_node, agg_urn, agg_name)

  # Put distinct IP addresses on all requested (explicit) interfaces
  assign_ip_addresses(root, request)

  # Instantiate link between aggregates
  for external_link in external_links:

    from_agg = external_link['from_agg']
    from_node = external_link['from_node']
    validate_aggregate_name(from_agg, aggregate_urns)
    from_agg_urn = aggregate_urns[from_agg]
    
    to_agg = external_link['to_agg']
    to_node = external_link['to_node']
    validate_aggregate_name(from_agg, aggregate_urns)
    to_agg_urn = aggregate_urns[to_agg]

    # Insert a new interface into each aggregate
    from_agg_interface = insert_interface(root, request, from_agg, from_node, \
                                            aggregate_stitched_interfaces)
    to_agg_interface = insert_interface(root, request, to_agg, to_node, \
                                          aggregate_stitched_interfaces)
      
    add_stitching_link(root, request, from_agg_urn, from_agg, from_agg_interface, \
                         to_agg_urn, to_agg, to_agg_interface)
        

  return root

# Notes
# This should take the following structure
# Aggregates: List of component_id, nickname pairs
# Internal: List of aggregate/rspec tuples
# External: List of aggregate/interface/aggregate/interface tuples
# That's the main interface 

if __name__ == "__main__":
  opts = parse_args(sys.argv)

  json_filename = opts.json_filename
  json_data = open(json_filename, 'r').read()
  data = json.loads(json_data)
#  print str(data)

  aggregates = data['aggregates']
  internal = data['internal']
  external = data['external']
  doc = instantiate_stitching_rspec(aggregates, internal, external)

  print cleanXML(doc)


#   template1 = internal[0]['rspec']
#   agg1 = aggregates[0]['urn']
#   if1 = external[0]['interface']
#   template2 = internal[1]['rspec']
#   agg2 = aggregates[1]['urn']
#   if2 = external[1]['interface']
#   doc = instantiate_stitching_rspec(template1, agg1, if1, template2, agg2, if2)
#   print cleanXML(doc)

  
# aggregates = [{'urn' : 'urn:publicid:IDN+boscontroller+authority+cm', 'name' : 'BOS'},
#               {'urn' : 'urn:publicid:IDN+pricontroller+authority+cm', 'name' : 'PRI'}]
# internal = [{'agg' : 'BOS', 'rspec' : '/Users/mbrinn/geni/rspecs/templates/one-node.xml'},
#             {'agg' : 'PRI', 'rspec' : '/Users/mbrinn/geni/rspecs/templates/one-node.xml'}]
# external = [{'agg' : 'BOS', 'interface' : 'if0'}, 
#             {'agg' : 'PRI', 'interface' : 'if0'}]
              
# j = {'aggregates' : aggregates, 'internal' : internal, 'external' : external}
# print json.dumps(j)
