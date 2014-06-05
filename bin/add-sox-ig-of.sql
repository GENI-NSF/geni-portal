-- -----------------------------------------------------------------
-- Create the entry for the SoX InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-sox-ig-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.sox.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/sox-ig-of.pem',
      -- NAME
     'SoX InstaGENI OpenFlow',
      -- DESCRIPTION
     'SoX InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.sox.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/sox-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'SoX InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
