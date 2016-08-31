<html>
<head>
    <meta charset=utf-8 />
    <title>Visualization - Frequent O-D Flow</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 
    <script src="jquery/jquery-3.0.0.min.js"></script> 
    <script src="jquery/jquery-ui.min.js"></script>
    <script src="leaflet/leaflet.js"></script>
    <script src="Leaflet.label/BaseMarkerMethods.js"></script>
    <script src="Leaflet.label/Label.js"></script>
    <script src="Leaflet.label/Marker.Label.js"></script>
    <script src="Leaflet.label/Map.Label.js"></script>
    <script src="Leaflet.label/Path.Label.js"></script>
    <script src="Leaflet.PolylineDecorator/L.LineUtil.PolylineDecorator.js"></script>
    <script src="Leaflet.PolylineDecorator/L.PolylineDecorator.js"></script>
    <script src="Leaflet.PolylineDecorator/L.RotatedMarker.js"></script>
    <script src="Leaflet.PolylineDecorator/L.Symbol.js"></script>
<!--    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>-->
    <script src="jLouvain.js"></script> 
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
var popup = L.popup();
    
var originMarkers = new L.FeatureGroup();    
var destinationMarkers = new L.FeatureGroup();
var odLine = new L.FeatureGroup();

//var originClusterMarkers = new L.FeatureGroup(); 
//var destinationClusterMarkers = new L.FeatureGroup(); 
    
var bandungCentroid=[-6.908744, 107.669810];
//var bandungCentroid=[-6.914744, 107.609810];
var bandungBounds=[[-6.839, 107.547], [-6.967, 107.738]]; //BANDUNG ONLY
var bandungBoundsExtend=[[-6.784, 107.493], [-7.057, 107.827]]; //CIMAHI, LEMBANG, CILEUNYI, RANCAEKEK, SOREANG 

    
var gridSize=0.001; //aprox 109,5m
    
//For get data location 1 / 2 / 3
var INDEX=3;
var pickup_lat="pickup"+INDEX+"_lat";
var pickup_long="pickup"+INDEX+"_long";
var dropoff_lat="dropoff"+INDEX+"_lat";
var dropoff_long="dropoff"+INDEX+"_long"; 
var pickup_grid100="pickup"+INDEX+"_grid100";
var dropoff_grid100="dropoff"+INDEX+"_grid100";
var pickup_area="pickup"+INDEX+"_area";
var dropoff_area="dropoff"+INDEX+"_area";
    
var dataTrip=[]; // key : index, value = trip record   
var dataGridArea=[]; // key : index, value = object{area_name, gridId}    
        
var originPoint=[]; // key = index , value = origin point
var destinationPoint=[]; // key = index , value = destination point

var areaGridId=[]; // key = area_name , value gridId's
var areaClusterNumber=[]; // key = area_name , value color
var areaLatLongO=[]; // key = area_name, value = origin point
var areaLatLongD=[]; // key = area_name, value = destination point    
var areaCentroidO=[]; // key = area_name, value = centroid origin point of thoose area
var areaCentroidD=[]; // key = area_name, value = centroid destination point of thoose area
var areaCentroidOMarkers=new L.FeatureGroup(); // key = area_name, value = circle object
var areaCentroidDMarkers=new L.FeatureGroup(); // key = area_name, value = circle object
    
var grid=[]; //key = row & col, value = grid object
var gridId=[]; // key = gridId, value = grid object
var gridRectangle=new L.FeatureGroup();
    
//graph
var node_data_o = []; // any type of string can be used as id
var node_data_d = [];
var edge_data = [];

    
var pointMappedToGridO=0;
var pointMappedToGridD=0;

var styleGrid={weight:0.5, color:'grey',fillColor:'grey',fillOpacity:0.01};
var styleSelectedGrid={weight:0.5, color:'red', fillColor:'red', fillOpacity:0.8};
function getStyleGrid(color){ return { weight:0.5, color:color, fillColor:color, fillOpacity:0.3};}    
    
//================= Function
    
function buildMap(bandungCentroid){
    var start = new Date();
    map = L.map('map').setView(bandungCentroid, 13);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 19,
        id: 'laezano.18b09133',
        accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
    }).addTo(map);
    console.log("Build map = "+(new Date() - start)+"ms");
}    

function drawBound(bound,color,weight,fillOpacity){
    var start = new Date();
    L.rectangle(bound, {color: color, weight: weight, fillOpacity:fillOpacity}).addTo(map);
    map.fitBounds(bound);
    console.log("Draw bound time = "+(new Date() - start)+"ms");
}

