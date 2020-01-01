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

//search:
$(function() {
  setTimeout(function() {
    if (getDeviceType()['type'] == 'desktop') $('#input_searchEqlogic').focus()
    $("body").on('keydown','#input_searchEqlogic', function(event) {
      if(event.key == 'Escape') {
        $(this).val('').keyup()
      }
    })
  }, 500)
})

$('#input_searchEqlogic').keyup(function () {
  var search = $(this).value()
  if (search == '') {
    $('#intentsContainer .panel-collapse.in').closest('.panel').find('.accordion-toggle').click()
    $('#intentsContainer .eqLogicDisplayCard').show()
    $('#intentsContainer .eqLogicThumbnailContainer').packery()
    return
  }
  search = normTextLower(search)
  $('.panel-collapse').attr('data-show',0)
  $('.eqLogicDisplayCard').hide()
  $('.eqLogicDisplayCard .name').each(function() {
    var text = $(this).text()
    text = normTextLower(text)
    if (text.indexOf(search) >= 0) {
      $(this).closest('.eqLogicDisplayCard').show();
      $(this).closest('.panel-collapse').attr('data-show',1)
    }
  })
  $('#intentsContainer .panel-collapse[data-show=1]').collapse('show')
  $('#intentsContainer .panel-collapse[data-show=0]').collapse('hide')
  $('#intentsContainer .eqLogicThumbnailContainer').packery()
})

$('#bt_openAll').off('click').on('click', function () {
  $("#intentsContainer .accordion-toggle[aria-expanded='false']").each(function() {
    $(this).click()
    $(this).closest('.panel').find('.eqLogicThumbnailContainer').packery()
  })
})
$('#bt_closeAll').off('click').on('click', function () {
  $("#intentsContainer .accordion-toggle[aria-expanded='true']").each(function() {
    $(this).click()
    $(this).closest('.panel').find('.eqLogicThumbnailContainer').packery()
  })
})
$('#bt_resetSearch').off('click').on('click', function () {
  $('#input_searchEqlogic').val('')
  $('#input_searchEqlogic').keyup()
})


//panels:
$('#devicesPanel .accordion-toggle').off('click').on('click', function () {
  setTimeout(function(){
    $('#devicesContainer .eqLogicThumbnailContainer').packery()
  },100)
});
$('#intentsPanels .accordion-toggle').off('click').on('click', function () {
  $thisContainer = $(this).closest('.panel').find('.eqLogicThumbnailContainer')
  setTimeout(function() {
       $thisContainer.packery()
    }, 100)
})

//ui:
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

$(function() {
    $('select[data-l2key="callbackScenario"]').off().on('change', function () {
        if ($(this).val() != null) {
            var select = $('select[data-l3key="action"]')
            if (select.val() == null) {
              let value = select.find('option:eq(0)').val()
              select.val(value).change()
            }
        }
    })
})

$('.bt_openScenario').off('click').on('click', function () {
    var url = 'index.php?v=d&p=scenario&id=' + $('select[data-l2key="callbackScenario"]').val()
    window.open(url).focus()
})

$('.bt_logScenario').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Log d'exécution du scénario}}"});
  $("#md_modal").load('index.php?v=d&modal=scenario.log.execution&scenario_id=' + $('select[data-l2key="callbackScenario"]').val()).dialog('open');
});

$(function() {
  //filter empty strings and remove 'None' group:
  intentGroups = Object.values(intentGroups)
  intentGroups = intentGroups.filter(Boolean)
  intentGroups.shift()
  $('.eqLogicAttr[data-l2key=group]').autocomplete({
    source: intentGroups,
    minLength: 1
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