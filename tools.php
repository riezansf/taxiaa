<html>
<head>
    <meta charset=utf-8 />
    <title>AA Taksi Frequent O-D Pattern</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 
    <script src="jquery/jquery-3.0.0.min.js"></script> 
    <script src="jquery/jquery-ui.min.js"></script>
    <script src="leaflet/leaflet.js"></script>
    <script src="Leaflet.label/Label.js"></script>
    <script src="Leaflet.label/BaseMarkerMethods.js"></script>
    <script src="Leaflet.label/Marker.Label.js"></script>
    <script src="Leaflet.label/Map.Label.js"></script>
    <script src="Leaflet.label/Path.Label.js"></script>
    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>
    <script src="util.js"></script> 
    
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <link rel="stylesheet" href="tools_style.css" />
    <link rel="stylesheet" href="jquery/jquery-ui.min.css" />
<script>
//lat = atas-bawah Y, makin kecil makin ke atas (utara)
//long = kiri-kanan X, makin kecil makin ke kiri (barat)
//atas-bawah = 14.23 KM , kiri-kanan=21.09 KM , diagonal=25.44 KM, luas=300.1107 KM
//0.001 point GPS = 109.5546875 Meter 
//Grid size (Meter) 10 / 25 / 50 / 100 / 200
var map;

var originMarkers = new L.FeatureGroup();    
var destinationMarkers = new L.FeatureGroup();
var odLine = new L.FeatureGroup();

var originClusterMarkers = new L.FeatureGroup(); 
var destinationClusterMarkers = new L.FeatureGroup(); 
    
var bandungCentroid=[-6.914744, 107.609810];
var bandungBounds=[[-6.839, 107.547], [-6.967, 107.738]]; //BANDUNG ONLY
var bandungBoundsExtend=[[-6.784, 107.493], [-7.057, 107.827]]; //CIMAHI, LEMBANG, CILEUNYI, RANCAEKEK, SOREANG 

// Variable for map
var gridSize=0.001;
var gridWeight=0.1; //Stroke width in pixels.
var gridColor="grey"; //Stroke color.
var gridFillColor="grey";
var gridFillOpacity=0.01;
var gridClassName="";

var data=[];    
        
var originPoint=[];
var destinationPoint=[];
   
var centroidOrigin=[];
var centroidDestination=[];
    
var grid=[];
    
var pointMappedToGridO=0;
var pointMappedToGridD=0;
    
function buildMap(bandungCentroid){
    var start = new Date();
    map = L.map('map').setView(bandungCentroid, 14);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 18,
        id: 'laezano.18b09133',
        accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
    }).addTo(map);
    console.log("Time to build map = "+(new Date() - start)+"ms");
}    

function drawBound(bound,color,weight,fillOpacity){
    var start = new Date();
    L.rectangle(bound, {color: color, weight: weight, fillOpacity:fillOpacity}).addTo(map);
    map.fitBounds(bound);
    console.log("Draw bound time = "+(new Date() - start)+"ms");
}
    
//=== GRID WITH RECTANGLE    
function drawGridRectangle(bounds,gridSize,weight,color,fillOpacity){
    var start = new Date();
    var gridCount=0;
    var row=0; var col=0;
    
    for(var j=bounds[0][0];j>=bounds[1][0];j=(j-gridSize).toFixed(12)){
        if(grid[row]==null){grid[row]=[]}
        
        var k=bounds[0][1]; 
        col=0;
        while(k<bounds[1][1]){
            var b=[[j, k] , [(j-gridSize).toFixed(12),(k+gridSize).toFixed(12)]];
            var rectangle = L.rectangle(b, {color: color, weight: weight, fillOpacity:fillOpacity}).bindLabel(row+","+col).addTo(map);
            
            if(grid[row][col]==null){grid[row][col]=[];}
            
            grid[row][col]={
                rectangle : rectangle,
                topLeft : [j,k],
                rightBottom : [(j-gridSize).toFixed(12), (k+gridSize).toFixed(12)],
                origin : [],
                destination : [],
                centroidOrigin : [],
                centroidDestination : []
            };
            
            k=(k+gridSize);
            gridCount++;
            col++;
        }
        row++;
    }
    console.log("Time to draw grid rectangle = "+(new Date() - start)+"ms");
    console.log("Grid size = "+row+"x"+col+" , Total grid : "+gridCount); 
}
   
