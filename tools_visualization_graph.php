<html>
<head>
    <meta charset=utf-8 />
    <title>Visualization - Frequent O-D Flow</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' /> 

    <script src="jquery/jquery-3.0.0.min.js"></script> 
    <!--<script src="jquery/jquery-ui.min.js"></script>
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
    <script type="text/javascript" src="jDBSCAN/jDBSCAN.js"></script>
    <script src="jLouvain.js"></script> 
    <script src="util.js"></script> 
    <script src="randomColor.js"></script> 
                    
    <link rel="stylesheet" href="jquery/jquery-ui.css" />
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <link rel="stylesheet" href="tools_style.css" />
    
-->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.16.1/vis.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.16.1/vis.min.css">

    <style type="text/css">
        #mynetwork {
            background-color: black;
            width: 1080px;
            height: 720px;
            border: 1px solid lightgray;
        }
    </style>
    
<script>

    
$(document).ready(function() { 
    var color = 'gray';
    var len = undefined;

    var nodes = [
        {id: 0, label: "0", group: 0,size:50},
        {id: 1, label: "1", group: 0},
        {id: 2, label: "2", group: 0},
        {id: 3, label: "3", group: 1},
        {id: 4, label: "4", group: 1},
        {id: 5, label: "5", group: 1},
        {id: 6, label: "6", group: 2},
        {id: 7, label: "7", group: 2},
        {id: 8, label: "8", group: 2},
        {id: 9, label: "9", group: 3},
        {id: 10, label: "10", group: 3},
        {id: 11, label: "11", group: 3},
        {id: 12, label: "12", group: 4},
        {id: 13, label: "13", group: 4},
        {id: 14, label: "14", group: 4},
        {id: 15, label: "15", group: 5},
        {id: 16, label: "16", group: 5},
        {id: 17, label: "17", group: 5},
        {id: 18, label: "18", group: 6},
        {id: 19, label: "19", group: 6},
        {id: 20, label: "20", group: 6},
        {id: 21, label: "21", group: 7},
        {id: 22, label: "22", group: 7},
        {id: 23, label: "23", group: 7},
        {id: 24, label: "24", group: 8},
        {id: 25, label: "25", group: 8},
        {id: 26, label: "26", group: 8},
        {id: 27, label: "27", group: 9},
        {id: 28, label: "28", group: 9},
        {id: 29, label: "29", group: 9}
    ];
    var edges = [
        {from: 1, to: 0},
        {from: 2, to: 0,arrows:'to', width:10},
        {from: 4, to: 3},
        {from: 5, to: 4},
        {from: 4, to: 0},
        {from: 7, to: 6},
        {from: 8, to: 7},
        {from: 7, to: 0},
        {from: 10, to: 9},
        {from: 11, to: 10},
        {from: 10, to: 4},
        {from: 13, to: 12},
        {from: 14, to: 13},
        {from: 13, to: 0},
        {from: 16, to: 15},
        {from: 17, to: 15},
        {from: 15, to: 10},
        {from: 19, to: 18},
        {from: 20, to: 19},
        {from: 19, to: 4},
        {from: 22, to: 21},
        {from: 23, to: 22},
        {from: 22, to: 13},
        {from: 25, to: 24},
        {from: 26, to: 25},
        {from: 25, to: 7},
        {from: 28, to: 27},
        {from: 29, to: 28},
        {from: 28, to: 0}
    ]

    // create a network
    var container = document.getElementById('mynetwork');
    var data = {
        nodes: nodes,
        edges: edges
    };
    var options = {
        nodes: {
            shape: 'dot',
            font: { size: 32, color: '#ffffff'},
            borderWidth: 2
        }
    };
    network = new vis.Network(container, data, options);
  
});
</script> 
</head>
<body>
    
    <div id="mynetwork"></div>
    
</body>
</html>

