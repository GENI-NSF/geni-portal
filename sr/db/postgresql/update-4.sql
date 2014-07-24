-- -------------------
-- Apply changes to add unique and no null and foreign key constraints, and related indices
-- See chapi #174, proto-ch #943, proto-ch #1081
-- -------------------

ALTER TABLE service_registry_attribute 
    ALTER COLUMN service_id SET NOT NULL,
    ADD CONSTRAINT FOREIGN KEY (service_id) REFERENCES service_registry(id);