-- -----------------------------------------------------------------
-- Create the entry for InstaGENI Utah DDC FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ig-utahddc-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.utahddc.geniracks.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/ig-utahddc-of.pem',
      -- NAME
     'UtahDDC InstaGENI OpenFlow',
      -- DESCRIPTION
     'InstaGENI Utah DDC Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.utahddc.geniracks.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/ig-utahddc-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'InstaGENI Utah DDC FOAM cert signer (self)',
      -- URN
     ''
    );
