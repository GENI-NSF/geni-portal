<!--
 This document can be translated online at:
    http://daringfireball.net/projects/markdown/dingus
-->
JacksApp
========

JacksApp wraps a Jacks canvas with GENI-specific functions. JacksApp
interacts with the embedding page via JavaScript event channels (see
Backbone.js). The events JacksApp sends to the embedding page are
primarily to request GENI AM API invocations on behalf of JacksApp.


Constructor
===========

    JacksApp(jacksElement, statusElement, buttonElement,
             sliceAms, allAms, sliceInfo,
             userInfo, readyCallback)

 * **jacksElement**: (type: string) the id of the HTML element that
   will hold the Jacks canvas
 * **statusElement**: (type: string) the id of the HTML element that
   will hold the JacksApp status
 * **buttonElement**: (type: string) the id of the HTML element that
   will hold the JacksApp buttons
 * **sliceAms**: (type: list of strings) The aggregate manager ids
   that are part of the slice named in `sliceInfo`
 * **allAms**: (type: dictionary mapping am_ids to Objects)
   Information about all aggregate managers (see below)
 * **sliceInfo**: (type: Object) Information about the slice to
   display (see below)
 * **userInfo**: (type: Object) Information about the current user
   (see below)
 * **readyCallback**: a function with signature
   `readyCallback(jacksApp, input, output)` (see below for more information)

allAms
------
The keys in the `allAms` dictionary are tokens representing aggregate
manager ids. The ids themselves are opaque to JacksApp and will be
used in request events to indicate which aggregate manager is the
destination for the corresponding GENI AM API call. As such, they can
be of any type that is a valid key in a JavaScript dictionary.

The values in the `allAms` dictionary are JavaScript Objects with the
following attribute:

 * **name**: A user-viewable name of the aggregate manager

For example:

    var allAms = { "am1": { name: "Utah ProtoGENI" },
                   "am2": { name: "RENCI ExoGENI" }
                 };

sliceAms
--------

A list whose elements are keys found in the `allAms` dictionary.

For example:

    var sliceAms = [ "am1" ];

sliceInfo
---------

A JavaScript Object containing information about the current
slice. The `sliceInfo` must contain at least the following attributes:

 * **slice_id**: (type: any) The id of the slice to be passed in
   request events from JacksApp to the embedding page
 * **slice_urn**: (type: string) The GENI URN of the slice
 * **slice_expiration**: (type: string) The expiration date of the
   slice in **what format?**
 * **slice_name**: (type: string) A user viewable name for the slice

userInfo
--------

A JavaScript Object containing information about the current user. The
`userInfo` must contain at least the following attributes:

 * **user_name**: The user's GENI username


readyCallback
-------------

A function to be called by JacksApp when initialization is
complete and JacksApp is ready to send and receive events. This
function will be called before any events are sent. The readyCallback
will receive the following parameters:

 * **jacksApp**: (type: JavaScript Object) the JacksApp instance
 * **input**: (type: Backbone.Event) the JacksApp input channel for
   sending events to JacksApp
 * **output**: (type: Backbone.Event) the JacksApp output channel for
   receiving events from JacksApp


Constants
=========

JacksApp defines the following constants for use by the embedding
page:

    ADD_EVENT_TYPE
    DELETE_EVENT_TYPE
    MANIFEST_EVENT_TYPE
    RENEW_EVENT_TYPE
    RESTART_EVENT_TYPE
    STATUS_EVENT_TYPE


Events to embedding page
========================

Manifest request
----------------

JacksApp sends a manifest request event to request a manifest rspec for a
given aggregate manager and slice. The event type is
`MANIFEST_EVENT_TYPE`.

The manifest request event from JacksApp will contain the following fields:

 * **name**: the constant `MANIFEST_EVENT_TYPE`
 * **am_id**: a key from the `all_ams` constructor argument
 * **slice_id**: the id contained in `slice_info` constructor argument
 * **callback**: the event channel for the manifest response event
 * **client_data**: an opaque data structure to be passed back in the
   `client_data` of the manifest response event


Events from embedding page
==========================

Response events from the embedding page to JacksApp follow the same
basic structure that includes at least three elements: `code`,
`value`, and `output`. This mimics the structure of responses from
GENI AM API calls and Federation API calls.

 * **code**: (type: integer) Zero to indicate success, or a non-zero
   positive integer to indicate that an error occurred when fulfilling
   the corresponding request event.
 * **value**: (type: any) If `code` is zero, the result of fulfilling
   the corresponding request event. If `code` is non-zero, the value
   of `value` is not defined.
 * **output**: (type: string) If `code` is non-zero, `output` is a
   user-viewable string describing the failure or error that occurred
   while fulfilling the corresponding request event. If `code` is
   zero, the value of `output` is not defined.

Manifest response
-----------------

In response to a manifest request event, the embedding page should
respond with a manifest response event. The event type for a manifest
response event is `MANIFEST_EVENT_TYPE`.

The manifest response event from the embedding page to JacksApp must
contain the following fields:

 * **code**: integer indicating success or failure. Zero is success,
   non-zero positive integers can be used to indicate failure.
 * **value**: if `code` is zero this is a string containing the XML
   manifest rspec. If `code` is non-zero, `value` is undefined.
 * **output**: if `code` is non-zero, this is a user-viewable string
   providing information about the failure. If `code` is zero, this is
   undefined.
 * **am_id**: The `am_id` from the corresponding manifest request
   event
 * **slice_id** The `slice_id` from the corresponding manifest request
   event
 * **client_data**: The `client_data` from the corresponding manifest
   request event


Events to Jacks
=================

Change topology request
-----------------------
JacksApp sends a `change-topology` request event to Jacks with a
manifest rspec to display allocated resources. See the Jacks
documentation for information about the `change-topology` event.


Events from Jacks
=================

Click event
-----------
JacksApp listens for the `click-event` event from Jacks. This event
indicates when a node or link in Jacks has been selected by the
user. See the Jacks documentation for information about the
`click-event` event.


Example
========

    var jaReadyCallback = function(ja, input, output) {
      // ja is the JacksApp instance
      // input is the event channel to send events to JacksApp
      // output is the event channel to receive events from JacksApp
    };

    var jacksApp = new JacksApp('#jacks-pane',
                                '#jacks-status',
                                '#jacks-buttons',
                                jacks_slice_ams,
                                jacks_all_ams,
                                jacks_slice_info,
                                jacks_user_info,
                                jaReadyCallback);
