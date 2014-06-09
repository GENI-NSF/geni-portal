-- -----------------------------------------------------------------
-- Create the entry for BBN GRAM Starter Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-gram-bbn.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://128.89.91.170:5002',
      -- CERT
      '/usr/share/geni-ch/sr/certs/gr-bbn1-am.pem',
      -- NAME
      'BBN GRAM (BOS)',
      -- DESCRIPTION
      'BBN GRAM Starter Rack (BOS)',
      -- URN
      'urn:publicid:IDN+boscontroller.gram.gpolab.bbn.com+authority+am'
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
      '/usr/share/geni-ch/sr/certs/gr-bbn1-ch.pem',
      -- NAME
      '',
      -- DESCRIPTION
      'BBN GRAM Starter Rack cert signer (self)',
      -- URN
     ''
    );
