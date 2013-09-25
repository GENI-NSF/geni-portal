-- -----------------------------------------------------------------
-- Create the entry for NYSERNet OpenFlow aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-nysernet-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.nysernet.org:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nysernet-of.pem',
      -- NAME
     'NYSERNet OpenFlow',
      -- DESCRIPTION
     'NYSERNet OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.nysernet.org+authority+am'
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
     '/usr/share/geni-ch/sr/certs/nysernet-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NYSERNet OpenFlow cert signer (self)',
      -- URN
     ''
    );
