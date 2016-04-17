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

var gridCountOrigin=[];
var gridCountDestination=[]; 
   
var centroidOrigin=[];
var centroidGridNumOrigin=[];
    
var centroidDestination=[];
var centroidGridNumDestination=[];
    
var grid=[];
    
function buildMap(bandungCentroid){
    var start = new Date();
    map = L.map('map').setView(bandungCentroid, 13);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 18,
        id: 'laezano.18b09133',
        accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
    }).addTo(map);
    console.log("Build map time = "+(new Date() - start)+"ms");
}    

function drawBound(bound,color,weight,fillOpacity){
    var start = new Date();
    L.rectangle(bound, {color: color, weight: weight, fillOpacity:fillOpacity}).addTo(map);
    map.fitBounds(bound);
    console.log("Draw bound time = "+(new Date() - start)+"ms");
}

//=== GRID WITH LINE
function drawGridLine(bounds,gridSize,weight,color){
    var start = new Date();
    var gridCount=0;
    var row=0; var col=0;
    for(var j=bounds[0][0];j>=bounds[1][0];j=(j-gridSize).toFixed(12)){
        var k=bounds[0][1]; 
        col=0;
        while(k<bounds[1][1]){
            //draw column
            var polyline = L.polyline([new L.LatLng(bounds[0][0],k),new L.LatLng(bounds[1][0],k)], { color: color, weight: weight }).bindLabel("col "+col).addTo(map);
            k=(k+gridSize);
            gridCount++;
            col++;
        }
        //draw row
        var polyline = L.polyline([new L.LatLng(j,bounds[0][1]),new L.LatLng(j,bounds[1][1])], { color: color, weight: weight }).bindLabel("row "+row).addTo(map);
        row++;
    }
    console.log("Draw grid line = "+(new Date() - start)+"ms");
    console.log("Grid size = "+row+"x"+col);   
}
    
function mapPointToGridLineOrigin(lines,bounds,gridSize){
    var row=0; var col=0;
    for(var j=bounds[0][0];j>=bounds[1][0];j=(j-gridSize).toFixed(12)){
        var k=bounds[0][1]; 
        col=0;
        if(gridCountOrigin[row]==null){gridCountOrigin[row]=[]}
        while(k<bounds[1][1]){
            //count grid origin
            if(gridCountOrigin[row][col]==null){gridCountOrigin[row][col]=[];}
            if( lines[8]>j && lines[8]<(j-gridSize).toFixed(12) && lines[9]>k && lines[9]<(k+gridSize)){
                //console.log(originPoint.length+". "+lines[8]+","+lines[9]+" in grid "+row+","+col);
                gridCountOrigin[row][col].push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] }});
                break;
            }
            k=(k+gridSize);
            col++;
        }
        row++;
    }
}    
    
function mapPointToGridLineDestination(lines,bounds,gridSize){
    var row=0; var col=0;
    for(var j=bounds[0][0];j>=bounds[1][0];j=(j-gridSize).toFixed(12)){
        var k=bounds[0][1]; 
        col=0;
        if(gridCountDestination[row]==null){gridCountDestination[row]=[]}
        while(k<bounds[1][1]){
            //count grid destination
            if(gridCountDestination[row][col]==null){gridCountDestination[row][col]=[];}
            if( lines[14]>j && lines[14]<(j-gridSize).toFixed(12) && lines[15]>k && lines[15]<(k+gridSize)){
                gridCountDestination[row][col].push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] }});
                break;
            }
            k=(k+gridSize);
            col++;
        }
        row++;
    }
}  
  
function calculateCentroidLineOrigin(drawCentroid){
    var old_time = new Date();;
    for(var i=0;i<gridCountOrigin.length;i++){
        centroidGridNumOrigin[i]=[];
        for(var j=0;j<gridCountOrigin[i].length;j++){     
            if(gridCountOrigin[i][j].length>1){
                var latXTotal = 0;
                var latYTotal = 0;
                var lonDegreesTotal = 0;
                for(var k=0;k<gridCountOrigin[i][j].length;k++){
                    var latDegrees = parseFloat(gridCountOrigin[i][j][k].location.latitude);
                    var lonDegrees = parseFloat(gridCountOrigin[i][j][k].location.longitude);
                    
                    var latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                var finalLatRadians = Math.atan2(latYTotal, latXTotal);
                var finalLatDegrees = finalLatRadians * 180 / Math.PI;
                var finalLonDegrees = lonDegreesTotal / gridCountOrigin[i][j].length;
            
                centroidOrigin.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                centroidGridNumOrigin[i][j]={location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }};
                
                //Centroid is solid RED
                if(drawCentroid){
                    var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "red", fillColor: "red", fillOpacity: 1}).addTo(map);
                }
            }
            else if(gridCountOrigin[i][j].length==1){
                centroidOrigin.push({location: { accuracy: 1, latitude: gridCountOrigin[i][j][0].location.latitude, longitude: gridCountOrigin[i][j][0].location.longitude }});
                if(drawCentroid){
                    var circle = L.circle([gridCountOrigin[i][j][0].location.latitude, gridCountOrigin[i][j][0].location.longitude], 5, { color: "red", fillColor: "red", fillOpacity: 0.5}).addTo(map);
                } 
            }
        }
    }
    var new_time = new Date();
    console.log("Calculate centroid origin time = "+(new_time - old_time)+" ms");
} 
    
