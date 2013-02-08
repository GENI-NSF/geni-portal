
-- Update 4 --

-- Add columns to support rspec metadata
ALTER TABLE rspec ADD COLUMN bound BOOLEAN;
UPDATE rspec SET bound = 'f';

ALTER TABLE rspec ADD COLUMN stitch BOOLEAN;
UPDATE rspec SET stitch = 'f';

ALTER TABLE rspec ADD COLUMN am_urns VARCHAR;
UPDATE rspec SET am_urns = '';

-- Update the schema version
INSERT INTO schema_version
    (key, extra)
  VALUES
    ('004', 'bound rspecs');
