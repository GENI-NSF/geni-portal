-- -----------------------------------------------------------------
-- Create the entry for the Clemson InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-clemson-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.clemson.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/clemson-ig-of.pem',
      -- NAME
     'Clemson InstaGENI OpenFlow',
      -- DESCRIPTION
     'Clemson InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.clemson.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/clemson-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Clemson InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
