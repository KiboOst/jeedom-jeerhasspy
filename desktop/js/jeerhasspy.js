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

/*
This plugin doesn't use Core eqLogic, but its own jeerhasspy_intent devices in database for intents.
So it have to rewrite some plugin.template stuff, and use utl var intent instead of id.
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
    if (coreVersion < 4.2) $('#intentsContainer .eqLogicThumbnailContainer').packery()
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
  if (coreVersion < 4.2) $('#intentsContainer .eqLogicThumbnailContainer').packery()
})

$('#bt_openAll').off('click').on('click', function () {
  $("#intentsContainer .accordion-toggle[aria-expanded='false']").each(function() {
    $(this).click()
    if (coreVersion < 4.2) $(this).closest('.panel').find('.eqLogicThumbnailContainer').packery()
  })
})
$('#bt_closeAll').off('click').on('click', function () {
  $("#intentsContainer .accordion-toggle[aria-expanded='true']").each(function() {
    $(this).click()
    if (coreVersion < 4.2) $(this).closest('.panel').find('.eqLogicThumbnailContainer').packery()
  })
})
$('#bt_resetSearch').off('click').on('click', function () {
  $('#input_searchEqlogic').val('')
  $('#input_searchEqlogic').keyup()
})

//contextMenu
$(function(){
  try {
    $.contextMenu('destroy', $('.nav.nav-tabs'))
    if (dataIntents.length == 0) return
    var intentGroups = []
    for (i=0; i<dataIntents.length; i++) {
      group = dataIntents[i].configuration.group
      if (group == null) group = '{{Aucun}}'
      if (group == "") group = '{{Aucun}}'
      group = group[0].toUpperCase() + group.slice(1)
      intentGroups.push(group)
    }
    intentGroups = Array.from(new Set(intentGroups))
    intentGroups.sort()
    var intentList = []
    for (i=0; i<intentGroups.length; i++) {
      group = intentGroups[i]
      intentList[group] = []
      for(j=0; j<dataIntents.length; j++)
      {
        intent = dataIntents[j]
        intentGroup = intent.configuration.group
        if (intentGroup == null) intentGroup = '{{Aucun}}'
        if (intentGroup == "") intentGroup = '{{Aucun}}'
        if (intentGroup.toLowerCase() != group.toLowerCase()) continue
        intentList[group].push([intent.name, intent.id])
      }
    }

    //set context menu!
    var contextmenuitems = {}
    var uniqId = 0
    for (var group in intentList) {
      groupIntent = intentList[group]
      items = {}
      for (var index in groupIntent) {
        intent = groupIntent[index]
        intentName = intent[0]
        intentId = intent[1]
        items[uniqId] = {'name': intentName, 'id' : intentId}
        uniqId ++
      }
      contextmenuitems[group] = {'name':group, 'items':items}
    }

    if (Object.entries(contextmenuitems).length > 0 && contextmenuitems.constructor === Object) {
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
  } catch(err) {
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

//panels:
if (coreVersion < 4.2) {
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
}

//Remove core displayCard events:
$(function() {
  $(".eqLogicDisplayCard").off('click').on('click', function(event) {
    $.hideAlert()
    if (event.ctrlKey) {
      var type = $('body').attr('data-page')
      var url = 'index.php?v=d&m='+type+'&p='+type+'&id='+$(this).attr('data-eqlogic_id')
      window.open(url).focus()
    } else {
      $('.eqLogicThumbnailDisplay').hide()
      $(this).addClass('active')
      $('.nav-tabs a:not(.eqLogicAction)').first().click()
      $.showLoading()
      var thisId = $(this).attr('data-eqLogic_id')
      $('#intentPage').show()

      $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
          action: "byId",
          intentId: thisId
        },
        dataType: 'json',
        error: function(request, status, error) {
          handleAjaxError(request, status, error)
        },
        success: function (data) {
          $('#eqlogictab').setValues(data.result, '.intentAttr')
        },
      })

      jeedomUtils.addOrUpdateUrl('intent', thisId)
      $.hideLoading()
      modifyWithoutSave = false
    }
    return false
  })

  $('.eqLogicDisplayCard').off('mouseup').on('mouseup', function(event) {
    if( event.which == 2 ) {
      event.preventDefault()
      var id = $(this).attr('data-eqlogic_id')
      $('.eqLogicDisplayCard[data-eqlogic_id="'+id+'"]').trigger(jQuery.Event('click', {ctrlKey: true}))
    }
  })
})

//UI buttons:
$('#bt_loadAssistant').off('click').on('click', function () {
  $.hideAlert()
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
        addSatellite(addr)
      }
    }
  })
})

$('#bt_deleteIntents').off('click').on('click', function () {
  $.hideAlert()
  bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer les intentions ?}}', function (result) {
    if (result) {
      deleteIntents()
    }
  })
})

$('.jeeRhasspyDeviceCard').off('click').on('click', function (event) {
  $('#md_modal3').dialog({title: "{{Edition de l'équipement}}"}).load('index.php?v=d&plugin=jeerhasspy&modal=device.edit&deviceId='+$(this).attr('data-eqlogic_id')).dialog('open')
})

$('#bt_showIntentsSummary').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Résumé des intentions}}"}).load('index.php?v=d&plugin=jeerhasspy&modal=intents.summary').dialog('open')
})

$('#bt_showBuildIntentsSummary').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Crée vos modèles}}"}).load('index.php?v=d&plugin=jeerhasspy&modal=createintents.summary').dialog('open')
})

//UI devices:
$('.bt_deleteSat').off('click').on('click', function () {
  $.hideAlert()
  var _id = $(this).closest('.jeeRhasspyDeviceCard').data('eqlogic_id')
  bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer ce satellite ?}}', function (result) {
    if (result) {
      deleteSatellite(_id)
      $('.jeeRhasspyDeviceCard[data-eqlogic_id="'+_id+'"]').remove()
      if (coreVersion < 4.2) $('.eqLogicThumbnailContainer').packery()
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
  //filter empty strings and remove 'None' group:
  intentGroups = Object.values(intentGroups)
  intentGroups = intentGroups.filter(Boolean)
  intentGroups.shift()
  $('.intentAttr[data-l2key=group]').autocomplete({
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

function addSatellite(_addr) {
  $.hideAlert()
  $.ajax({
    type: "POST",
    url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
    data: {
      action: "addSatellite",
      addr: _addr,
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

//Intent page UI:
$('#bt_intentSave').off('click').on('click', function () {
  var thisId = $('#eqlogictab input[data-l1key="id"]').val()
  var intentValues = $('#eqlogictab').getValues('.intentAttr')[0]
  $.ajax({
    type: "POST",
    url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
    data: {
      action: "saveIntent",
      intentValues : json_encode(intentValues),
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'})
        return
      }
      $('#div_alert').showAlert({message: '{{Intention sauvegardée: }}'+data.result, level: 'success'})
    },
  })

})

$('#bt_intentRemove').off('click').on('click', function () {
  var thisId = $('#eqlogictab input[data-l1key="id"]').val()
  bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer cette intention ?}}', function(result) {
    if (result) {
      $.ajax({
        type: "POST",
        url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
        data: {
          action: "remove",
          intentId : thisId,
        },
        dataType: 'json',
        error: function (request, status, error) {
          handleAjaxError(request, status, error)
        },
        success: function (data) {
          if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'})
            return
          }
          $('.eqLogicDisplayCard[data-eqlogic_id='+thisId+']').remove()
          jeedomUtils.addOrUpdateUrl('intent', null)
          $('a[data-action="returnToThumbnailDisplay"]').click()
          $('#div_alert').showAlert({message: '{{Intention supprimée: }}'+data.result, level: 'success'})
        },
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

  if ($('input[data-l1key="isInteract"]').prop('checked')) {
    $('#intentScenario').hide()
  }
})

$('input[data-l1key="isInteract"]').change(function() {
  if ($(this).prop('checked')) {
    $('#intentScenario').hide()
  } else {
    $('#intentScenario').show()
  }
})

$('.bt_openScenario').off('click').on('click', function () {
  var url = 'index.php?v=d&p=scenario&id=' + $('select[data-l1key="scenario"]').val()
  window.open(url).focus()
})

$('.bt_logScenario').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Log d'exécution du scénario}}"})
  $("#md_modal").load('index.php?v=d&modal=scenario.log.execution&scenario_id=' + $('select[data-l1key="scenario"]').val()).dialog('open')
})


//integrate some core features for intent instead of core eqlogic id:
$('a[data-action="returnToThumbnailDisplay"]').on('mouseup', function () {
  $.hideAlert()
  jeedomUtils.addOrUpdateUrl('intent', null)
})

$(function() {
  //open intent from url:
  if (is_numeric(getUrlVars('intent'))) {
    var card = $('.eqLogicDisplayCard[data-eqlogic_id='+getUrlVars('intent')+']')
    if (card.length == 1) {
      card.click()
    }
  }

  //open equipment modal from url:
  if (is_numeric(getUrlVars('id'))) {
    setTimeout(() => {
      $('#intentPage').hide()
      $('div.eqLogicThumbnailDisplay').show()
      $('#devicesContainer').addClass('in')
    })
    var card = $('.jeeRhasspyDeviceCard[data-eqlogic_id='+getUrlVars('id')+']')
    if (card.length == 1) {
      $('#md_modal3').dialog({title: "{{Edition de l'équipement}}"}).load('index.php?v=d&plugin=jeerhasspy&modal=device.edit&deviceId='+getUrlVars('id')).dialog('open')
    }
  }
})