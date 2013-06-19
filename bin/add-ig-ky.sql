-- -----------------------------------------------------------------
-- Create the entry for Kentucky InstaGENI Rack:
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
      'https://boss.lan.sdn.uky.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
      '/usr/share/geni-ch/sr/certs/ig-ky-cm.pem',
      -- NAME
      'Kentucky InstaGENI',
      -- DESCRIPTION
      'InstaGENI Kentucky Rack',
      -- URN
      'urn:publicid:IDN+lan.sdn.uky.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/ig-ky-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kentucky InstaGENI CA',
      -- URN
     ''
    );
