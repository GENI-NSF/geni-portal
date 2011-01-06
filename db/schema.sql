
-- Clearinghouse Schema

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

DROP TABLE IF EXISTS ch_user;

CREATE TABLE ch_user
(
        id SERIAL,
        eppn VARCHAR,
        name VARCHAR,
        email VARCHAR,
        slice_owner BOOLEAN NOT NULL DEFAULT FALSE
);


DROP TABLE IF EXISTS ch_aggregate;

CREATE TABLE ch_aggregate
(
        id SERIAL,
        url VARCHAR
);

-- reset client_min_messages to default
set client_min_messages='NOTICE';
