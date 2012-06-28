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
  <node client_id="foo">
    <hardware_type name="pc600"/>
  </node>
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
  <node client_id="foo">
    <hardware_type name="pc600"/>
  </node>
  <node client_id="bar">
    <hardware_type name="pc600"/>
  </node>
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
  <node client_id="E1">
    <hardware_type name="pc600"/>
    <interface client_id="E1:if0"/>
  </node>
  <node client_id="E2">
    <hardware_type name="pc600"/>
    <interface client_id="E2:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="E1:if0"/>
    <interface_ref client_id="E2:if0"/>
    <property source_id="E1:if0" dest_id="E2:if0"/>
    <property source_id="E2:if0" dest_id="E1:if0"/>
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
  <node client_id="E1">
    <hardware_type name="pc600"/>
    <interface client_id="E1:if0"/>
    <interface client_id="E1:if1"/>
  </node>
  <node client_id="E2">
    <hardware_type name="pc600"/>
    <interface client_id="E2:if0"/>
    <interface client_id="E2:if1"/>
  </node>
  <node client_id="E3">
    <hardware_type name="pc600"/>
    <interface client_id="E3:if0"/>
    <interface client_id="E3:if1"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="E1:if0"/>
    <interface_ref client_id="E2:if0"/>
    <property source_id="E1:if0" dest_id="E2:if0"/>
    <property source_id="E2:if0" dest_id="E1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="E2:if1"/>
    <interface_ref client_id="E3:if1"/>
    <property source_id="E2:if1" dest_id="E3:if1"/>
    <property source_id="E3:if1" dest_id="E2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="E1:if1"/>
    <interface_ref client_id="E3:if0"/>
    <property source_id="E1:if1" dest_id="E3:if0"/>
    <property source_id="E3:if0" dest_id="E1:if1"/>
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
  <node client_id="E1">
    <hardware_type name="pc600"/>
    <interface client_id="E1:if0"/>
    <interface client_id="E1:if1"/>
  </node>
  <node client_id="E2">
    <hardware_type name="pc600"/>
    <interface client_id="E2:if0"/>
    <interface client_id="E2:if1"/>
  </node>
  <node client_id="E3">
    <hardware_type name="pc600"/>
    <interface client_id="E3:if0"/>
    <interface client_id="E3:if1"/>
  </node>
  <node client_id="E4">
    <hardware_type name="pc600"/>
    <interface client_id="E4:if0"/>
    <interface client_id="E4:if1"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="E1:if0"/>
    <interface_ref client_id="E2:if0"/>
    <property source_id="E1:if0" dest_id="E2:if0"/>
    <property source_id="E2:if0" dest_id="E1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="E2:if1"/>
    <interface_ref client_id="E3:if1"/>
    <property source_id="E2:if1" dest_id="E3:if1"/>
    <property source_id="E3:if1" dest_id="E2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="E4:if1"/>
    <interface_ref client_id="E3:if0"/>
    <property source_id="E4:if1" dest_id="E3:if0"/>
    <property source_id="E3:if0" dest_id="E4:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan3">
    <interface_ref client_id="E1:if1"/>
    <interface_ref client_id="E4:if0"/>
    <property source_id="E1:if1" dest_id="E4:if0"/>
    <property source_id="E4:if0" dest_id="E1:if1"/>
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
  <node client_id="E1">
    <hardware_type name="pc600"/>
    <interface client_id="E1:if0"/>
  </node>
  <node client_id="E2">
    <hardware_type name="pc600"/>
    <interface client_id="E2:if0"/>
    <interface client_id="E2:if1"/>
  </node>
  <node client_id="E3">
    <hardware_type name="pc600"/>
    <interface client_id="E3:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="E1:if0"/>
    <interface_ref client_id="E2:if0"/>
    <property source_id="E1:if0" dest_id="E2:if0"/>
    <property source_id="E2:if0" dest_id="E1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="E2:if1"/>
    <interface_ref client_id="E3:if0"/>
    <property source_id="E2:if1" dest_id="E3:if0"/>
    <property source_id="E3:if0" dest_id="E2:if1"/>
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
  <node client_id="E1">
    <hardware_type name="pc600"/>
    <interface client_id="E1:if0"/>
  </node>
  <node client_id="E2">
    <hardware_type name="pc600"/>
    <interface client_id="E2:if0"/>
    <interface client_id="E2:if1"/>
    <interface client_id="E2:if2"/>
  </node>
  <node client_id="E3">
    <hardware_type name="pc600"/>
    <interface client_id="E3:if0"/>
  </node>
  <node client_id="E4">
    <hardware_type name="pc600"/>
    <interface client_id="E4:if0"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="E1:if0"/>
    <interface_ref client_id="E2:if0"/>
    <property source_id="E1:if0" dest_id="E2:if0"/>
    <property source_id="E2:if0" dest_id="E1:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="E2:if1"/>
    <interface_ref client_id="E3:if0"/>
    <property source_id="E2:if1" dest_id="E3:if0"/>
    <property source_id="E3:if0" dest_id="E2:if1"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan2">
    <interface_ref client_id="E2:if2"/>
    <interface_ref client_id="E4:if0"/>
    <property source_id="E2:if2" dest_id="E4:if0"/>
    <property source_id="E4:if0" dest_id="E2:if2"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
