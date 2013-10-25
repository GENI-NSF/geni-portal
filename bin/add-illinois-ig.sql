-- -----------------------------------------------------------------
-- Create the entry for the Illinois InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-illinois-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.illinois.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/illinois-ig-cm.pem',
      -- NAME
     'Illinois InstaGENI',
      -- DESCRIPTION
     'Illinois InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.illinois.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/illinois-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Illinois InstaGENI CA',
      -- URN
     ''
    );
