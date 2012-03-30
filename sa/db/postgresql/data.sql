
-- ----------------------------------------------------------------------
-- A few fake records to insert into the database
-- ----------------------------------------------------------------------
INSERT INTO service (stype, url, priority)
  values ('credential-store',
          'https://dagoola.gpolab.bbn.com/credstore/cred',
          1);
INSERT INTO service (stype, url, priority)
  values ('credential-store',
          'https://dagoola.gpolab.bbn.com/geni/cs',
          2);
INSERT INTO service (stype, url, priority)
  values ('aggregate-manager',
          'https://localhost:8001/',
          1);
