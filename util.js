//setup map *bandung from gmaps -6.9106759,107.5857585 *from web -6.914744, 107.609810
function setupMap(){
    var map = L.map('map').setView([-6.914744, 107.609810], 13);
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 18,
        id: 'laezano.18b09133',
        accessToken: 'pk.eyJ1IjoibGFlemFubyIsImEiOiIxYzMzNmJmOTdjY2M4MmI5N2U2ZWI1ZjYyZTYyZGVmNCJ9.-VDDMhYojWz8ghvMftCkcw'
    }).addTo(map);
    
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

function drawBorder(){


}

//===== generate random color
Array.prototype.getRandom= function(cut){
    var i= Math.floor(Math.random()*this.length);
    if(cut && i in this){
        return this.splice(i, 1)[0];
    }
    return this[i];
}
var htmlColor= ["AliceBlue","AntiqueWhite","Aqua","Aquamarine","Azure","Beige","Bisque","Black","BlanchedAlmond","Blue","BlueViolet","Brown","BurlyWood","CadetBlue","Chartreuse","Chocolate","Coral","CornflowerBlue","Cornsilk","Crimson","Cyan","DarkBlue","DarkCyan","DarkGoldenRod","DarkGray","DarkGrey","DarkGreen","DarkKhaki","DarkMagenta","DarkOliveGreen","Darkorange","DarkOrchid","DarkRed","DarkSalmon","DarkSeaGreen","DarkSlateBlue","DarkSlateGray","DarkSlateGrey","DarkTurquoise","DarkViolet","DeepPink","DeepSkyBlue","DimGray","DimGrey","DodgerBlue","FireBrick","FloralWhite","ForestGreen","Fuchsia","Gainsboro","GhostWhite","Gold","GoldenRod","Gray","Grey","Green","GreenYellow","HoneyDew","HotPink","IndianRed","Indigo","Ivory","Khaki","Lavender","LavenderBlush","LawnGreen","LemonChiffon","LightBlue","LightCoral","LightCyan","LightGoldenRodYellow","LightGray","LightGrey","LightGreen","LightPink","LightSalmon","LightSeaGreen","LightSkyBlue","LightSlateGray","LightSlateGrey","LightSteelBlue","LightYellow","Lime","LimeGreen","Linen","Magenta","Maroon","MediumAquaMarine","MediumBlue","MediumOrchid","MediumPurple","MediumSeaGreen","MediumSlateBlue","MediumSpringGreen","MediumTurquoise","MediumVioletRed","MidnightBlue","MintCream","MistyRose","Moccasin","NavajoWhite","Navy","OldLace","Olive","OliveDrab","Orange","OrangeRed","Orchid","PaleGoldenRod","PaleGreen","PaleTurquoise","PaleVioletRed","PapayaWhip","PeachPuff","Peru","Pink","Plum","PowderBlue","Purple","Red","RosyBrown","RoyalBlue","SaddleBrown","Salmon","SandyBrown","SeaGreen","SeaShell","Sienna","Silver","SkyBlue","SlateBlue","SlateGray","SlateGrey","Snow","SpringGreen","SteelBlue","Tan","Teal","Thistle","Tomato","Turquoise","Violet","Wheat","White","WhiteSmoke","Yellow","YellowGreen"];  
//52 bright color
var markerColors=["Aqua","Aquamarine","Black","Blue","BlueViolet","Brown","Chartreuse","CornflowerBlue","Cyan","DarkBlue","DarkGreen","DarkMagenta","DarkOrange","DarkOrchid","DarkRed","DarkRed","DeepPink","DodgerBlue","Gold","Green","GreenYellow","HotPink","Indigo","LawnGreen","LightCoral","LightSeaGreen","Lime","Magenta","Maroon","MediumBlue","MediumOrchid","MidnightBlue","Navy","Olive","OrangRed","PaleGreen","PaleVioletRed","Peru","Plum","Purple","RebeccaPurple","Red","RoyalBlue","SaddleBrown","Sienna","SlateBlue","SpringGreen","Tomato","Turquoise","Violet","Yellow","YellowGreen"];    
          
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
    