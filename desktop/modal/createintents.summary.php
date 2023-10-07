<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$_objet = array();
foreach ((jeeObject::all()) as $object) {
	$_objet[$object->getId()] = $object->getName();
}
$_saveformulaire = config::byKeys(['formulaire-jeedom_lumiere','formulaire-jeedom_chauffage','formulaire-jeedom_volet'], 'jeerhasspy');
$_configformulaire = config::byKeys(['config-jeedom_lumiere','config-jeedom_chauffage','config-jeedom_volet'], 'jeerhasspy');

//sendVarToJS('_saveformulaire', $_saveformulaire);
//sendVarToJS('_configformulaire', $_configformulaire);
?>
<style>
	.multiselect {
	  width: 200px;
	  display: flex;
	}

	.selectBox {
	  position: relative;
	  white-space: nowrap;
	  margin-left: 5px;
	  margin-right: 5px;
	}

	.selectBox select {
	  width: 100%;
	  font-weight: bold;
	}

	.overSelect {
	  position: absolute;
	  left: 0;
	  right: 0;
	  top: 0;
	  bottom: 0;
	}

	.checkboxes {
	  display: block;
	  margin-left: 5px;
	  margin-right: 5px;
	  white-space: nowrap;
	  border: 1px #dadada solid;
	}

	.checkboxes label {
	  display: block;
	}

	.checkboxes label:hover {
	  background-color: #1e90ff;
	}
</style>
<div id="div_alertIntentsForm"></div>
<div style="display:flex;align-items: flex-start;">
	<div id="menu" style="display:flex;flex-direction: column;">
		<?php
			echo '<div>';
			foreach ($_configformulaire as $key => $value) {
				echo '<div style="display: flex; justify-content: space-between;">';
				echo '<a href="#" onclick="showForm(\'' . $key . '\')">' . substr($key, 7) . '</a>';
				if (isset($_saveformulaire['formulaire-' . substr($key, 7)]['active'])) {
					if ($_saveformulaire['formulaire-' . substr($key, 7)]['active'] == "1") {
						echo '<input type="checkbox" id="select' . $key . '" name="' . $key . '" checked>';
					} else {
						echo '<input type="checkbox" id="select' . $key . '" name="' . $key . '">';
					}
				} else {
					echo '<input type="checkbox" id="select' . $key . '" name="' . $key . '">';
				}
				echo "</div>";
			}
			echo '</div>';
		?>
		<a class="btn btn-success btn-sm roundedRight" id="bt_trainmodel"><i class="fas fa-check-circle"></i> Sauvegarder / Ré-entraîner</a>
	</div>
	<div style="display:flex;">
		<?php
			foreach ($_configformulaire as $formname => $formconfig) {
				$nom_table_save = 'formulaire-' . substr($formname, 7); // on enleve le config-
				echo '<div id="' . $formname . '" style="display:none;">';
				echo '<h2>' . $formconfig["titre"] . '</h2>';
				foreach ($formconfig["module"] as $key => $value) {
					echo '<div class="' . $key . '">';
					switch ($value["type"]) {
						case 'select':
							echo '<label for="' . $value["name"] . '">' . $value["balise"] . '</label>';
							echo '<select class="' . $value["name"] . '" name="' . $value["name"] . '" multiple size="' . $value["size"] . '">';
							if (is_array($value["option"])) {
								$tableval = $value["option"];
								foreach ($tableval as $idoption => $nomoption) {
									if (isset($_saveformulaire[$nom_table_save][$value["name"]])) {
										if ($_saveformulaire[$nom_table_save][$value["name"]][$idoption] == "1") {
											echo '<option value="' . $idoption . '" selected>' . $idoption . '</option>';
										} else {
											echo '<option value="' . $idoption . '">' . $idoption . '</option>';
										}
									} else {
										if ($nomoption == "1") {
											echo '<option value="' . $idoption . '" selected>' . $idoption . '</option>';
										} else {
											echo '<option value="' . $idoption . '">' . $idoption . '</option>';
										}
									}
								}
							} else {
								$tablenom = $value["option"];
								foreach ($$tablenom as $idoption => $nomoption) {
									if (isset($_saveformulaire[$nom_table_save][$value["name"]])) {
										if (array_key_exists($idoption, $_saveformulaire[$nom_table_save][$value["name"]])) {
											echo '<option value="' . $idoption . '" selected>' . $nomoption . '</option>';
										} else {
											echo '<option value="' . $idoption . '">' . $nomoption . '</option>';
										}
									} else {
										echo '<option value="' . $idoption . '">' . $nomoption . '</option>';
									}
								}
							}
							echo '</select>';
							break;
						case 'text':
							if (isset($_saveformulaire[$nom_table_save][$value["name"]])) {
								if (is_array($_saveformulaire[$nom_table_save][$value["name"]])) {
									$value["option"] = implode(';', $_saveformulaire[$nom_table_save][$value["name"]]);
								} else {
									$value["option"] = $_saveformulaire[$nom_table_save][$value["name"]];
								}
							}
							echo '<label for="' . $value["name"] . '">' . $value["balise"] . '</label>';
							echo '<input type="text" class="' . $value["name"] . '" name="' . $value["name"] . '" value="' . $value["option"] . '" style="width: 90%;">';
							break;
						case 'build_phrase':
							echo '<div class="multiselect">';
							foreach ($value["div"] as $keydiv => $valuediv) {
								switch ($valuediv["type"]) {
									case 'texte':
										echo '<div class="' . $valuediv["class"] . '">' . $valuediv["text"] . '</div>';
										break;
									case 'checkbox':
										echo '<div class="' . $valuediv["class"] . '">';
										foreach ($valuediv["text"] as $keytext => $valuetext) {
											if (isset($_saveformulaire[$nom_table_save][$key][1])) {
												if (in_array($valuetext["text"], $_saveformulaire[$nom_table_save][$key][1])) {
													echo '<label for="' . $valuetext["name"] . '"><input type="checkbox" id="' . $valuetext["name"] . '" checked />' . $valuetext["text"] . '</label>';
												} else {
													echo '<label for="' . $valuetext["name"] . '"><input type="checkbox" id="' . $valuetext["name"] . '" />' . $valuetext["text"] . '</label>';
												}
											} else {
												echo '<label for="' . $valuetext["name"] . '"><input type="checkbox" id="' . $valuetext["name"] . '" />' . $valuetext["text"] . '</label>';
											}
										}
										echo '</div>';
										break;
									default:
										# code...
										break;
								}
							}
							echo '</div>';
							break;
						case 'multitext':
							for ($i=0; $i < count($value["name"]); $i++) {
								if (isset($_saveformulaire[$nom_table_save][$value["name"][$i]])) {
									if (is_array($_saveformulaire[$nom_table_save][$value["name"][$i]])) {
										$value["option"][$i] = implode(';', $_saveformulaire[$nom_table_save][$value["name"][$i]]);
									} else {
										$value["option"][$i] = $_saveformulaire[$nom_table_save][$value["name"][$i]];
									}
								}
								echo '<label for="' . $value["name"][$i] . '">' . $value["balise"][$i] . '</label>';
								echo '<input type="text" class="' . $value["name"][$i] . '" name="' . $value["name"][$i] . '" value="' . $value["option"][$i] . '" action="' . $i . '" style="width: 90%;">';
							}
							break;
						default:
							# code...
							break;
					}
					echo '</div>';
					echo '<br>';
				}
				echo '</div>';
			}
		?>
	</div>
