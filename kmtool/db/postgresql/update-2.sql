-- Add table for self asserting email address

DROP TABLE IF EXISTS km_email_confirm;

CREATE TABLE km_email_confirm (
    id SERIAL PRIMARY KEY,
    eppn VARCHAR NOT NULL,
    email VARCHAR NOT NULL,
    nonce VARCHAR NOT NULL,
    created timestamp DEFAULT (NOW() at time zone 'utc') NOT NULL
);
