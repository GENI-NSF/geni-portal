-- -----------------------------------------------------------------
-- Create the entry for ExoGENI UH Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f bin/add-exo-uh.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://uh-hn.exogeni.net:11443/orca/xmlrpc',
      -- CERT
     '/usr/share/geni-ch/sr/certs/exo-uh-am.pem',
      -- NAME
     'UH ExoGENI',
      -- DESCRIPTION
     'ExoGENI University of Houston Rack',
      -- URN
     'urn:publicid:IDN+exogeni.net:uhvmsite+authority+am'
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
     '/usr/share/geni-ch/sr/certs/exo-uh-am.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'ExoGENI UH Rack cert signer (self)',
      -- URN
     ''
    );