function calculateCentroidLineDestination(drawCentroid){
    var old_time = new Date();
    for(var i=0;i<gridCountDestination.length;i++){
        centroidGridNumDestination[i]=[];
        for(var j=0;j<gridCountDestination[i].length;j++){     
            if(gridCountDestination[i][j].length>1){
                var latXTotal = 0;
                var latYTotal = 0;
                var lonDegreesTotal = 0;
                for(var k=0;k<gridCountDestination[i][j].length;k++){
                    var latDegrees = parseFloat(gridCountDestination[i][j][k].location.latitude);
                    var lonDegrees = parseFloat(gridCountDestination[i][j][k].location.longitude);
                    
                    var latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                var finalLatRadians = Math.atan2(latYTotal, latXTotal);
                var finalLatDegrees = finalLatRadians * 180 / Math.PI;
                var finalLonDegrees = lonDegreesTotal / gridCountDestination[i][j].length;
            
                centroidDestination.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                centroidGridNumDestination[i][j]={location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }};
                
                if(drawCentroid){
                    var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "red", fillColor: "red", fillOpacity: 0.5}).addTo(map);    
                }
                
            }
            else if(gridCountDestination[i][j].length==1){
                centroidDestination.push({location: { accuracy: 1, latitude: gridCountDestination[i][j][0].location.latitude, longitude: gridCountDestination[i][j][0].location.longitude }});
                if(drawCentroid){
                    var circle = L.circle([gridCountDestination[i][j][0].location.latitude, gridCountDestination[i][j][0].location.longitude], 5, { color: "red", fillColor: "red", fillOpacity: 0.5}).addTo(map);
                }
            }
        }
    }
    var new_time = new Date();
    console.log("Calculate centroid time = "+(new_time - old_time)+" ms");
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
    console.log("Draw grid rectangle = "+(new Date() - start)+"ms");
    console.log("Grid size = "+row+"x"+col+"  TOTAL grid : "+gridCount); 
}
   
function mapPointToGrid(lat,long,OD){
    for(var j=0;j<grid.length;j++){
        for(var k=0;k<grid[j].length;k++){
            if( lat>grid[j][k].topLeft[0] && 
                lat<grid[j][k].rightBottom[0] &&  
                long<grid[j][k].rightBottom[1]){
                
                if(OD=="origin"){
                    grid[j][k].origin.push({location: { accuracy: 1, latitude: lat, longitude: long }});
                }else{
                    grid[j][k].destination.push({location: { accuracy: 1, latitude: lat, longitude: long }});
                }    
                break;
            }
        }
    }
}
    
function calculateCentroidOrigin(drawCentroid){
    var old_time = new Date();;
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
            
                grid[i][j].centroidOrigin.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                centroidOrigin.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});;
                
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
            }
        }
    }
    var new_time = new Date();
    console.log("Calculate centroid origin time = "+(new_time - old_time)+" ms");
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
            
                grid[i][j].centroidDestination.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                centroidDestination.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                
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
}    
    
function read_draw_count_data(csv,date,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bounds,gridSize){
    allTextLines = csv.split(/\r\n|\n/);
    var lines = [];

    var old_time = new Date();
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
        
        if(date==""){ var whereDate=true; }else{ var whereDate=date; }
        
        if(lines[1]==whereDate && lines[9]!="" && lines[14]!="" && lines[15]!="" && lines[8]!=null && lines[9]!=null && lines[14]!=null && lines[15]!=null){  //if coordinate !=""
            
            originPoint.push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] }});
            destinationPoint.push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] }});
                        
            if(drawPointOrigin){ //origin point color is blue
                var circle = L.circle([lines[8], lines[9]], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).bindLabel(originPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
            }
            if(drawPointDestination){ //destination point color is green
                var circle = L.circle([lines[14], lines[15]], 5, { color: "green", fillColor: "green", fillOpacity: 1}).bindLabel(destinationPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
            }
            
            if(mapOrigin){
                //mapPointToGridLineOrigin(lines,bounds,gridSize);
                mapPointToGrid(lines[8],lines[9],"origin");
            }
            if(mapDestination){
                //mapPointToGridLlineDestination(lines,bounds,gridSize);
                mapPointToGrid(lines[14],lines[15],"destination");
            }
        }
    }
    var new_time = new Date();
    console.log("Read data, Draw, & Count Grid = "+(new_time - old_time)+" ms");
}   
    
