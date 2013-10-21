-- -----------------------------------------------------------------
-- Create the entry for the Clemson InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-clemson-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.clemson.edu:12369/protogeni/xmlrpc/am/2.0'
      -- CERT
     '/usr/share/geni-ch/sr/certs/clemson-ig-cm.pem',
      -- NAME
     'Clemson InstaGENI',
      -- DESCRIPTION
     'Clemson InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.clemson.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/clemson-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Clemson InstaGENI CA',
      -- URN
     ''
    );
