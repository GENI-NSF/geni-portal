-- -----------------------------------------------------------------
-- Create the entry for the Cornell InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-cornell-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.geni.it.cornell.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/cornell-ig-of.pem',
      -- NAME
     'Cornell InstaGENI OpenFlow',
      -- DESCRIPTION
     'Cornell InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.geni.it.cornell.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/cornell-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Cornell InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
