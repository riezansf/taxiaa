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
   
var centroidOrigin=[];
var centroidDestination=[];
    
var grid=[];
    
var pointMappedToGridO=0;
var pointMappedToGridD=0;
    
var clusteredPoints=[];
    
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
    
function read_draw_count_data(csv,date,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bounds,gridSize){
    allTextLines = csv.split(/\r\n|\n/);
    var lines = [];

    var old_time = new Date();
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
        
        if(date==""){ var whereDate=true; }else{ var whereDate=date; }
        
        if(lines[1]==whereDate && lines[9]!="" && lines[14]!="" && lines[15]!="" && lines[8]!=null && lines[9]!=null && lines[14]!=null && lines[15]!=null){  //if coordinate !=""
                      
            originPoint.push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] },timestamp: Math.round(new Date(lines[1]+" "+lines[2]).getTime()/1000)});
            destinationPoint.push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] },timestamp: Math.round(new Date(lines[1]+" "+lines[3]).getTime()/1000)});
                        
            if(drawPointOrigin){ //origin point color is blue
                var circle = L.circle([lines[8], lines[9]], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).bindLabel(originPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
            }
            if(drawPointDestination){ //destination point color is green
                var circle = L.circle([lines[14], lines[15]], 5, { color: "green", fillColor: "green", fillOpacity: 1}).bindLabel(destinationPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
            }
            
            if(mapOrigin){
                mapPointToGrid(lines[8],lines[9],"origin");
            }
            if(mapDestination){
                mapPointToGrid(lines[14],lines[15],"destination");
            }
        }
    }
    var new_time = new Date();
    console.log("\nTime to read data, draw point, & map to grid = "+(new_time - old_time)+" ms");
    console.log("Origin point "+originPoint.length+" , Destination point : "+destinationPoint.length);
    console.log("Origin point mapped to grid : "+pointMappedToGridO+" , Destination point mapped to grid : "+pointMappedToGridD);
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
    
$(document).ready(function() {    
    var gridSize=0.001;
    var gridWeight=0.1; //Stroke width in pixels.
    var gridColor="grey"; //Stroke color.
    var gridFillColor="grey";
    var gridFillOpacity=0.01;
    var gridClassName="";
    
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize,gridWeight,gridColor,gridFillOpacity);
    
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
            var clusterColorOrigin="blue";
            var drawPointRadiusOrigin=10;
            
            var drawCentroidDestination=false; //green
            var epsDestination=0.2;
            var minPtsDestination=2;
            var clusterColorDestination="green";
            var drawPointRadiusDestination=10;
            
            var when="2015-12-25";
            
            //Rectangle gridbased dbscan
            read_draw_count_data(data,when,drawPointOrigin,drawPointDestination,mapOrigin,mapDestination,bandungBounds,gridSize); 
            
    //            calculateCentroidOrigin(drawCentroidOrigin); 
    //            dbscan(centroidOrigin,epsOrigin,minPtsOrigin,clusterColorOrigin,drawPointRadiusOrigin);
    //          
    //            calculateCentroidDestination(drawCentroidDestination);    
    //            dbscan(centroidDestination,epsDestination,minPtsDestination,clusterColorDestination,drawPointRadiusDestination);
    //        
        
            //==== Non gridbased dbscan
            dbscan(originPoint,0.2,5,"asdasd","random",10);
//            dbscan(destinationPoint,0.2,5,clusterColorDestination,10);
        
            for(var i=1; i<clusteredPoints.length; i++){
                for(var j=0; j<clusteredPoints[i].length; j++){
                    var date = new Date(clusteredPoints[i][j].timestamp*1000);
                    // Hours part from the timestamp
                    var hours = date.getHours();
                    // Minutes part from the timestamp
                    var minutes = "0" + date.getMinutes();
                    // Seconds part from the timestamp
                    var seconds = "0" + date.getSeconds();
                    var time = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);

                    console.log(i+" "+time);
                }
                
            }
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