function mapPointToGrid(lat,long,OD){
    for(var j=0;j<grid.length;j++){
        for(var k=0;k<grid[j].length;k++){
            if( lat>grid[j][k].topLeft[0] && lat<grid[j][k].rightBottom[0] && long<grid[j][k].rightBottom[1]){
                //.log(typeof(lat)+">"+typeof(grid[j][k].topLeft[0])); 
                if(OD=="origin"){
                    grid[j][k].origin.push({location: { accuracy: 1, latitude: (lat), longitude: (long) }});
                    pointMappedToGridO++;
                }else{
                    grid[j][k].destination.push({location: { accuracy: 1, latitude: (lat), longitude: (long) }});
                    pointMappedToGridD++;
                }    
                break;
            }
        }
    }
}
    
function calculateCentroidOrigin(drawCentroid){
    var singleCentroid=0;
    
    var old_time = new Date();
    for(var i=0;i<grid.length;i++){
        for(var j=0;j<grid[i].length;j++){     
                    
            if(grid[i][j].origin.length>1){
                var latXTotal = 0;
                var latYTotal = 0;
                var lonDegreesTotal = 0;
                for(var k=0;k<grid[i][j].origin.length;k++){
                    var latDegrees = parseFloat(grid[i][j].origin[k].location.latitude);
                    var lonDegrees = parseFloat(grid[i][j].origin[k].location.longitude);
                    
                    var latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                var finalLatRadians = Math.atan2(latYTotal, latXTotal);
                var finalLatDegrees = finalLatRadians * 180 / Math.PI;
                var finalLonDegrees = lonDegreesTotal / grid[i][j].origin.length;
            
                grid[i][j].centroidOrigin.push({location: { accuracy: 1, latitude: finalLatDegrees.toString(), longitude: finalLonDegrees.toString() }});
                centroidOrigin.push({location: { accuracy: 1, latitude: finalLatDegrees.toString(), longitude: finalLonDegrees.toString() }});;
                
                //Centroid is solid RED
                if(drawCentroid){
                    var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).addTo(map);
                }
            }
            else if(grid[i][j].origin.length==1){
                grid[i][j].centroidOrigin.push({location: { accuracy: 1, latitude: grid[i][j].origin[0].location.latitude, longitude: grid[i][j].origin[0].location.longitude }});
                centroidOrigin.push({location: { accuracy: 1, latitude: grid[i][j].origin[0].location.latitude, longitude: grid[i][j].origin[0].location.longitude  }});;
                
                if(drawCentroid){
                    var circle = L.circle([grid[i][j].origin[0].location.latitude, grid[i][j].origin[0].location.longitude ], 5, { color: "blue", fillColor: "blue", fillOpacity: 0.5}).addTo(map);
                } 
                singleCentroid++;
            }
        }
    }
    var new_time = new Date();
    console.log("\nCalculate centroid origin time = "+(new_time - old_time)+" ms");
    console.log("Active grid (centroid) Origin : "+centroidOrigin.length);
    console.log("1 point in 1 grid Origin : "+singleCentroid);
} 
    
