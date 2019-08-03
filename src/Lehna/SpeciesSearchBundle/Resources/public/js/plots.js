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


/**
 * Extract values of target key from an array of JSON objects
 * @param {Object} json array of JSON objects
 * @param {any} key target key
 */
function unpack(json, key) {
  return json.map(row => row[key])
}

/**
 * This is the base plotting class used to plot maps and graphics in GOTIT
 * Requires plotly.js 
 */
class BasePlot {
  constructor(containerId) {
    this.container = $(containerId)
    this.container.html('')
    this.d3 = Plotly.d3

    this.gd3 = this.d3.select(containerId) // assign graph to container
    this.gd = this.gd3.node(); // gd : graphical object

    this.plot = this.plot.bind(this)
    this.resize = this.resize.bind(this)

    $(window).resize(this.resize)
  }

  resize() {
    if (this.container.hasClass('collapse in') ||
      !this.container.hasClass('collapse'))
      Plotly.Plots.resize(this.gd)
  }

  plot() { }
}

/**
 * Bar plot class for Species Hypotheses query 
 */
class BarPlot extends BasePlot {
  constructor(containerId) {
    super(containerId)
    this.layout = {
      // X axis
      xaxis: {
        title: Translator.trans("methode.label", {}, 'queries'),
        titlefont: {
          family: 'sans serif',
          size: 18,
          color: '#7f7f7f'
        },
        fixedrange: true
      },
      // Y axis
      yaxis: {
        title: Translator.trans('queries.rearrangement.short', {}, 'queries'),
        titlefont: {
          family: 'sans serif',
          size: 18,
          color: '#7f7f7f'
        },
        fixedrange: true
      },
      // Text layout
      font: {
        family: 'sans serif',
        size: 14
      },
      showlegend: true,
      // Grouped bar chart
      barmode: 'group',
    }
  }

  refresh(json) {
    if (json.length) {
      if (this.container.hasClass('collapse in')) {
        this.plot(json)
      } else {
        this.container.on('shown.bs.collapse', event => {
          this.plot(json)
        })
        this.show()
      }
    } else {
      this.hide()
    }
  }

  show() {
    $(".plot-overlay").collapse('hide')
    this.container.collapse('show')
  }

  hide() {
    $(".plot-overlay").collapse('show')
    this.container.collapse('hide')
  }

  /** Plots JSON response as bar plot */
  plot(json) {
    let data = {
      match: [],
      split: [],
      lump: [],
      reshuffling: [],
      methode: []
    }
    json.reduce((currentData, row) => {
      currentData.match.push(row.match)
      currentData.split.push(row.split)
      currentData.lump.push(row.lump)
      currentData.reshuffling.push(row.reshuffling)
      currentData.methode.push(row.methode)
      return currentData
    }, data)

    let traces = Object.keys(data)
      .filter(key => {
        return key != 'methode'
      })
      .map(key => {
        return {
          x: data.methode, // method label
          y: data[key], // counts
          name: key, // counters.key
          type: 'bar',
        }
      })

    // Build plot
    Plotly.newPlot(
      this.gd, // plotly object
      traces, // data
      this.layout, // layout options
      {
        scrollZoom: false,
        displaylogo: false,
        modeBarButtonsToRemove: [ // remove unwanted control buttons 
          'sendDataToCloud', 'box', 'lasso2d', 'select2d', 'pan2d',
          'zoom2d', 'zoomIn2d', 'zoomOut2d', 'autoScale2d', 'resetScale2d'
        ]
      })
    this.resize()
  }
}

/**
 * Base class for plotting maps and geographical data using plotly.js
 */
class BaseGeoPlot extends BasePlot {
  constructor(containerId) {
    super(containerId)
    this.layout = plotlyconfig.geo.layout // from options.js
  }

  resize() {
    let self = this
    self.container.css("visibility", "hidden")
    $(".geo-overlay").show()
    Plotly.Plots
      .resize(self.gd)
      .then(function () {
        self.container.css("visibility", "visible")
        $(".geo-overlay").hide()
      })
  }
}

/**
 * Class for plotting MOTU distribution map
 */
class MotuGeoPlot extends BaseGeoPlot {

  /**
   * Constructeur MotuGeoPLot
   * @param {string} containerId identifier of the DOM container for the map (e.g : "#myMapContainer")
   */
  constructor(containerId) {
    super(containerId)
  }

  /**
   * Plot geographical data 
   * @param {JSON} json geographical data
   */
  plot(json) {
    let self = this
    let motu_map = {}

    // Partition samples by MOTU
    json.reduce((currentData, row) => {
      if (row.motu in currentData) {
        currentData[row.motu].push(row)
      } else {
        currentData[row.motu] = [row]
      }
      return currentData
    }, motu_map)

    // Create a trace for each MOTU
    let i = 0
    let traces = Object.keys(motu_map)
      .map(motu => {
        i++
        return self.build_station_data(
          motu_map[motu], {
            marker: {
              symbol: 5 * Math.floor(i / 10),
              size: 8
            },
            opacity: 0.7
          }
        )
      })

    // Plot
    Plotly.newPlot(self.gd, traces, self.layout, {
      displaylogo: false,
      modeBarButtonsToRemove: ['sendDataToCloud', 'box', 'lasso2d', 'select2d', 'pan2d']
    })
    self.resize()
  }



  build_station_data(json, update = {}) {
    let coords = {
      latitude: [],
      longitude: [],
      hover: []
    }
    json.reduce((currentCoords, row) => {
      currentCoords.latitude.push(row['latitude']),
        currentCoords.longitude.push(row['longitude']),
        currentCoords.hover.push([
          "MOTU " + row["motu"],
          row['code_station'],
          "Coords:" + row['latitude'] + ";" + row['longitude'],
          "Alt:" + row['altitude'] + "m",
          row['commune'],
          row['pays']
        ].join("<br>"))
      return currentCoords
    }, coords)

    let data = {
      type: 'scattergeo',
      lat: coords.latitude,
      lon: coords.longitude,
      hoverinfo: 'text',
      text: coords.hover,
      // These are overridden by the 'update' argument
      marker: {
        size: 9,
        line: {
          width: 1
        }
      },
      name: "MOTU " + json[0].motu,
    }

    // override defaults with 'update' argument
    $.extend(true, data, update)
    return data
  }
}

export { BarPlot, MotuGeoPlot }