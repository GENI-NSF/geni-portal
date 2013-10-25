-- -----------------------------------------------------------------
-- Create the entry for the Illinois InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-illinois-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.illinois.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/illinois-ig-of.pem',
      -- NAME
     'Illinois InstaGENI OpenFlow',
      -- DESCRIPTION
     'Illinois InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.illinois.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/illinois-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Illinois InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
