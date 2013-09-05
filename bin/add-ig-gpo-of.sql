-- -----------------------------------------------------------------
-- Create the entry for InstaGENI GPO FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ig-gpo-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.instageni.gpolab.bbn.com:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/ig-gpo-of.pem',
      -- NAME
     'GPO InstaGENI OpenFlow',
      -- DESCRIPTION
     'InstaGENI GPO Rack OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.instageni.gpolab.bbn.com+authority+am'
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
     '/usr/share/geni-ch/sr/certs/ig-gpo-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'InstaGENI GPO FOAM cert signer (self)',
      -- URN
     ''
    );