var selectedGrid=[];
function selectRectangle(e){ //set style of grid(rectangle) when clicked
    var layer=e.target;    
    var gridNo=layer.label._content.split(" ")[0];
    if(layer.options.fillColor==styleGrid.fillColor){
        layer.setStyle(styleSelectedGrid);
        selectedGrid.push(gridNo);
    }else{
        layer.setStyle(styleGrid);
        selectedGrid.remove(gridNo);
    }
    console.log(selectedGrid);
} 

function doNothing(e){}
    
function enableSelectGrid(enable){ // toggle to enable-disable select grid
    //grid index start from 1
    if(enable){
        console.log("select enabled");
        for(var i=1;i<gridId.length;i++){
            gridId[i].rectangle.on('click', selectRectangle);
        }
    }else{
        console.log("select disabled");
        for(var i=1;i<gridId.length;i++){
            gridId[i].rectangle.on('click', doNothing);
        }
    }  
} 
    
function setStyleSelectedArea(elm,style){ //get grid(s) at <tr> attr, set style
    var grid=elm.parent().parent().attr("grid").split(",");
    
    if(grid.length>0 && grid[0]!=""){
        selectedGrid=grid; //assign existing grid to new one
        for(var i=0;i<grid.length;i++){   
            gridId[grid[i]].rectangle.setStyle(style);
        }
    }
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
            rectangle = L.rectangle(bound, styleGrid).bindLabel(gridCount+" "+row+","+col);
         
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
            
            gridRectangle.addLayer(rectangle);
                        
            k=(k+gridSize);
            gridCount++;
            col++;
        }
        row++;
    }
    
    map.addLayer(gridRectangle);
    
    console.log("Draw grid rectangle = "+(new Date() - start)+"ms");
    console.log("Grid size = "+row+"x"+col+" , Total grid : "+gridCount); 
}
       
function getGridId(lat,long){
    for(var j=1;j<gridId.length;j++){
        if(lat>gridId[j].topLeft[0] && lat<gridId[j].rightBottom[0] && long<gridId[j].rightBottom[1]){
            return j;       
        }
    }
    return "Luar Bdg"
}    

function getGridArea(gridId){
  if(typeof gridId=="undefined"){
    return "Luar Bdg";
  }else{
    for(var j=0;j<dataGridArea.length;j++){
        var grid=dataGridArea[j].id.split(",");
        if(grid.indexOf(gridId.toString()) > -1){
            return dataGridArea[j].area_name;
            break;
        }
    }
    return "Luar Bdg";
  }
}    
    
function calculateCentroidArea(data,originOrDestination){
    var old_time = new Date();
    var singleCentroid=0;
    
    var latXTotal;
    var latYTotal;
    var lonDegreesTotal;
    
    var latLong; var latDegrees; var lonDegrees;
    var latRadians;
    var finalLatRadians ; var finalLatDegrees ; var finalLonDegrees 
    
    var circle;
    for (var areaName in data){
        if (typeof data[areaName] !== 'function') {
            if(data[areaName].length>1){
                latXTotal = 0;
                latYTotal = 0;
                lonDegreesTotal = 0;
                
                for(var k=0;k<data[areaName].length;k++){
                    latLong = data[areaName][k].split(",");
                    
                    latDegrees = parseFloat(latLong[0]);
                    lonDegrees = parseFloat(latLong[1]);
                    
                    latRadians = Math.PI * latDegrees / 180;
                    latXTotal += Math.cos(latRadians);
                    latYTotal += Math.sin(latRadians);

                    lonDegreesTotal = lonDegreesTotal+lonDegrees;
                }
                finalLatRadians = Math.atan2(latYTotal, latXTotal);
                finalLatDegrees = finalLatRadians * 180 / Math.PI;
                finalLonDegrees = lonDegreesTotal / data[areaName].length;
            
                circle=L.circle([finalLatDegrees,finalLonDegrees], 10, { fillOpacity: 1});
                
                if(originOrDestination=="origin"){
                    areaCentroidO[areaName.replace(/ /g,'')]=finalLatDegrees.toString()+","+finalLonDegrees.toString();
                    areaCentroidOMarkers.addLayer(circle.setStyle({color: "blue", fillColor: "red"}).bindLabel(data[areaName].length+" trip from "+areaName));
                }else{
                    areaCentroidD[areaName.replace(/ /g,'')]=finalLatDegrees.toString()+","+finalLonDegrees.toString();
                    areaCentroidDMarkers.addLayer(circle.setStyle({color: "green", fillColor: "red"}).bindLabel(data[areaName].length+" trip to "+areaName));
                }
                
            }else{
                latLong=data[areaName][0].split(",");
                circle=L.circle([latLong[0],latLong[1]], 5, { fillOpacity: 1}).bindLabel(areaName);
                
                if(originOrDestination=="origin"){
                    areaCentroidO[areaName.replace(/ /g,'')]=latLong[0]+","+latLong[1];
                    areaCentroidOMarkers.addLayer(circle.setStyle({color: "blue", fillColor: "red"}).bindLabel("1 trip from "+areaName));
                }else{
                    areaCentroidD[areaName.replace(/ /g,'')]=latLong[0]+","+latLong[1];
                    areaCentroidDMarkers.addLayer(circle.setStyle({color: "green", fillColor: "red"}).bindLabel("1 trip to "+areaName));
                }
            }            
        }
    }
    
    if(originOrDestination=="origin"){
        areaCentroidOMarkers.addTo(map);
    }else{
        areaCentroidDMarkers.addTo(map);
    }
    
    var new_time = new Date();
    console.log("Calculate area "+originOrDestination+" origin time = "+(new_time - old_time)+" ms");
}     
    
