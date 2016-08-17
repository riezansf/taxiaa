<html>
<head>
    <meta charset=utf-8 />
    <title>ARIMA</title>
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
    <script src="randomColor.js"></script> 
    
    <link rel="stylesheet" href="jquery/jquery-ui.css" />
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <link rel="stylesheet" href="tools_style.css" /> 
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
      
 
var gridSize=0.005;
var styleGrid={weight:0.5, color:'grey',fillColor:'grey',fillOpacity:0.01};
    
var originPoint=[]; 
var originMarkers = new L.FeatureGroup(); 
var originClusterMarkers = new L.FeatureGroup(); 
   
var centroidOrigin=[];
//var centroidDestination=[];
    
var grid=[];
var gridId=[];
    
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
function drawGridRectangle(bounds,gridSize){
    var start = new Date();
    var gridCount=1;
    var row=1; var col=1;
    var rectangle;
    var k; var bound;
    
    for(var j=bounds[0][0];j>=bounds[1][0];j=(j-gridSize).toFixed(12)){
        if(grid[row]==null){grid[row]=[]}
        
        k=bounds[0][1]; 
        col=1;
        while(k<bounds[1][1]){
            bound=[[j, k] , [(j-gridSize).toFixed(12),(k+gridSize).toFixed(12)]];
            rectangle = L.rectangle(bound, styleGrid).bindLabel(gridCount+" "+row+","+col).addTo(map);
         
            gridId[gridCount]={
                row: row,
                col: col,
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
   
function mapPointToGrid(lat,long){
    for(var j=0;j<grid.length;j++){
        for(var k=0;k<grid[j].length;k++){
            if( lat>grid[j][k].topLeft[0] && lat<grid[j][k].rightBottom[0] && long<grid[j][k].rightBottom[1]){
               
                grid[j][k].origin.push({location: { accuracy: 1, latitude: (lat), longitude: (long) }});
                pointMappedToGridO++;
                  
                break;
            }
        }
    }
}
    
function getGridId(lat,long){
    for(var j=1;j<gridId.length;j++){
        if(lat>gridId[j].topLeft[0] && lat<gridId[j].rightBottom[0] && long<gridId[j].rightBottom[1]){
            return j;       
        }
    }
    return "Luar Bdg";
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
                    var circle = L.circle([finalLatDegrees, finalLonDegrees], 5, { color: "red", fillColor: "red", fillOpacity: 1}).addTo(map);
                }
            }
            else if(grid[i][j].origin.length==1){
                grid[i][j].centroidOrigin.push({location: { accuracy: 1, latitude: grid[i][j].origin[0].location.latitude, longitude: grid[i][j].origin[0].location.longitude }});
                centroidOrigin.push({location: { accuracy: 1, latitude: grid[i][j].origin[0].location.latitude, longitude: grid[i][j].origin[0].location.longitude  }});;
                
                if(drawCentroid){
                    var circle = L.circle([grid[i][j].origin[0].location.latitude, grid[i][j].origin[0].location.longitude ], 5, { color: "red", fillColor: "red", fillOpacity: 0.5}).addTo(map);
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
     
function read_draw_count_data(data){
    var old_time = new Date();
    var circle;
    var gridNo;
    
    map.removeLayer(originMarkers);
    originMarkers=new L.FeatureGroup(); 
    for (var i=0; i<data.length; i++) {
        originPoint.push({location: { accuracy: 1, latitude: data[i].pickup2_lat, longitude: data[i].pickup2_long }});

        //origin point color is blue
        circle = L.circle([data[i].pickup2_lat,data[i].pickup2_long], 5, { color: "blue", fillColor: "blue", fillOpacity: 1}).bindLabel(data[i].trip_id+". "+data[i].pickup2_lat+","+data[i].pickup2_long);
        originMarkers.addLayer(circle);
        
        gridNo=getGridId(data[i].pickup2_lat,data[i].pickup2_long);
        if(gridNo!="Luar Bdg"){
            gridId[gridNo].origin.push(data[i].trip_date+" "+data[i].pickup);
        }
        
        //console.log(getGridId(data[i].pickup2_lat,data[i].pickup2_long));
        //mapPointToGrid(data[i].pickup2_lat,data[i].pickup2_long);           
    }
    map.addLayer(originMarkers);

    var new_time = new Date();
    console.log("\nTime to read data, draw point, & map to grid = "+(new_time - old_time)+" ms");
    console.log("Origin point "+originPoint.length);
    //console.log("Origin point mapped to grid : "+pointMappedToGridO);
}   
       
function dbscan(data,eps,minPts,color,drawPointRadius){
    var old_time = new Date();
    var dbscan = jDBSCAN().eps(eps).minPts(minPts).distance('HAVERSINE').data(data);
    var dbscanResult = dbscan();
    var clusterCenters = dbscan.getClusters();
    var clusterCount=[];
    var unclustered=0;
    
    //each point labeled with cluster number
    for(var i=0; i<dbscanResult.length; i++){
        if(clusterCount[dbscanResult[i]]==null){ clusterCount[dbscanResult[i]]=0; }
        clusterCount[dbscanResult[i]]++;  
        
        if(dbscanResult[i]!=0){ 
            if(color=="random"){
                //var drawColor=htmlColor[dbscanResult[i]];
                var drawColor=htmlColor[dbscanResult[i]];
            }else{
                var drawColor=color;                               
            }
            
            var circle = L.circle([data[i].location.latitude, data[i].location.longitude], drawPointRadius, {
                color: drawColor,
                fillColor: drawColor,
                fillOpacity: 1
            }).bindLabel("Cluster number : "+dbscanResult[i]+"\nRecord no : "+i).addTo(map); 
                
             originClusterMarkers.addLayer(circle);
            
            
//             for(var j=0;j<grid.length;j++){
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
    //map.removeLayer(originMarkers);
    map.addLayer(originClusterMarkers);
    
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
    $(".date").datepicker({ dateFormat: 'yy-mm-dd', minDate : '2015-12-23', maxDate : '2015-12-31'});
 
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize);
    
    $("#generate").click(function(){
        //map.removeLayer(originMarkers);
        
        $.getJSON("tools_preprocess_get.php",{
                req : "getTripForArimaData",
                datePeriod : $("#datePeriod").val(),
                timePeriod : $("#timePeriod").val()
            },
            function(data, status){
                $.each(data, function (index, value) { data[index]=value; });
 
                read_draw_count_data(data); // read data, assign to grid
            
                var dataArima=[];
                var start; var startWIB;
                var end = new Date($("#datePeriod").val()); end.setTime( end.getTime() + end.getTimezoneOffset()*60*1000);
                var startPlus3;
                var countTripIn3Period;
            
                for (var i=1; i<gridId.length; i++) { //looping grid, 1 grid 1 file csv
                    if(gridId[i].origin.length!=0){
                        //convert to GMT 0
                        startWIB=new Date("2015-12-07");
                        start=new Date(startWIB.valueOf() + startWIB.getTimezoneOffset() * 60000);
                        
                        while(start < end){ //looping baris di csv
                           //date +3H from start    
                           startPlus3= new Date(start); startPlus3.setHours(start.getHours() + 3);
                            
                           countTripIn3Period=0;
                           for(var j=0; j<gridId[i].origin.length; j++){ //looping untuk count trip di tiap periode
                               var tripDateTimeWIB=new Date(gridId[i].origin[j]);
                               var tripDateTime=new Date(tripDateTimeWIB.valueOf() + tripDateTimeWIB.getTimezoneOffset() * 60000);
                               
                               //console.log("tripDateTime "+tripDateTime);
                               if(tripDateTime>start && tripDateTime<startPlus3){
                                   countTripIn3Period=countTripIn3Period+1;
                               }
                           }
                            
                            var a=start.getFullYear()+"-"+(start.getMonth()+1).padZero()+"-"+start.getDate().padZero()+" "+start.getHours().padZero()+":"+start.getMinutes().padZero();
                            var b=startPlus3.getFullYear()+"-"+(startPlus3.getMonth()+1).padZero()+"-"+startPlus3.getDate().padZero()+" "+startPlus3.getHours().padZero()+":"+startPlus3.getMinutes().padZero();
                            
                            //console.log(i+","+a+"-"+b+","+countTripIn3Period);
                            dataArima.push(i+","+a+"-"+b+","+countTripIn3Period);
                            
                           //shift 3hours 
                           var newDate = start.setHours(start.getHours() + 3);
                           start = new Date(newDate);
                        }
                    }                
                }
    
                //update dataset for arima in db
                $.ajax({
                    type: "POST",
                    url: "tools_preprocess_post.php",
                    data: {
                        req : "saveArimaData",
                        arimaData : dataArima
                    },
                    dataType: "json",
                    success: function(data){
                        //console.log("success");
                        //location.reload();
                    },
                    failure: function(errMsg) { alert (errMsg);}
                });
            
                $.getJSON("tools_preprocess_get.php",{
                    req : "getArimaData",
                    datePeriod : $("#datePeriod").val(),
                    timePeriod : $("#timePeriod").val()
                },
                function(data, status){
                    $.each(data, function (index, value) { data[index]=value; });
                    
                    console.log(data);
                    var max=-1;
                     for (var i=0; i<data.length; i++) {
                        if(parseInt(data[i].count)>max){
                            max=data[i].count;
                        }
                     }
                    
                    for (var i=0; i<data.length; i++) {
                        var R = Math.ceil((255 * data[i].count) / max);
                        var G = Math.ceil((255 * (max - data[i].count)) / max); 
                        var B = 0;
                        var color= "rgb("+R+" ,"+G+","+ B+")";
                        //console.log(color);
                        
                        if(data[i].count!=0){
                            gridId[data[i].grid].rectangle.setStyle({fillColor:color,fillOpacity:1}).bindLabel(data[i].count);    
                        }
                        
                         console.log(data[i].count);
                        if(parseInt(data[i].count)>max){
                            max=data[i].count;
                        }
                    }
                    console.log(max);
                });
 
                
                //calculateCentroidOrigin(false); //plot centroid, red color 
        
                //dbscan(originPoint,0.1,2,"random",10);
            }
        );  
        
    });
    
        
});
    
    
</script> 
</head>

<body>
    <div class="container">    
        <div class="sideBar">
            <div id="chooseData">
                <b>Choose Data</b><br>
                <table>
                    <tr>
                        <td>Period</td>
                        <td>:</td>
                        <td>
                            <input type="text" id="datePeriod" class="date" value="2015-12-09" size=12 readonly> 
                            <br>
                            <select id="timePeriod">
                                <option value="00:00-03:00" selected>00:00 - 03:00</option>
                                <option value="03:00-06:00">03:00 - 06:00</option>
                                <option value="06:00-09:00">06:00 - 09:00</option>
                                <option value="09:00-12:00">09:00 - 12:00</option>
                                <option value="12:00-15:00">12:00 - 15:00</option>
                                <option value="15:00-18:00">15:00 - 18:00</option>
                                <option value="18:00-21:00">18:00 - 21:00</option>
                                <option value="21:00-24:00">21:00 - 24:00</option>
                            </select>
<!--                            <input type="text" id="endPeriod" class="date" value="2015-12-31" size=12>-->
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="button" id="generate" value="Generate">
                        </td>
                    </tr>
                </table>
            </div>
 <!--           <hr>

            <div id="dbscan">
                <b>Find Cluster (DBSCAN)</b><br>
                 <table>
                    <tr>
                        <td>
                            Eps : <input type="text" id="eps" class="" value="0.2" size=5>&nbsp;
                            Min Pts : <input type="text" id="minpts" class="" value="3" size=5>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="button" id="clustering" value="Process" disabled></td>
                    </tr>
                </table>
            </div>

            <hr>
            <div id="sp">
                <b>Location Area</b><br>
                <table id="tableArea">
                    <tbody>
                    </tbody>
                </table>
            </div>
            <input type="button" id="addArea" value="Add">
        -->
        </div>
        <div class="content">
            <div id="map"> </div>
            
            <div id="footer" hidden> </div>
        </div>  
    </div>
</body>
</html>