function calculateCentroidDestination(drawCentroid){
    var old_time = new Date();;
    for(var i=0;i<grid.length;i++){
        for(var j=0;j<grid[i].length;j++){     
                    
            //console.log(grid[i][j].destination);
            if(grid[i][j].destination.length>1){
                var latXTotal = 0;
                var latYTotal = 0;
                var lonDegreesTotal = 0;
                for(var k=0;k<grid[i][j].destination.length;k++){
                    var latDegrees = parseFloat(grid[i][j].destination[k].location.latitude);
                    var lonDegrees = parseFloat(grid[i][j].destination[k].location.longitude);
                    
                    var latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                var finalLatRadians = Math.atan2(latYTotal, latXTotal);
                var finalLatDegrees = finalLatRadians * 180 / Math.PI;
                var finalLonDegrees = lonDegreesTotal / grid[i][j].destination.length;
            
                grid[i][j].centroidDestination.push({location: { accuracy: 1, latitude: finalLatDegrees.toString(), longitude: finalLonDegrees.toString() }});
                centroidDestination.push({location: { accuracy: 1, latitude: finalLatDegrees.toString(), longitude: finalLonDegrees.toString() }});
                
                //Centroid is solid RED
                if(drawCentroid){
                    var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "green", fillColor: "green", fillOpacity: 1}).addTo(map);
                }
            }
            else if(grid[i][j].destination.length==1){
                grid[i][j].centroidDestination.push({ location: { accuracy: 1, latitude: grid[i][j].destination[0].location.latitude, longitude: grid[i][j].destination[0].location.longitude }});
                centroidDestination.push({location: { accuracy: 1, latitude: grid[i][j].destination[0].location.latitude, longitude: grid[i][j].destination[0].location.longitude }}); 
                if(drawCentroid){
                    var circle = L.circle([grid[i][j].destination[0].location.latitude, grid[i][j].destination[0].location.longitude ], 5, { color: "blue", fillColor: "blue", fillOpacity: 0.5}).addTo(map);
                } 
            }
        }
    }
    var new_time = new Date();
    console.log("Calculate centroid destination time = "+(new_time - old_time)+" ms");
    console.log("Total trip : "+destinationPoint.length+" , Active grid Destination:"+centroidDestination.length);
          
}    
    
function load_draw_data(data){
    var old_time = new Date();
    for (var i=0; i<data.length; i++) {
        originPoint.push({location: { accuracy: 1, latitude: data[i].pickup_loc_2_lat, longitude: data[i].pickup_loc_2_long }});
        destinationPoint.push({location: { accuracy: 1, latitude: data[i].dropoff_loc_2_lat, longitude: data[i].dropoff_loc_2_long }});

        //origin point color is blue
        var circle = L.circle([data[i].pickup_loc_2_lat,data[i].pickup_loc_2_long], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).bindLabel(originPoint.length+". "+data[i].pickup_loc_2_lat+","+data[i].pickup_loc_2_long);
        originMarkers.addLayer(circle);
        
        //destination point color is green
        var circle = L.circle([data[i].dropoff_loc_2_lat,data[i].dropoff_loc_2_long], 5, { color: "green", fillColor: "green", fillOpacity: 1}).bindLabel(destinationPoint.length+". "+data[i].dropoff_loc_2_lat+","+data[i].dropoff_loc_2_long);
        destinationMarkers.addLayer(circle);
        
        //Draw line origin point to destintion
        var polyline = L.polyline(
            [new L.LatLng(data[i].pickup_loc_2_lat,data[i].pickup_loc_2_long),new L.LatLng(data[i].dropoff_loc_2_lat,data[i].dropoff_loc_2_long)], 
            {
                color: 'red',
                weight: 1
            }
        );
        odLine.addLayer(polyline);
        
//            if(mapOrigin){
//                mapPointToGrid(data[8],data[9],"origin");
//            }
//            if(mapDestination){
//                mapPointToGrid(data[14],data[15],"destination");
//            }
        
    }
    
    map.addLayer(originMarkers);
    map.addLayer(destinationMarkers);
    map.addLayer(odLine);
    
    var new_time = new Date();
    console.log("\nTime to read data, draw point, & map to grid = "+(new_time - old_time)+" ms");
    console.log("Origin point "+originPoint.length+" , Destination point : "+destinationPoint.length);
    //console.log("Origin point mapped to grid : "+pointMappedToGridO+" , Destination point mapped to grid : "+pointMappedToGridD);
}   
       
