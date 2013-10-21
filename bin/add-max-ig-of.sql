-- -----------------------------------------------------------------
-- Create the entry for the MAX InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-max-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.max.org:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/max-ig-of.pem',
      -- NAME
     'MAX InstaGENI OpenFlow',
      -- DESCRIPTION
     'MAX InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.max.org+authority+am'
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
     '/usr/share/geni-ch/sr/certs/max-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'MAX InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
