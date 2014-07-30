-- -------------------
-- Apply changes to add unique and no null and foreign key constraints, and related indices
-- See chapi #174, proto-ch #943, proto-ch #1081
-- -------------------

ALTER TABLE logging_entry
   ALTER COLUMN event_time SET NOT NULL;

ALTER TABLE logging_entry_attribute 
   ALTER COLUMN event_id SET NOT NULL,
   ADD FOREIGN KEY (event_id) REFERENCES logging_entry(id),
   ALTER COLUMN attribute_name SET NOT NULL;

ALTER TABLE logging_entry_old
   ALTER COLUMN event_time SET NOT NULL;

ALTER TABLE logging_entry_attribute_old
   ALTER COLUMN event_id SET NOT NULL,
   ADD FOREIGN KEY (event_id) REFERENCES logging_entry_old(id),
   ALTER COLUMN attribute_name SET NOT NULL;
