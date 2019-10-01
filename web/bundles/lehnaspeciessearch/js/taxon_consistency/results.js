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

import { fetchCurrentUser } from '../utils.js'
import { dtconfig, linkify } from '../datatables_utils.js'

/**
 * Toggle loading mode on
 */
function uiWaitResponse() {
  $("#main-form").find("button[type='submit']").button('loading')
}

/**
 * Toggle loading mode off
 */
function uiReceivedResponse() {
  $("#main-form").find("button[type='submit']").button('reset')
}

/**
 * Initialize result table
 * @param {String} tableId DOM table id
 */
function initDataTable(tableId) {
  uiWaitResponse()
  if (!$.fn.DataTable.isDataTable(tableId)) {
    fetchCurrentUser().then(response => response.json())
      .then(user => {
        // Disable export buttons for invited users
        let dtbuttons = user.role === 'ROLE_INVITED' ? [] : dtconfig.buttons

        const table = $(tableId)
        let dataTable = table.DataTable({
          autoWidth: false,
          responsive: {
            orthogonal: "responsive",
            details: {
              type: 'column'
            }
          },
          language: dtconfig.language[$("html").attr("lang")],
          ajax: {
            "url": Routing.generate("consistency-query"),
            "dataSrc": "rows",
            "type": "POST",
            "data": _ => {
              return $("#main-form").serialize()
            }
          },
          dom: "lfrtipB",
          buttons: dtbuttons,
          order: [1, 'asc'],
          columns: [
            dtconfig.expandColumn, {
              data: "code_lm",
              render: renderLinkify('id_lm', "lotmateriel_show")
            }, {
              data: "taxname_lm",
              render: renderLinkify('idtax_lm', "referentieltaxon_show")
            },
            {
              data: "critere_lm"
            },
            {
              data: "code_biomol",
              render: renderLinkify('id_indiv', "individu_show")
            },
            {
              data: "code_tri_morpho",
              render: renderLinkify('id_indiv', "individu_show")
            },
            {
              data: "taxname_indiv",
              render: renderLinkify('idtax_lm', "referentieltaxon_show")
            },
            {
              data: "critere_indiv"
            },
            {
              data: "code_seq",
              defaultContent: "",
              render: renderLinkify('id_seq', "sequenceassemblee_show")
            },
            {
              data: "taxname_seq",
              render: renderLinkify('idtax_lm', "referentieltaxon_show"),
              defaultContent: ""
            },
            {
              data: "critere_seq",
              defaultContent: ""
            }
          ],
          drawCallback: function (settings) {
            uiReceivedResponse()
            $('[data-toggle="tooltip"]').tooltip()
          } // drawCallback
        }) // datatables

        /****************
         * Submit form handler
         */
        $("#main-form").submit(function (event) {
          event.preventDefault()
          uiWaitResponse()
          dataTable.ajax.reload()
        })
      })
  }
}


/**
 * Generate URLs with ellipsis or not, depending of display mode in DataTable
 * @param {string} fieldName name of the field to use as ID in generated URL
 * @param {string} url route to generate base URL
 */
function renderLinkify(fieldName, url) {
  return {
    responsive: linkify(url, { col: fieldName, ellipsis: false }),
    _: null,
    display: linkify(url, { col: fieldName })
  }
}

export { initDataTable }