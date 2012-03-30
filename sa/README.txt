
Create a Slice
==============

export GENI_USER=`uuidgen`
export GENI_PROJECT='myproj'
export SLICE_NAME='myslice'
curl --insecure -X PUT https://dagoola.gpolab.bbn.com/sa/sa.php/createslice/$GENI_USER/$GENI_PROJECT/$SLICE_NAME



Thoughts on a REST API
======================

ListSlices: GET https://dagoola.gpolab.bbn.com/sa/slices
   - with client ssl
   - show only the slices the user can see

CreateSlice: PUT https://dagoola.gpolab.bbn.com/sa/slices/$SLICE_ID
   - with signed document containing user, project, etc.

RenewSlice: POST https://dagoola.gpolab.bbn.com/sa/slices/$SLICE_ID
   - with client ssl
   - with new expiration in the POST data

AddUserToSlice: POST https://dagoola.gpolab.bbn.com/sa/slices/$SLICE_ID
   - with client ssl
   - with new user in the POST data

DeleteUserFromSlice: DELETE https://dagoola.gpolab.bbn.com/sa/slices/$SLICE_ID/$USER_ID
   - with client ssl
