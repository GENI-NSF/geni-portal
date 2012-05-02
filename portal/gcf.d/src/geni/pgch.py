#----------------------------------------------------------------------
# Copyright (c) 2011 Raytheon BBN Technologies
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
"""
Reference GENI GCF Clearinghouse. Uses SFA Certificate and credential objects.
Run from gcf-ch.py
Will produce signed user credentials from a GID, return a
list of aggregates read from a config file, and create a new Slice Credential.

"""

import datetime
import traceback
import uuid
import os
import json
import urllib2

import dateutil.parser
from SecureXMLRPCServer import SecureXMLRPCServer
import geni.util.cred_util as cred_util
import geni.util.cert_util as cert_util
import geni.util.urn_util as urn_util
import sfa.trust.gid as gid


# Substitute eg "openflow//stanford"
# Be sure this matches init-ca.py:CERT_AUTHORITY 
# This is in publicid format
SLICE_AUTHORITY = "geni//gpo//gcf"

# Credential lifetimes in seconds
# Extend slice lifetimes to actually use the resources
USER_CRED_LIFE = 86400
SLICE_CRED_LIFE = 3600

# Make the max life of a slice 30 days (an arbitrary length).
SLICE_MAX_LIFE_SECS = 30 * 24 * 60 * 60

# The list of Aggregates that this Clearinghouse knows about
# should be defined in the gcf_config file in the am_* properties.
# ListResources will refer the client to these aggregates
# Clearinghouse.runserver currently does the register_aggregate_pair
# calls for each row in that file
# but this should be doable dynamically
# Some sample pairs:
# GPOMYPLC = ('urn:publicid:IDN+plc:gpo1+authority+sa',
#             'http://myplc1.gpolab.bbn.com:12348')
# TESTGCFAM = ('urn:publicid:IDN+geni.net:gpo+authority+gcf', 
#              'https://127.0.0.1:8001') 
# OTHERGPOMYPLC = ('urn:publicid:IDN+plc:gpo+authority+site2',
#                    'http://128.89.81.74:12348')
# ELABINELABAM = ('urn:publicid:IDN+elabinelab.geni.emulab.net',
#                 'https://myboss.elabinelab.geni.emulab.net:443/protogeni/xmlrpc/am')

class PGSAnCHServer(object):
    def __init__(self, delegate, logger):
        self._delegate = delegate
        self.logger = logger

    def GetCredential(self, args=None):
        # all none means return user cred
        # else cred is user cred, id is uuid or urn of object, type=Slice
        #    where omni always uses the urn
        # return is slice credential
        #args: credential, type, uuid, urn
        code = 0
        output = ''
        value = ''
        try:
            value = self._delegate.GetCredential(args)
        except Exception, e:
            output = str(e)
            code = 1 # FIXME: Better codes
            
        return dict(code=code, value=value, output=output)

    def Resolve(self, args):
        # Omni uses this, Flack may not need it

        # ID may be a uuid, hrn, or urn
        #   Omni uses hrn for type=User, urn for type=Slice
        # type is Slice or User
        # args: credential, hrn, urn, uuid, type
        # Return is dict:
#When the type is Slice:
#
#{
#  "urn"  : "URN of the slice",
#  "uuid" : "rfc4122 universally unique identifier",
#  "creator_uuid" : "UUID of the user who created the slice",
#  "creator_urn" : "URN of the user who created the slice",
#  "gid"  : "ProtoGENI Identifier (an x509 certificate)",
#  "component_managers" : "List of CM URNs which are known to contain slivers or tickets in this slice. May be stale"
#}
#When the type is User:
#
#{
#  "uid"  : "login (Emulab) ID of the user.",
#  "hrn"  : "Human Readable Name (HRN)",
#  "uuid" : "rfc4122 universally unique identifier",
#  "email": "registered email address",
#  "gid"  : "ProtoGENI Identifier (an x509 certificate)",
#  "name" : "common name",
#}
        code = 0
        output = ''
        value = ''
        try:
            self.logger.debug("Calling resolve in delegate")
            value = self._delegate.Resolve(args)
        except Exception, e:
            output = str(e)
            code = 1 # FIXME: Better codes
            
        return dict(code=code, value=value, output=output)

    def Register(self, args):
        # Omni uses this, Flack should not for our purposes
        # args are credential, hrn, urn, type
        # cred is user cred, type must be Slice
        # returns slice cred
        code = 0
        output = ''
        value = ''
        try:
            self.logger.debug("Calling register in delegate")
            value = self._delegate.Register(args)
        except Exception, e:
            output = str(e)
            code = 1 # FIXME: Better codes
            
        return dict(code=code, value=value, output=output)

