<html>
<head>
    <meta charset=utf-8 />
    <title>AA Taksi Frequent O-D Pattern</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>   
    <script src="leaflet/leaflet.js"></script>
    <script src="Leaflet.label/Label.js"></script>
    <script src="Leaflet.label/BaseMarkerMethods.js"></script>
    <script src="Leaflet.label/Marker.Label.js"></script>
    <script src="Leaflet.label/Map.Label.js"></script>
    <script src="Leaflet.label/Path.Label.js"></script>
    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>
    <script src="util.js"></script> 
    
<script>
//Var    
    
function buildMap(){
    var map = L.map('map').setView([-6.914744, 107.609810], 13);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 18,
        id: 'laezano.18b09133',
        accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
    }).addTo(map);
}    

function drawBound(){
    //lat = atas-bawah Y, makin kecil makin ke atas (utara)
    //long = kiri-kanan X, makin kecil makin ke kiri (barat)
    //atas-bawah = 14.23 KM , kiri-kanan=21.09 KM , diagonal=25.44 KM, luas=300.1107 KM
    //0.001 point GPS = 109.5546875 Meter 
    //Grid size (Meter) 10 / 25 / 50 / 100 / 200

    //===== Draw Bandung Rectangle
    var bounds = [[-6.839, 107.547], [-6.967, 107.738]]; //Bounds BANDUNG ONLY
    //var bounds = [[-6.784, 107.493], [-7.057, 107.827]]; //Bounds include CIMAHI, LEMBANG, CILEUNYI, RANCAEKEK, SOREANG 
    L.rectangle(bounds, {color: "#ff7800", weight: 0.1, fillOpacity:0.01}).addTo(map);
    map.fitBounds(bounds);
}

function drawGrid(){
   //===== Draw Line to create GRID
    var gridCount=0;
    var row=0; var col=0;
    for(var j=-6.839;j>=-6.967;j=(j-0.001).toFixed(12)){
        var k=107.547; 
        col=0;
        while(k<107.738){
            //draw column
            var polyline = L.polyline([new L.LatLng(-6.839,k),new L.LatLng(-6.967,k)], { color: 'grey', weight: 0.1 }).bindLabel("col "+col).addTo(map);
            k=(k+0.001);
            gridCount++;
            col++;
        }
        //draw row
        var polyline = L.polyline([new L.LatLng(j,107.547),new L.LatLng(j,107.738)], { color: 'grey', weight: 0.1 }).bindLabel("row "+row).addTo(map);
        row++;
    }
    console.log("Grid size = "+row+"x"+col);   

    //==== TODO Draw Rectangle to create grid
    //label = new L.Label()
    //label.setContent("static label")
    //label.setLatLng(polygon.getBounds().getCenter())
    //map.showLabel(label); 
}
    
    
$(document).ready(function() {
    buildMap();
    drawBound();
    drawGrid();
    
    $.ajax({
        type: "GET",
        url: "argo_gps_join_12.csv",
        dataType: "text",
        success: function(data) {
            processData(data);
        }
    });
});
    
function processData(allText) {
    var allTextLines = allText.split(/\r\n|\n/);
    var lines = [];
    
    var originPoint=[];
    var destinationPoint=[];
    
    var countGridOrigin=[];
    var countGridDestination=[];
    
    //===== Read data, Draw, & Count Grid
    var old_time = new Date();
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
        //lines[1]=="2015-12-25" && 
        if(lines[1]=="2015-12-25" && lines[9]!="" && lines[14]!="" && lines[15]!="" && lines[8]!=null && lines[9]!=null && lines[14]!=null && lines[15]!=null){  //if coordinate !=""
            originPoint.push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] }});
            destinationPoint.push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] }});
                        
            //var circle = L.circle([lines[8], lines[9]], 5, { color: "red", fillColor: "red", fillOpacity: 1}).bindLabel(originPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
            
            //count grid
            var row=0; var col=0;
            for(var j=-6.839;j>=-6.967;j=(j-0.001).toFixed(12)){
                var k=107.547; 
                col=0;
                if(countGridOrigin[row]==null){countGridOrigin[row]=[]}
                while(k<107.738){
//                    //count grid origin
//                    if(countGridOrigin[row][col]==null){countGridOrigin[row][col]=[];}
//                    if( lines[8]>j && lines[8]<(j-0.001).toFixed(12) && lines[9]>k && lines[9]<(k+0.001)){
//                        //console.log(originPoint.length+". "+lines[8]+","+lines[9]+" in grid "+row+","+col);
//                        countGrid[row][col].push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] }});
//                        break;
//                    }
                    
                    //count grid destination
                    if(countGridDestination[row][col]==null){countGridDestination[row][col]=[];}
                    if( lines[14]>j && lines[14]<(j-0.001).toFixed(12) && lines[15]>k && lines[15]<(k+0.001)){
                        countGridDestination[row][col].push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] }});
                        break;
                    }
                    
                    k=(k+0.001);
                    col++;
                }
                row++;
            }
        }
    }
    var new_time = new Date();
    console.log("Read data, Draw, & Count Grid = "+(new_time - old_time)+" ms");
    
    //==== Calculate center point (centroid) in each grid
    var old_time = new Date();
    var midPoint=[];
    var midPointGrid=[];