function load_draw_data(data){
    var old_time = new Date();
    var circleO; var circleD; var polyline;
    var pGrid; var dGrid;
    var pArea; var dArea;
        
    originPoint=[]; destinationPoint=[];
    
    for (var i=0; i<data.length; i++) {
        originPoint.push({location: { accuracy: 1, latitude: data[i][pickup_lat], longitude: data[i][pickup_long] }});
        destinationPoint.push({location: { accuracy: 1, latitude: data[i][dropoff_lat], longitude: data[i][dropoff_long] }});

        //origin point color is blue
        circleO = L.circle([data[i][pickup_lat],data[i][pickup_long]], 5, { color: "blue", fillColor: "blue", fillOpacity: 0.5});
       
        //destination point color is green
        circleD = L.circle([data[i][dropoff_lat],data[i][dropoff_long]], 5, { color: "green", fillColor: "green", fillOpacity: 0.5});
                 
        //bind popup, add to feature group layer
        circleO.bindLabel(data[i].trip_id+". "+data[i][pickup_area]+" > "+data[i][dropoff_area]+" "+data[i].km+"km");
        originMarkers.addLayer(circleO);
        
        circleD.bindLabel(data[i].trip_id+". "+data[i][pickup_area]+" > "+data[i][dropoff_area]+" "+data[i].km+"km");
        destinationMarkers.addLayer(circleD);
         
        //Draw line origin point to destintion
        polyline = L.polyline(
            [new L.LatLng(data[i][pickup_lat],data[i][pickup_long]),new L.LatLng(data[i][dropoff_lat],data[i][dropoff_long])], 
            { color: 'red', weight: 1, opacity : 0.5 }
        );
        odLine.addLayer(polyline);
        
        //grid = array key = "areaname" , value = lat,long
        if(areaLatLongO[data[i][pickup_area]]==null){ areaLatLongO[data[i][pickup_area]]=[] }
        areaLatLongO[data[i][pickup_area]].push(data[i][pickup_lat]+","+data[i][pickup_long]);
        
        if(areaLatLongD[data[i][dropoff_area]]==null){ areaLatLongD[data[i][dropoff_area]]=[] }
        areaLatLongD[data[i][dropoff_area]].push(data[i][dropoff_lat]+","+data[i][dropoff_long]);
        
        node_data_o.push(data[i][pickup_area].replace(/ /g,''));
        node_data_d.push(data[i][dropoff_area].replace(/ /g,''));
    
    }
    
    //calculate centroid point in each area
    calculateCentroidArea(areaLatLongO,"origin");
    calculateCentroidArea(areaLatLongD,"destination");

    //map.addLayer(originMarkers);
    //map.addLayer(destinationMarkers);
    //map.addLayer(odLine);
    
    var new_time = new Date();
    console.log("Read data, draw point = "+(new_time - old_time)+" ms");
    console.log("Origin point "+originPoint.length+" , Destination point : "+destinationPoint.length);
}       

