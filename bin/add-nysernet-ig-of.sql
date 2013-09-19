-- -----------------------------------------------------------------
-- Create the entry for the NYSERNet InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-nysernet-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.nysernet.org:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nysernet-ig-of.pem',
      -- NAME
     'NYSERNet InstaGENI OpenFlow',
      -- DESCRIPTION
     'NYSERNet InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.nysernet.org+authority+am'
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
     '/usr/share/geni-ch/sr/certs/nysernet-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NYSERNet InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