function dbscan(data,eps,minPts,color,drawPointRadius){
   
    var old_time = new Date();
    var dbscan = jDBSCAN().eps(eps).minPts(minPts).distance('HAVERSINE').data(data);
    var dbscanResult = dbscan();
    var clusterCenters = dbscan.getClusters();
    var clusterCount=[];
    var unclustered=0;
    
    for(var i=0; i<dbscanResult.length; i++){
        if(clusterCount[dbscanResult[i]]==null){ clusterCount[dbscanResult[i]]=0; }
        clusterCount[dbscanResult[i]]++;  
        
        if(dbscanResult[i]!=0){
            //colors.getRandom()  //dbscanResultOrigin[2]   
            if(color=="random"){
                var drawColor=markerColors[dbscanResult[i]];
            }else{
                var drawColor=color;                               
            }
            
            var circle = L.circle([data[i].location.latitude, data[i].location.longitude], drawPointRadius, {
                color: drawColor,
                fillColor: drawColor,
                fillOpacity: 1
            }).addTo(map);              
        }else{
            unclustered++;
        }       
    }
    console.log("== CLUSTERING ==");
    console.log("Total point = "+ data.length);
    console.log("Cluster summary = "+ clusterCount);
    console.log("Cluster center = "+ clusterCenters);
    console.log("Cluster count = "+ (clusterCount.length-1));
    console.log("Clustered trip = "+ (data.length-clusterCount[0]) );
    console.log("Unclustered trip = "+ clusterCount[0]);
    var new_time = new Date();
    console.log("Clustering time = "+(new_time - old_time)+" ms");
}    
    
$(document).ready(function() {
    var boundColor="grey";
    var boundWeight=0.1;
    var boundOppacity=0.2;
    var gridLineWeight=0.1;
    var gridLineColor="grey";
    
    var gridSize=0.001;

    var gridWeight=0.5;
    var gridColor="grey";
    var gridFillOpacity=0.1;
    
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize,gridWeight,gridColor,gridFillOpacity);
    
    //== grid with line
    //drawBound(bandungBounds,boundColor,boundWeight,boundOppacity);
    //drawGridLine(bandungBounds,gridSize,gridLineWeight,gridLineColor);
    
    $.ajax({
        type: "GET",
        url: "argo_gps_join_12.csv",
        dataType: "text",
        success: function(data) {
            var drawPointOrigin=false; //blue
            var drawPointDestination=false; //green
            
            var mapOrigin=true;
            var mapDestination=true;
            
            var drawCentroidOrigin=false; //blue   
            var epsOrigin=0.2;
            var minPtsOrigin=2;
            var clusterColorOrigin="random";
            var drawPointRadiusOrigin=10;
            
            var drawCentroidDestination=false; //green
            var epsDestination=0.2;
            var minPtsDestination=2;
            var clusterColorDestination="random";
            var drawPointRadiusDestination=10;
            
            var when="2015-12-25";
            
            //Rectangle gridbased dbscan
            read_draw_count_data(data,when,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bandungBounds,gridSize); 
            
            calculateCentroidOrigin(drawCentroidOrigin); 
            console.log("Total trip : "+originPoint.length+" , Active grid Origin :"+centroidOrigin.length);
//            dbscan(centroidOrigin,epsOrigin,minPtsOrigin,clusterColorOrigin,drawPointRadiusOrigin);
//            
            calculateCentroidDestination(drawCentroidDestination);    
            console.log("Total trip : "+destinationPoint.length+" , Active grid Destination:"+centroidDestination.length);
            
            //dbscan(centroidDestination,epsDestination,minPtsDestination,clusterColorDestination,drawPointRadiusDestination);
        
        
            //==== Non gridbased dbscan
//            dbscan(originPoint,0.2,5,clusterColorOrigin,10);
//            dbscan(destinationPoint,0.2,5,clusterColorDestination,10);
        
        }
    });
});
    
//=================== FOR ANIMATION
//        var i = 0;                     //  set your counter to 1
//        function myLoop () {           //  create a loop function
//           setTimeout(function () {    //  call a 3s setTimeout when the loop is called
//                  //map.removeLayer(marker); 
//                marker = new L.Marker(new L.LatLng(lines[i][5], lines[i][4]), {
//                    icon:	new L.NumberedDivIcon({number: i})
//                });
//                marker.addTo(map);
//                
//
//                i++;                     //  increment the counter
//                if (i < lines.length) {            //  if the counter < 10, call the loop function
//                 myLoop();             //  ..  again which will trigger another 
//                }                        //  ..  setTimeout()
//           }, 100)
//        }
//        myLoop();                      //  start the loop     
    
</script> 
</head>

<body>
    <div id='map'></div>  
</body>
</html>