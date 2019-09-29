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

import { initBaseMap, updateBounds } from '../../../bundles/lehnaspeciessearch/js/map_settings.js'

let radius = 5
let markerStyles = {
  target: {
    opacity: 1
  },
  proximity: {
    color: "black",
    fillColor: "#ff4f09",
    fillOpacity: 1,
    weight:1,
    radius: radius
  }
}


/**
 * Initializes leaflet map with base layers
 * @param {String} dom_id Map container DOM id
 */
export function initMap(dom_id) {

  let map = initBaseMap(dom_id)

  map.markerLayers = {
    target: L.layerGroup(),
    proximity: L.layerGroup()
  }

  map.setTarget = function(lat, lon){
    console.log(lat, lon)
    let targetMarker = L.marker([lat, lon], markerStyles.target)
    map.markerLayers.target = L.layerGroup([targetMarker])
  }

  map.update = function (json) {
    let plotParams = this.prepareGeoMarkers(json.stations)
    this.updateMarkers(plotParams.markers)
    this.updateBounds(plotParams.bounds)
  }

  /**
  * Clears current map markers
  */
  map.resetMarkers = function () {
    for (let group in this.markerLayers) {
      this.markerLayers[group].clearLayers()
    }
  }

  /**
  * Builds markers to be displayed on map from a JSON object
  * @param {Object} json Station sampling response
  */
  map.prepareGeoMarkers = function (json) {
    let plotParams = {
      markers: this.markerLayers,
      bounds: null
    }

    /**
     * Builds a new station representation and adds it to the plotParams accumulator
     * @param {Object} plotParams accumulator that stores all station representations
     * @param {Object} row A station from JSON data response
     */
    function buildStationRepr(plotParams, row) {
      let lat = row.lat,
        lon = row.lon
      // Generate corresponding url
      row.station_url = Routing.generate("station_show", {
        id: row.id, _locale: $("html").attr("lang")
      })

      // Text placeholder for null altitude
      if (row.altitude === null) row.altitude = '-'

      // Create marker
      let marker = L.circleMarker([lat, lon], markerStyles.proximity)
        .bindPopup(
          Mustache.render($("template#leaflet-popup-template").html(), row))
      plotParams.markers.proximity.addLayer(marker)


      // Update map bounds to view all stations
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
      else {
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
      }
      return plotParams
    }
    
    // Marker layers
    return json.reduce(buildStationRepr, plotParams)
  }

  map.updateLegend = function (markers) {
    if (this.legend)
      this.removeControl(this.legend)

    let overlayMarks = {
      [Translator.trans("maps.labels")]: map.labelsLayer
    }

    this.legend = L.control
      .layers(null, overlayMarks)
      .addTo(this)
  }

  map.updateBounds = updateBounds(map)

  map.updateMarkers = function (markers) {
    // Add each layers to map
    for (let layerGroup in markers) {
      markers[layerGroup].addTo(this)
    }

    // Update legend
    this.updateLegend(markers)

    L.DomEvent.on(this.sliderControls.radiusSlider, "input",
      (event) => {
        let radius = event.target.value
        markers.proximity.invoke("setRadius", radius)
      })

    L.DomEvent.on(this.sliderControls.opacitySlider, "input",
      (event) => {
        let opacity = event.target.value
        markers.target.invoke("setOpacity", opacity)
        markers.proximity.invoke("setStyle", { opacity: opacity })
      })
  }



  return map
}