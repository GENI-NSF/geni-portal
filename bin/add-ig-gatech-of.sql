-- -----------------------------------------------------------------
-- Create the entry for InstaGENI Georgia Tech FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ig-gatech-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://foam.instageni.rnoc.gatech.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/ig-gatech-of.pem',
      -- NAME
     'Georgia Tech InstaGENI OpenFlow',
      -- DESCRIPTION
     'InstaGENI Georgia Tech Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.rnoc.gatech.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/ig-gatech-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'InstaGENI Georgia Tech FOAM cert signer (self)',
      -- URN
     ''
    );
