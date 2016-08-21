<html>
<head>
    <meta charset=utf-8 />
    <title>AA Taksi Frequent O-D Pattern</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 
    <script src="jquery/jquery-3.0.0.min.js"></script> 
    <script src="jquery/jquery-ui.min.js"></script>
    <script src="leaflet/leaflet.js"></script>
    <script src="Leaflet.label/BaseMarkerMethods.js"></script>
    <script src="Leaflet.label/Label.js"></script>
    <script src="Leaflet.label/Marker.Label.js"></script>
    <script src="Leaflet.label/Map.Label.js"></script>
    <script src="Leaflet.label/Path.Label.js"></script>
<!--    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>-->
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

var originClusterMarkers = new L.FeatureGroup(); 
var destinationClusterMarkers = new L.FeatureGroup(); 
    
var bandungCentroid=[-6.918744, 107.669810];
//var bandungCentroid=[-6.914744, 107.609810];
var bandungBounds=[[-6.839, 107.547], [-6.967, 107.738]]; //BANDUNG ONLY
var bandungBoundsExtend=[[-6.784, 107.493], [-7.057, 107.827]]; //CIMAHI, LEMBANG, CILEUNYI, RANCAEKEK, SOREANG 

// Variable for map
var gridSize=0.001; //aprox 109,5m
var INDEX=3;
var pickup_lat="pickup"+INDEX+"_lat";
var pickup_long="pickup"+INDEX+"_long";
var dropoff_lat="dropoff"+INDEX+"_lat";
var dropoff_long="dropoff"+INDEX+"_long"; 
var pickup_grid100="pickup"+INDEX+"_grid100";
var dropoff_grid100="dropoff"+INDEX+"_grid100";
var pickup_area="pickup"+INDEX+"_area";
var dropoff_area="dropoff"+INDEX+"_area";
    
var dataTrip=[];    
var dataGridArea=[];    
        
var originPoint=[];
var destinationPoint=[];
   
var centroidOrigin=[];
var centroidDestination=[];
    
var grid=[];
var gridId=[];
    
var pointMappedToGridO=0;
var pointMappedToGridD=0;

var styleGrid={weight:0.5, color:'grey',fillColor:'grey',fillOpacity:0.01};
var styleSelectedGrid={weight:0.5, color:'red', fillColor:'red', fillOpacity:0.8};
function getStyleGrid(color){ return { weight:0.5, color:color, fillColor:color, fillOpacity:0.6};}
    
