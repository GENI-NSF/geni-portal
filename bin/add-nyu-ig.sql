-- -----------------------------------------------------------------
-- Create the entry for the NYU InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-nyu-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://genirack.nyu.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nyu-ig-cm.pem',
      -- NAME
     'NYU InstaGENI',
      -- DESCRIPTION
     'NYU InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+genirack.nyu.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/nyu-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NYU InstaGENI CA',
      -- URN
     ''
    );
