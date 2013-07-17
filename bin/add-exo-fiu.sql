-- -----------------------------------------------------------------
-- Create the entry for ExoGENI FIU Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f bin/add-exo-fiu.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://fiu-hn.exogeni.net:11443/orca/xmlrpc',
      -- CERT
     '/usr/share/geni-ch/sr/certs/exo-fiu-am.pem',
      -- NAME
     'FIU ExoGENI',
      -- DESCRIPTION
     'ExoGENI FIU Rack',
      -- URN
     'urn:publicid:IDN+exogeni.net:fiuvmsite+authority+am'
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
     '/usr/share/geni-ch/sr/certs/exo-fiu-am.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'ExoGENI FIU Rack cert signer (self)',
      -- URN
     ''
    );