function dbscan(data,eps,minPts,color,drawPointRadius,OD){
    var old_time = new Date();
    var dbscan = jDBSCAN().eps(parseFloat(eps)).minPts(parseInt(minPts)).distance('HAVERSINE').data(data);
    var dbscanResult = dbscan();
    var clusterCenters = dbscan.getClusters();
    var clusterCount=[];
    var unclustered=0;
    
    //each point labeled with cluster number
    for(var i=0; i<dbscanResult.length; i++){
        if(clusterCount[dbscanResult[i]]==null){ clusterCount[dbscanResult[i]]=0; }
        clusterCount[dbscanResult[i]]++;  
        
        if(dbscanResult[i]!=0){ 
            if(color=="random"){ var drawColor=markerColors[dbscanResult[i]]; }else{ var drawColor=color; }
            
            //var drawColor=color;
            
            var circle = L.circle([data[i].location.latitude, data[i].location.longitude], drawPointRadius, {
                color: drawColor,
                fillColor: drawColor,
                fillOpacity: 1
            }).bindLabel("Cluster number : "+dbscanResult[i]+"\nRecord no : "+i).addTo(map); 
            
            if(OD="origin"){ originClusterMarkers.addLayer(circle); }else{ destinationClusterMarkers.addLayer(circle); }
            
//            //grid coloring
//            for(var j=0;j<grid.length;j++){
//                for(var k=0;k<grid[j].length;k++){                                       
//                    if(data[i].location.latitude>grid[j][k].topLeft[0] && data[i].location.latitude<grid[j][k].rightBottom[0] && data[i].location.longitude<grid[j][k].rightBottom[1]){
//                        //console.log(typeof(data[i].location.latitude)+">"+typeof(grid[j][k].topLeft[0]));  
//                        grid[j][k].rectangle.setStyle({fillColor:drawColor,fillOpacity:0.5});
//                        break;
//                    }
//                }
//            }         
        }else{
            unclustered++;
        }       
    }
    console.log("\n== CLUSTERING ==");
    console.log("Total point = "+ data.length);
    console.log("Cluster summary = "+ clusterCount);
    console.log("Cluster center (count) = "+ clusterCenters.length);
    console.log("Clustered trip = "+ (data.length-clusterCount[0]) );
    console.log("Unclustered trip = "+ clusterCount[0]);
    var new_time = new Date();
    console.log("Clustering time = "+(new_time - old_time)+" ms");
}    
    
$(document).ready(function() { 
    $(".date").datepicker({
        dateFormat: 'yy-mm-dd',
        minDate : '2015-12-1',
        maxDate : '2015-12-31'
    });
            
    var drawPointOrigin=true; //blue
    var drawPointDestination=true; //green

    var mapOrigin=true;
    var mapDestination=true;

    //Variable for grid & cluster
    var drawCentroidOrigin=false; //blue   
    var colorOrigin="random";
    var drawPointRadiusOrigin=10;

    var drawCentroidDestination=false; //green

    var colorDestination="random";
    var drawPointRadiusDestination=10;

    buildMap(bandungCentroid);
       
    $("#loadData").click(function(){
        //originMarkers= new L.FeatureGroup();   
        //destinationMarkers= new L.FeatureGroup();   
        
        //map.removeLayer(originMarkers);
        //map.removeLayer(destinationMarkers);
        
        $.getJSON("tools_load_data.php",{
            startPeriod : $("#startPeriod").val(),
            endPeriod : $("#endPeriod").val()
        },
        function(data, status){
            $("#dataLoaded").show();
            $("#footer").show();
            $("#clustering").attr("disabled",false);
            
            $.each(data, function (index, value) {
                data[index]=value;
            });
        
            load_draw_data(data); 
        
            //Filter
            $("#fOriginMarkers").change(function(){
                if(this.checked) { map.addLayer(originMarkers); }else{ map.removeLayer(originMarkers); }
            });
            $("#fDestinationMarkers").change(function(){
                if(this.checked) { map.addLayer(destinationMarkers); }else{ map.removeLayer(destinationMarkers); }
            });
        });  
    });
    
    $("#clustering").click(function(){
         //validate inputText
         
        if($("#gridSize").val()==""){
            //==== Non gridbased dbscan
            map.removeLayer(originMarkers);
            map.removeLayer(destinationMarkers);
            
            dbscan(originPoint,$("#eps").val(),$("#minpts").val(),colorOrigin,10,"origin");
            //dbscan(destinationPoint,$("#eps").val(),$("#minpts").val(),colorDestination,10,"destiation");
        }else{
            //drawGridRectangle(bandungBounds,gridSize,gridWeight,gridColor,gridFillOpacity);
            
            //calculateCentroidOrigin(drawCentroidOrigin); 
            //dbscan(centroidOrigin,epsOrigin,minPtsOrigin,clusterColorOrigin,drawPointRadiusOrigin);
            
            //calculateCentroidDestination(drawCentroidDestination);    
            //dbscan(centroidDestination,epsDestination,minPtsDestination,clusterColorDestination,drawPointRadiusDestination);
        } 
         
        $("#filterLoadData").hide();
        $("#filterClusteringResult").show();
        
        $("#fOriginCluterMarkers").change(function(){
            if(this.checked && $("#fDestinationCluterMarkers").is(':checked')){
                //blue & green
            }else{
                //random color
            }
        }); 

         $("#fDestinationCluterMarkers").change(function(){
            if(this.checked && $("#fOriginCluterMarkers").is(':checked')){
                //blue & green
            }else{
                //random color
            }
        }); 
     });
    
});
</script> 
</head>

