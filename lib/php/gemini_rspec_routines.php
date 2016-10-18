<?php
/*
 
 * -----------------------------------------------------------------------------

Copyright (c) 2012-2015 University of Kentucky

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and/or hardware specification (the "Work") to deal in the
Work without restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Work, and to permit persons to whom the Work is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Work.

THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS IN THE WORK.

-----------------------------------------------------------------------------
*/
define("gemini_namespace",'http://www.geni.net/resources/rspec/ext/gemini/1');
define("color_namespace",'http://www.geni.net/resources/rspec/ext/color/2');
define("rspec_namespace",'http://www.geni.net/resources/rspec/3');
define("emulab_namespace","http://www.protogeni.net/resources/rspec/ext/emulab/1");
define("RSPECLINT","/usr/bin/rspeclint"); // WARNING: Not installed on portal systems

// Unused function that relies on rspeclint being installed
function validateRspec($rspec_string)
{
  $result = False;
  $message = '';
  $rspec_filename = tempnam("/tmp",'_');
  file_put_contents($rspec_filename,$rspec_string);
  $command = RSPECLINT.' '.$rspec_filename.' 2>&1';
  exec($command,$output,$retval);
  if($retval == 0)
    {
      $result = True;
    }
  else
    {
      $result = False;
      $message = implode("\n",$output);
    }
  unlink($rspec_filename);
  return array($result,$message);
}

function add_gemini($rspec_string)
{
  //error_log("adding gemini... ");
  $ddoc = new DOMDocument();
  //$rspec_roottag = DOMDocument::loadXML($rspec_string);
  
  $ddoc->loadXML($rspec_string);
  //$rspec_roottag = $ddoc->loadXML($rspec_string);
  //$rspec_root = $rspec_roottag->firstChild;
  $rspec_roottag = $ddoc->documentElement;//->getAttribute('rspec');
  $rspec_root = $rspec_roottag->firstChild;
  //$rspec_root = $ddoc->firstChild;
  if (!$rspec_roottag)
    {
      return ("");
    }
  $isExogeni = False;
  //$gem_mp_node = getGeminiNode($rspec_roottag,'MP');
  //$gem_mp_node = getGeminiNode($ddoc,'MP');
  $DiskJSON = json_decode(file_get_contents('http://genidesktop.netlab.uky.edu/repo/DiskImages.json'),True);
  $all_ams = array();
  $nodes_to_delete = array();
  $rspec_nodes = $rspec_roottag->getElementsByTagNameNS(rspec_namespace, 'node');
  foreach ($rspec_nodes as $rspec_node) 
    {
      $delete_me = false;
      if(isGemini($rspec_node))
        {
	  // If nodes already has GEMINI Extensions,
	  list($rspec_node,$delete_me) = delete_colortag($rspec_node);
	  if($delete_me)
	    {
	      array_push($nodes_to_delete,$rspec_node);
	    }
	}
      if(!$delete_me)
	{
	  if($rspec_node->hasAttribute('component_manager_id'))
	    {
	      $this_am = $rspec_node->getAttribute('component_manager_id');
	    }
	  else
	    {
	      $this_am = 'unbound';
	    }
	  if(strpos($this_am,'openflow') !== false)
	    {
	      continue;
	    }
	  if(!in_array($this_am,$all_ams))
	    {
	      array_push($all_ams,$this_am);
	    }
	}
    }
  foreach ($nodes_to_delete as $node_to_delete)
    {
      $rspec_roottag->removeChild($node_to_delete);
    }
  /*
       $rspec_nodes = array();
       $exogeni_virt_types = array('exogeni-m4','xo.small','xo.medium','xo.large','xo.xlarge','m1.small','m1.large','m1.xlarge','c1.medium','c1.xlarge');
       $rspec_nodes = $rspec_roottag->getElementsByTagNameNS(rspec_namespace, 'node');
       foreach ($rspec_nodes as $rspec_node) 
       {
       if($rspec_node->hasAttribute('component_manager_id'))
       {
       $this_am = $rspec_node->getAttribute('component_manager_id');
       }
       else
       {
       $this_am = 'unbound';
       }
       if(strpos($this_am,'openflow') !== false)
       {
       continue;
       }
       if(!in_array($this_am,$all_ams))
       {
       array_push($all_ams,$this_am);
       }/*
       $sliver_type_nodes = $rspec_node->getElementsByTagNameNS(rspec_namespace, 'sliver_type');
       if($sliver_type_nodes->length > 0)
       {
       $sliver_type_node = $sliver_type_nodes->item(0);
       $virt_type = '';
       if($sliver_type_node->hasAttribute('name'))
       {
       $virt_type = $sliver_type_node->getAttribute('name');
       }
       if($virt_type != '')
       {
       $diskimage_nodes = $sliver_type_node->getElementsByTagNameNS(rspec_namespace, 'disk_image');
       if($diskimage_nodes->length == 0)
       {
       list($disk_image,$version) = getDiskImageNode($virt_type,$DiskJSON,'MP');
       if($disk_image != '')
       {
       //$disk_image_node = $rspec_roottag->CreateElementNS(rspec_namespace,'disk_image');
       $disk_image_node = $ddoc->CreateElementNS(rspec_namespace,'disk_image');
       $disk_image_node->setAttribute('name',$disk_image);
       if($version != '')
       {
       $disk_image_node->setAttribute('version',$version);
       }
       $sliver_type_node->appendChild($disk_image_node);
       }
       }
       if(!$isExogeni && in_array(strtolower($virt_type),$exogeni_virt_types))
       {
       $isExogeni = True;
       }
       }
       //$rspec_node->appendChild($gem_mp_node->cloneNode(True));
       }
       }*/
  $index = 0;
  if(count($all_ams) > 0)
    {
      foreach($all_ams as $this_am)
	{
	  //$gemini_GN_clone = getGeminiNode($rspec_roottag,'GN',$DiskJSON,$this_am,$index);
	  $gemini_GN_clone = getGeminiNode($ddoc,'GN',$DiskJSON,$this_am,$index,$isExogeni);
	  //$rspec_root->appendChild($gemini_GN_clone);
	  $rspec_roottag->appendChild($gemini_GN_clone);
	  //$ddoc->appendChild($gemini_GN_clone);
	  $index = $index + 1;
	}
    }
  //error_log("The submitted rspec is ".$ddoc->saveXML());
  return trim($ddoc->saveXML());
}

