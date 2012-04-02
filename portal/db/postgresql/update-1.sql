
-- Add the owner column to the slice table
ALTER TABLE slice ADD COLUMN
  owner UUID REFERENCES account (account_id);

-- Add an index for the owner column on the slice table
CREATE INDEX slice_index_owner ON slice (owner);
