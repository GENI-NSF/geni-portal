-- -----------------------------------------------------------------
-- Create the entry for InstaGENI Georgia Tech FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-ig-gatech-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
     'https://foam.nlr.net:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/nlr-of.pem',
      -- NAME
     'NLR OpenFlow',
      -- DESCRIPTION
     'NLR OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.nlr.net+authority+am'
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
     '/usr/share/geni-ch/sr/certs/nlr-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'NLR OpenFlow cert signer (self)',
      -- URN
     ''
    );
