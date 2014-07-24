-- -------------------
-- Apply changes to add unique and no null and foreign key constraints, and related indices
-- See chapi #174, proto-ch #943, proto-ch #1081
-- -------------------

ALTER TABLE cs_attribute
  ADD CONSTRAINT PRIMARY KEY (id),
  ALTER COLUMN name SET NOT NULL,
  ADD CONSTRAINT UNIQUE (name);

ALTER TABLE cs_privilege
  ADD CONSTRAINT PRIMARY KEY (id),
  ALTER COLUMN name SET NOT NULL,
  ADD CONSTRAINT UNIQUE (name);

ALTER TABLE cs_action
  ADD CONSTRAINT PRIMARY KEY (id),
  ALTER COLUMN name SET NOT NULL,
  ALTER COLUMN context_type SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (context_type) REFERENCES cs_context_type(id);

ALTER TABLE cs_assertion
  ALTER COLUMN principal SET NOT NULL,
  ALTER COLUMN attribute SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (attribute) REFERENCES cs_attribute(id),
  ALTER COLUMN context_type SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (context_type) REFERENCES cs_context_type(id);

ALTER TABLE cs_policy
  ADD CONSTRAINT PRIMARY KEY (id),
  ALTER COLUMN attribute SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (attribute) REFERENCES cs_attribute(id),
  ALTER COLUMN context_type SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (context_type) REFERENCES cs_context_type(id),
  ALTER COLUMN privilege SET NOT NULL,
  ADD CONSTRAINT FOREIGN KEY (privilege) REFERENCES cs_privilege(id);

ALTER TABLE cs_context_type
  ADD CONSTRAINT PRIMARY KEY (id),
  ALTER COLUMN name SET NOT NULL,
  ADD CONSTRAINT UNIQUE (name);
