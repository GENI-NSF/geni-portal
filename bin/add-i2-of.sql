-- -----------------------------------------------------------------
-- Create the entry for I2 OpenFlow aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-i2-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://foam.net.internet2.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/i2-of.pem',
      -- NAME
     'Internet2 OpenFlow',
      -- DESCRIPTION
     'Internet2 OpenFlow',
      -- URN
     'urn:publicid:IDN+ion.internet2.edu+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/i2-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Internet2 OpenFlow cert signer (self)',
      -- URN
     ''
    );
