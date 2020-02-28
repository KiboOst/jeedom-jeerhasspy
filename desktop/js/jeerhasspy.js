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

//contextMenu
$(function(){
  try{
    $.contextMenu('destroy', $('.nav.nav-tabs'))
    pluginId =  $('body').attr('data-page')
    jeedom.eqLogic.byType({
      type: pluginId,
      error: function (error) {
        $('#div_alert').showAlert({message: error.message, level: 'danger'});
      },
      success: function (_eqs) {
        if(_eqs.length == 0){
          return;
        }
        var eqsGroups = []
        for(i=0; i<_eqs.length; i++){
          group = _eqs[i].configuration.group
          if (group == null) continue
          if (group == "") group = 'Aucun'
          group = group[0].toUpperCase() + group.slice(1)
          eqsGroups.push(group)
        }
        eqsGroups = Array.from(new Set(eqsGroups))
        eqsGroups.sort()
        var eqsList = []
        for(i=0; i<eqsGroups.length; i++){
          group = eqsGroups[i]
          eqsList[group] = []
          for(j=0; j<_eqs.length; j++)
          {
            eq = _eqs[j]
            eqGroup = eq.configuration.group
            if (eqGroup == null) continue
            if (eqGroup == "") eqGroup = 'Aucun'
            if (eqGroup.toLowerCase() != group.toLowerCase()) continue
            eqsList[group].push([eq.name, eq.id])
          }
        }

        //set context menu!
        var contextmenuitems = {}
        var uniqId = 0
        for (var group in eqsList) {
          groupEq = eqsList[group]
          items = {}
          for (var index in groupEq) {
            eq = groupEq[index]
            eqName = eq[0]
            eqId = eq[1]
            items[uniqId] = {'name': eqName, 'id' : eqId}
            uniqId ++
          }
          contextmenuitems[group] = {'name':group, 'items':items}
        }
        if (Object.entries(contextmenuitems).length > 0 && contextmenuitems.constructor === Object){
          $('.nav.nav-tabs').contextMenu({
            selector: 'li',
            autoHide: true,
            zIndex: 9999,
            className: 'eq-context-menu',
            callback: function(key, options, event) {
              tab = null
              tabObj = null
              if (document.location.toString().match('#')) {
                tab = '#' + document.location.toString().split('#')[1]
                if (tab != '#') {
                  tabObj = $('a[href="' + tab + '"]')
                }
              }
              $.hideAlert()
              if (event.ctrlKey || event.originalEvent.which == 2) {
                var type = $('body').attr('data-page')
                var url = 'index.php?v=d&m='+type+'&p='+type+'&id='+options.commands[key].id
                if (tabObj) url += tab
                window.open(url).focus()
              } else {
                $('.eqLogicDisplayCard[data-eqLogic_id="' + options.commands[key].id + '"]').click()
                if (tabObj) tabObj.click()
              }
              initPickers()
            },
            items: contextmenuitems
          })
        }
      }
    })
  }catch(err) {
    console.log(err)
  }
})


//Spinners
function initPickers() {
  $('input[type="number"]').spinner({
    icons: { down: "ui-icon-triangle-1-s", up: "ui-icon-triangle-1-n" }
  })
  setTimeout(function(){
    $('input[type="number"][data-l3key="minConfidence"]').each(function() {
      if ($(this).val() == '') $(this).val(0)
    })
  }, 250)
}
$('#intentsContainer .eqLogicDisplayCard').off('click').on('click', function () {
    initPickers()
})

