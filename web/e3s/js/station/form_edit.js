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

import { initMap } from './proximity-map.js'

$(document).ready(function () {
  // Capitalize function maj()
  maj($('#bbees_e3sbundle_station_codeStation'));
  maj($('#bbees_e3sbundle_station_nomStation'));
  // Init modal map container
  $(".modal-dialog").css("width", "95vw")



  // Initialise binding logics between country and municipality ("Commune") inputs

  function updateCommuneList(){
    // Update Commune list of select input in main form
    let formData = new FormData()
    formData.append(country.attr("name"), country.val())
    // Fetch updated form with new Commune list
    fetch(window.location.pathname, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    }).then(response => response.text()).then(html => {
      // Replace Commune option list
      $('#bbees_e3sbundle_station_communeFk').html($(html).find('#bbees_e3sbundle_station_communeFk').html())
    })
  }

  let country = $('#bbees_e3sbundle_station_paysFk')

  country.change(updateCommuneList)

  // Modal window for Commune form
  // 1) Update form with "Add new Commune" button
  let add_commune_btn = $("template#add-municipality-btn-tmp").html();
  $(add_commune_btn).insertBefore(
    $('select[id="bbees_e3sbundle_station_communeFk"]')
  );

  // 2) Actions of the modal myModalCommune; (i) capitalization of the commonname and regionName fields (ii) automatic generation of the common code
  $('#myModalCommune').on('shown.bs.modal', function (e) {

    // Update country in Commune modal form
    $(this).find('select#bbees_e3sbundle_commune_paysFk')
      .val(country.val())

    // Used to generate and update Commune code in modal form
    function updateCommuneCode() {
      // Elements of Commune code
      let name = $('#bbees_e3sbundle_commune_nomCommune').val()
      let region = $('#bbees_e3sbundle_commune_nomRegion').val()
      let country = $('#bbees_e3sbundle_commune_paysFk option:selected').text()
        .split(' ').join('_')

      // Generate and update
      let code = [name, region, country]
        .map(elt => elt.split(' ').join('_'))
        .join(' | ')
      $('#bbees_e3sbundle_commune_codeCommune').val(code)

      return code
    }


    // Disable country (force set by main form)
    $('#bbees_e3sbundle_commune_paysFk').attr("disabled", true);
    // Commune code is auto-generated, disable as well
    $('#bbees_e3sbundle_commune_codeCommune').attr("disabled", true);

    // Commune name input
    $('#bbees_e3sbundle_commune_nomCommune').keyup(function (e) {
      var input = $(this)
      // Force uppercase
      var nomCommuneUpperCase = input.val().toUpperCase()
      input.val(nomCommuneUpperCase)
      updateCommuneCode()
    })

    // Region name input 
    $('#bbees_e3sbundle_commune_nomRegion').keyup(function (e) {
      var input = $(this);
      var nomRegionUpperCase = input.val().toUpperCase();
      input.val(nomRegionUpperCase);
      updateCommuneCode()
    })
  })

  // 3) Actions after the creation of a new municipality
  $('form[name="bbees_e3sbundle_commune"]').on('submit', function (ev) {
    $('#bbees_e3sbundle_commune_paysFk').attr("disabled", false);
    $('#bbees_e3sbundle_commune_codeCommune').attr("disabled", false);
    ev.preventDefault();
    var form = $(this);
    callAjax(form, $('#bbees_e3sbundle_station_communeFk'));
  });


  // Management of the "Show Station" button
  // 1) Added button "See Stations"
  let stationsmap_btn_html = $('template#see-stations-btn-tmp').html()
  $(stationsmap_btn_html).insertBefore(
    $('select[id="bbees_e3sbundle_station_precisionLatLongVocFk"]')
  )

  let stationMap = initMap("station-geo-map")

  // Modal station map 
  $("#detailsModal").on("shown.bs.modal", _ => {
    stationMap.invalidateSize()
    stationMap.fitBounds(stationMap.bounds, { maxZoom: 10, padding: L.point(30, 30) })
  })

  $('#detailsModal').on('show.bs.modal', function (ev) {

    let lat = parseFloat($('#bbees_e3sbundle_station_latDegDec').val().replace(',', '.'))
    let lon = parseFloat($('#bbees_e3sbundle_station_longDegDec').val().replace(',', '.'))

    stationMap.setTarget(lat, lon)

    var data = {
      'latitude': lat,
      'longitude': lon
    }

    $.ajax({
      type: 'POST',
      data: data,
      url: Routing.generate('geocoordstations'),
      error: function (jqXHR, textStatus, errorThrown) {
        alert('error ajax request : ' + errorThrown);
        console.log(jqXHR);
        console.log(textStatus);
        console.log(errorThrown);
      },
      success: function (response) {
        stationMap.update(response)
      }
    });
  });
});