</div>
<script>
function showForm(formId) {
	// Masquer tous les formulaires
	<?php
		foreach ($_configformulaire as $key => $value) {
			echo 'document.getElementById(\'' . $key . '\').style.display = \'none\';';
		}
	?>

	// Afficher le formulaire spécifié
	document.getElementById(formId).style.display = 'block';
}
$('#bt_trainmodel').off().on('click',function() {
	saveIntentsForm()
})
//console.log(_saveformulaire)
//console.log(_configformulaire)

function buildphrase(form) {
	let textsInDivs = $("#" + form + " .build_phrase .textintent").map(function() {
		return $(this).text();
	}).get();
	let selectedCheckboxes = $("#" + form + " .checkboxes input:checked").map(function() {
		return $(this).parent().text();
	}).get();
	return [textsInDivs, selectedCheckboxes]
}
function buildintent(intentname) {
	let valform = {}
	if ($("#select" + intentname).is(':checked')) {
		valform["active"] = "1"
	} else {
		valform["active"] = "0"
	}
	valform["build_phrase"] = buildphrase(intentname)
	let formobjet = $("#" + intentname + " .objet option:selected").map(function() {
		return [$(this).val(),$(this).text()];
	}).get();
	valform["objet"] = {}
	// pas de la boucle for a 2 car on incrémente de 2 a chaque fois
	for (let i = 0; i < formobjet.length; i+=2) {
		valform["objet"][formobjet[i]] = formobjet[i+1]
	}
	valform["category"] = {}
	let formcategory = $("#" + intentname + " .select_category .category");
	formcategory = formcategory[0].options
	for (let i = 0; i < formcategory.length; i++) {
		if (formcategory[i].selected) {
			valform["category"][formcategory[i].value] = "1"
		} else {
			valform["category"][formcategory[i].value] = "0"
		}
	}
	valform['nom_equipement'] = $("#" + intentname + " .nom_equipement").val().split(';')
	valform['custom_word'] = {}
	let custommot = $("#" + intentname + " .custom_word").val().split(';')
	for (let i = 0; i < custommot.length; i++) {
		let indexEspace = custommot[i].indexOf(' ');
		if (indexEspace !== -1) {
			valform['custom_word'][custommot[i].slice(0, indexEspace)] = custommot[i];
		}
	}
	// recupere les valeurs des input text et les split dans un tableau qui a le nom de l'input
	let formcommand = $("#" + intentname + " .saisi_commande input").map(function() {
		return [$(this).attr('action'),$(this).val().split(';')];
	}).get();
	valform["saisi_commande"] = []
	for (let i = 0; i < formcommand.length; i+=2) {
		valform["saisi_commande"][formcommand[i]] = formcommand[i+1]
	}
	valform['retour'] = $("#" + intentname + " .retour").val()
	return valform
}

function saveIntentsForm() {
	var intentsValues = {}
	intentsValues["jeedom_lumiere"] = buildintent("config-jeedom_lumiere")
	intentsValues["jeedom_chauffage"] = buildintent("config-jeedom_chauffage")
	intentsValues["jeedom_volet"] = buildintent("config-jeedom_volet")
	//console.log(intentsValues)
	$.ajax({
		type: "POST",
		url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
		data: {
		  action: "buildmodel",
		  intents: json_encode(intentsValues)
		},
		dataType: 'json',
		error: function (request, status, error) {
			$('#div_alertIntentsForm').showAlert({message: error.message, level: 'danger'})
		},
		success: function (data) {
		  if (data.state != 'ok') {
			$('#div_alertIntentsForm').showAlert({message: error.message, level: 'danger'})
			return
		  }
		  $('#div_alertIntentsForm').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
		},
	})
}
</script>