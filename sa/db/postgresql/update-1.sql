-- Update 1

-- Add tables for sliver info in SA

DROP TABLE if EXISTS sa_sliver_info;

CREATE TABLE sa_sliver_info (
       id SERIAL,
       slice_urn varchar not null,
       sliver_urn varchar unique not null,
       creation timestamp without time zone,
       expiration timestamp without time zone,
       creator_urn varchar not null,
       aggrgate_url varchar not null
);
CREATE INDEX sa_sliver_info_id ON sa_sliver_info(id);

-- Update the schema version
INSERT INTO schema_version
       (key, extra)
VALUES ('006', 'sa_sliver_info');

       