//    for(var i=0;i<countGridOrigin.length;i++){
    for(var i=0;i<countGridDestination.length;i++){
        midPointGrid[i]=[];
//        for(var j=0;j<countGridOrigin[i].length;j++){     
        for(var j=0;j<countGridDestination[i].length;j++){     
//            if(countGridOrigin[i][j].length>1){
            if(countGridDestination[i][j].length>1){
                var latXTotal = 0;
                var latYTotal = 0;
                var lonDegreesTotal = 0;
//                for(var k=0;k<countGridOrigin[i][j].length;k++){
                for(var k=0;k<countGridDestination[i][j].length;k++){
//                    var latDegrees = parseFloat(countGrid[i][j][k].location.latitude);
//                    var lonDegrees = parseFloat(countGrid[i][j][k].location.longitude);
                    
                    var latDegrees = parseFloat(countGridDestination[i][j][k].location.latitude);
                    var lonDegrees = parseFloat(countGridDestination[i][j][k].location.longitude);

                    var latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                var finalLatRadians = Math.atan2(latYTotal, latXTotal);
                var finalLatDegrees = finalLatRadians * 180 / Math.PI;
                var finalLonDegrees = lonDegreesTotal / countGridDestination[i][j].length;
//                var finalLonDegrees = lonDegreesTotal / countGrid[i][j].length;
            
                midPoint.push({location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }});
                midPointGrid[i][j]={location: { accuracy: 1, latitude: finalLatDegrees, longitude: finalLonDegrees }};
                
                //console.log(i+","+j+" "+countGrid[i][j].length+" "+finalLatDegrees+","+finalLonDegrees);
                //var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "black", fillColor: "black", fillOpacity: 0.5}).addTo(map);
            }
//            else if(countGridOrigin[i][j].length==1){
            else if(countGridDestination[i][j].length==1){
                midPoint.push({location: { accuracy: 1, latitude: countGrid[i][j][0].location.latitude, longitude: countGrid[i][j][0].location.longitude }});
                //var circle = L.circle([countGrid[i][j][0].location.latitude, countGrid[i][j][0].location.longitude], 5, { color: "red", fillColor: "red", fillOpacity: 0.5}).addTo(map);
            }
        }
    }
    var new_time = new Date();
    console.log("Calculate centroid = "+(new_time - old_time)+" ms");
    
    
    //======== CLUSTERING ORIGIN GRID MID POINT
    // Eps -+ 2xgridSize 
    // Min pts = jumlah grid yang masih dalam satu kawasan
    var old_time = new Date();
    var dbscanOriginMidPoint = jDBSCAN().eps(0.2).minPts(2).distance('HAVERSINE').data(midPoint);
    var dbscanResultOriginMidPoint = dbscanOriginMidPoint();
    var clusterCentersOriginMidPoint = dbscanOriginMidPoint.getClusters();
    var clusterCountOriginMidPoint=[];
    var unclusteredOriginMidPoint=0;
    
    for(var i=0; i<dbscanResultOriginMidPoint.length; i++){
        if(clusterCountOriginMidPoint[dbscanResultOriginMidPoint[i]]==null){ clusterCountOriginMidPoint[dbscanResultOriginMidPoint[i]]=0; }
        clusterCountOriginMidPoint[dbscanResultOriginMidPoint[i]]++;  
        
        if(dbscanResultOriginMidPoint[i]!=0){
            //colors.getRandom()  //dbscanResultOrigin[2]   
            var circle = L.circle([midPoint[i].location.latitude, midPoint[i].location.longitude], 10, {
                color: markerColors[dbscanResultOriginMidPoint[i]],
                fillColor: markerColors[dbscanResultOriginMidPoint[i]],
                fillOpacity: 2
            }).addTo(map);              
        }else{
            unclusteredOriginMidPoint++;
        }       
    }
    console.log("== ORIGIN MIDPOINT ==");
    console.log("Total point = "+ midPoint.length);
    console.log("Cluster summary = "+ clusterCountOriginMidPoint);
    console.log("Cluster center = "+ clusterCentersOriginMidPoint);
    console.log("Cluster count = "+ (clusterCountOriginMidPoint.length-1));
    console.log("Clustered trip = "+ (midPoint.length-clusterCountOriginMidPoint[0]) );
    console.log("Unclustered trip = "+ clusterCountOriginMidPoint[0]);
    var new_time = new Date();
    console.log("Clustering time = "+(new_time - old_time)+" ms");
        
    //========== CULSTERING DESTINATION POINT
    var dbscanDestination = jDBSCAN().eps(0.25).minPts(10).distance('HAVERSINE').data(destinationPoint);
    var dbscanResultDestination = dbscanDestination();
    var clusterCountDestination=[];
    var unclusteredDestination=0;
    
    for(var i=0; i<dbscanResultDestination.length; i++){
        if(clusterCountDestination[dbscanResultDestination[i]]==null){ clusterCountDestination[dbscanResultDestination[i]]=0; }
        clusterCountDestination[dbscanResultDestination[i]]++;  
        
        if(dbscanResultDestination[i]!=0){
            //colors.getRandom()    
            var circle = L.circle([destinationPoint[i].location.latitude, destinationPoint[i].location.longitude], 10, {
                color: markerColors[3],
                //fillColor: markerColors[3],
                fillOpacity: 0.5
            }).addTo(map);
            

            
        }else{
            unclusteredDestination++;
        }       
    }

    console.log("== DESTINATION ==");
    console.log("Total trip = "+ destinationPoint.length);
    console.log("Cluster summary = "+ clusterCountDestination);
    console.log("Cluster count = "+ (clusterCountDestination.length-1));
    console.log("Clustered trip = "+ (destinationPoint.length-clusterCountDestination[0]) );
    console.log("Unclustered trip = "+ clusterCountDestination[0]);
    
}
    
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

