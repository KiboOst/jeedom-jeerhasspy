/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$('.accordion-toggle').off('click').on('click', function () {
  setTimeout(function(){
    $('#devicesContainer .eqLogicThumbnailContainer').packery()
  },100)
});

$('#bt_resetSearch').off('click').on('click', function () {
  $('#in_searchEqlogic').val('')
  $('#in_searchEqlogic').keyup()
})

$('#bt_loadAssistant').on('click', function () {
    bootbox.prompt({
        title: '<i class="fa fa-exclamation-triangle warning"></i> {{Importation de l\'assistant}}',
        inputType: 'select',
        inputOptions: [
            {
                text: '{{Conserver toutes les Intentions}}',
                value: 'mode_keep',
            },
            {
                text: '{{Supprimer les Intentions qui ne sont plus dans l\'assistant}}',
                value: 'mode_clean',
            },
            {
                text: '{{Supprimer et recréer toutes les Intentions}}',
                value: 'mode_delete',
            }
        ],
        value: 'mode_keep',
        callback: function (result) {
            if (result == 'mode_keep' || result == 'mode_clean' || result == 'mode_delete') {
                $.hideAlert()
                if (result == 'mode_delete') {
                    deleteIntents()
                }
                var _cleanIntents = "0"
                if (result == 'mode_clean') {
                    _cleanIntents = "1"
                }
                loadAssistant(_cleanIntents)
            }
        }
    })
})

$('#bt_deleteIntents').on('click', function () {
    bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer les intentions ?}}', function (result) {
        if (result) {
            deleteIntents()
        }
    })
})

$('.jeeRhasspyDeviceCard').off('click').on('click', function () {
    $.hideAlert()
    var site_id = $(this).data('site_id')
    $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "test",
            siteId: site_id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function (data) {
            $('#div_alert').showAlert({
                message: '{{Test TTS envoyé.}}',
                level: 'success'
            })
        }
    })
})

function loadAssistant(_cleanIntents) {
  if (!isset(_cleanIntents)) _cleanIntents = "0"
    $.hideAlert()
    $.ajax({
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "loadAssistant",
            mode: _cleanIntents,
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'})
                return;
            }
            url = window.location.href
            loadPage(url);
        }
    })
}

function deleteIntents() {
    $.hideAlert()
    $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "deleteIntents",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'})
                return;
            }
            $('#intentsContainer').empty()
            $('#div_alert').showAlert({message: '{{Suppression réussie, veuillez recharger la page (F5).}}', level: 'success'})
        }
    })
}