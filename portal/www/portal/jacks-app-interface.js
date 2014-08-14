//----------------------------------------------------------------------      
// Copyright (c) 2011-2014 Raytheon BBN Technologies                          
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

// A set of methods for making requests from the Jacks App (JA) to 
// the embedding page (EP).
//
// The JA will have two streams, input and output for communicating with
// the EP.
// 

// JA initiaties:
// The JA will initiate communication by a call to
//     output.trigger(event_name, event_data) 
// and the EP will be triggered by a corresponding call 
//     output.on(event_name, callback)

// EP initiaies:
// The EP will initiate communication by a call to
//     input.trigger(event_name, event_data) and the
// and the JA will be triggered by a corresponding call
//     input.on(event_name, callback)

// The signature of callback functions is 
//     function callback(event_data)

// Event data comes in two forms:
//    request_event (from JA to EP)
//    response_event (from EP to JA)
// 
// The base class of request_event is:
//     am_id : aggregate identifier in the terminology of the EP
//     slice_id : slice identifier in the terminology of the EP
//     client_data : context of client call (for operating on response)

// There are these types of request events, plus additional fields:
//   renew_request_event [expiration_time]
//   delete_request_event
//   status_requaest_event
//   manifest_request_event
//   create_sliver_request_event [rspec, users]

// The base class of the response_event is:
//     code : error_code
//     value : value of response (manifest, status, e.g.) if sucess
//     output : error message if failure
//     client_data : client_data of corresponding request event

function generate_request_event(am_id, slice_id, client_data) {
    event = {am_id:am_id, slice_id:slice_id, client_data:client_data};
    return event;
}

function generate_renew_request_event(am_id, slice_id, client_data, 
				      expiration_time) {
    event = generate_request_event(am_id, slice_id, client_data);
    event.expiration_time = expiration_time;
    return event;
}

function generate_create_sliver_request_event(am_id, slice_id, client_data, 
					     rspec, users) {
    event = generate_request_event(am_id, slice_id, client_data);
    event.rspec = rspec;
    event.users = users;
    return event;
}

function generate_response_event(reqeust_event, code, value, output) {
    event = {request_event.client_data, code:code, value:value, output:output};
    return event;
}

var CREATE_SLIVER_EVENT_TYPE = 'CREATE_SLIVER';
var DELETE_EVENT_TYPE = 'DELETE';
var MANIFEST_EVENT_TYPE = 'MANIFEST';
var RENEW_EVENT_TYPE = 'RENEW';
var STATUS_EVENT_TYPE = 'STATUS';

var jacks_app_input = {};
var jacks_app_output = {};

