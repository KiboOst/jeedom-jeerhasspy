<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('jeerhasspy');
sendVarToJS('eqType', $plugin->getId());

$eqLogicId = $_GET['deviceId'];
$eqLogic = eqLogic::byId($eqLogicId);
$eqLogicValues = jeedom::toHumanReadable(utils::o2a($eqLogic));
sendVarToJS('eqLogicValues', $eqLogicValues);

?>
<div id="div_alertDevice"></div>
<div class="input-group pull-right" style="display:inline-flex">
	<span class="input-group-btn">
		<a id="bt_eqLogicConfigure" class="btn btn-default btn-sm"><i class="fas fa-cogs"></i>{{Configuration avancée}}
		</a><a  id="bt_eqLogicSave" class="btn btn-success btn-sm roundedRight"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
	</span>
</div>
<br/><br/>
<div role="tabpanel" class="tab-pane active" id="eqlogictab">
	<br/>
	<form class="form-horizontal">
	  <fieldset>
		  <div class="form-group">
			  <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
			  <div class="col-sm-3">
				  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
				  <input type="text" class="eqLogicAttr form-control" data-l1key="name"/>
			  </div>
		  </div>

		  <div class="form-group">
			  <label class="col-sm-3 control-label" >{{Objet parent}}</label>
			  <div class="col-sm-3">
				  <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
					<option value="">{{Aucun}}</option>
					<?php
					  $options = '';
					  foreach ((jeeObject::buildTree(null, false)) as $object) {
						$decay = $object->getConfiguration('parentNumber');
						$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $decay) . $object->getName() . '</option>';
					  }
					  echo $options;
					?>
			   </select>
			 </div>
		 </div>

		 <div class="form-group">
			  <label class="col-sm-3 control-label">{{Catégorie}}</label>
			  <div class="col-sm-9">
			   <?php
				  foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
				  echo '<label class="checkbox-inline">';
				  echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
				  echo '</label>';
				  }
				?>
			 </div>
		 </div>

		 <div class="form-group">
		  <label class="col-sm-3 control-label"></label>
		  <div class="col-sm-9">
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		  </div>
		</div>
	  </fieldset>
	</form>
  </div>
<script>
$(function() {
  $('.ui-dialog-content #eqlogictab').setValues(eqLogicValues, '.eqLogicAttr')
})

$('.ui-dialog-content #bt_eqLogicConfigure').off('click').on('click', function() {
	var eqName = $('input.eqLogicAttr[data-l1key="name"]')
	eqName = (eqName.length ? ' : '+eqName.val() : '')
	$('#md_modal').dialog().load('index.php?v=d&modal=eqLogic.configure&eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
})

$('.ui-dialog-content #bt_eqLogicSave').off('click').on('click', function() {
	var eqValues = $('.ui-dialog-content #eqlogictab').getValues('.eqLogicAttr')
	eqValues = eqValues[0]
	jeedom.eqLogic.save({
		type: eqType,
		id: eqValues['id'],
		eqLogics: [eqValues],
		error: function(error) {
			$('#div_alertDevice').showAlert({message: error.message, level: 'danger'})
		},
		success: function(data) {
			modifyWithoutSave = false
			$('#div_alertDevice').showAlert({message: '{{Equipement sauvegardé}}', level: 'success'})
		}
	})
})
</script>