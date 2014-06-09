-- -----------------------------------------------------------------
-- Create the entry for BBN GRAM Starter Rack:
--
-- Execute as:
--
--    psql -U portal -h localhost -f add-gram-bbncam.sql portal
--
-- -----------------------------------------------------------------

insert into service_registry
    (service_type, service_url, service_cert, service_name,
     service_description, service_urn)
  values
    ( -- TYPE: zero = aggregate
      0,
      -- URL
      'https://128.89.118.100:5002',
      -- CERT
      '/usr/share/geni-ch/sr/certs/gr-bbncam-am.pem',
      -- NAME
      'BBN-CAM GRAM (BBN-CAM)',
      -- DESCRIPTION
      'BBN-CAM GRAM Rack (BBN-CAM)',
      -- URN
      'urn:publicid:IDN+bbn-cam-ctrl-1.bbn.com+authority+am'
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
      '/usr/share/geni-ch/sr/certs/gr-bbncam-ch.pem',
      -- NAME
      '',
      -- DESCRIPTION
      'BBN-CAM GRAM Rack cert signer (self)',
      -- URN
     ''
    );
