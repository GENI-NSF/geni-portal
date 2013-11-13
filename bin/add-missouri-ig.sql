-- -----------------------------------------------------------------
-- Create the entry for the University of Missouri-Columbia
--   InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-missouri-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.rnet.missouri.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/missouri-ig-cm.pem',
      -- NAME
     'Missouri InstaGENI',
      -- DESCRIPTION
     'Missouri InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.rnet.missouri.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/missouri-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Missouri InstaGENI CA',
      -- URN
     ''
    );
