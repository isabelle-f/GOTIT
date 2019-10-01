/*
* This file is part of the SpeciesSearchBundle.
*
* Authors : see information concerning authors of GOTIT project in file AUTHORS.md
*
* SpeciesSearchBundle is free software : you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
* 
* SpeciesSearchBundle is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License along with SpeciesSearchBundle.  If not, see <https://www.gnu.org/licenses/>
* 
* Author : Louis Duchemin <ls.duchemin@gmail.com>
*/

import { initBaseMap, updateBounds } from '../map_settings.js'

let radius = 5
let outerStroke = radius * 3 / 5
let nDashes = 10

function radiusToDasharray(radius, n = 10) {
  let length = 2 * Math.PI * radius / n
  return `${length},${length}`
}

let markerStyles = {
  co1: {
    color: 'black',
    fillColor: 'lime',
    fillOpacity: 1,
    radius: radius - outerStroke / 2 - 0.5,
    opacity: 0.75,
    weight: 1,
    pane: "co1Pane"
  },
  bioMat: {
    color: "#ff4f09",
    fillOpacity: 0,
    radius: radius,
    dashArray: null
  },
  bioMatExt: {
    color: '#00b7ff',
    weight: outerStroke,
    fillOpacity: 0,
    radius: radius,
    dashArray: radiusToDasharray(radius, nDashes),
    lineJoin: 'bevel',
    lineCap: "butt",
    pane: "bioMatExtPane"
  },
  lmpLine: { color: 'lime', weight: 2, dashArray: '4,10' }
}

/**
 * Initializes leaflet map with base layers
 * @param {String} dom_id Map container DOM id
 */
function initMap(dom_id) {

  let map = initBaseMap(dom_id)

  map.markerLayers = {
    co1: L.layerGroup(),
    bioMat: L.layerGroup(),
    bioMatExt: L.layerGroup(),
    lmpLines: L.layerGroup()
  }

  map.createPane("bioMatExtPane")
  map.getPane("bioMatExtPane").style.zIndex = 698

  map.createPane("co1Pane")
  map.getPane("co1Pane").style.zIndex = 699


  /**
* Clears current map markers and resets LMP lines
* @param {FormData} formData details form data
*/
  map.resetMarkers = function (formData) {
    for (let group in this.markerLayers) {
      this.markerLayers[group].clearLayers()
    }

    let lmp = {
      bioMat: formData.get("lmp_lm"),
      co1: formData.get("lmp_co1")
    }

    if (lmp.bioMat !== "")
      this.markerLayers.lmpLines.addLayer(
        L.polyline([
          [lmp.bioMat, -720], [lmp.bioMat, 720]
        ], markerStyles.lmpLine)
          .setStyle({ color: "orange" })
      )

    if (lmp.co1 !== "")
      this.markerLayers.lmpLines.addLayer(
        L.polyline([
          [lmp.co1, -720], [lmp.co1, 720]
        ], markerStyles.lmpLine)
          .setStyle({ color: "lime", dashOffset: 7 })
      )
  }

  map.updateLegend = function (markers) {
    if (this.legend)
      this.removeControl(this.legend)

    let overlayMarks = {
      [$("template#co1-legend").html()]: markers.co1,
      [$("template#bio-mat-legend").html()]: markers.bioMat,
      [$("template#bio-mat-ext-legend").html()]: markers.bioMatExt,
      [$("template#lmp-legend").html()]: markers.lmpLines,
      [Translator.trans("maps.labels")]: map.labelsLayer
    }

    this.legend = L.control
      .layers(null, overlayMarks)
      .addTo(this)
  }

  map.updateBounds = updateBounds(map)

  map.updateMarkers = function (markers) {
    for (let layerGroup in markers) {
      markers[layerGroup].addTo(this)
    }

    this.updateLegend(markers)

    L.DomEvent.on(this.sliderControls.radiusSlider, "input",
      (event) => {
        let radius = event.target.value
        let stroke = radius * 3 / 5
        markers.bioMat.invoke("setRadius", radius)
        markers.bioMatExt.invoke("setRadius", radius)
        markers.co1.invoke("setRadius", radius - stroke / 2 - 0.5)
        markers.bioMat.invoke("setStyle", { weight: stroke })
        markers.bioMatExt.invoke("setStyle", { weight: stroke })
      })

    L.DomEvent.on(this.sliderControls.opacitySlider, "input",
      (event) => {
        let opacity = event.target.value
        markers.bioMat.invoke("setStyle", { opacity: opacity })
        markers.bioMatExt.invoke("setStyle", { opacity: opacity })
        markers.co1.invoke("setStyle", { fillOpacity: opacity })
        markers.lmpLines.invoke("setStyle", { opacity: opacity })
      })
  }

  /**
* Builds markers to be displayed on map from a JSON object
* @param {Object} json Station sampling response
*/
  map.prepareGeoMarkers = function (json) {
    // Marker layers
    return json.reduce((plotParams, row) => {
      let lat = row.latitude,
        lon = row.longitude
      row.station_url = Routing.generate("station_show", { id: row.station_id, _locale: $("html").attr("lang") })

      if (row.altitude === null) row.altitude = '-'
      if (row.co1 === true) {
        plotParams.markers.co1
          .addLayer(L.circleMarker([lat, lon], markerStyles.co1)
            .bindPopup(
              Mustache.render($("template#leaflet-popup-template").html(), row)
            )
          )
      }
      let bioMatType = row.bio_mat_id === null ? "bioMatExt" : "bioMat"
      plotParams.markers[bioMatType]
        .addLayer(L.circleMarker([lat, lon], markerStyles[bioMatType])
          .bindPopup(
            Mustache.render($("template#leaflet-popup-template").html(), row)
          ))

      if (plotParams.bounds === null) {
        plotParams.bounds = {
          lat: {
            min: lat,
            max: lat
          },
          lon: {
            min: lon,
            max: lon
          }
        }
      }

      plotParams.bounds = {
        lat: {
          min: Math.min(plotParams.bounds.lat.min, lat),
          max: Math.max(plotParams.bounds.lat.max, lat)
        },
        lon: {
          min: Math.min(plotParams.bounds.lon.min, lon),
          max: Math.max(plotParams.bounds.lon.max, lon),
        }
      }
      return plotParams
    }, {
        markers: this.markerLayers,
        bounds: null
      })
  }

  return map
}



export { initMap }