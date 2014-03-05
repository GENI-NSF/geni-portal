-- -----------------------------------------------------------------
-- Create the entry for Starlight OpenFlow aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-sl-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://sl-geni.northwestern.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/sl-of.pem',
      -- NAME
     'Starlight OpenFlow',
      -- DESCRIPTION
     'Starlight OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:sl-geni.northwestern.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/sl-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Starlight OpenFlow cert signer (self)',
      -- URN
     ''
    );
