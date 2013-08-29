-- -----------------------------------------------------------------
-- Create the entry for GPO FOAM aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-gpo-of.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://foam.gpolab.bbn.com:3626/foam/gapi/2',
      -- CERT
     '/usr/share/geni-ch/sr/certs/gpo-of.pem',
      -- NAME
     'GPO OpenFlow',
      -- DESCRIPTION
     'GPO Lab OpenFlow',
      -- URN
     'urn:publicid:IDN+openflow:foam:foam.gpolab.bbn.com+authority+am'
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
     '/usr/share/geni-ch/sr/certs/gpo-of.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'GPO Lab FOAM cert signer (self)',
      -- URN
     ''
    );