# Skipping Remove, DiscoverResources

    def GetKeys(self, args):
        # cred is user cred
        # return list( of dict(type='ssh', key=$key))
        # args: credential
        code = 0
        output = ''
        value = ''
        try:
            value = self._delegate.GetKeys(args)
        except Exception, e:
            output = str(e)
            code = 1 # FIXME: Better codes
            
        return dict(code=code, value=value, output=output)

# Skipping BindToSlice, RenewSlice, Shutdown, GetVersion
# =====
# CH API:

# Skipping GetCredential, Register, Resolve, Remove, Shutdown

    def ListComponents(self, args):
        # Returns list of CMs (AMs)
        # cred is user cred or slice cred - Omni uses user cred
        # return list( of dict(gid=<cert>, hrn=<hrn>, url=<AM URL>))
        # Matt seems to say hrn is not critical, and can maybe even skip cert
        # args: credential
        code = 0
        output = ''
        value = ''
        try:
            value = self._delegate.GetKeys(args)
        except Exception, e:
            output = str(e)
            code = 1 # FIXME: Better codes
            
        return dict(code=code, value=value, output=output)

# Skipping PostCRL, List, GetVersion

class PGClearinghouse(object):

    def __init__(self, gcf=False):
        self.logger = cred_util.logging.getLogger('gcf-pgch')
        self.slices = {}
        self.aggs = []
        self.gcf=gcf

    def load_aggregates(self):
        """Loads aggregates from the clearinghouse section of the config file.
        
        In the config section there are keys for each am, am_1, am_2, ..., am_n
        
        The value for each key is the urn and url of the aggregate separated by a comma
           
        Returns True if aggregates were loaded, False otherwise.
        """
        
        for (key, val) in self.config['clearinghouse'].items():
            if not key.startswith('am_'):
                continue
            
            (urn,url) = val.split(',')
            urn = urn.strip()
            url = url.strip()
            if not urn:
                self.logger.warn('Empty URN for aggregate %s in gcf_config' % key)
                continue
            
            if not url:
                self.logger.warn('Empty URL for aggregate %s in gcf_config' % key)
                continue
            if urn in [x for (x, _) in self.aggs]:
                self.logger.warn('Duplicate URN %s in gcf_config' % key)
                continue
            
            self.logger.info("Registering AM %s at %s", urn, url)
            self.aggs.append((urn, url))
            
    def loadURLs():
        for (key, val) in self.config['clearinghouse'].items():
            if key.lower() == 'sa_url':
                self.sa_url = val.strip()
                continue
            if key.lower() == 'ma_url':
                self.ma_url = val.strip()
                continue
            if key.lower() == 'sr_url':
                self.sr_url = val.strip()
                continue
        
    def runserver(self, addr, keyfile=None, certfile=None,
                  ca_certs=None, authority=None,
                  user_len=None, slice_len=None, config=None):
        """Run the clearinghouse server."""
        # ca_certs is a dir of several certificates for peering
        # If not supplied just use the certfile as the only trusted root
        self.keyfile = keyfile
        self.certfile = certfile

        self.config = config
        
        # Error check the keyfile, certfile all exist
        if keyfile is None or not os.path.isfile(os.path.expanduser(keyfile)):
            raise Exception("Missing CH key file %s" % keyfile)
        if certfile is None or not os.path.isfile(os.path.expanduser(certfile)):
            raise Exception("Missing CH cert file %s" % certfile)

        if ca_certs is None:
            ca_certs = certfile
            self.logger.info("Using only my CH cert as a trusted root cert")

        self.trusted_root_files = cred_util.CredentialVerifier(ca_certs).root_cert_files
            
        if not os.path.exists(os.path.expanduser(ca_certs)):
            raise Exception("Missing CA cert(s): %s" % ca_certs)

        global SLICE_AUTHORITY, USER_CRED_LIFE, SLICE_CRED_LIFE
        SLICE_AUTHORITY = authority
        USER_CRED_LIFE = int(user_len)
        SLICE_CRED_LIFE = int(slice_len)

        # Load up the aggregates
        self.load_aggregates()
        
        # load up URLs for things we proxy for
        self.loadURLs()

        # This is the arg to _make_server
        ca_certs_onefname = cred_util.CredentialVerifier.getCAsFileFromDir(ca_certs)

        # This is used below by CreateSlice
        self.ca_cert_fnames = []
        if os.path.isfile(os.path.expanduser(ca_certs)):
            self.ca_cert_fnames = [os.path.expanduser(ca_certs)]
        elif os.path.isdir(os.path.expanduser(ca_certs)):
            self.ca_cert_fnames = [os.path.join(os.path.expanduser(ca_certs), name) for name in os.listdir(os.path.expanduser(ca_certs)) if name != cred_util.CredentialVerifier.CATEDCERTSFNAME]

        # Create the xmlrpc server, load the rootkeys and do the ssl thing.
        self._server = self._make_server(addr, keyfile, certfile,
                                         ca_certs_onefname)
        self._server.register_instance(PGSAnCHServer(self, self.logger))
        self.logger.info('GENI PGCH Listening on port %d...' % (addr[1]))
        self._server.serve_forever()

    def _make_server(self, addr, keyfile=None, certfile=None,
                     ca_certs=None):
        """Creates the XML RPC server."""
        # ca_certs is a file of concatenated certs
        return SecureXMLRPCServer(addr, keyfile=keyfile, certfile=certfile,
                                  ca_certs=ca_certs)

    def _naiveUTC(self, dt):
        """Converts dt to a naive datetime in UTC.

        if 'dt' has a timezone then
        convert to UTC
        strip off timezone (make it "naive" in Python parlance)
        """
        if dt.tzinfo:
            tz_utc = dateutil.tz.tzutc()
            dt = dt.astimezone(tz_utc)
            dt = dt.replace(tzinfo=None)
        return dt

    def GetCredential(self, args=None):
        # FIXME: Validate client cert signed by me?
        #args: credential, type, uuid, urn
        credential = None
        if args and args.has_key('credential'):
            credential = args['credential']
        type = None
        if args and args.has_key('type'):
            type = args['type']
        urn = None
        if args and args.has_key('urn'):
            urn = args['urn']
        uuid = None
        if args and args.has_key('uuid'):
            uuid = args['uuid']
        self.logger.debug("In getCred")
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("GetCredential failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to GetCredential. Cant get user GID from SSL client certificate." % exc)
        self.logger.debug("Constructed user_gid")
        if credential is None:
            # return user credential

            if self.gcf:
                return self.CreateUserCredential(self._server.pem_cert)
            else:
                # follow make_user_credential in sa/php/sa_utils?
                raise Exception("Real CH get user cred not implemented")

        if not type or type.lower() != 'slice':
            self.logger.error("Expected type of slice, got %s", type)

        # id is urn or uuid
        if not urn and not uuid:
            raise Exception("Missing ID for slice to get credential for")
        if urn and not urn_util.is_valid_urn(urn):
            self.logger.error("URN not a valid URN: %s", urn)
            # Confirm it is a valid UUID
            raise Exception("Given invalid URN to look up slice %s. FIXME: Look up slice by UUID?" % urn)

        if uuid:
            # FIXME: Check a valid UUID
            # look up by uuid
            raise Exception("Got UUID in GetCredential - unsupported")

        if self.gcf:
            # For now, do this as a createslice
            return self.CreateSlice(urn)
            # FIXME: This must look it up

        # FIXME: Need the slice_id given the urn
        # need the client cert
        # lookup_slice with arg slice_urn
        argsdict=dict(slice_urn=urn)
        slicetriple = None
        try:
            slicetriple = invokeCH(sa_url, 'lookup_slice_by_urn', self.logger, argsdict)
        except Exception, e:
            self.logger.error("Exception doing lookup_slice: %s" % e)
            raise
        sliceval = getValueFromTriple(slicetriple, self.logger, "lookup_slice to get slice cred")
        if not sliceval or not sliceval.has_key('slice_id'):
            self.logger.error("malformed slice value from lookup_slice: %s" % sliceval)
            raise Exception("malformed sliceval from lookup_slice")
        slice_id=sliceval['slice_id']
        self.logger.info("Found slice id %s for urn %s", slice_id, urn)
        argsdict = dict(experimenter_certificate=self._server.pem_cert, slice_id=slice_id)
        res = None
        try:
            res = invokeCH(sa_url, 'get_slice_credential', self.logger, argsdict)
        except Exception, e:
            self.logger.error("Exception doing get_slice_cred: %s" % e)
            raise
        return getValueFromTriple(res, self.logger, "get_slice_credential")

    def Resolve(self, args):
        # args: credential, hrn, urn, uuid, type
        # ID may be a uuid, hrn, or urn
        #   Omni uses hrn for type=User, urn for type=Slice
        # type is Slice or User
        # Return is dict: (see above)

        # FIXME: Validate client cert signed by me?
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("GetCredential failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to GetCredential. Cant get user GID from SSL client certificate." % exc)

        credential = None
        if args and args.has_key('credential'):
            credential = args['credential']
        type = None
        if args and args.has_key('type'):
            type = args['type']
        urn = None
        if args and args.has_key('urn'):
            urn = args['urn']
        hrn = None
        if args and args.has_key('hrn'):
            hrn = args['hrn']
        uuid = None
        if args and args.has_key('uuid'):
            uuid = args['uuid']

        if credential is None:
            raise Exception("Resolve missing credential")

        # FIXME validate credential as user cred

        # confirm type is Slice or User
        if not type:
            self.logger.error("Missing type to Resolve")
            raise Exception("Missing type to Resolve")
        if type.lower() == 'slice':
            # type is slice

            # FIXME: Handle slice uuid as input here

            if not urn or not urn_util.is_valid_urn(urn):
                self.logger.error("Didnt get a valid URN for slice in resolve: %s", urn)
                if uuid:
                    self.logger.error("Got a UUID instead? %s" % uuid)
                raise Exception("Didnt get a valid URN for slice in resolve: %s", urn)

            # For type slice, error means no known slice. Else the slice exists.
            if self.slices.has_key(urn):
                return dict(urn=urn, uuid='', creator_uuid='', creator_urn='', gid='', component_managers=list())
