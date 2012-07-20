<?php

namespace Overview;

/**
 * This documents the GENI Clearinghouse API
 * essentials: how methods are invoked, the return values and the
 * authorization and validation of the calls.
 */
class Overview
{

 /**
 * All invocations of GENI CH API calls are sent by
 * constructing a dictionary consisting of key/value pairs. 
 * These pairs are mandatory:
 <ul>
 <li>"operation" : the name of the API method to be invoked</li>
 <li>"signer" : the UUID of the user who is invoking (and signing) the request</li>
</ul>
* The user then adds any name/value pairs required for that particular method
* invocation.
* <br><br>
* The message is then S/MIME signed by the private key of the requestor.
* The requestor then sends the message via HTTPS to the URL of the service
* providing the desired method. For example, to invoke the 'lookup_slice' method, the user must send the signed message to a 'slice authority' server whose URL is found in the Clearinghouse Service Registry.
* <br><br>
* The message is then JSON encoded and sent to the server. On the server,
* the message 'signer' field is retrieved and the public key of the user
* is used to validate the signature of the message. 
* <br> 
* An authorization step is then invoked 
* to determine if the given user has the privilege to invoke the given
* method, possibly in the particular (e.g. slice or project) context.
* <br><br>
* If the invocation is not authorized, the user is given an error message indicating that reason. If the invocation is authorized, the method is invoked by passign the full message to the method, who is responsible for unpacking the dictionary argument and computing the response.
* <br><br>
* The response is then JSON encoded and returned to the requestor via HTTPS.
 */
function Message_Structure()
{
}

/**
 * All method calls from the GENI CH API return dictionary 
 * representing a 3-tuple of values:
 <ul>
 <li>"code" : the error code, if any (0 if no error) </li>
 <li>"value" : the result value, only alid if error is 0. This is the value indicated as the 'return' for all documented functions </li>
 <li>"output" : the error detail (typically descriptive string) associated with invocation error (i.e. error is not 0) </li>
</ul>
 */
function Return_Structure()
{
}

}


?>
