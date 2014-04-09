-- -----------------------------------------------------------------
-- Create the entry for the Kansas InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-kansas-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.ku.gpeni.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/kansas-ig-of.pem',
      -- NAME
     'Kansas InstaGENI OpenFlow',
      -- DESCRIPTION
     'Kansas InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.ku.gpeni.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/kansas-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kansas InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
