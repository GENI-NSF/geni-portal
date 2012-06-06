INSERT INTO schema_version(key, extra) values ('003', 'schema version');

-- Add a request rspec for a single node.
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('One compute node', 'GENI', '3',
   'Any one compute node.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="foo"/>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Two compute nodes', 'GENI', '3',
   'Any two compute nodes.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="foo"/>
  <node client_id="bar"/>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Two nodes, one link', 'GENI', '3',
   'Two nodes with a link between them.',
   '<rspec type="request" 
	xmlns="http://www.geni.net/resources/rspec/3" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.geni.net/resources/rspec/3 
	http://www.geni.net/resources/rspec/3/request.xsd">  
  <node client_id="VM-1" >
    <interface client_id="VM-1:if0"/>
  </node>
  <node client_id="VM-2">
    <interface client_id="VM-2:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="VM-1:if0"/>
    <interface_ref client_id="VM-2:if0"/>
    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>
    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Three nodes, triangle topology', 'GENI', '3',
   'Three nodes in a triangle topology.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="VM-1">
    <interface client_id="VM-1:if0"/>
    <interface client_id="VM-1:if1"/>
  </node>
  <node client_id="VM-2">
    <interface client_id="VM-2:if0"/>
    <interface client_id="VM-2:if1"/>
  </node>
  <node client_id="VM-3">
    <interface client_id="VM-3:if0"/>
    <interface client_id="VM-3:if1"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="VM-1:if0"/>
    <interface_ref client_id="VM-2:if0"/>
    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>
    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="VM-2:if1"/>
    <interface_ref client_id="VM-3:if1"/>
    <property source_id="VM-2:if1" dest_id="VM-3:if1"/>
    <property source_id="VM-3:if1" dest_id="VM-2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="VM-1:if1"/>
    <interface_ref client_id="VM-3:if0"/>
    <property source_id="VM-1:if1" dest_id="VM-3:if0"/>
    <property source_id="VM-3:if0" dest_id="VM-1:if1"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Four nodes, diamond topology', 'GENI', '3',
   'Four nodes in a diamond topology.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="VM-1">
    <interface client_id="VM-1:if0"/>
    <interface client_id="VM-1:if1"/>
  </node>
  <node client_id="VM-2">
    <interface client_id="VM-2:if0"/>
    <interface client_id="VM-2:if1"/>
  </node>
  <node client_id="VM-3">
    <interface client_id="VM-3:if0"/>
    <interface client_id="VM-3:if1"/>
  </node>
  <node client_id="VM-4">
    <interface client_id="VM-4:if0"/>
    <interface client_id="VM-4:if1"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="VM-1:if0"/>
    <interface_ref client_id="VM-2:if0"/>
    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>
    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="VM-2:if1"/>
    <interface_ref client_id="VM-3:if1"/>
    <property source_id="VM-2:if1" dest_id="VM-3:if1"/>
    <property source_id="VM-3:if1" dest_id="VM-2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="VM-4:if1"/>
    <interface_ref client_id="VM-3:if0"/>
    <property source_id="VM-4:if1" dest_id="VM-3:if0"/>
    <property source_id="VM-3:if0" dest_id="VM-4:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan3">
    <interface_ref client_id="VM-1:if1"/>
    <interface_ref client_id="VM-4:if0"/>
    <property source_id="VM-1:if1" dest_id="VM-4:if0"/>
    <property source_id="VM-4:if0" dest_id="VM-1:if1"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Three nodes, linear topology', 'GENI', '3',
   'Three nodes in a linear topology.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="VM-1">
    <interface client_id="VM-1:if0"/>
  </node>
  <node client_id="VM-2">
    <interface client_id="VM-2:if0"/>
    <interface client_id="VM-2:if1"/>
  </node>
  <node client_id="VM-3">
    <interface client_id="VM-3:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="VM-1:if0"/>
    <interface_ref client_id="VM-2:if0"/>
    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>
    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="VM-2:if1"/>
    <interface_ref client_id="VM-3:if0"/>
    <property source_id="VM-2:if1" dest_id="VM-3:if0"/>
    <property source_id="VM-3:if0" dest_id="VM-2:if1"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Four nodes, star topology', 'GENI', '3',
   'Four nodes in a star topology.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"
       type="request">
  <node client_id="VM-1">
    <interface client_id="VM-1:if0"/>
  </node>
  <node client_id="VM-2">
    <interface client_id="VM-2:if0"/>
    <interface client_id="VM-2:if1"/>
    <interface client_id="VM-2:if2"/>
  </node>
  <node client_id="VM-3">
    <interface client_id="VM-3:if0"/>
  </node>
  <node client_id="VM-4">
    <interface client_id="VM-4:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="VM-1:if0"/>
    <interface_ref client_id="VM-2:if0"/>
    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>
    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="VM-2:if1"/>
    <interface_ref client_id="VM-3:if0"/>
    <property source_id="VM-2:if1" dest_id="VM-3:if0"/>
    <property source_id="VM-3:if0" dest_id="VM-2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="VM-2:if2"/>
    <interface_ref client_id="VM-4:if0"/>
    <property source_id="VM-2:if2" dest_id="VM-4:if0"/>
    <property source_id="VM-4:if0" dest_id="VM-2:if2"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
-- Need 3 nodes where middle is a delay node
-- Need the Click Router schema
