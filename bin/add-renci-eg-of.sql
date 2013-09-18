-- -----------------------------------------------------------------
-- Create the entry for the RENCI ExoGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-renci-eg-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://rci-hn.exogeni.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/renci-eg-of.pem',
      -- NAME
     'RENCI ExoGENI OpenFlow',
      -- DESCRIPTION
     'RENCI ExoGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:rci-hn.exogeni.gpolab.bbn.com+authority+am'
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
     '/usr/share/geni-ch/sr/certs/renci-eg-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'RENCI ExoGENI FOAM cert signer (self)',
      -- URN
     ''
    );