function getCheckedWeekday(){
    var checkedWeekday = [];
    $("input[name='weekday[]']:checked").each(function () { checkedWeekday.push(parseInt($(this).val())); });
    return checkedWeekday;
}
function getCheckedDay(){
    var checkedDay = [];
    $("input[name='day[]']:checked").each(function () { checkedDay.push($(this).val()); });
    return checkedDay;
}  
    
$(document).ready(function() { 
    $(".date").datepicker({ dateFormat: 'yy-mm-dd', minDate : '2015-12-1', maxDate : '2015-12-31'});
    $( "#slider-range" ).slider({ min: 0, max: 10, values: [ 1 ],
      slide: function( event, ui ) {
        $( "#filterWeight" ).val( ui.values[0] );
      }
    }); 
   
        //=========================== TODO
        //create grid luarbandung
        //area luar bandung labeling

        //toggle show hide grid

        //remove inactive o-d point after weight filtering >1
        //circle size : Origin degree out , Destination degree in
        //line = edge weight 

        //add filter show/hide only 1 cluster to show
        //add filter edge weight
    
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize);
    //drawGridRectangleLb(bandungBoundsCimahi,gridSize);
    
    //get areaname list
    $.getJSON("tools_preprocess_get.php",{ 
        index : INDEX,
        req : "getGridArea",
        startPeriod : $("#startPeriod").val(),
        endPeriod : $("#endPeriod").val(),
        weekday : getCheckedWeekday,
        day : getCheckedDay
    },
        function(data, status){
            var old_time = new Date();
            $.each(data, function (index, value) { data[index]=value; });
            //var no; var grid; var color;
            
            for (var i=0; i<data.length; i++){
                //no=(i+1);
                //color=randomColor();
//                grid=data[i].id.split(",");
//                for(var j=0;j<grid.length;j++){          
//                    gridId[grid[j]].rectangle.setStyle(getStyleGrid(color)).bindLabel(data[i].area_name+" "+gridId[grid[j]].rectangle.label._content);
//                }
                
                areaGridId[data[i].area_name.replace(/ /g,'')]=data[i].id;  // key = area_name , value gridId's     
            }
            dataGridArea=data; // key : index, value = object{area_name, gridId}
            
            var new_time = new Date();
            console.log("\nLoad Area name grid = "+(new_time - old_time)+" ms");
            
            //load data after getArea finish!
            //$("#loadData").click();
        }
    );
    
    $("#loadData").click(function(){
        $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : "getTrip",
                startPeriod : $("#startPeriod").val(),
                endPeriod : $("#endPeriod").val(),
                weekday : getCheckedWeekday,
                day : getCheckedDay
            },
            function(data, status){
                //$("#loadData").attr("value","Data loaded!");
                $.each(data, function (index, value) { data[index]=value; });
        
                load_draw_data(data);     
                getEdge();

                //Filter
                $("#footer").show();
                $("#fOriginMarkers").change(function(){
                    if(this.checked) { map.addLayer(originMarkers); }else{ map.removeLayer(originMarkers); }
                });
                $("#fDestinationMarkers").change(function(){
                    if(this.checked) { map.addLayer(destinationMarkers); }else{ map.removeLayer(destinationMarkers); }
                });
                $("#fOdLine").change(function(){
                    if(this.checked) { map.addLayer(odLine); }else{ map.removeLayer(odLine); }
                });
                $("#fAreaCentroidO").change(function(){
                    if(this.checked) { map.addLayer(areaCentroidOMarkers); }else{ map.removeLayer(areaCentroidOMarkers); }
                });  
                $("#fAreaCentroidD").change(function(){
                    if(this.checked) { map.addLayer(areaCentroidDMarkers); }else{ map.removeLayer(areaCentroidDMarkers); }
                });  
                $("#fGridRectangle").change(function(){
                    if(this.checked) { map.addLayer(gridRectangle); }else{ map.removeLayer(gridRectangle); }
                });  
            }
        );  
    });
    
    function getEdge(){
         $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : "getCountTrip",
                startPeriod : $("#startPeriod").val(),
                endPeriod : $("#endPeriod").val(),
                weekday : getCheckedWeekday,
                day : getCheckedDay
            },
            function(data, status){
                $.each(data, function (index, value) { data[index]=value; });
                
                //genereate edge data
                for(var i=0;i<data.length;i++){
                    edge_data.push({
                        source : data[i][pickup_area].replace(/ /g,''),
                        target : data[i][dropoff_area].replace(/ /g,''),
                        //weight : Math.ceil(parseFloat(data[i]["weight"]) / getCheckedWeekday().length)
                        weight : parseFloat(data[i]["weight"])
                    });
                } 
             
                //== Graph Clustering - jLouvain
                var old_time = new Date();
                var node = node_data_o.concat(node_data_d).getUnique();
                var community = jLouvain().nodes(node).edges(edge_data);
                var result  = community();
                console.log(result);
                //print modularity inside jLouvain
                
                //cluster count
                var generatedCluster=0;
                for (var areaName in result){
                    if (typeof result[areaName] !== 'function') {
                        if(result[areaName]>generatedCluster){ generatedCluster=result[areaName]}
                    }
                    
                }
                console.log("Generated cluster "+(generatedCluster+1));
                console.log("Node "+node.length);
                console.log("Edge "+edge_data.length);
                var new_time = new Date();
                console.log("Louvain clustering = "+(new_time - old_time)+" ms");
             
             
                //Display Clustering Reselut, coloring active grid & draw arrow line
                for(var i=0;i<edge_data.length;i++){
                   if(edge_data[i].source!=edge_data[i].target && edge_data[i].weight>1){
                        //coloring active grid, with cluster color
                        if(typeof areaGridId[edge_data[i].source]!="undefined" && typeof areaGridId[edge_data[i].target]!="undefined"){                
                            //console.log(edge_data[i].source+"("+result[edge_data[i].source]+"),"+edge_data[i].target+"("+result[edge_data[i].target]+"),"+edge_data[i].weight);
                            
                            //active grid origin
                            var grid=areaGridId[edge_data[i].source.replace(/ /g,'')].split(",");
                            for(var j=0;j<grid.length;j++){          
                                gridId[grid[j]].rectangle.setStyle({ weight:0.5, color:markerColors[result[edge_data[i].source]], fillColor:markerColors[result[edge_data[i].source]], fillOpacity:0.8}).bindLabel(result[edge_data[i].source]+" "+edge_data[i].source);
                            } 
                            
                            //active grid destination
                            var grid=areaGridId[edge_data[i].target.replace(/ /g,'')].split(",");
                            for(var j=0;j<grid.length;j++){          
                                gridId[grid[j]].rectangle.setStyle({ weight:0.5, color:markerColors[result[edge_data[i].target]], fillColor:markerColors[result[edge_data[i].target]], fillOpacity:0.8}).bindLabel(result[edge_data[i].target]+" "+edge_data[i].target);
                            }
                            
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
                            var latlngs =  [ 
                                      new L.LatLng(areaCentroidO[edge_data[i].source].split(",")[0],areaCentroidO[edge_data[i].source].split(",")[1]),
                                      new L.LatLng(areaCentroidD[edge_data[i].target].split(",")[0],areaCentroidD[edge_data[i].target].split(",")[1])
                                    ];
                            var polyline = new arrowPolyline(latlngs, {color: "red",weight: parseInt(edge_data[i].weight),opacity:0.5}).addTo(map);
                            polyline.bindLabel(edge_data[i].weight+" trip");
                            polyline.addArrows();
                            //areaToAreaLine.addLayer(polyline.addArrows()); 
                            //end of draw polyline

                        }
                   }
                }
                //map.addLayer(areaToAreaLine);
        
                //==== Print graph statistic
                getGraphStatistic("getODRank");
                getGraphStatistic("getWeightOut");
                getGraphStatistic("getWeightIn");
                getGraphStatistic("getDegreeOut");
                getGraphStatistic("getDegreeIn");
                getGraphStatistic("getKmOut");
                getGraphStatistic("getKmIn");
                
            }
        );
    }  

    function getGraphStatistic(statistic){
        $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : statistic,
                startPeriod : $("#startPeriod").val(),
                endPeriod : $("#endPeriod").val(),
                weekday : getCheckedWeekday,
                day : getCheckedDay
            },
            function(data, status){
                $.each(data, function (index, value) { data[index]=value; });
                //console.log(data);
            
                switch (statistic) {
                    case "getODRank":
                        console.log("\n==== Top 10 OD RANK ");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1]+","+data[i][2])
                        }
                    break;    
                        
                    case "getWeightOut":
                        console.log("\n==== Top 10 WEIGHT Out");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1])
                        }
                    break;
                        
                    case "getWeightIn":
                        console.log("\n=== Top 10 WEIGHT In");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1])
                        }
                    break;   
                        
                    case "getDegreeOut":
                        console.log("\n==== Top 10 DEGREE Out");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1])
                        }
                    break;    
                        
                    case "getDegreeIn":
                        console.log("\n==== Top 10 DEGREE In");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1])
                        }
                    break;     
                        
                    case "getKmOut":
                        console.log("\n==== Top 10 KM Out");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1]+","+data[i][2]+","+data[i][3])
                        }
                    break; 
                        
                    case "getKmIn":
                        console.log("\n==== Top 10 KM In");
                        for(var i=0;i<data.length;i++){
                            console.log(data[i][0]+","+data[i][1]+","+data[i][2]+","+data[i][3])
                        }
                    break;
                        
                    default : break;
                }
            }
        );  
    }
});
</script> 
</head>
<body>
    <div class="container">    
        <div class="sideBar">
            <div id="chooseData">
                <table>
                    <tr>
                        <td>Period</td>
                        <td colspan="3">
                            <input type="text" id="startPeriod" class="date" value="2015-12-30"> - <input type="text" id="endPeriod" class="date" value="2015-12-31">
                        </td>
                    </tr>
                    <tr>
                        <td>Week</td>
                        <td>
                            <input type="checkbox" name="weekday[]" class="weekday" value="1" checked="true"> Mon <br>
                            <input type="checkbox" name="weekday[]" class="weekday" value="2" checked="true"> Tue <br>
                            <input type="checkbox" name="weekday[]" class="weekday" value="3" checked="true"> Wed <br>
                            <input type="checkbox" name="weekday[]" class="weekday" value="4" checked="true"> Thr <br>
                           
                        </td>
                        <td valign="top" colspan="2">
                            <input type="checkbox" name="weekday[]" class="weekday" value="5" checked="true"> Fri <br>
                            <input type="checkbox" name="weekday[]" class="weekday" value="6" checked="true"> Sat <br>
                            <input type="checkbox" name="weekday[]" class="weekday" value="7" checked="true"> Sun <br>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">Days</td>
                        <td>
                            <input type="checkbox" name="day[]" value="00-06" checked="true"> (00-06) DiniHari <br>
                            <input type="checkbox" name="day[]" value="06-10" checked="true"> (06-10) Pagi <br>
                            <input type="checkbox" name="day[]" value="10-14" checked="true"> (10-14) Siang <br>
                            <input type="checkbox" name="day[]" value="14-18" checked="true"> (14-18) Sore <br>
                            <input type="checkbox" name="day[]" value="18-24" checked="true"> (18-24) Malam <br>