//configure:
$('#bt_configureIntRemote').off('click').on('click', function () {
    $.hideAlert()
      bootbox.confirm({
        title: '<i class="fa fa-exclamation-triangle warning"></i> {{Configuration de l\'event <i>Intent Recognized</i>}}',
        message: '<p>{{Cette action va modifier votre profil sur Rhasspy et redémarrer le service.}}</p>',
        callback: function (result) {
          if (result) {
              _url = $('input[data-urlType="url_int"]').val()
              configureRemoteHandle(_url)
          }
        }
      })
})
$('#bt_configureExtRemote').off('click').on('click', function () {
    $.hideAlert()
      bootbox.confirm({
        title: '<i class="fa fa-exclamation-triangle warning"></i> {{Configuration de l\'event <i>Intent Recognized</i>}}',
        message: '<p>{{Cette action va modifier votre profil sur Rhasspy et redémarrer le service.}}</p>',
        callback: function (result) {
          if (result) {
              _url = $('input[data-urlType="url_ext"]').val()
              configureRemoteHandle(_url)
          }
        }
      })
})
$('#bt_configureWakeEvent').off('click').on('click', function () {
    $.hideAlert()
    bootbox.prompt({
        title: '<i class="fa fa-exclamation-triangle warning"></i> {{Configuration de l\'event <i>Wakeword Detected</i>}}',
        message: '<p>{{Cette action va modifier votre profil sur Rhasspy et redémarrer le service.}}</p>',
        inputType: 'select',
        inputOptions: [
            {
                text: '{{Utiliser l\'url interne}}',
                value: 'url_int',
            },
            {
                text: '{{Utiliser l\'url externe}}',
                value: 'url_ext',
            }
        ],
        value: 'url_int',
        callback: function (result) {
            if (result == 'url_int' || result == 'url_ext') {
                configureWakeEvent(result)
            }
        }
    })
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
$('#bt_loadAssistant').off('click').on('click', function () {
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

$('#bt_addsatellite').off('click').on('click', function () {
  $.hideAlert()
  var form = $("#addSatelliteFormContainer").html();
  bootbox.confirm({
    message: form,
    callback: function (result) {
      if (result) {
        var addr = $('.bootbox-body #addSatelliteForm').find('input[name="addr"]').val()
        var siteId = $('.bootbox-body #addSatelliteForm').find('input[name="siteId"]').val()
        if (!addr.startsWith("http")){
            $('#div_alert').showAlert({
                message: '{{Vous devez indiquer l\'url complète du satellite (http://... ou https://...).}}',
                level: 'danger'
            })
          	bootbox.hideAll()
          	return false
      	}
        var checkAdrss = addr.replace('://', '')
        if (!checkAdrss.includes(":")){
            $('#div_alert').showAlert({
                message: '{{Vous devez indiquer l\'url complète du satellite (exemple: http://192.168.0.10:12101).}}',
                level: 'danger'
            })
          	bootbox.hideAll()
          	return false
      	}
        addSatellite(siteId, addr)
      }
    }
  })
})

$('#bt_deleteIntents').off('click').on('click', function () {
    bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer les intentions ?}}', function (result) {
        if (result) {
            deleteIntents()
        }
    })
})

$('#bt_showIntentsSummary').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Résumé des intentions}}"})
      .load('index.php?v=d&plugin=jeerhasspy&modal=intents.summary').dialog('open')
})

//UI devices:
$('.bt_deleteSat').off('click').on('click', function () {
  $.hideAlert()
  var _id = $(this).closest('.jeeRhasspyDeviceCard').data('eqlogic_id')
  bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer ce satellite ?}}', function (result) {
      if (result) {
          deleteSatellite(_id)
          $('.jeeRhasspyDeviceCard[data-eqlogic_id="'+_id+'"]').remove()
          $('.eqLogicThumbnailContainer').packery()
      }
  })
})

$('.bt_configure').off('click').on('click', function () {
  $.hideAlert()
  var siteId = $(this).closest('.jeeRhasspyDeviceCard').data('site_id')
  var form = $("#configDeviceFormContainer").html();
  bootbox.confirm({
    message: form,
    callback: function (result) {
      if (result) {
        var url = $('.bootbox-body #configDeviceForm').find('select[name="configUrl"]').val()
        var configWakeEvent = $('.bootbox-body #configDeviceForm').find('input[name="configWakeEvent"]').prop('checked')
        url = _url = $('input[data-urlType="'+url+'"]').val()
        configureRhasspyProfile(siteId, url, true, configWakeEvent)
      }
    }
  })
})

$('.bt_speakTest').off('click').on('click', function () {
    $.hideAlert()
    var site_id = $(this).closest('.jeeRhasspyDeviceCard').data('site_id')
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

$('.bt_goToDevice').off('click').on('click', function () {
  $.hideAlert()
  window.open($(this).closest('.jeeRhasspyDeviceCard').data('site_url')).focus()
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

    if ($('input[data-l2key="isInteract"]').prop('checked')) {
      $('#intentScenario').hide()
    }
})

$('input[data-l2key="isInteract"]').change(function() {
  if ($(this).prop('checked')) {
    $('#intentScenario').hide()
  } else {
    $('#intentScenario').show()
  }
})

$('.bt_openScenario').off('click').on('click', function () {
    var url = 'index.php?v=d&p=scenario&id=' + $('select[data-l2key="callbackScenario"]').val()
    window.open(url).focus()
})

$('.bt_logScenario').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Log d'exécution du scénario}}"})
  $("#md_modal").load('index.php?v=d&modal=scenario.log.execution&scenario_id=' + $('select[data-l2key="callbackScenario"]').val()).dialog('open')
})

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
            loadPage(window.location.href)
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

function deleteSatellite(_id) {
    $.hideAlert()
    $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "deleteSatellite",
            id: _id,
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
            $('#div_alert').showAlert({message: '{{Suppression du satellite réussie.}}', level: 'success'})
        }
    })
}

function addSatellite(_siteId, _addr) {
    $.hideAlert()
    $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "addSatellite",
            addr: _addr,
            siteId: _siteId,
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
            $('#div_alert').showAlert({message: '{{Satellite ajouté, veuillez recharger la page (F5).}}', level: 'success'})
        }
    })
}

function configureRhasspyProfile(_siteId, _url, _configRemote, _configWake) {
    $.ajax({
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
            action: "configureRhasspyProfile",
            siteId: _siteId,
            url: _url,
            configRemote: _configRemote,
            configWake: _configWake
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
            $('#div_alert').showAlert({message: '{{Configuration de votre Rhasspy réussie.}}', level: 'success'})
        }
    })
}