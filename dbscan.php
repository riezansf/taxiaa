<html>
<head>
<meta charset=utf-8 />
<title>AA Taksi DBSCAN Frequent O-D Pattern</title>
<meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>    
    
<link rel="stylesheet" href="leaflet/leaflet.css" />
<script src="leaflet/leaflet.js"></script>
<script src="Leaflet.label/Label.js"></script>
<script src="Leaflet.label/BaseMarkerMethods.js"></script>
<script src="Leaflet.label/Marker.Label.js"></script>
<script src="Leaflet.label/Map.Label.js"></script>
<script src="Leaflet.label/Path.Label.js"></script>
    
<script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>
    
<script src="util.js"></script>
    
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
</head
<body>

<div id='map'></div>
    
<script>

var map = L.map('map').setView([-6.914744, 107.609810], 13);
L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
    maxZoom: 18,
    id: 'laezano.18b09133',
    accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
}).addTo(map);
    
$(document).ready(function() {
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
        
    //===== Read data
    var old_time = new Date();
    for (var i=0; i<allTextLines.length; i++) {
        var lines = allTextLines[i].split(',');
        //lines[1]=="2015-12-25" && 
        if(lines[8]!="" && lines[9]!="" && lines[14]!="" && lines[15]!="" && lines[8]!=null && lines[9]!=null && lines[14]!=null && lines[15]!=null){  //if coordinate !=""
            originPoint.push({location: { accuracy: 1, latitude: lines[8], longitude: lines[9] }});
            destinationPoint.push({location: { accuracy: 1, latitude: lines[14], longitude: lines[15] }});
            
            //var circle = L.circle([lines[8], lines[9]], 5, { color: "red", fillColor: "red", fillOpacity: 1}).bindLabel(originPoint.length+". "+lines[8]+","+lines[9]).addTo(map);
        }
    }
    var new_time = new Date();
    console.log("Read data & Draw = "+(new_time - old_time)+" ms");
    
    //======== CLUSTERING ORIGIN POINT
    // Eps -+ jarak masuk akal antar lokasi trip yang masih dibilang satu kawasan
    // Min pts, expert judgement, 1 day = xx trip in each place
    var old_time = new Date();
    var dbscanOrigin = jDBSCAN().eps(0.1).minPts(30).distance('HAVERSINE').data(originPoint);
    var dbscanResultOrigin = dbscanOrigin();
    var clusterCentersOrigin = dbscanOrigin.getClusters();
    var clusterCountOrigin=[];
    var unclusteredOrigin=0;
    
    for(var i=0; i<dbscanResultOrigin.length; i++){
        if(clusterCountOrigin[dbscanResultOrigin[i]]==null){ clusterCountOrigin[dbscanResultOrigin[i]]=0; }
        clusterCountOrigin[dbscanResultOrigin[i]]++;  
        
        if(dbscanResultOrigin[i]!=0){
            //colors.getRandom()  //dbscanResultOrigin[2]   
            var circle = L.circle([originPoint[i].location.latitude, originPoint[i].location.longitude], 10, {
                color: markerColors[dbscanResultOrigin[i]],
                fillColor: markerColors[2],
                fillOpacity: 0.5
            }).addTo(map);
                      
//Draw line origin point to destintion
//            var polyline = L.polyline(
//                [new L.LatLng(originPoint[i].location.latitude, originPoint[i].location.longitude),new L.LatLng(destinationPoint[i].location.latitude, destinationPoint[i].location.longitude)], 
//                {
//                    color: 'red',
//                    weight: 1
//                }
//            ).addTo(map);
        }else{
            unclusteredOrigin++;
        }       
    }
    console.log("== ORIGIN ==");
    console.log("Total trip = "+ originPoint.length);
    console.log("Cluster summary = "+ clusterCountOrigin);
    console.log("Cluster center = "+ clusterCentersOrigin);
    console.log("Cluster count = "+ (clusterCountOrigin.length-1));
    console.log("Clustered trip = "+ (originPoint.length-clusterCountOrigin[0]) );
    console.log("Unclustered trip = "+ clusterCountOrigin[0]);
    var new_time = new Date();
    console.log("Clustering origin point = "+(new_time - old_time)+" ms");
    
//    //========== CULSTERING DESTINATION POINT
//    var old_time = new Date();
//    var dbscanDestination = jDBSCAN().eps(0.3).minPts(10).distance('HAVERSINE').data(destinationPoint);
//    var dbscanResultDestination = dbscanDestination();
//    var clusterCountDestination=[];
//    var unclusteredDestination=0;
//    
//    for(var i=0; i<dbscanResultDestination.length; i++){
//        if(clusterCountDestination[dbscanResultDestination[i]]==null){ clusterCountDestination[dbscanResultDestination[i]]=0; }
//        clusterCountDestination[dbscanResultDestination[i]]++;  
//        
//        if(dbscanResultDestination[i]!=0){
//            //colors.getRandom()    
//            var circle = L.circle([destinationPoint[i].location.latitude, destinationPoint[i].location.longitude], 10, {
//                color: markerColors[3],
//                //fillColor: markerColors[3],
//                fillOpacity: 0.5
//            }).addTo(map);
//        
//        }else{
//            unclusteredDestination++;
//        }       
//    }
//    console.log("== DESTINATION ==");
//    console.log("Total trip = "+ destinationPoint.length);
//    console.log("Cluster summary = "+ clusterCountDestination);
//    console.log("Cluster count = "+ (clusterCountDestination.length-1));
//    console.log("Clustered trip = "+ (destinationPoint.length-clusterCountDestination[0]) );
//    console.log("Unclustered trip = "+ clusterCountDestination[0]);
//    var new_time = new Date();
//    console.log("Clustering destination point = "+(new_time - old_time)+" ms");    
}
</script> 
</body>
</html>

