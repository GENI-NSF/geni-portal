-- -----------------------------------------------------------------
-- Create the entry for the Kettering InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-kettering-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.geni.kettering.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/kettering-ig-of.pem',
      -- NAME
     'Kettering InstaGENI OpenFlow',
      -- DESCRIPTION
     'Kettering InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.geni.kettering.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/kettering-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kettering InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