#{
#  "urn"  : "URN of the slice",
#  "uuid" : "rfc4122 universally unique identifier",
#  "creator_uuid" : "UUID of the user who created the slice",
#  "creator_urn" : "URN of the user who created the slice",
#  "gid"  : "ProtoGENI Identifier (an x509 certificate)",
#  "component_managers" : "List of CM URNs which are known to contain slivers or tickets in this slice. May be stale"
#}
            else:
                raise Exception("No such slice locally")

        elif type.lower() == 'user':
            # type is user
            # This should be an hrn. Maybe handle others?
            # return a list of slices
            # FIXME
            return dict(slices=list())
        else:
            self.logger.error("Unknown type %s" % type)
            raise Exception("Unknown type %s" % type)

    def Register(self, args):
        # Omni uses this, Flack should not for our purposes
        # args are credential, hrn, urn, type
        # cred is user cred, type must be Slice
        # returns slice cred
        # FIXME: Validate client cert signed by me?
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("GetCredential failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to GetCredential. Cant get user GID from SSL client certificate." % exc)

        credential = None
        if args and args.has_key('credential'):
            credential = args['credential']
        type = None
        if args and args.has_key('type'):
            type = args['type']
        urn = None
        if args and args.has_key('urn'):
            urn = args['urn']
        hrn = None
        if args and args.has_key('hrn'):
            hrn = args['hrn']

        if credential is None:
            raise Exception("Register missing credential")

        # FIXME validate credential as user cred

        # confirm type is Slice or User
        if not type:
            self.logger.error("Missing type to Resolve")
            raise Exception("Missing type to Resolve")
        if not type.lower() == 'slice':
            self.logger.error("Tried to register type %s" % type)
            raise Exception("Can't register non slice %s" % type)

        if not urn and hrn is not None:
            # FIXME: Convert hrn to urn
            raise Exception("hrn to Register not supported")

        return self.CreateSlice(urn)

    def GetKeys(self, args):
        credential = None
        if args and args.has_key('credential'):
            credential = args['credential']
        # cred is user cred
        # return list( of dict(type='ssh', key=$key))

        # FIXME: Validate client cert signed by me?
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("GetCredential failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to GetCredential. Cant get user GID from SSL client certificate." % exc)

        if credential is None:
            raise Exception("Resolve missing credential")

        # FIXME validate credential as user cred
        ret = list()
        ret.append(dict(type='ssh', key='some SSH public key'))
        return ret

    def ListComponents(self, args):
        credential = None
        if args and args.has_key('credential'):
            credential = args['credential']
        # Returns list of CMs (AMs)
        # cred is user cred or slice cred - Omni uses user cred
        # return list( of dict(gid=<cert>, hrn=<hrn>, url=<AM URL>))
        # Matt seems to say hrn is not critical, and can maybe even skip cert
        # FIXME: Implement using ListAggregates

        # FIXME: Validate client cert signed by me?
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("GetCredential failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to GetCredential. Cant get user GID from SSL client certificate." % exc)

        if credential is None:
            raise Exception("Resolve missing credential")

        # FIXME validate credential as user cred
        ret = list()
        for (urn, url) in self.aggs:
            # FIXME: convert urn to hrn
            hrn = urn
            ret.append(dict(gid='amcert', hrn=hrn, url=url))
        return ret

    def GetVersion(self):
        self.logger.info("Called GetVersion")
        version = dict()
        version['gcf-pgch_api'] = 1
        return version

    # FIXME: Change that URN to be a name and non-optional
    # Currently gcf-test.py doesnt supply it, and
    # Omni takes a name and constructs a URN to supply
    def CreateSlice(self, urn_req = None):
        self.logger.info("Called CreateSlice URN REQ %r" % urn_req)
        slice_gid = None

        if urn_req and self.slices.has_key(urn_req):
            # If the Slice has expired, treat this as
            # a request to renew
            slice_cred = self.slices[urn_req]
            slice_exp = self._naiveUTC(slice_cred.expiration)
            if slice_exp <= datetime.datetime.utcnow():
                # Need to renew this slice
                self.logger.info("CreateSlice on %r found existing cred that expired at %r - will renew", urn_req, slice_exp)
                slice_gid = slice_cred.get_gid_object()
            else:
                self.logger.debug("Slice cred is still valid at %r until %r - return it", datetime.datetime.utcnow(), slice_exp)
                return slice_cred.save_to_string()
        
        # First ensure we have a slice_urn
        if urn_req:
            # FIXME: Validate urn_req has the right form
            # to be issued by this CH
            if not urn_util.is_valid_urn(urn_req):
                # FIXME: make sure it isnt empty, etc...
                urn = urn_util.publicid_to_urn(urn_req)
            else:
                urn = urn_req
        else:
            # Generate a unique URN for the slice
            # based on this CH location and a UUID

            # Where was the slice created?
            (ipaddr, port) = self._server.socket._sock.getsockname()
            # FIXME: Get public_id start from a properties file
            # Create a unique name for the slice based on uuid
            slice_name = uuid.uuid4().__str__()[4:12]
            public_id = 'IDN %s slice %s//%s:%d' % (SLICE_AUTHORITY, slice_name,
                                                                   ipaddr,
                                                                   port)
            # this func adds the urn:publicid:
            # and converts spaces to +'s, and // to :
            urn = urn_util.publicid_to_urn(public_id)

        # Now create a GID for the slice (signed credential)
        if slice_gid is None:
            try:
                slice_gid = cert_util.create_cert(urn, self.keyfile, self.certfile)[0]
            except Exception, exc:
                self.logger.error("Cant create slice gid for slice urn %s: %s", urn, traceback.format_exc())
                raise Exception("Failed to create slice %s. Cant create slice gid" % urn, exc)

        # Now get the user GID which will have permissions on this slice.
        # Get client x509 cert from the SSL connection
        # It doesnt have the chain but should be signed
        # by this CHs cert, which should also be a trusted
        # root at any federated AM. So everyone can verify it as is.
        # Note that if a user from a different CH (installed
        # as trusted by this CH for some reason) called this method,
        # that user would be used here - and can still get a valid slice
        try:
            user_gid = gid.GID(string=self._server.pem_cert)
        except Exception, exc:
            self.logger.error("CreateSlice failed to create user_gid from SSL client cert: %s", traceback.format_exc())
            raise Exception("Failed to create slice %s. Cant get user GID from SSL client certificate." % urn, exc)

        # OK have a user_gid so can get a slice credential
        # authorizing this user on the slice
        try:
            expiration = datetime.datetime.utcnow() + datetime.timedelta(seconds=SLICE_CRED_LIFE)
            # add delegatable=True to make this slice delegatable
            slice_cred = self.create_slice_credential(user_gid,
                                                      slice_gid,
                                                      expiration, delegatable=True)
        except Exception, exc:
            self.logger.error('CreateSlice failed to get slice credential for user %r, slice %r: %s', user_gid.get_hrn(), slice_gid.get_hrn(), traceback.format_exc())
            raise Exception('CreateSlice failed to get slice credential for user %r, slice %r' % (user_gid.get_hrn(), slice_gid.get_hrn()), exc)
        self.logger.info('Created slice %r' % (urn))
        
        self.slices[urn] = slice_cred
        
        return slice_cred.save_to_string()
    
    def RenewSlice(self, slice_urn, expire_str):
        self.logger.info("Called RenewSlice(%s, %s)", slice_urn, expire_str)
        if not self.slices.has_key(slice_urn):
            self.logger.warning('Slice %s was not found', slice_urn)
            return False
        try:
            in_expiration = dateutil.parser.parse(expire_str)
            in_expiration = cred_util.naiveUTC(in_expiration)
        except:
            self.logger.warning('Unable to parse date "%s"', expire_str)
            return False
        # Is requested expiration valid? It must be in the future,
        # but not too far into the future.
        now = datetime.datetime.utcnow()
        if in_expiration < now:
            self.logger.warning('Expiration "%s" is in the past.', expire_str)
            return False
        duration = in_expiration - now
        max_duration = datetime.timedelta(seconds=SLICE_MAX_LIFE_SECS)
        if duration > max_duration:
            self.logger.warning('Expiration %s is too far in the future.',
                                expire_str)
            return False
        # Everything checks out, so create a new slice cred and tuck it away.
        user_gid = gid.GID(string=self._server.pem_cert)
        slice_cred = self.slices[slice_urn]
        slice_gid = slice_cred.get_gid_object()
        # if original slice' privileges were all delegatable,
        # make all the privs here delegatable
        # Of course, the correct thing would be to do it priv by priv...
        dgatable = False
        if slice_cred.get_privileges().get_all_delegate():
            dgatable = True
        slice_cred = self.create_slice_credential(user_gid, slice_gid,
                                                  in_expiration, delegatable=dgatable)
        self.logger.info("Slice %s renewed to %s", slice_urn, expire_str)
        self.slices[slice_urn] = slice_cred
        return True

    def DeleteSlice(self, urn_req):
        self.logger.info("Called DeleteSlice %r" % urn_req)
        if self.slices.has_key(urn_req):
            self.slices.pop(urn_req)
            self.logger.info("Deleted slice")
            return True
        self.logger.info('Slice was not found')
        # Slice not found!
        # FIXME: Raise an error so client knows why this failed?
        return False

    def ListAggregates(self):
        self.logger.info("Called ListAggregates")
        # TODO: Allow dynamic registration of aggregates
        return self.aggs
    
    def CreateUserCredential(self, user_gid):
        '''Return string representation of a user credential
        issued by this CH with caller/object this user_gid (string)
        with user privileges'''
        # FIXME: Validate arg - non empty, my user
        user_gid = gid.GID(string=user_gid)
        self.logger.info("Called CreateUserCredential for GID %s" % user_gid.get_hrn())
        expiration = datetime.datetime.utcnow() + datetime.timedelta(seconds=USER_CRED_LIFE)
        try:
            ucred = cred_util.create_credential(user_gid, user_gid, expiration, 'user', self.keyfile, self.certfile, self.trusted_root_files)
        except Exception, exc:
            self.logger.error("Failed to create user credential for %s: %s", user_gid.get_hrn(), traceback.format_exc())
            raise Exception("Failed to create user credential for %s" % user_gid.get_hrn(), exc)
        return ucred.save_to_string()
    
    def create_slice_credential(self, user_gid, slice_gid, expiration, delegatable=False):
        '''Create a Slice credential object for this user_gid (object) on given slice gid (object)'''
        # FIXME: Validate the user_gid and slice_gid
        # are my user and slice
        return cred_util.create_credential(user_gid, slice_gid, expiration, 'slice', self.keyfile, self.certfile, self.trusted_root_files, delegatable)

