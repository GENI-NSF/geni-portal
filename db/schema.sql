
-- Clearinghouse Schema

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

DROP TABLE IF EXISTS ch_person CASCADE;

CREATE TABLE ch_person
(
        id SERIAL PRIMARY KEY,
        name VARCHAR,
        email VARCHAR,
        telephone VARCHAR
);


DROP TABLE IF EXISTS ch_user;

CREATE TABLE ch_user
(
        id SERIAL PRIMARY KEY,
        eppn VARCHAR,
        person_id INTEGER,
        slice_owner BOOLEAN NOT NULL DEFAULT FALSE,

        CONSTRAINT ch_user_person_id
                FOREIGN KEY (person_id)
                REFERENCES ch_person(id)
);


DROP TABLE IF EXISTS ch_aggregate;

CREATE TABLE ch_aggregate
(
        id SERIAL PRIMARY KEY,
        url VARCHAR,
        latitude FLOAT,
        longitude FLOAT,
        organization VARCHAR,
        nickname VARCHAR,
        admin_id INTEGER,
        tech_id INTEGER,

        CONSTRAINT ch_aggregate_admin_id
                FOREIGN KEY (admin_id)
                REFERENCES ch_person(id),
        CONSTRAINT ch_aggregate_tech_id
                FOREIGN KEY (tech_id)
                REFERENCES ch_person(id)
);

-- reset client_min_messages to default
set client_min_messages='NOTICE';
