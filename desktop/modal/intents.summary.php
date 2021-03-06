<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$scenarios = scenario::all();
$_scenarios = array();
foreach ($scenarios as $scenario){
	$_scenarios[$scenario->getId()] = $scenario->getHumanName();
}
sendVarToJS('_scenarios', $_scenarios);

?>
<div id="div_alertIntentsSummary"></div>
<div class="input-group pull-right" style="display:inline-flex">
	<span class="input-group-btn">
		<a class="btn btn-sm roundedLeft" id="bt_refreshIntentsSummary"><i class="fas fa-refresh"></i> {{Rafraîchir}}
		</a><a class="btn btn-success btn-sm roundedRight" id="bt_saveIntentsSummary"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
	</span>
</div>
<br/><br/>
<table class="table table-bordered table-condensed tablesorter" id="table_intentsSummary">
	<thead>
		<tr>
			<th>{{Intent}}</th>
			<th data-sorter="checkbox" data-filter="false">{{Actif}}</th>
			<th data-sorter="checkbox" data-filter="false">{{Interact}}</th>
			<th data-sorter="select">{{Scénario}}</th>
			<th data-sorter="select" data-filter="false">{{Action}}</th>
			<th data-sorter="inputs" data-filter="false">{{Min.Conf}}</th>
			<th data-sorter="false" data-filter="false">{{Tags}}</th>
			<th data-sorter="false" data-filter="true">{{UserTags}}</th>
			<th data-sorter="false" data-filter="false">{{Actions}}</th>
		</tr>
	</thead>
	<tbody>

	</tbody>
</table>

<script>
initTableSorter()
refreshIntentsSummary()
var tableIntcSummary = $('#table_intentsSummary')
tableIntcSummary[0].config.widgetOptions.resizable_widths = ['','70px', '90px', '', '120px', '95px', '70px', '160px', '40px']
tableIntcSummary.trigger('applyWidgets')
tableIntcSummary.trigger('resizableReset')
tableIntcSummary.trigger('sorton', [[[1,0]]])

$('#bt_refreshIntentsSummary').off().on('click',function() {
	refreshIntentsSummary()
})

$('#bt_saveIntentsSummary').off().on('click',function() {
	saveIntentsSummary()
})

$('#table_intentsSummary').on({
  'click': function(event) {
  	var scId = $(this).closest('.intent').find('select[data-l1key="scenario"]').val()
	if (!scId) return
	$("#md_modal2").dialog({title: "{{Log d'exécution du scénario}}"})
	$("#md_modal2").load('index.php?v=d&modal=scenario.log.execution&scenario_id=' + scId).dialog('open')
	var modal = $('.ui-dialog[aria-describedby="md_modal2"]')
	modal.css({top: modal.position().top - 20 + "px"})
  }
}, '.bt_summaryLogScenario')

$('#table_intentsSummary').on({
  'change': function(event) {
  	updateIsInteract($(this).closest('tr').data('id'))
  }
}, 'input[data-l1key="isInteract"]')

function updateIsInteract(_id) {
	var _tr = $('tr[data-id="'+_id+'"]')
	if (_tr.find('input[data-l1key="isInteract"]').prop('checked')) {
		_tr.find('select[data-l1key="scenario"]').prop('disabled', 'disabled')
		_tr.find('select[data-l2key="action"]').prop('disabled', 'disabled')
		_tr.find('input[data-l2key="minConfidence"]').prop('disabled', 'disabled')
		_tr.find('.callbackScTagsToggle').prop('disabled', 'disabled')
		_tr.find('input[data-l2key="user"]').prop('disabled', 'disabled')
		_tr.find('.bt_summaryLogScenario').addClass('disabled')
	} else {
		_tr.find('select[data-l1key="scenario"]').removeAttr('disabled')
		_tr.find('select[data-l2key="action"]').removeAttr('disabled')
		_tr.find('input[data-l2key="minConfidence"]').removeAttr('disabled')
		_tr.find('.callbackScTagsToggle').removeAttr('disabled')
		_tr.find('input[data-l2key="user"]').removeAttr('disabled')
		_tr.find('.bt_summaryLogScenario').removeClass('disabled')
	}
}

