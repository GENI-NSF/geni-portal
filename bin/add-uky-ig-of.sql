-- -----------------------------------------------------------------
-- Create the entry for the University of Kentucky InstaGENI FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-uky-ig-of.sql portal
--
-- -----------------------------------------------------------------

urn:publicid:IDN+openflow:foam:foam.lan.sdn.uky.edu+authority+am


insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.lan.sdn.uky.edu:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/uky-ig-of.pem',
      -- NAME
     'UKY InstaGENI OpenFlow',
      -- DESCRIPTION
     'University of Kentucky InstaGENI Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.lan.sdn.uky.edu+authority+am'
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
     '/usr/share/geni-ch/sr/certs/uky-ig-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'Kentucky InstaGENI FOAM cert signer (self)',
      -- URN
     ''
    );
