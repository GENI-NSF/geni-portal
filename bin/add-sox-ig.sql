-- -----------------------------------------------------------------
-- Create the entry for the SoX InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-sox-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.sox.net:12369/protogeni/xmlrpc/am/2.0',
      -- CERT
     '/usr/share/geni-ch/sr/certs/sox-ig-cm.pem',
      -- NAME
     'SoX InstaGENI',
      -- DESCRIPTION
     'SoX InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.sox.net+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/sox-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'SoX InstaGENI CA',
      -- URN
     ''
    );
