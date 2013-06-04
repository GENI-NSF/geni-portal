-- -----------------------------------------------------------------
-- Create the entry for ExoGENI NICTA Rack:
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
     'https://nicta-hn.exogeni.net:11443/orca/xmlrpc',
      -- CERT
     '/usr/share/geni-ch/sr/certs/exo-nicta-am.pem',
      -- NAME
     'NICTA ExoGENI',
      -- DESCRIPTION
     'ExoGENI NICTA Rack',
      -- URN
     'urn:publicid:IDN+exogeni.net:nictavmsite+authority+am'
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
     '/usr/share/geni-ch/sr/certs/exo-nicta-am.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'ExoGENI NICTA Rack cert signer (self)',
      -- URN
     ''
    );