function buildMap(bandungCentroid){
    var start = new Date();
    map = L.map('map').setView(bandungCentroid, 13);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 19,
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
    
function load_draw_data(data){
    var old_time = new Date();
    var circleO; var circleD;
    var pGrid; var dGrid;
    var pArea; var dArea;
    
    for (var i=0; i<data.length; i++) {
        originPoint.push({location: { accuracy: 1, latitude: data[i][pickup_lat], longitude: data[i][pickup_long] }});
        destinationPoint.push({location: { accuracy: 1, latitude: data[i][dropoff_lat], longitude: data[i][dropoff_long] }});

        //origin point color is blue
        circleO = L.circle([data[i][pickup_lat],data[i][pickup_long]], 5, { color: "blue", fillColor: "blue", fillOpacity: 1});
       
        //destination point color is green
        circleD = L.circle([data[i][dropoff_lat],data[i][dropoff_long]], 5, { color: "green", fillColor: "green", fillOpacity: 1});
         
        pGrid=getGridId(data[i][pickup_lat],data[i][pickup_long]);
        dGrid=getGridId(data[i][dropoff_lat],data[i][dropoff_long]);
        pArea=getGridArea(pGrid);
        dArea=getGridArea(dGrid);
        
//        dataTrip[i]={ 
//            id :  data[i].trip_id,
//            "pickup_grid100" : pGrid,
//            dropoff_grid100 : dGrid,
//            pickup_area : pArea,
//            dropoff_area : dArea
//        };
        dataTrip[i]={};
        dataTrip[i]["id"]=data[i].trip_id;
        dataTrip[i][pickup_grid100]=pGrid;
        dataTrip[i][dropoff_grid100]=dGrid;
        dataTrip[i][pickup_area]=pArea;
        dataTrip[i][dropoff_area]=dArea;
        
        circleO.bindLabel(data[i].trip_id+". "+pGrid+"("+pArea+") - "+dGrid+"("+dArea+")");
        originMarkers.addLayer(circleO);
        
        circleD.bindLabel(data[i].trip_id+". "+pGrid+"("+pArea+") - "+dGrid+"("+dArea+")");
        destinationMarkers.addLayer(circleD);
        //console.log(pGrid+"/"+pArea+" to "+dGrid+"/"+dArea);
         
//        //Draw line origin point to destintion
//        var polyline = L.polyline(
//            [new L.LatLng(data[i].pickup_loc_2_lat,data[i].pickup_loc_2_long),new L.LatLng(data[i].dropoff_loc_2_lat,data[i].dropoff_loc_2_long)], 
//            {
//                color: 'red',
//                weight: 1
//            }
//        );
//        odLine.addLayer(polyline);
        
    }
    
    map.addLayer(originMarkers);
    map.addLayer(destinationMarkers);
    //map.addLayer(odLine);
    
    var new_time = new Date();
    console.log("\nTime to read data, draw point, lookup grid & area name = "+(new_time - old_time)+" ms");
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
    $(".date").datepicker({ dateFormat: 'yy-mm-dd', minDate : '2015-12-1', maxDate : '2015-12-31'});
            
    buildMap(bandungCentroid);
    drawGridRectangle(bandungBounds,gridSize);
    
    //get areaname list
    $.getJSON("tools_preprocess_get.php",{ req : "getGridArea" , index : INDEX },
        function(data, status){
            var old_time = new Date();
            $.each(data, function (index, value) { data[index]=value; });
            var no; var grid; var color;
            for (var i=0; i<data.length; i++){
                no=(i+1);
                color=randomColor();
                $("#tableArea tbody").append(
                    "<tr id='rowArea"+no+"' grid='"+data[i].id+"' color='"+color+"'>"+
                        "<td class='areaNum'>"+no+"</td>"+
                        "<td><input type='text' name='area[]' class='area' size=\"15\" value='"+data[i].area_name+"'></td>"+
                        "<td><input type='button' name='editArea[]' class='editArea' value='Edit'></td>"+
                        "<td><input type='button' name='deleteArea[]' class='deleteArea' value='X'></td>"+
                    "</tr>");
                grid=data[i].id.split(",");
                
                for(var j=0;j<grid.length;j++){          
                    gridId[grid[j]].rectangle.setStyle(getStyleGrid(color)).bindLabel(data[i].area_name+" "+gridId[grid[j]].rectangle.label._content);
                }
            }
            dataGridArea=data;
            var new_time = new Date();
            console.log("\nTime to load grid area = "+(new_time - old_time)+" ms");
        
            //load data after getArea finish!
            //$("#loadData").click();
        }
    );
    
    $("#loadData").click(function(){
        $("#loadData").attr("disabled","true");

        $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : "getTrip",
                startPeriod : $("#startPeriod").val(),
                endPeriod : $("#endPeriod").val()
            },
            function(data, status){
                $("#loadData").attr("value","Data loaded!");
                $("#footer").show();
                $("#clustering").attr("disabled",true);

                $.each(data, function (index, value) { data[index]=value; });

                //map.removeLayer(originMarkers);
                //map.removeLayer(destinationMarkers);
                //map.removeLayer(odLine);
            
                
                load_draw_data(data);
                console.log(dataTrip);
            
                //Filter
                $("#fOriginMarkers").change(function(){
                    if(this.checked) { map.addLayer(originMarkers); }else{ map.removeLayer(originMarkers); }
                });
                $("#fDestinationMarkers").change(function(){
                    if(this.checked) { map.addLayer(destinationMarkers); }else{ map.removeLayer(destinationMarkers); }
                });
                $("#fOdLine").change(function(){
                    if(this.checked) { map.addLayer(odLine); }else{ map.removeLayer(odLine); }
                });
                
                $("#updateData").attr("disabled",false);
            }
        );  
    });
   
    $("#updateData").click(function(){
       var old_time = new Date();
         $.ajax({
            type: "POST",
            url: "tools_preprocess_post.php",
            data: {
                index : INDEX,
                req : "updateTrip",
                data : dataTrip
            },
            dataType: "json",
            success: function(data){
                console.log("Time to update gridId & area name to trip data "+(new Date() - old_time)+"ms");
                //$("#exportData").attr("disabled",false);
            },
            failure: function(errMsg) { alert (errMsg);}
        });
     });
    
    $("#exportData").click(function(){
        $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : "exportData"
            },
            function(data, status){
                
            }
        );  
    });
    
    $("#clustering").click(function(){
        $("#clustering").attr("disabled","true");
        //validate inputText
         
        //==== Non gridbased dbscan
        map.removeLayer(originMarkers);
        map.removeLayer(destinationMarkers);
        map.removeLayer(odLine);    

        //odPoint=$.merge(originPoint,destinationPoint);
        dbscan(originPoint,$("#eps").val(),$("#minpts").val(),colorOrigin,10,"origin");
        dbscan(destinationPoint,$("#eps").val(),$("#minpts").val(),colorDestination,10,"destination");
        
        //setMarkerColor(originClusterMarkers._layers,"black");
        //console.log(originClusterMarkers._layers.pop());
        
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
        $("#clustering").attr("value","Cluster generated!");
     });
    
    $("#addArea").click(function(){
        var no = $("#tableArea tr").length+1;
        var newAreaDom=
            "<tr id='rowArea"+no+"' grid='' color=''>"+
                "<td class='areaNum'>"+no+"</td>"+
                "<td><input type='text' name='area[]' class='area' size=\"10\" value=''></td>"+
                "<td><input type='button' name='editArea[]' class='editArea' value='Edit'></td>"+
                "<td><input type='button' name='deleteArea[]' class='deleteArea' value='X'></td>"+
            "</tr>";
        $("#tableArea tbody").append(newAreaDom);
    });
    
    $(document).on("click", ".deleteArea", function(){   });
    
    $(document).on("focus", ".area", function(){ //highlight grid
        setStyleSelectedArea($(this),styleSelectedGrid);
        enableSelectGrid(false);
        
        var area=$(this).val();

        $.getJSON("tools_preprocess_get.php",{
                index : INDEX,
                req : "getTrip",
                startPeriod : $("#startPeriod").val(),
                endPeriod : $("#endPeriod").val(),
                area : area
            },
            function(data, status){
                $.each(data, function (index, value) { data[index]=value; });

                load_draw_data(data);
                //console.log(dataGridArea);
            }
        );  
    });
    
    $(document).on("focusout", ".area", function(){ //un-highlight grid 
        //set style acording index position in table
        setStyleSelectedArea($(this),getStyleGrid($(this).parent().parent().attr("color")));
        enableSelectGrid(false);
    });
    
    $(document).on("click", ".editArea", function(){ 
        var editButton=$(this);
        var oldGrid=editButton.parent().parent().attr("grid").split(",");
        
        if(editButton.attr("value")=="Edit"){ //selecting grid
            editButton.attr("value","Done");
            $("#addArea").attr("disabled",true); //disable add area while selecting grid   
            
            if(oldGrid.length>0){setStyleSelectedArea(editButton,styleSelectedGrid);} //show old selected grid
        
            enableSelectGrid(true); //enable to select grid
        }
        else if(editButton.attr("value")=="Done"){    
    
            enableSelectGrid(false); //disable selcect grid
                    
            $.ajax({
                type: "POST",
                url: "tools_preprocess_post.php",
                data: {
                    index : INDEX,
                    req : "updateGridArea",
                    areaName : $(this).parent().prev().children().val(),
                    grid : selectedGrid.toString(),
                    oldGrid : oldGrid.toString() 
                },
                dataType: "json",
                success: function(data){
                    location.reload();
                },
                failure: function(errMsg) { alert (errMsg);}
            });
        }     
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
                        <td>
                            : <input type="text" id="startPeriod" class="date" value="2015-12-20"> - <input type="text" id="endPeriod" class="date" value="2015-12-31">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="button" id="loadData" value="Load Data">
                            <input type="button" id="updateData" value="Update Data" disabled>
                            <input type="button" id="exportData" value="Expost CSV area pair">
                        </td>
                    </tr>
                </table>
            </div>
            <hr>
<!--
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
-->
            <div id="sp">
                <b>Location Area</b><br>
                <table id="tableArea">
                    <tbody>
                    </tbody>
                </table>
            </div>
            <input type="button" id="addArea" value="Add">
        </div>
        
        <div class="content">
            <div id="map"> </div>
            
            <div id="footer" hidden>
                <b>Filter</b><br>
                
                <div id="filterLoadData">
                    <input type="checkbox" id="fOriginMarkers" value="originMarkers" checked="true"> Origin Marker <br>
                    <input type="checkbox" id="fDestinationMarkers" value="destinationMarkers" checked="true"> Destination Marker <br>
                    <input type="checkbox" id="fOdLine" value="fOdLine" checked="true"> Line <br>
                </div>
                
                <div id="filterClusteringResult" hidden>
                    <input type="checkbox" id="fOriginCluterMarkers" value="originCluterMarkers" checked="true"> Origin Cluter Marker <br>
                    <input type="checkbox" id="fOdestinationCluterMarkers" value="destinationCluterMarkers" checked="true"> Destination Cluter Marker <br>
                </div>
<!--                <div id="toggle">Toggle</div>-->
            </div>
        </div>  
    </div>
</body>
</html>

