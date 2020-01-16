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
			<th data-sorter="select">{{Scénario}}</th>
			<th data-sorter="select" data-filter="false">{{Action}}</th>
			<th data-sorter="checkbox" data-filter="false">{{Actif}}</th>
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
tableIntcSummary[0].config.widgetOptions.resizable_widths = ['', '', '120px', '45px', '95px', '70px', '160px', '40px']
tableIntcSummary.trigger('applyWidgets')
tableIntcSummary.trigger('resizableReset')
tableIntcSummary.trigger('sorton', [[[1,0]]])

$('#bt_refreshIntentsSummary').off().on('click',function() {
	refreshIntentsSummary()
})

$('#bt_saveIntentsSummary').off().on('click',function() {
	saveIntentsSummary()
})

function refreshIntentsSummary() {
	jeedom.eqLogic.byType({
		type : 'jeerhasspy',
		error: function (error) {
			$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
		},
		success : function(data){
			$('#table_intentsSummary tbody').empty()
			var table = []
			for(var i in data){

				var intent = data[i]
				if (intent.configuration.type != 'intent') continue
				var tr = '<tr class="intent" data-id="' + intent.id + '">'
				tr += '<td>'
				tr += '<input class="eqLogicAttr hidden" data-l1key="id">'
				tr += '<input class="eqLogicAttr hidden" data-l1key="configuration" data-l2key="type">'
				tr += '<span class="eqLogicAttr" data-l1key="name"></span>'
				tr += '</td>'

				tr += '<td>'
				if (intent.configuration.hasOwnProperty('callbackScenario') && intent.configuration.callbackScenario.hasOwnProperty('scenario')) {
					tr += '<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="scenario">'
					tr += '<option value="-1">None</option>'
					Object.entries(_scenarios).forEach(([key, value]) => tr += '<option value="' + key + '">' + value + '</option>')
					tr += '</select>'
				}
				tr += '</td>'

				tr += '<td>'
				tr += '<select class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="action">'
				tr += '<option value="start">{{Start}}</option>'
				tr += '<option value="startsync">{{Start (sync)}}</option>'
				tr += '<option value="stop">{{Stop}}</option>'
				tr += '<option value="activate">{{Activer}}</option>'
				tr += '<option value="deactivate">{{Désactiver}}</option>'
				tr += '<option value="resetRepeatIfStatus">{{Remise à zero des SI}}</option>'
				tr += '</select>'
				tr += '</td>'

				tr += '<td>'
				tr += '<center><input type="checkbox" class="eqLogicAttr" data-label-text="{{Actif}}" data-l1key="isEnable"></center>'
				tr += '</td>'

				tr += '<td>'
				tr += ' <input style="width: 100%;" type="number" value="0" min="0" max="1" step="0.1" class="eqLogicAttr input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="minConfidence" />';
				tr += '</td>'

				tr += '<td>'
				tr += '<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">'
					tr += '<i class="fas fa-tags"></i>&nbsp;&nbsp;&nbsp;<span class="caret"></span>'
				tr += '</button>'
				tr += '<ul class="dropdown-menu" role="menu" style="top:unset;left:unset;">'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagIntent"/>&nbsp;intent</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagEntities"/>&nbsp;entities</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagSlots"/>&nbsp;slots</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagSiteId"/>&nbsp;siteId</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagQuery"/>&nbsp;query</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagConfidence"/>&nbsp;confidence</a></li>'
					tr += '<li><a tabIndex="-1"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagWakeword"/>&nbsp;wakeword</a></li>'
				tr += '</ul>'

				tr += '</td>'

				tr += '<td>'
				tr += ' <input style="width: 100%;" class="eqLogicAttr input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="user_tags" />';
				tr += '</td>'

				tr += '<td>'
				tr += '<a class="btn btn-sm btn-success bt_summaryLogScenario" target="_blank" title="{{Ouvrir le log du scénario.}}"><i class="far fa-file-alt"></i> {{Log}}</a>';
				tr += '</td>'

				var result = $(tr)
				result.setValues(intent, '.eqLogicAttr')
				table.push(result)
			}
			$('#table_intentsSummary tbody').append(table)
			$("#table_intentsSummary").trigger("update")

			$('.bt_summaryGotoScenario').off().on('click', function() {
				var scId = $(this).data('scid')
				var url = 'index.php?v=d&p=scenario&id='+scId
				window.open(url).focus()
			})

			$('.bt_summaryLogScenario').off('click').on('click', function () {
				var scId = $(this).closest('.intent').find('select[data-l3key="scenario"]').val()
				$("#md_modal2").dialog({title: "{{Log d'exécution du scénario}}"})
				$("#md_modal2").load('index.php?v=d&modal=scenario.log.execution&scenario_id=' + scId).dialog('open')
				var modal = $('.ui-dialog[aria-describedby="md_modal2"]')
				modal.css({top: modal.position().top - 20 + "px"})
			})
		}
	})
}

function saveIntentsSummary() {
	var intents = $('#table_intentsSummary tbody .intent').getValues('.eqLogicAttr')
	intents.forEach(function (intent) {
		jeedom.eqLogic.save({
			eqLogics : [intent],
			type: 'jeerhasspy',
			error: function (error) {
				$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
			},
			success : function(data){
				$('#div_alertIntentsSummary').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
			}
		})
	})
	refreshIntentsSummary()

	/*
	var intents = $('#table_intentsSummary tbody .intent').getValues('.eqLogicAttr')
	jeedom.eqLogic.save({
		eqLogics : intents,
		type: 'jeerhasspy',
		error: function (error) {
			$('#div_alertIntentsSummary').showAlert({message: error.message, level: 'danger'})
		},
		success : function(data){
			$('#div_alertIntentsSummary').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
			refreshIntentsSummary()
		}
	})
	*/
}
</script>