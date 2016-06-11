//===== generate random color
Array.prototype.getRandom= function(cut){
    var i= Math.floor(Math.random()*this.length);
    if(cut && i in this){
        return this.splice(i, 1)[0];
    }
    return this[i];
}

if (!Array.prototype.last){
    Array.prototype.last = function(){
        return this[this.length - 1];
    };
};

var htmlColor= ["AliceBlue","AntiqueWhite","Aqua","Aquamarine","Azure","Beige","Bisque","Black","BlanchedAlmond","Blue","BlueViolet","Brown","BurlyWood","CadetBlue","Chartreuse","Chocolate","Coral","CornflowerBlue","Cornsilk","Crimson","Cyan","DarkBlue","DarkCyan","DarkGoldenRod","DarkGray","DarkGrey","DarkGreen","DarkKhaki","DarkMagenta","DarkOliveGreen","Darkorange","DarkOrchid","DarkRed","DarkSalmon","DarkSeaGreen","DarkSlateBlue","DarkSlateGray","DarkSlateGrey","DarkTurquoise","DarkViolet","DeepPink","DeepSkyBlue","DimGray","DimGrey","DodgerBlue","FireBrick","FloralWhite","ForestGreen","Fuchsia","Gainsboro","GhostWhite","Gold","GoldenRod","Gray","Grey","Green","GreenYellow","HoneyDew","HotPink","IndianRed","Indigo","Ivory","Khaki","Lavender","LavenderBlush","LawnGreen","LemonChiffon","LightBlue","LightCoral","LightCyan","LightGoldenRodYellow","LightGray","LightGrey","LightGreen","LightPink","LightSalmon","LightSeaGreen","LightSkyBlue","LightSlateGray","LightSlateGrey","LightSteelBlue","LightYellow","Lime","LimeGreen","Linen","Magenta","Maroon","MediumAquaMarine","MediumBlue","MediumOrchid","MediumPurple","MediumSeaGreen","MediumSlateBlue","MediumSpringGreen","MediumTurquoise","MediumVioletRed","MidnightBlue","MintCream","MistyRose","Moccasin","NavajoWhite","Navy","OldLace","Olive","OliveDrab","Orange","OrangeRed","Orchid","PaleGoldenRod","PaleGreen","PaleTurquoise","PaleVioletRed","PapayaWhip","PeachPuff","Peru","Pink","Plum","PowderBlue","Purple","Red","RosyBrown","RoyalBlue","SaddleBrown","Salmon","SandyBrown","SeaGreen","SeaShell","Sienna","Silver","SkyBlue","SlateBlue","SlateGray","SlateGrey","Snow","SpringGreen","SteelBlue","Tan","Teal","Thistle","Tomato","Turquoise","Violet","Wheat","White","WhiteSmoke","Yellow","YellowGreen"];  
//52 bright color
var markerColors=["Aqua","Aquamarine","Black","Blue","BlueViolet","Brown","Chartreuse","CornflowerBlue","Cyan","DarkBlue","DarkGreen","DarkMagenta","DarkOrange","DarkOrchid","DarkRed","DarkRed","DeepPink","DodgerBlue","Gold","Green","GreenYellow","HotPink","Indigo","LawnGreen","LightCoral","LightSeaGreen","Lime","Magenta","Maroon","MediumBlue","MediumOrchid","MidnightBlue","Navy","Olive","OrangRed","PaleGreen","PaleVioletRed","Peru","Plum","Purple","RebeccaPurple","Red","RoyalBlue","SaddleBrown","Sienna","SlateBlue","SpringGreen","Tomato","Turquoise","Violet","Yellow","YellowGreen"];    

function midpoint (lat1, lng1, lat2, lng2) {
    lat1= deg2rad(lat1);
    lng1= deg2rad(lng1);
    lat2= deg2rad(lat2);
    lng2= deg2rad(lng2);

    dlng = lng2 - lng1;
    Bx = Math.cos(lat2) * Math.cos(dlng);
    By = Math.cos(lat2) * Math.sin(dlng);
    lat3 = Math.atan2( Math.sin(lat1)+Math.sin(lat2),
    Math.sqrt((Math.cos(lat1)+Bx)*(Math.cos(lat1)+Bx) + By*By ));
    lng3 = lng1 + Math.atan2(By, (Math.cos(lat1) + Bx));

   return (lat3*180)/Math.PI +','+ (lng3*180)/Math.PI;
}

function deg2rad (degrees) {
    return degrees * Math.PI / 180;
};

//===== numbered marker    
L.NumberedDivIcon = L.Icon.extend({
	options: {
    iconUrl: 'marker_hole.png',
    number: '',
    shadowUrl: null,
    iconSize: new L.Point(25, 41),
		iconAnchor: new L.Point(13, 41),
		popupAnchor: new L.Point(0, -33),
		//iconAnchor: (Point)
		//popupAnchor: (Point)
		className: 'leaflet-div-icon'
	},
	createIcon: function () {
		var div = document.createElement('div');
		var img = this._createImg(this.options['iconUrl']);
		var numdiv = document.createElement('div');
		numdiv.setAttribute ( "class", "number" );
		numdiv.innerHTML = this.options['number'] || '';
		div.appendChild ( img );
		div.appendChild ( numdiv );
		this._setIconStyles(div, 'icon');
		return div;
	},
	//you could change this to add a shadow like in the normal marker if you really wanted
	createShadow: function () { return null;}
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
//                i++;                     //  increment the counter
//                if (i < lines.length) {            //  if the counter < 10, call the loop function
//                 myLoop();             //  ..  again which will trigger another 
//                }                        //  ..  setTimeout()
//           }, 100)
//        }
//        myLoop();                      //  start the loop
    