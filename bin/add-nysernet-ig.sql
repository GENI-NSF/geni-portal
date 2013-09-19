-- -----------------------------------------------------------------
-- Create the entry for the NYSERNet InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-nysernet-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.nysernet.org:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nysernet-ig-cm.pem',
      -- NAME
     'NYSERNet InstaGENI',
      -- DESCRIPTION
     'NYSERNet InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.nysernet.org+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/nysernet-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NYSERNet InstaGENI CA',
      -- URN
     ''
    );
