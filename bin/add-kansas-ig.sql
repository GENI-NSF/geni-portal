-- -----------------------------------------------------------------
-- Create the entry for the Kansas InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-kansas-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.ku.gpeni.net:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/kansas-ig-cm.pem',
      -- NAME
     'Kansas InstaGENI',
      -- DESCRIPTION
     'Kansas InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.ku.gpeni.net+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/kansas-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kansas InstaGENI CA',
      -- URN
     ''
    );
