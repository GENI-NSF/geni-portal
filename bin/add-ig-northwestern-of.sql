-- -----------------------------------------------------------------
-- Create the entry for InstaGENI Utah FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f bin/add-ig-utah-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://foam.instageni.northwestern.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/ig-nw-of.pem',
      -- NAME
     'Northwestern InstaGENI OpenFlow',
      -- DESCRIPTION
     'InstaGENI Northwestern Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.northwestern.edu+authority+am'
    );

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: 7 = CA
      7,
      -- URL
     '',
      -- CERT (self signed)
     '/usr/share/geni-ch/sr/certs/ig-nw-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'InstaGENI Northwestern FOAM cert signer (self)',
      -- URN
     ''
    );
