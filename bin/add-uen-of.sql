-- -----------------------------------------------------------------
-- Create the entry for Utah Education Network (UEN) OpenFlow aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-uen-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://foamyflow.chpc.utah.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/uen-of.pem',
      -- NAME
     'UEN OpenFlow',
      -- DESCRIPTION
     'Utah Education Network OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foamyflow.chpc.utah.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/uen-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'UEN OpenFlow cert signer (self)',
      -- URN
     ''
    );
