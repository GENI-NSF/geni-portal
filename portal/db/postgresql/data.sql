INSERT INTO schema_version(key, extra) values ('003', 'schema version');

-- Add a request rspec for a single node.
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('One compute node', 'GENI', '3', 'Any one compute node.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="foo"/>
</rspec>'
);