function remove_gemini($rspec_roottag)
{
  $rspec_root = $rspec_roottag->firstChild;
  $rspec_nodes = $rspec_roottag->getElementsByTagNameNS(rspec_namespace, 'node');
  foreach ($rspec_nodes as $rspec_node) 
    {
      if(isGemini($rspec_node))
        {
	  list($rspec_node,$delete_me) = delete_colortag($rspec_node);
	  if($delete_me)
	    {
	      $rspec_roottag->removeChild($rspec_node);
	    }
	}
    }
  return $rspec_roottag;
}

function isGemini($node)
{
  $colors = $node->getElementsByTagNameNS(color_namespace, 'resource_color');
  if($colors->length > 0 )
    {
      if($colors->item(0)->hasAttribute('color') && $colors->item(0)->getAttribute('color') == 'gemini')
        {
	  return True;
        }
    }
  return False;
}

function delete_colortag($node)
{
  $to_delete = False;
  $colors = $node->getElementsByTagNameNS(color_namespace,'resource_color');
  $gem_nodes = $node->getElementsByTagNameNS(gemini_namespace,'node');
  $gem_node = $gem_nodes->item(0);
  if($gem_node->hasAttribute('type') && ($gem_node->getAttribute('type') == 'global_node') )
    {
      $to_delete = True;
    }
  else
    {
      $node->removeChild($colors->item(0));
    }
  //error_log("Need to delete ".$node->getAttribute('client_id'));
  return array($node,$to_delete);
}


function getDiskImageNode($virt_type,$DiskJSON,$gemini_type)
{
  $url = '';
  $version = '';
  $exogeni_virt_types = array('exogeni-m4','xo.small','xo.medium','xo.large','xo.xlarge','m1.small','m1.large','m1.xlarge','c1.medium','c1.xlarge');
  if(in_array(strtolower($virt_type),$exogeni_virt_types))
    {
      $virt_type = 'exogeni';
    }
  switch($virt_type)
    {
      /*case 'emulab-openvz' :
	if($gemini_type == 'MP')
	{
	$url = $DiskJSON['MP_Image_URI'];
	}
	elseif($gemini_type == 'GN')
	{
	$url = $DiskJSON['GN_Image_URI'];
	}
	break;
      */
    case 'emulab-xen' :
	$url = $DiskJSON['Xen_Image_URI'];
	break;
    case 'exogeni' :
      $url = $DiskJSON['exogeni_GN_URI'];
      $version = $DiskJSON['exogeni_GN_version'];
      break;
    }
  return array($url,$version);
}

