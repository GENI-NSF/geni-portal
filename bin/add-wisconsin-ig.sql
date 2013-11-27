-- -----------------------------------------------------------------
-- Create the entry for the Wisconsin InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-wisconsin-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.wisc.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/wisconsin-ig-cm.pem',
      -- NAME
     'Wisconsin InstaGENI',
      -- DESCRIPTION
     'Wisconsin InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.wisc.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/wisconsin-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Wisconsin InstaGENI CA',
      -- URN
     ''
    );
