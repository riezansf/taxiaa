<html>
<head>
    <meta charset=utf-8 />
    <title>AA Taksi Frequent O-D Pattern</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script> 
    <script src="leaflet/leaflet.js"></script>
    <script src="Leaflet.label/Label.js"></script>
    <script src="Leaflet.label/BaseMarkerMethods.js"></script>
    <script src="Leaflet.label/Marker.Label.js"></script>
    <script src="Leaflet.label/Map.Label.js"></script>
    <script src="Leaflet.label/Path.Label.js"></script>
    
    <script src="Leaflet.PolylineDecorator/L.LineUtil.PolylineDecorator.js"></script>
    <script src="Leaflet.PolylineDecorator/L.PolylineDecorator.js"></script>
    <script src="Leaflet.PolylineDecorator/L.RotatedMarker.js"></script>
    <script src="Leaflet.PolylineDecorator/L.Symbol.js"></script>
    
    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>
    <script src="util.js"></script> 
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <style>
        body { margin:0; padding:0; }
        #map { position:absolute; top:0; bottom:0; width:100%; }
        .leaflet-div-icon {
            background: transparent;
            border: none;
        }

        .leaflet-marker-icon .number{
            position: relative;
            top: -37px;
            font-size: 12px;
            width: 25px;
            text-align: center;
        }
    </style>
    
<script>
//lat = atas-bawah Y, makin kecil makin ke atas (utara)
//long = kiri-kanan X, makin kecil makin ke kiri (barat)
//atas-bawah = 14.23 KM , kiri-kanan=21.09 KM , diagonal=25.44 KM, luas=300.1107 KM
//0.001 point GPS = 109.5546875 Meter 
//Grid size (Meter) 10 / 25 / 50 / 100 / 200
var map;
var bandungCentroid=[-6.914744, 107.609810];
var bandungBounds=[[-6.839, 107.547], [-6.967, 107.738]]; //BANDUNG ONLY
var bandungBoundsExtend=[[-6.784, 107.493], [-7.057, 107.827]]; //CIMAHI, LEMBANG, CILEUNYI, RANCAEKEK, SOREANG 
  
var allTextLines;    
    
var originPoint=[];
var destinationPoint=[];
   
var centroidOrigin=[];
var centroidDestination=[];
    
var grid=[];
var gridByNumber=[];
    
var pointMappedToGridO=0;
var pointMappedToGridD=0;
    
var clusteredPoints;
    
var result=[];
    
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
            var rectangle = L.rectangle(b, {color: color, weight: weight, fillOpacity:fillOpacity}).bindLabel(gridCount+" "+row+","+col).addTo(map);
            
            if(grid[row][col]==null){grid[row][col]=[];}
            
            grid[row][col]={
                gridNumber : gridCount,
                rectangle : rectangle,
                topLeft : [j,k],
                rightBottom : [(j-gridSize).toFixed(12), (k+gridSize).toFixed(12)],
                origin : [],
                destination : [],
                centroidOrigin : [],
                centroidDestination : [],
                centroidGrid : midpoint(j,k,(j-gridSize).toFixed(12),(k+gridSize).toFixed(12))
            };
            
            gridByNumber[gridCount]={
                gridNumber : gridCount,
                rectangle : rectangle,
                topLeft : [j,k],
                rightBottom : [(j-gridSize).toFixed(12), (k+gridSize).toFixed(12)],
                origin : [],
                destination : [],
                centroidOrigin : [],
                centroidDestination : [],
                centroidGrid : midpoint(j,k,(j-gridSize).toFixed(12),(k+gridSize).toFixed(12))
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
    
function mapPointToGrid(point,OD){
    var gridNo=0;
    for(var j=0;j<grid.length;j++){
        for(var k=0;k<grid[j].length;k++){
            gridNo++;
            if( point.location.latitude>grid[j][k].topLeft[0] && point.location.latitude<grid[j][k].rightBottom[0] && point.location.longitude<grid[j][k].rightBottom[1]){
                //.log(typeof(lat)+">"+typeof(grid[j][k].topLeft[0])); 
                point.grid={
                  no:gridNo,
                  row:j,
                  column:k
                };
                if(OD=="origin"){
                    grid[j][k].origin.push(point);
                    pointMappedToGridO++;
                }else{
                    grid[j][k].destination.push(point);
                    pointMappedToGridD++;
                }
                break;
            }
        }
    }
}
    
function read_draw_count_data(csv,date,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bounds,gridSize){
    allTextLines = csv.split(/\r\n|\n/);
    var lines = [];
    var old_time = new Date();
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
        if(date==""){ var whereDate=true; }else{ var whereDate=date; }
        // 
        if(lines[1]==whereDate && lines[9]!="" && lines[14]!="" && lines[15]!="" && lines[8]!=null && lines[9]!=null && lines[14]!=null && lines[15]!=null){  //if coordinate !=""
            
            var origin={location: { accuracy: 1, latitude: lines[8], longitude: lines[9] },timestamp: Math.round(new Date(lines[1]+" "+lines[2]).getTime()/1000), grid:""};
            var destination={location: { accuracy: 1, latitude: lines[14], longitude: lines[15] },timestamp: Math.round(new Date(lines[1]+" "+lines[3]).getTime()/1000), grid:""};
            
            originPoint.push(origin);
            destinationPoint.push(destination);
                        
            if(mapOrigin){ mapPointToGrid(origin,"origin"); }
            if(mapDestination){ mapPointToGrid(destination,"destination");}
            
            if(origin.grid.no!=undefined && destination.grid.no!=undefined){
                console.log(origin.grid.no+" -1 "+destination.grid.no+" -2");
            }
            
            if(drawPointOrigin){ //origin point color is blue
                var circle = L.circle([origin.location.latitude, origin.location.longitude], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).bindLabel(origin.grid.no+". "+origin.location.latitude+","+origin.location.longitude).addTo(map);
            }
            if(drawPointDestination){ //destination point color is green
                var circle = L.circle([destination.location.latitude, destination.location.longitude], 5, { color: "green", fillColor: "green", fillOpacity: 1}).bindLabel(destination.grid.no+". "+destination.location.latitude+","+destination.location.latitude).addTo(map);
            }
        }
    }
    var new_time = new Date();
    console.log("\nTime to read data, draw point, & map to grid = "+(new_time - old_time)+" ms");
    console.log("Origin point "+originPoint.length+" , Destination point : "+destinationPoint.length);
    console.log("Origin point mapped to grid : "+pointMappedToGridO+" , Destination point mapped to grid : "+pointMappedToGridD);
}  
    
