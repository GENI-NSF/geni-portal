-- -----------------------------------------------------------------
-- Create the entry for the Stanford InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-stanford-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.stanford.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/stanford-ig-cm.pem',
      -- NAME
     'Stanford InstaGENI',
      -- DESCRIPTION
     'Stanford InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.stanford.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/stanford-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Stanford InstaGENI CA',
      -- URN
     ''
    );
