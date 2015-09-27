var map = L.map('map').setView([53.125, 8.8], 11);
var selectedCategory, selectedColumn = 0, selectedDataset, selectedLevel, currentData, legend, controls;

L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6IjZjNmRjNzk3ZmE2MTcwOTEwMGY0MzU3YjUzOWFmNWZhIn0.Y8bhBaUMqFiPrDRW9hieoQ', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
    '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
    'Imagery © <a href="http://mapbox.com">Mapbox</a>, ' +
    'Data &copy; <a href="http://daten.bremen.de/">Bremen</a>',
    id: 'mapbox.light'
}).addTo(map);


// control that shows state info on hover
var info = L.control();

info.onAdd = function (map) {
    this._div = L.DomUtil.create('div', 'info');
    this.update();
    return this._div;
};

info.update = function (props) {
    this._div.innerHTML = '<h4>Bremen Insight</h4>' +  (props ?
        '<b>' + props.name + '</b><br />' + extractValue(props) + ' ' + currentData.columns[selectedColumn]
            : 'Hover over a Stadtteil');
};

info.addTo(map);


// get color depending on population density value
function getColor(d) {
    var grades = getScala();
    return d > grades[7] ? '#800026' :
        d > grades[6]  ? '#BD0026' :
            d > grades[5]  ? '#E31A1C' :
                d > grades[4]  ? '#FC4E2A' :
                    d > grades[3]   ? '#FD8D3C' :
                        d > grades[2]   ? '#FEB24C' :
                            d > grades[1]   ? '#FED976' :
                                '#FFEDA0';
}

function style(feature) {
    return {
        weight: 1,
        opacity: 1,
        color: '#fff',
        dashArray: '',
        fillOpacity: 0.8,
        fillColor: getColor(extractValue(feature.properties))
    };
}

function highlightFeature(e) {
    var layer = e.target;

    layer.setStyle({
        weight: 3,
        color: '#fff',
        dashArray: '',
        fillOpacity: 1
    });

    if (!L.Browser.ie && !L.Browser.opera) {
        layer.bringToFront();
    }

    info.update(layer.feature.properties);
}
function extractValue(props) {
    if(!props || !props.dataPoints || !props.dataPoints[selectedCategory]) {
        return null;
    }
    return props.dataPoints[selectedCategory][selectedColumn];
}

var geojson;

function resetHighlight(e) {
    geojson.resetStyle(e.target);
    info.update();
}

function zoomToFeature(e) {
    map.fitBounds(e.target.getBounds());
}

function is_touch_device() {
    return 'ontouchstart' in window // works on most browsers
        || 'onmsgesturechange' in window; // works on ie10
}

function onEachFeature(feature, layer) {
    if(is_touch_device()) {
        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight,
            click: highlightFeature
        });
    } else {
        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight,
            click: zoomToFeature
        });
    }
}

function getScala() {
    var maxPrecision = currentData.columnMaxima[selectedColumn] > 1000 ? 1000 : 100;
    var maxima = Math.ceil(currentData.columnMaxima[selectedColumn] / maxPrecision) * maxPrecision;
    var minPrecision = currentData.columnMinima[selectedColumn] > 1000 ? 1000 : 100;
    var minima = Math.ceil(currentData.columnMinima[selectedColumn] / minPrecision) * minPrecision;
    var grades = [];
    for(var i=0; i<8; i++) {
        grades.push(minima + Math.floor((maxima-minima) / 8 * i));
    }
    return grades;
}

function refreshDataPoints() {
    if(geojson) {
        //geojson.removeFrom(map);
    }
    geojson = L.geoJson(currentData, {
        style: style,
        onEachFeature: onEachFeature
    }).addTo(map);

    var grades = getScala();

    if(legend) {
        legend.removeFrom(map);
    }


    legend = L.control({position: 'bottomright'});
    legend.onAdd = function (map) {

        var div = L.DomUtil.create('div', 'info legend'),
            labels = [],
            from, to;

        for (var i = 0; i < grades.length; i++) {
            from = grades[i];
            to = grades[i + 1];

            labels.push(
                '<i style="background:' + getColor(from + 1) + '"></i> ' +
                from + (to ? '&ndash;' + to : '+'));
        }

        div.innerHTML = labels.join('<br>');
        return div;
    };

    legend.addTo(map);
}

function addDevsMarker(){
    $('#devs').click(function(){
        L.marker([53.15, 8.8]).addTo(map)
            .bindPopup('Made by: <br> <a href="http://dantz.me">Andreas Dantz</a> <br> Artur Hallmann <br> <a href="http://niklasmeyer.de">Niklas Meyer</a>')
            .openPopup();
    });
}