function getGeminiNode($dom,$type,$DiskJSON = array(),$cm_urn = '',$index = 0,$isExogeni)
{
  $color_res = $dom->createElementNS(color_namespace,'color:resource_color');
  $color_res->setAttribute('color','gemini');
  $color_xml = $dom->createElementNS(color_namespace,'color:xmlblob');
  $color_blob = $dom->createElementNS(color_namespace,'color:blob');
  $gem_node = $dom->createElementNS(gemini_namespace,'gemini:node');

  if($type == 'MP')
    {
      $gem_services = $dom->createElementNS(gemini_namespace,'gemini:services');
      $gem_active = $dom->createElementNS(gemini_namespace,'gemini:active');
      $gem_active->setAttribute('install','yes');
      $gem_active->setAttribute('enable','yes');
      $gem_passive = $dom->createElementNS(gemini_namespace,'gemini:passive');
      $gem_passive->setAttribute('install','yes');
      $gem_passive->setAttribute('enable','yes');
      $gem_services->appendChild($gem_active);
      $gem_services->appendChild($gem_passive);
      $gem_node->appendChild($gem_services);
      $gem_node->setAttribute('type','mp_node');
      $color_blob->appendChild($gem_node);
      $color_xml->appendChild($color_blob);
      $color_res->appendChild($color_xml);

      return $color_res;
    }
  else
    {
      $image = '';
      $version = '';
      $gem_node->setAttribute('type','global_node');
      $node = $dom->createElementNS(rspec_namespace,'node');
      $gem_monitor = $dom->createElementNS(gemini_namespace,'gemini:monitor_urn');
      $emulab_public_ip = $dom->createElementNS(emulab_namespace,'emulab:routable_control_ip');
      $node->appendChild($emulab_public_ip);
      $gem_monitor->setAttribute('name','');
      $node->setAttribute('client_id','GDGN'.$index);
      $node->setAttribute('exclusive','false');
      if($cm_urn != 'unbound')
        {
	  $node->setAttribute('component_manager_id',$cm_urn);
        }
      $sliver_type = $dom->createElementNS(rspec_namespace,'sliver_type');
      if(strpos($cm_urn,'exogeni.net') > 0 || $isExogeni)
	{
	  $sliver_type->setAttribute('name','xo.small');
	  list($image,$version) = getDiskImageNode('xo.small',$DiskJSON,'GN');
	}
      //elseif($cm_urn == 'urn:publicid:IDN+utahddc.geniracks.net+authority+cm')
      else
	{
	  $sliver_type->setAttribute('name','emulab-xen');
	  list($image,$version) = getDiskImageNode('emulab-xen',$DiskJSON,'GN');
	}
      //else
      //{
      //            $sliver_type->setAttribute('name','emulab-openvz');
      //list($image,$version) = getDiskImageNode('emulab-openvz',$DiskJSON,'GN');
      //}
      if($image != '')
        {
	  $disk = $dom->createElementNS(rspec_namespace,'disk_image');
	  $disk->setAttribute('name',$image);
	  if($version != '')
            {
	      $disk->setAttribute('version',$version);
            }
	  $sliver_type->appendChild($disk);
        }
      $gem_node->appendChild($gem_monitor);
      $color_blob->appendChild($gem_node);
      $color_xml->appendChild($color_blob);
      $color_res->appendChild($color_xml);
      $node->appendChild($sliver_type);
      $node->appendChild($color_res);

      return $node;
    }
}

function bind_rspec_to_am($rspec_string,$am_replacements)
{
  $rspec_roottag = DOMDocument::loadXML($rspec_string);
  foreach ($rspec_roottag->getElementsByTagNameNS(rspec_namespace, 'node') as $rspec_node) 
    {
      if($rspec_node->hasAttribute('component_manager_id'))
	{
	  $new_am = $am_replacements[$rspec_node->getAttribute('component_manager_id')];
	  if(isset($new_am))
	    {
	      $rspec_node->setAttribute('component_manager_id',$new_am);
	      foreach ($rspec_node->getElementsByTagNameNS(gemini_namespace,'monitor_urn') as $gem_node) 
		{
		  if($gem_node->hasAttribute('name'))
		    {
		      $gem_node->setAttribute('name',$new_am);
		    }
		}
	    }
	}
    }
  unset($new_am);
  foreach ($rspec_roottag->getElementsByTagNameNS(rspec_namespace, 'link') as $link_node) 
    {
      foreach ($link_node->getElementsByTagNameNS(rspec_namespace, 'component_manager') as $cm_node) 
	{
	  if($cm_node->hasAttribute('name'))
	    {
	      $new_am = $am_replacements[$cm_node->getAttribute('name')];
	      if(isset($new_am))
		{
		  $cm_node->setAttribute('name',$new_am);
		}
	    }
	}

    }
  return trim($rspec_roottag->saveXML());
}
?>