<body>
    <div class="container">    
        <div class="sideBar">
            <div id="chooseData">
                <b>Choose Data</b><br>
                Start : <input type="text" id="startPeriod" class="date" value="2015-12-01"><br>
                End : <input type="text" id="endPeriod" class="date" value="2015-12-02"><br>
                Week : <br>
                <input type="checkbox" name="weekday" value="weekday" checked="true"> Weekday
                <input type="checkbox" name="weekend" value="weekend" checked="true"> Weekend <br>
                Days : <br>
                <input type="checkbox" name="pagi" value="pagi" checked="true"> Pagi <br>
                <input type="checkbox" name="siang" value="siang" checked="true"> Siang <br>
                <input type="checkbox" name="sore" value="sore" checked="true"> Sore <br>
                <input type="checkbox" name="malam " value="malam" checked="true"> Malam <br>
                <input type="checkbox" name="dinihari" value="dinihari" checked="true"> Dini Hari <br>
                <input type="button" id="loadData" value="Load Data"><div id="dataLoaded" hidden> >> Data Loaded!</div>
            </div>
            <hr>
            <div id="dbscan">
                <b>Preprocessing</b><br>
                Eps : <input type="text" id="eps" class="" value="0.2"><br>
                Min Pts : <input type="text" id="minpts" class="" value="3"><br>
                Grid Size : 
                <select id="gridSize">
                  <option value="">Tanpa Grid</option>
                  <option value="50">50 Meter</option>
                  <option value="100">100 Meter</option>
                  <option value="200">200 Meter</option>
                  <option value="500">500 Meter</option>
                </select>
                <br>
                <input type="button" id="clustering" value="Process" disabled><div id="dataLoaded" hidden> Cluster Generated!</div>
            </div>
            <hr>
            <div id="sp">
                <b>Clustering</b><br>
                Type :
                <select>
                  <option value="gridToGrid">Grid to Grid</option>
                  <option value="clusterToCluster">Cluster to Cluster</option>
                </select>
                <br>
                <div id="gridToGrid">
                    Grid Size : 
                    <select>
                      <option value="50">50 Meter</option>
                      <option value="100">100 Meter</option>
                      <option value="200">200 Meter</option>
                      <option value="500">500 Meter</option>
                    </select>
                </div>
            </div>
            <br>
            <input type="button" id="process" value="Process">
        </div>
        <div class="content">
            <div id="map"> </div>
            <div id="footer" hidden>
                <div id="toggle">Toggle<br></div>
                
                <b>Filter</b><br>
                Show Hide<br>
                
                <div id="filterLoadData">
                    <input type="checkbox" id="fOriginMarkers" value="originMarkers" checked="true"> Origin Marker <br>
                    <input type="checkbox" id="fDestinationMarkers" value="destinationMarkers" checked="true"> Destination Marker <br>
                </div>
                
                <div id="filterClusteringResult" hidden>
                    <input type="checkbox" id="fOriginCluterMarkers" value="originCluterMarkers" checked="true"> Origin Cluter Marker <br>
                    <input type="checkbox" id="fOdestinationCluterMarkers" value="destinationCluterMarkers" checked="true"> Destination Cluter Marker <br>
                </div>
                
            </div>
        </div>  
    </div>
</body>
</html>

