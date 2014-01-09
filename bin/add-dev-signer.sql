-- -----------------------------------------------------------------
-- Create the entry for the signer of the dev servers
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-dev-signer.sql portal
--
-- -----------------------------------------------------------------
insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: 7 = CA
      7,
      -- URL
     '',
      -- CERT (self signed)
     '/etc/ssl/certs/ca-gpolab.crt',
      -- NAME
     '',
      -- DESCRIPTION
     'GPO Dev server CA (for Flack)',
      -- URN
     ''
    );
