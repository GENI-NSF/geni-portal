-- -----------------------------------------------------------------
-- Create the entry for Utah DDC InstaGENI Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ig-utahddc.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://boss.utahddc.geniracks.net:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
      '/usr/share/geni-ch/sr/certs/ig-utahddc-cm.pem',
      -- NAME
      'UtahDDC InstaGENI',
      -- DESCRIPTION
      'InstaGENI Utah DDC Rack',
      -- URN
      'urn:publicid:IDN+utahddc.geniracks.net+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/ig-utahddc-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'InstaGENI Utah DDC CA',
      -- URN
     ''
    );
