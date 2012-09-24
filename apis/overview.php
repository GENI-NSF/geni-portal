<?php
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

namespace Overview;

/**
 * This documents the GENI Clearinghouse API
 * essentials: how methods are invoked, the return values and the
 * authorization and validation of the calls.
 */
class Overview
{

/**
 * The GENI Clearinghouse API consists of a series of services provided
 * by a series of service providers, namely:
<ul>
<li>Authorization Service: Provides services for authorizing particular actions  by particular users in certain contexts based on their identity, roles and privileges </li>
<li>Logging Service: Provides services to log and retrieve tagged information about critical events, e.g. aggregate interactions, slice, member, project modifications, etc.</li>
<li>Member Authority: Provides services regarding membership of GENI experimenters including their roles, privileges, account status, credentials, key materials. Note: it is from the Member Authority that experimenters get a GENI account and credentials for interacting with other services.  </li>
<li>Project Authority: Provides services regarding management of projects and project membership </li>
<li>Service Registry: Provides lists of service providers (of the kinds described here) registered within the GENI Clearinghouse </li>
<li>Slice Authority: Provides services regarding management of slices and slice membership and credentials </li>
</ul>
*
* A client of a GENI CH API queries the Service Registry for service providers of particular types. The Service Registry is accessed by a known static URL.
* The client then forms a message, described under 'Message_Structure', sends it via HTTPS to the given server and receives a response, described under 'Return_Structure'.
 */
function Service_Flow()
{
}

 /**
  * All CH API calls are fundamentally posting S/MIME messages to the appropriate
  * service URL.
 * The message should contain a dictionary consisting of key/value pairs. 
 * This pair is mandatory:
 <ul>
 <li>"operation" : the name of the API method to be invoked</li>
</ul>
* The user then adds any name/value pairs required for that particular method
* invocation.
* <br><br>
* For example, to invoke the 'lookup_slice' method, 
* the user creates a dictionary with the keys 'operation'=>'lookup_slice', 
* 'slice_id'=> $slice_id. 
* <br><br>
* That dictionary is put in an S/MIME message signed 
* with the private key of the requestor, JSON encoded  and posted via HTTPS to 
* a 'slice authority' server whose URL 
* is found in the Clearinghouse Service Registry.
* <br><br>
* On the server,
* the message signature is retrieved and the public key of the user
* is used to validate the signature of the message. 
* <br> <br>
* If validated, the message is JSON decoded and the argument dictionary is extracted.
* An authorization step is then invoked 
* to determine if the given user has the privilege to invoke the given
* method, possibly in the particular (e.g. slice or project) context.
* <br><br>
* If the message is not validated, or the invocation is not authorized, 
* a response is generated as an error message 
* indicating that reason. 
*
* <br><br>If the invocation is authorized, the 'operation' field 
* in the dictionary of the message is used to dispatch the message appropriately.
* The service method then receives the full dictionary as given in the message, 
* and the function generates a response as documented in these API pages. 
* <br><br>
* The response is then JSON encoded and S/MIME signed with the server's private key
*  and returned to the requestor via HTTPS.
 */
function Message_Structure()
{
}

/**
 * The GENI Clearinghouse API contains services whose arguments are often one of these types:
 * <br><br>
 * <b>Context</b>: Methods that call for a "Context" and "ContextID" 
 * argument reflect the relationship of a principal to a particular object.
 * Specifically, privileges of GENI members may be provided and tested on the granularity of a context.
 * For some context_types, there is a specific context_id required, indicating a context of a particular object (e.g. a specific slice or project). Other context types, as noted, are not applied to a particular context_id. These are the context_types recognized by GENI:
<ul>
<li>PROJECT=1: Refers to a specific project and takes a context_id (i.e. the project's UUID) </li>
<li>SLICE=2: Refers to a specific slice and takes a context_id (i.e. the slice's UUID) </li>
<li>RESOURCE=3: Refers to the ability to take actions that manage resources (e.g. creating a project) </li>
<li>SERVICE=4: Refers to the ability to change the set of services provided by the Clearinghouse (those in the Service Registry, e.g.) </li>
<li>MEMBER=5: Refers to the abilitiy to change the account information about GENI members </li>
</ul>
*<br><br>
 * <b>Role_Types</b>: Calls specifying a 'role_type' require an indication of the role a given member plays within a group (e.g. a slice or project). 
 * Privileges are often allotted to members based on their role within a group.
 * The set of roles recognized in the GENI CH API are:
<ul>
<li>LEAD=1: The lead or owner of a project or slice. There can be only one of these per project/slice. They are the person responsible and principal point-of-contact regarding all activity on that project or slice </li>
<li>ADMIN=2: A person with privileges to change attributes or privileges of other members of a group, but not the lead. </li>
<li>MEMBER=3: A normal member of a group with privileges to normal (typically read and write) operations on entities managed by that group. </li>
<li>AUDITOR=4: A member of the group with read-only privileges. </li>
</ul>
*<br><br>
* <b>Attributes</b>: Many objects are tagged with dictionaries of name/value pairs for later query (by explicit match on a single attribute, or by "AND" of a dictionary of attributes or by "OR" of "AND" of a list of dictionaries of attributes)
 */
function Argument_Types() 
{
}

/**
 * All method calls from the GENI CH API return a dictionary 
 * representing a 3-tuple of values:
 <ul>
 <li>"code" : the error code, if any (0 if no error) </li>
 <li>"value" : the result value, only valid if "code" is 0. This is the value indicated as the 'return' for all documented functions </li>
 <li>"output" : the error detail (typically a descriptive string) associated with an invocation error (i.e. error is not 0) </li>
</ul>
 */
function Return_Structure()
{
}


}


?>
