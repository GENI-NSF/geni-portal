<script type="text/javascript" src="http://openlayers.org/api/OpenLayers.js"></script>
<script type="text/javascript">

// Adapted from http://acuriousanimal.com/code/animatedCluster/


window.onload = function init() {


            // Create a map and add OSM raster layer as the base layer
            var map1 = new OpenLayers.Map("map1");
            var osm1 = new OpenLayers.Layer.OSM();
            map1.addLayer(osm1);
            
            // Initial view location
            var center = new OpenLayers.LonLat(-96,38);
            center.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
            map1.setCenter(center, 4);
            // Add a LayerSwitcher control
            //map1.addControl(new OpenLayers.Control.LayerSwitcher());
            
            // set up presentation rules for style
            var rule = new OpenLayers.Rule({
                symbolizer: {
                    fillColor: '#E17000',
                    fillOpacity: 0.75, 
                    strokeColor: '#5F584E',
                    strokeOpacity: 1,
                    strokeWidth: 2,
                    pointRadius: "${radius}",
                    label: "${resourceCount}",
                    labelOutlineWidth: 1,
                    title: "${resourceTitle}",
                    fontColor: "#ffffff",
                    fontFamily: 'Arial',
                    fontSize: "12px"
                }
            });
           
            // set up style
            var style = new OpenLayers.Style(null, {
                context: {
                  resourceCount: 
                    function(feature) {
                      var sum = 0;
                      for(var i = 0; i < feature.cluster.length; i++) {
                        sum = sum + feature.cluster[i].attributes.resources;
                      }
                      return sum;
                    } ,
                  resourceTitle:
                    function(feature) {
                      var sum = feature.cluster[0].attributes.component_id + " (" + feature.cluster[0].attributes.resources + ")";
                      for(var i = 1; i < feature.cluster.length; i++) {
                         sum = sum + ", " + feature.cluster[i].attributes.component_id + " (" + feature.cluster[i].attributes.resources + ")";
                       }
                      return sum;
                    } ,
                  radius:
                    function(feature) {
                      var sum = 0;
                      for(var i = 0; i < feature.cluster.length; i++) {
                        sum = sum + feature.cluster[i].attributes.resources;
                      }
                      if(sum < 10) {
                        return 10;
                      }
                      else if (sum < 100) {
                        return 14;
                      }
                      else {
                        return 22;
                      }
                      
                    }
                    
                    
                }, 
                  
                rules: [rule]
                
                
                
                
            });            

            // Create a vector layers
            var vector1 = new OpenLayers.Layer.Vector("GENI Resources", {
                protocol: new OpenLayers.Protocol.HTTP({
                    url: "map.json",
                    format: new OpenLayers.Format.GeoJSON()
                }),
                strategies: [
                    new OpenLayers.Strategy.Fixed(),
                    new OpenLayers.Strategy.Cluster()
                ],
                styleMap:  new OpenLayers.StyleMap(style)
            });
            map1.addLayer(vector1);
            

            /* var vector2 = new OpenLayers.Layer.Vector("GENI Sites", {
                protocol: new OpenLayers.Protocol.HTTP({
                    url: "am.json",
                    format: new OpenLayers.Format.GeoJSON()
                }),
                strategies: [
                    new OpenLayers.Strategy.Fixed()
                ],
                styleMap:  new OpenLayers.StyleMap(
                {
                    fillColor: '#ffffff',
                    fillOpacity: 0, 
                    strokeWidth: 0,
                    pointRadius: 10,
                    title: "${component_id}"
            }

                )
            });
            map1.addLayer(vector2); */
            
}

</script>
<div id="map1" style="width: 793px; height: 400px"></div>