-- The Click Router Example Experimentschema
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Click Router Example Experiment', 'GENI', '3',
   'The Click Router Example Experiment topology.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec type="request" xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.geni.net/resources/rspec/3">
  <node client_id="top" >
    <services>
      <execute command="/local/build-click.sh" shell="sh"/>
      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>
    </services>
    <hardware_type name="pc600"/>
    <interface client_id="top:if1"/>
    <interface client_id="top:if2"/>
    <interface client_id="top:if3"/>
  </node>
  <node client_id="left" >
    <services>
      <execute command="/local/build-click.sh" shell="sh"/>
      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>
    </services>
    <hardware_type name="pc600"/>
    <interface client_id="left:if1"/>
    <interface client_id="left:if2"/>
  </node>
  <node client_id="right" >
    <services>
      <execute command="/local/build-click.sh" shell="sh"/>
      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>
    </services>
    <hardware_type name="pc600"/>
    <interface client_id="right:if1"/>
    <interface client_id="right:if2"/>
  </node>
  <node client_id="bottom" >
    <services>
      <execute command="/local/build-click.sh" shell="sh"/>
      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>
    </services>
    <hardware_type name="pc600"/>
    <interface client_id="bottom:if1"/>
    <interface client_id="bottom:if2"/>
    <interface client_id="bottom:if3"/>
  </node>
  <node client_id="hostA" >
    <hardware_type name="pc600"/>
    <interface client_id="hostA:if1"/>
  </node>
  <node client_id="hostB" >
    <hardware_type name="pc600"/>
    <interface client_id="hostB:if1"/>
  </node>
  <link client_id="link-0">
    <property source_id="top:if1" dest_id="left:if1" capacity="100000"/>
    <property source_id="left:if1" dest_id="top:if1" capacity="100000"/>
    <interface_ref client_id="top:if1"/>
    <interface_ref client_id="left:if1"/>
  </link>
  <link client_id="link-1">
    <property source_id="top:if2" dest_id="right:if1" capacity="100000"/>
    <property source_id="right:if1" dest_id="top:if2" capacity="100000"/>
    <interface_ref client_id="top:if2"/>
    <interface_ref client_id="right:if1"/>
  </link>
  <link client_id="link-2">
    <property source_id="left:if2" dest_id="bottom:if1" capacity="100000"/>
    <property source_id="bottom:if1" dest_id="left:if2" capacity="100000"/>
    <interface_ref client_id="left:if2"/>
    <interface_ref client_id="bottom:if1"/>
  </link>
  <link client_id="link-3">
    <property source_id="right:if2" dest_id="bottom:if2" capacity="100000"/>
    <property source_id="bottom:if2" dest_id="right:if2" capacity="100000"/>
    <interface_ref client_id="right:if2"/>
    <interface_ref client_id="bottom:if2"/>
  </link>
  <link client_id="link-A">
    <property source_id="hostA:if1" dest_id="top:if3" capacity="100000"/>
    <property source_id="top:if3" dest_id="hostA:if1" capacity="100000"/>
    <interface_ref client_id="hostA:if1"/>
    <interface_ref client_id="top:if3"/>
  </link>
  <link client_id="link-B">
    <property source_id="bottom:if3" dest_id="hostB:if1" capacity="100000"/>
    <property source_id="hostB:if1" dest_id="bottom:if3" capacity="100000"/>
    <interface_ref client_id="bottom:if3"/>
    <interface_ref client_id="hostB:if1"/>
  </link>
</rspec>'
);
-- 3 nodes where middle is a delay node
INSERT INTO rspec(name, schema, schema_version, description, rspec)
  VALUES
  ('Two nodes with one delay node', 'GENI', '3',
   'Linear topology with delay node in the middle.',
   '<?xml version="1.0" encoding="UTF-8"?>
<rspec xmlns="http://www.geni.net/resources/rspec/3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xmlns:delay="http://www.protogeni.net/resources/rspec/ext/delay/1"
       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd http://www.protogeni.net/resources/rspec/ext/delay/1 http://www.protogeni.net/resources/rspec/ext/delay/1/request-delay.xsd"
       type="request">
  <node client_id="PC1">
    <hardware_type name="pc600"/>
    <interface client_id="PC1:if0">
      <ip address="192.168.2.1" netmask="255.255.255.0" type="ipv4"/>
    </interface>
  </node>
  <node client_id="PC2">
    <hardware_type name="pc600"/>
    <interface client_id="PC2:if0">
      <ip address="192.168.2.2" netmask="255.255.255.0" type="ipv4"/>
    </interface>
  </node>
  <node client_id="delay">
    <sliver_type name="delay">
      <delay:sliver_type_shaping>
        <delay:pipe source="delay:if0" dest="delay:if1" capacity="1000000" packet_loss="0" latency="1"/>
        <delay:pipe source="delay:if1" dest="delay:if0" capacity="1000000" packet_loss="0" latency="1"/>
      </delay:sliver_type_shaping>
    </sliver_type>
    <interface client_id="delay:if0"/>
    <interface client_id="delay:if1"/>
  </node>
  <link client_id="lan0">
    <interface_ref client_id="delay:if0"/>
    <interface_ref client_id="PC1:if0"/>
    <property source_id="delay:if0" dest_id="PC1:if0"/>
    <property source_id="PC1:if0" dest_id="delay:if0"/>
    <link_type name="lan"/>
  </link>
  <link client_id="lan1">
    <interface_ref client_id="delay:if1"/>
    <interface_ref client_id="PC2:if0"/>
    <property source_id="delay:if1" dest_id="PC2:if0"/>
    <property source_id="PC2:if0" dest_id="delay:if1"/>
    <link_type name="lan"/>
  </link>
</rspec>'
);
