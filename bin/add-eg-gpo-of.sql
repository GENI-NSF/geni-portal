-- -----------------------------------------------------------------
-- Create the entry for ExoGENI GPO FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-eg-gpo-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://bbn-hn.exogeni.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/exo-gpo-of.pem',
      -- NAME
     'GPO ExoGENI OpenFlow',
      -- DESCRIPTION
     'ExoGENI GPO Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:bbn-hn.exogeni.gpolab.bbn.com+authority+am'
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
     '/usr/share/geni-ch/sr/certs/exo-gpo-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'ExoGENI GPO OpenFlow cert signer (self)',
      -- URN
     ''
    );
