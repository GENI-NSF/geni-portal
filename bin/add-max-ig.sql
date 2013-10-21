-- -----------------------------------------------------------------
-- Create the entry for the MAX InstaGENI aggregate:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-max-ig.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://instageni.maxgigapop.net:12369/protogeni/xmlrpc/am/2.0'
      -- CERT
     '/usr/share/geni-ch/sr/certs/max-ig-cm.pem',
      -- NAME
     'MAX InstaGENI',
      -- DESCRIPTION
     'MAX InstaGENI Rack',
      -- URN
     'urn:publicid:IDN+instageni.maxgigapop.net+authority+cm'
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
     '/usr/share/geni-ch/sr/certs/max-ig-boss.pem',
      -- NAME
     '',
      -- DESCRIPTION
     'MAX InstaGENI CA',
      -- URN
     ''
    );
