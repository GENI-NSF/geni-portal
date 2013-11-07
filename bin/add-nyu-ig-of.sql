-- -----------------------------------------------------------------
-- Create the entry for the NYU InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-nyu-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.genirack.nyu.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nyu-ig-of.pem',
      -- NAME
     'NYU InstaGENI OpenFlow',
      -- DESCRIPTION
     'NYU InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.genirack.nyu.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/nyu-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NYU InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