<!--
                            <input type="text" id="amount" value="12 - 18" readonly style="border:0; font-weight:bold;width:50px;">
                            <div id="slider-range" ></div>
-->
                        </td>
                        <td valign="top">
                            
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <input type="button" id="loadData" value="Load Data">
                        </td>
                    </tr>
                    
                </table>
            </div>
            <hr>
            <div id="clustering">
                <b>Visualize Graph Cluster</b><br>
                 <table>
                    <tr>
                        <td colspan="2"><input type="button" id="proccess" value="Process"></td>
                    </tr>
                    <tr>
                        <td colspan="2" id="filterCluster">
                            Show/Hide Cluster<br>
                            <input type="checkbox" name="cluster" value="" checked="true"> | Cluster 1 | Area,Area,Area<br><br><br>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" id="filterTrip">
                            Filter Trip Count<br>
                            
                            <input type="text" id="filterWeight" value="1" readonly style="border:0; font-weight:bold;width:50px;">
                            <div id="slider-range" ></div>

                        </td>
                </table>
            </div>
        </div>
        
        <div class="content">
            <div id="map"> </div>
            
            <div id="footer" hidden>
                <b>Filter</b><br>
                
                <div id="filterLoadData">
                    <input type="checkbox" id="fOriginMarkers" value="originMarkers" > Origin Marker <br>
                    <input type="checkbox" id="fDestinationMarkers" value="destinationMarkers" > Destination Marker <br>
                    <input type="checkbox" id="fOdLine" value="fOdLine" > Line <br>
                    <input type="checkbox" id="fAreaCentroidO" value="" checked> Area Centroid Origin<br>
                    <input type="checkbox" id="fAreaCentroidD" value="" checked> Area Centroid Destination<br>
                    <input type="checkbox" id="fGridRectangle" value="" checked> Grid <br>
                </div>
                
            
<!--                <div id="toggle">Toggle</div>-->
            </div>
        </div>  
    </div>
</body>
</html>