function mapGridODPair(csv){
    allTextLines = csv.split(/\r\n|\n/);
    var lines = [];
    var old_time = new Date();
    var color=0;
    var totalSupport=0;
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
  
        if(lines.length==3 && lines[0]!=lines[1]){
            
            console.log(lines[0]+" to "+lines[1]+" support "+lines[2]);
            totalSupport+=parseInt(lines[2]);
            
            var gridOrigin=gridByNumber[lines[0]].centroidGrid.split(",");
            var gridDestination=gridByNumber[lines[1]].centroidGrid.split(",");
            
            //Polylines with Arrow
            var arrowPolyline = L.Polyline.extend({
                addArrows: function(){
                    var points = this.getLatLngs()
                    for (var p = 0; p +1 < points.length; p++){

                        var diffLat = points[p+1]["lat"] - points[p]["lat"]
                        var diffLng = points[p+1]["lng"] - points[p]["lng"]
                        var center = [points[p]["lat"] + diffLat/2,points[p]["lng"] + diffLng/2]

                        var angle = 360 - (Math.atan2(diffLat, diffLng)*57.295779513082)

                        var arrowM = new L.marker(center,{
                           icon: new L.divIcon({ 
                                className : "arrowIcon",
                                iconSize: new L.Point(30,30), 
                                iconAnchor: new L.Point(15,15), 
                                html : "<div style = 'font-size: 20px; -webkit-transform: rotate("+ angle +"deg)'>&#10152;</div>"
                           })
                        }).addTo(map);
                   }
                }
            });
            var latlngs =  [new L.LatLng(gridOrigin[0], gridOrigin[1]),new L.LatLng(gridDestination[0], gridDestination[1])];    
            var polyline = new arrowPolyline(latlngs, {color: htmlColor[color],weight:(lines[2]/592)*300,opacity:0.5}).addTo(map);
            polyline.addArrows();
            
            color++;
        }
    }
    var new_time = new Date();  
    console.log("Total Support ="+totalSupport);
}      
       