function refreshIntentsSummary() {
	$.ajax({
		type: "POST",
		url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
		data: {
		  action: "allIntents"
		},
		dataType: 'json',
		error: function(request, status, error) {
			$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'})
				return
			 }

			$('#table_intentsSummary tbody').empty()
			var table = []
			for (var i in data.result) {
				var intent = data.result[i]
				var tr = '<tr class="intent" data-id="' + intent.id + '">'
				tr += '<td>'
				tr += '<input class="intentAttr hidden" data-l1key="id">'
				tr += '<span class="intentAttr" data-l1key="name"></span>'
				tr += '</td>'

				tr += '<td>'
				tr += '<center><input type="checkbox" class="intentAttr" data-label-text="{{Actif}}" data-l1key="isEnable"></center>'
				tr += '</td>'

				tr += '<td>'
				tr += '<center><input type="checkbox" class="intentAttr" data-label-text="{{Interact}}" data-l1key="isInteract"></center>'
				tr += '</td>'

				tr += '<td>'
				tr += '<select class="intentAttr form-control" data-l1key="scenario" data-l2key="id">'
				tr += '<option value="-1">None</option>'
				Object.entries(_scenarios).forEach(([key, value]) => tr += '<option value="' + key + '">' + value + '</option>')
				tr += '</select>'
				tr += '</td>'

				tr += '<td>'
				tr += '<select class="intentAttr form-control input-sm" data-l1key="scenario" data-l2key="action">'
				tr += '<option value="start">{{Start}}</option>'
				tr += '<option value="startsync">{{Start (sync)}}</option>'
				tr += '<option value="stop">{{Stop}}</option>'
				tr += '<option value="activate">{{Activer}}</option>'
				tr += '<option value="deactivate">{{Désactiver}}</option>'
				tr += '<option value="resetRepeatIfStatus">{{Remise à zero des SI}}</option>'
				tr += '</select>'
				tr += '</td>'

				tr += '<td>'
				tr += ' <input style="width: 100%;" type="number" value="0" min="0" max="1" step="0.1" class="intentAttr input-sm" data-l1key="scenario" data-l2key="minConfidence" />';
				tr += '</td>'

				tr += '<td>'
				tr += '<button type="button" class="btn btn-default btn-sm dropdown-toggle callbackScTagsToggle" data-toggle="dropdown">'
					tr += '<i class="fas fa-tags"></i>&nbsp;&nbsp;&nbsp;<span class="caret"></span>'
				tr += '</button>'
				tr += '<ul class="dropdown-menu" role="menu" style="top:unset;left:unset;">'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="intent"/>&nbsp;intent</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="entities"/>&nbsp;entities</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="slots"/>&nbsp;slots</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="siteid"/>&nbsp;siteId</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="query"/>&nbsp;query</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="confidence"/>&nbsp;confidence</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="wakeword"/>&nbsp;wakeword</a></li>'
				tr += '</ul>'

				tr += '</td>'

				tr += '<td>'
				tr += ' <input style="width: 100%;" class="intentAttr input-sm" data-l1key="tags" data-l2key="user" />';
				tr += '</td>'

				tr += '<td>'
				tr += '<a class="btn btn-sm btn-success bt_summaryLogScenario" target="_blank" title="{{Ouvrir le log du scénario.}}"><i class="far fa-file-alt"></i> {{Log}}</a>';
				tr += '</td>'

				var result = $(tr)
				result.setValues(intent, '.intentAttr')
				table.push(result)
			}
			$('#table_intentsSummary tbody').append(table)
			$("#table_intentsSummary").trigger("update")

			$('input[data-l2key="isInteract"]').each(function (index, el) {
				updateIsInteract($(this).closest('tr').data('id'))
			})
		},
	  })
}

function saveIntentsSummary() {
	var intentsValues = $('#table_intentsSummary tbody .intent').getValues('.intentAttr')
	$.ajax({
		type: "POST",
		url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
		data: {
		  action: "saveAllIntents",
		  intentsValues : json_encode(intentsValues),
		},
		dataType: 'json',
		error: function (request, status, error) {
			$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
		},
		success: function (data) {
		  if (data.state != 'ok') {
			$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
			return
		  }
		  $('#div_alertIntentsSummary').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
		  refreshIntentsSummary()
		},
	  })
}
</script>