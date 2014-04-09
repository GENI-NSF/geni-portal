-- -----------------------------------------------------------------
-- Create the entry for MAX Regional Network aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-max.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://max-myplc.dragon.maxgigapop.net:12346',
      -- CERT
     '/usr/share/geni-ch/sr/certs/max.pem',
      -- NAME
     'MAX Regional Network',
      -- DESCRIPTION
     'Mid-Atlantic Crossroads Regional Network',
      -- URN
     'urn:publicid:IDN+dragon.maxgigapop.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/max.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'MAX cert signer (self)',
      -- URN
     ''
    );
