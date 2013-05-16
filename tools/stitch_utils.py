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

# Routines to manipulate RSPEC templates to produce stitching-ready rspecs
import sys
from xml.dom.minidom import *

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
def add_stitching_link(doc, request, template1, agg1_urn, agg1_label, if1, \
                         template2, agg2_urn, agg2_label, if2):

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

# Create a request rspec that:
#   Instantiates template1 on agg1 (URN)
#   Instantiates template2 on agg2 (URN)
#   Builds a stitching link between the two on described interfaces
def instantiate_stitching_rspec(template1, agg1_urn, link1, \
                                  template2, agg2_urn, if2):
#  print "ISR %s %s %s %s %s %s" % (template1, agg1_urn, if1, \
#                                     template2, agg2_urn, if2)

  template1_doc = parse(open(template1, 'r'))
  template1_node = template1_doc.childNodes[0]
#  print template1_doc.toxml()

  template2_doc = parse(open(template2, 'r'))
  template2_node = template2_doc.childNodes[0]
#  print template2_doc.toxml()

  root = Document()
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

  agg1_label = "A"
  agg2_label = "B"
  copy_from_template(root, request, template1_node, agg1_urn, agg1_label)
  copy_from_template(root, request, template2_node, agg2_urn, agg2_label)
  add_stitching_link(root, request, template1, agg1_urn, agg1_label, if1, \
                       template2, agg2_urn, agg2_label, if2)

  print cleanXML(root)

if __name__ == "__main__":
  if sys.argv < 6:
    print "Usage: stitch_utils.py template1 agg1 if1 template2 agg2 if2"
    sys.exit(0)

  template1 = sys.argv[1]
  agg1 = sys.argv[2]
  if1 = sys.argv[3]
  template2 = sys.argv[4]
  agg2= sys.argv[5]
  if2 = sys.argv[6]
  instantiate_stitching_rspec(template1, agg1, if1, template2, agg2, if2)

  

      

