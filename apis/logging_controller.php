<?php

namespace Logging_Service;

//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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


/**
 * The GENI Clearinginghouse Logging api allows clients
 * to log events with particular tagging (dictionaries of name/value pairs)
 * and query for logged events matching those name/value pairs
 * <br><br>
 * There is one write interface:
<ul>
<li>   log_event($event_time, $user_id, $contexts, $message) </li>
</ul>
 * <br><br>
 * There are two read interface:
<ul>
<li>   get_log_entries_by_author($event_time, $user_id) </li>
<li>   get_log_entries_by_attributes($event_time, $user_id, $attribute_sets) </li>
</ul>
 */
class Logging_Service {

/**
 * Log an event and store it in the logging service archive for future query
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" name of this method ("log_event")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"event_time" : time of logged event</li>
   <li>"user_id" : ID of user logging event</li>
   <li>"attributes" : dictionary of name/value pairs to be associated with event</li>
  "<li> message" : text message of logged event</li>
</ul>
 * @return boolean Success/Failure
 *   
 */
function log_event($args_dict)
{
}

/**
 * Return list of logged events written by given user
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" name of this method ("get_log_entries_by_author")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"event_time" : time of logged event</li>
   <li>"user_id" : ID of user logging event</li>
</ul>
 * @return array List of log events (time, user_id, context_type, context_id, message) written by given user
 *   
 */
function get_log_entries_by_author($args_dict)
{
}

/**
 * Return list of logged events matching any of a list of attribute sets
 *   (that is, it is an "OR" of "ANDS" of a dictionary of name/value pairs).
 * 
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" name of this method ("get_log_entries_by_attributes")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"event_time" : time of logged event</li>
   <li>"attribute_sets" : List of dictionaries (name/value pairs) for which<
*      if any one is completely matched, the entry is returned. </li>
</ul>
 * @return array List of log events (time, user_id, context_type, context_id, message) matching given list of attribute dictionaries
 *   
 */
function get_log_entries_by_attributes($args_dict)
{
}

}

?>
