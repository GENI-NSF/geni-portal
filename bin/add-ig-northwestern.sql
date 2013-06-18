-- -----------------------------------------------------------------
-- Create the entry for Northwestern InstaGENI Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f bin/add_exo_nicta.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://boss.instageni.northwestern.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
      '/usr/share/geni-ch/sr/certs/ig-northwestern-cm.pem',
      -- NAME
      'Northwestern InstaGENI',
      -- DESCRIPTION
      'InstaGENI Northwestern Rack',
      -- URN
      'urn:publicid:IDN+instageni.northwestern.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/ig-northwestern-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Northwestern InstaGENI CA',
      -- URN
     ''
    );
