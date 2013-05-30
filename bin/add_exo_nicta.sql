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
      -- CERT (none yet for NICTA rack)
     '',
      -- NAME
     'NICTA ExoGENI',
      -- DESCRIPTION
     'ExoGENI NICTA Rack',
      -- URN
     'urn:publicid:IDN+exogeni.net:nictavmsite+authority+am'
    );