function dbscan(data,eps,minPts,timeEps,color,drawPointRadius){
    var old_time = new Date();
    var dbscan = jDBSCAN().eps(eps).minPts(minPts).distance('HAVERSINE').timeEps(timeEps).data(data);
    var dbscanResult = dbscan();
    var clusterCenters = dbscan.getClusters();
    var clusterCount=[];
   
    var unclustered=0;
    
    //each point labeled with cluster number
    for(var i=0; i<dbscanResult.length; i++){
        if(clusterCount[dbscanResult[i]]==null){ clusterCount[dbscanResult[i]]=0; }
        clusterCount[dbscanResult[i]]++;  
        
        if(clusteredPoints[dbscanResult[i]]==null){ clusteredPoints[dbscanResult[i]]=[]; }
        clusteredPoints[dbscanResult[i]].push(data[i]); 
        
        if(dbscanResult[i]!=0){ 
            if(color=="random"){
                var drawColor=markerColors[dbscanResult[i]];
            }else{
                var drawColor=color;                               
            }
            
            var date = new Date(data[i].timestamp*1000);
            // Hours part from the timestamp
            var hours = date.getHours();
            // Minutes part from the timestamp
            var minutes = "0" + date.getMinutes();
            // Seconds part from the timestamp
            var seconds = "0" + date.getSeconds();
            var time = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
         
            
            var circle = L.circle([data[i].location.latitude, data[i].location.longitude], drawPointRadius, {
                color: drawColor,
                fillColor: drawColor,
                fillOpacity: 1
            }).bindLabel("Cluster number : "+dbscanResult[i]+"\nRecord no : "+i+"\nTime : "+time).addTo(map); 
                
             for(var j=0;j<grid.length;j++){
                for(var k=0;k<grid[j].length;k++){                                       
                    if(data[i].location.latitude>grid[j][k].topLeft[0] && data[i].location.latitude<grid[j][k].rightBottom[0] && data[i].location.longitude<grid[j][k].rightBottom[1]){
                        //console.log(typeof(data[i].location.latitude)+">"+typeof(grid[j][k].topLeft[0]));  
                        grid[j][k].rectangle.setStyle({fillColor:drawColor,fillOpacity:0.8});
                        break;
                    }
                }
            }         
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

function taniarza(no,point){
    var gridNo=0;
    for(var j=0;j<grid.length;j++){
        for(var k=0;k<grid[j].length;k++){
            gridNo++;
            if(point.location.latitude>grid[j][k].topLeft[0] && point.location.latitude<grid[j][k].rightBottom[0] && point.location.longitude<grid[j][k].rightBottom[1]){                
                console.log(no+" "+gridNo+" "+j+","+k);
                return no+" "+gridNo+" "+j+","+k;
                break;
            }
        }
    }
}    
    
function natya(csv){
    allTextLines = csv.split(/\r\n|\n/);
    console.log(allTextLines.length);
    var lines = [];
    var point;
    for (var i=0; i<allTextLines.length; i++) {
        lines = allTextLines[i].split(',');
        point={location: { accuracy: 1, latitude: lines[0], longitude: lines[1] },timestamp: "", grid:""};
        result.push(taniarza(i,point));
    }
}    
    
$(document).ready(function() {    
    var gridSize=0.005;
    var gridWeight=0.5; //Stroke width in pixels.
    var gridColor="grey"; //Stroke color.
    var gridFillColor="grey";
    var gridFillOpacity=0.01;
    var gridClassName="";
    
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize,gridWeight,gridColor,gridFillOpacity);
    
    $.ajax({
        type: "GET",
        url: "allpoint.csv",
        dataType: "text",
        success: function(data) {
           natya(data); 
           for (var i=0; i<result.length; i++) {
            $("#result").append(result[i]);  
           }
        }
    });
    
//    $.ajax({
//        type: "GET",
//        url: "output_prefixspan.csv",
//        dataType: "text",
//        success: function(data) {
//           mapGridODPair(data); 
//        
//           //var centroidGrid=gridByNumber[0].centroidGrid.split(",");
//           //var circle = L.circle([centroidGrid[0], centroidGrid[1]], 5, { color: "red", fillColor: "red", fillOpacity: 1}).bindLabel().addTo(map);
//        }
//    });
    
//    $.ajax({
//        type: "GET",
//        url: "argo_gps_join_12.csv",
//        dataType: "text",
//        success: function(data) {
//            var drawPointOrigin=true; //blue
//            var drawPointDestination=true; //green
//            
//            var mapOrigin=true;
//            var mapDestination=true;
//            
//            var drawCentroidOrigin=false; //blue   
//            var epsOrigin=0.2;
//            var minPtsOrigin=2;
//            var clusterColorOrigin="blue";
//            var drawPointRadiusOrigin=10;
//            
//            var drawCentroidDestination=false; //green
//            var epsDestination=0.2;
//            var minPtsDestination=2;
//            var clusterColorDestination="green";
//            var drawPointRadiusDestination=10;
//            
//            var when="2015-12-25";
//            
//            //read_draw_count_data(data,when,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bandungBounds,gridSize); 
//        }
//    });

});
    
</script> 
</head>

<body>
    <div id='result'></div>  
    <div id='map'></div>  
</body>
</html>