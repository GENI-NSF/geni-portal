-- -----------------------------------------------------------------
-- Create the entry for SoX OpenFlow aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-sox-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.sox.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/sox-of.pem',
      -- NAME
     'SoX OpenFlow',
      -- DESCRIPTION
     'SoX OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.sox.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/sox-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'SoX OpenFlow cert signer (self)',
      -- URN
     ''
    );
