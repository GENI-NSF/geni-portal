-- -----------------------------------------------------------------
-- Create the entry for the ION aggregate (MAX based):
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ion-am.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://geni-am.net.internet2.edu:12346',
      -- CERT
     '/usr/share/geni-ch/sr/certs/ion.pem',
      -- NAME
     'Internet2 ION',
      -- DESCRIPTION
     'Internet2 ION - for stitching only',
      -- URN
     'urn:publicid:IDN+ion:internet2:edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/ion.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Internet2 ION AM cert signer (self)',
      -- URN
     ''
    );
