-- -----------------------------------------------------------------
-- Create the entry for Kettering InstaGENI Rack:
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
      'https://boss.geni.kettering.edu:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
      '/usr/share/geni-ch/sr/certs/ig-kettering-cm.pem',
      -- NAME
      'Kettering InstaGENI',
      -- DESCRIPTION
      'InstaGENI Kettering Rack',
      -- URN
      'urn:publicid:IDN+geni.kettering.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/ig-kettering-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kettering InstaGENI CA',
      -- URN
     ''
    );