# Force the unicode strings python creates to be ascii
def _decode_list(data):
    rv = []
    for item in data:
        if isinstance(item, unicode):
            item = item.encode('utf-8')
        elif isinstance(item, list):
            item = _decode_list(item)
        elif isinstance(item, dict):
            item = _decode_dict(item)
        rv.append(item)
    return rv

# Force the unicode strings python creates to be ascii
def _decode_dict(data):
    rv = {}
    for key, value in data.iteritems():
        if isinstance(key, unicode):
           key = key.encode('utf-8')
        if isinstance(value, unicode):
           value = value.encode('utf-8')
        elif isinstance(value, list):
           value = _decode_list(value)
        elif isinstance(value, dict):
           value = _decode_dict(value)
        rv[key] = value
    return rv

def invokeCH(url, operation, logger, argsdict, mycert=None, mykey=None):
    # Invoke the real CH
    # for now, this should json encode the args and operation, do an http put
    # entry 1 in dict is named operation and is the operation, rest are args
    # json decode result, getting a dict
    # return the result
    if not operation or operation.strip() == '':
        raise Exception("missing operation")
    if not url or url.strip() == '':
        raise Exception("missing url")
    if not argsdict:
        raise Exception("missing argsdict")

    # Put operation in front of argsdict
    toencode = dict(operation=operation)
    for (k,v) in argsdict.items():
        toencode[k]=v
    argstr = json.dumps(toencode)

    logger.info("Will do put of %s", argstr)
    print ("Doing  put of %s" % argstr)

    # now http put this, grab result into putres
    # This is the most trivial put client. This appears to be harder to do / less common than you would expect.
    # See httplib2 for an alternative approach using another library.
    # This approach isn't very robust, may have other issues
    opener = urllib2.build_opener(urllib2.HTTPSHandler)
    req = urllib2.Request(url, data=argstr)
    req.add_header('Content-Type', 'application/json')
    req.get_method = lambda: 'PUT'

    putres = None
    putresHandle = None
    try:
        putresHandle = opener.open(req)
    except Exception, e:
        logger.error("invokeCH failed to open conn to %s: %s", url, e)
        return None

    if putresHandle:
        try:
            putres=putresHandle.read()
        except Exception, e:
            logger.error("invokeCH failed to read result of put to %s: %s", url, e)
            return None

    resdict = None
    if putres:
        logger.debug("invokeCH Got result of %s" % putres)
        resdict = json.loads(putres, encoding='ascii', object_hook=_decode_dict)
    
    # FIXME: Check for code, value, output keys?
    return resdict

def getValueFromTriple(triple, logger, opname):
    if not triple:
        self.logger.error("Got empty result triple after %s" % opname)
        raise Exception("Return struct was null for %s" % opname)
    if not triple.has_key('value'):
        self.logger.error("Malformed return from %s: %s" % (opname, triple))
        raise Exception("malformed return from %s: %s" % (opname, triple))
    return triple['value']
