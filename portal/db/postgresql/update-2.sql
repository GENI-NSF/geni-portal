
-- Add a uniqueness constraint on slice names
CREATE UNIQUE INDEX slice_name_key ON slice (name);

-- Add a column for slice URN.
ALTER TABLE slice ADD COLUMN
  urn VARCHAR UNIQUE;
