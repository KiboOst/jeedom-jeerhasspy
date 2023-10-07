<?php
class Model {

	public static function loadCom($uri,$headers=[],$data=null,$timeout=5) {
		$url = config::byKey('rhasspyAddr', 'jeerhasspy');
		$port = config::byKey('rhasspyPort', 'jeerhasspy');
		$request_http = new com_http("http://".$url.":".$port . "" . $uri);
		$request_http->setLogError(false);
		$request_http->setPost($data);
		$request_http->setHeader($headers);
		$result=$request_http->exec($timeout);
		if ($result == "") {
			return "";
		}
		log::add('jeerhasspy','debug',$request_http->getUrl());
		log::add('jeerhasspy','debug',$result);
		return $result;
	}

	public static function trainRhasspyModel() {
		try {
			$response = self::loadCom('/api/train',[],["language" => "fr"], 60);
			log::add('jeerhasspy', 'debug', "Réentraînement terminé");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors du réentraînement : " . $e);
		}
	}

	public static function getRhasspySentences() {
		try {
			$response = self::loadCom('/api/sentences', ["Content-Type: application/json"]);
			$sentences = str_replace("√®", "è", $response);
			$pattern = '/\[([^\]]+)\]\s*([\s\S]*?)(?=\n\n|\Z)/';
			preg_match_all($pattern, $sentences, $matches);
			$result = [];
			foreach ($matches[1] as $index => $key) {
				$result[$key] = explode("\n", $matches[2][$index]);
			}
			return $result;
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des sentences : " . $e);
			return [];
		}
	}

	public static function getRhasspySlots() {
		try {
			$response = self::loadCom('/api/slots', ["Content-Type: application/json"]);
			$slots = $response;
			$slots = str_replace("√®", "è", $slots);
			return json_decode($slots, true);
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des slots : " . $e);
			return [];
		}
	}

	public static function getRhasspyCustomWord() {
		try {
			$response = self::loadCom('/api/custom-words', ["Content-Type: text/plain"]);
			// Convertir la réponse en hexadécimal pour trouver les sauts de ligne (0a), utiliser explode(/n) ne fonctionne pas donc on passe par hex2bin
			$texte_encode_hex = bin2hex($response);
			$words = explode('0a', $texte_encode_hex);
			$phoneme_dict = [];
			foreach ($words as $ligne) {
				// Diviser chaque ligne en un mot et son phonème en utilisant l'espace comme séparateur
				$elements = explode("20", $ligne);
				
				// Si l'élément est vide, passez au suivant
				if (count($elements) < 2) {
					continue;
				}
			
				// Afficher le mot et son phonème
				$mot = $elements[0];
				$phoneme = implode("20", array_slice($elements, 1));
				$phoneme_dict[hex2bin($mot)] =hex2bin($mot) . " " . hex2bin($phoneme);
			}
			return $phoneme_dict;
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des mots personnalisés : " . $e);
			return [];
		}
	}

	public static function setRhasspySlots($data) {
		try {
			$response = self::loadCom('/api/slots?overwrite_all=true', ["Content-Type: application/json"], json_encode($data));
			log::add('jeerhasspy', 'debug', "Slots ajoutés");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des slots : " . $e);
		}
	}

	public static function setRhasspySentences($data) {
		try {
			log::add('jeerhasspy', 'debug', $data);
			$response = self::loadCom('/api/sentences', [], $data);
			log::add('jeerhasspy', 'debug', "Sentences ajoutées");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des sentences : " . $e);
		}
	}

	public static function setRhasspyCustomWord($data) {
		try {
			$response = self::loadCom('/api/custom-words', [], implode("\n", $data));
			log::add('jeerhasspy', 'debug', "Mots personnalisés ajoutés");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des mots personnalisés : " . $e);
		}
	}

	public static function buildModel($formvalues) {
		log::add('jeerhasspy', 'debug', 'buildModel');
		$new_intent = "";
		$liste_equipement = utils::o2a(eqLogic::all());
		$intent = [
			"nbInteractQuery" => "0",
			"name" => "",
			"group" => "rhasspybuild",
			"enable" => "1",
			"display" => ["icon" => ""],
			"query" => "",
			"options" => ["mustcontain" => "", "synonymes" => "", "waitBeforeReply" => "", "convertBinary" => "", "exclude_regexp" => "", "allowSyntaxCheck" => "0"],
			"reply" => "",
			"person" => "",
			"comment" => "",
			"filtres" => ["type" => ["info" => "0", "action" => "1"], "subtype" => ["numeric" => "1", "binary" => "1", "string" => "1", "other" => "1", "slider" => "1", "message" => "1", "color" => "1", "select" => "1"], "unite" => ["none" => "1", "mn" => "0", "V" => "0", "%" => "0", "W" => "0", "ms" => "0", "s" => "0", "°C" => "0", "dBm" => "0", "A" => "0", "WH" => "0", "kW" => "0", "kWh" => "0", "€/L" => "0", "€" => "0", "Km" => "0", "mm" => "0", "km/h" => "0", "ppm " => "0", "Hpa" => "0", "°" => "0", "m" => "0", "mm/h" => "0", "w/m²" => "0", "lux" => "0", "Pa" => "0", "mV" => "0", "lqi" => "0", "MB" => "0", "TB" => "0", "sec" => "0", "Mo" => "0"], "object" => [], "plugin" => [], "category" => ["noCategory" => "1", "heating" => "1", "security" => "1", "energy" => "1", "light" => "1", "opening" => "1", "automatism" => "1", "multimedia" => "1", "default" => "1"], "visible" => ["object" => "1", "eqlogic" => "1", "cmd" => "1"], "eqLogic_id" => "all"],
			"actions" => ["cmd" => []]
		];
		log::add('jeerhasspy', 'debug', 'recuperation des données rhasspy');
		$exist_slot = self::getRhasspySlots();
		$exist_intent = self::getRhasspySentences();
		$exist_custom_word = self::getRhasspyCustomWord();
		
		log::add('jeerhasspy', 'debug', 'compilation des données rhasspy');
		foreach ($formvalues as $key => $value) {
			$exist_intent[$key] = [];
			$exist_intent[$key][] = "commande = (" . implode(' | ', $value["build_phrase"][1]) . "){commande}";
			$exist_intent[$key][] = "equipement = ($" . $key . "_equipement){equipement}";
			$exist_intent[$key][] = "objet = ($" . $key . "_objet){objet}";
			$exist_intent[$key][] = "phrase_demande = (" . str_replace("] [", "|", implode(' ', $value["build_phrase"][0])) . ")";
			$exist_intent[$key][] = "<phrase_demande>";
			$exist_slot[$key . '_equipement'] = $value['nom_equipement'];
			foreach ($value['custom_word'] as $i => $word) {
				$exist_custom_word[$i] = $word;
			}
			$exist_slot[$key . '_objet'] = [];
			foreach ($value['objet'] as $i => $objet) {
				$exist_slot[$key . '_objet'][] = $objet;
			}
		}
		foreach ($exist_intent as $key => $value) {
			$new_intent .= "[".$key."]\n";
			if (is_array($value)) {
				foreach ($value as $line) {
					$new_intent .= $line."\n";
				}
			} else {
				$new_intent .= $value."\n";
			}
			$new_intent .= "\n";
		}

		log::add('jeerhasspy', 'debug', 'envoi des données vers rhasspy');
		self::setRhasspySentences($new_intent);
		self::setRhasspySlots($exist_slot);
		self::setRhasspyCustomWord($exist_custom_word);

		log::add('jeerhasspy', 'debug', 'retrain du model rhasspy');
		self::trainRhasspyModel();

		log::add('jeerhasspy', 'debug', 'recuperation des données jeedom');
		$results = utils::o2a(interactDef::all());
		foreach ($results as &$result) {
			$result['nbInteractQuery'] = count(interactQuery::byInteractDefId($result['id']));
			$result['nbEnableInteractQuery'] = count(interactQuery::byInteractDefId($result['id'], true));
			if (isset($result['link_type']) && $result['link_type'] == 'cmd' && $result['link_id'] != '') {
				$link_id = '';
				foreach (explode('&&', $result['link_id']) as $cmd_id) {
					$cmd = cmd::byId($cmd_id);
					if (is_object($cmd)) {
						$link_id .= cmd::cmdToHumanReadable('#' . $cmd->getId() . '# && ');
					}
				}
				$result['link_id'] = trim(trim($link_id), '&&');
			}
		}

		log::add('jeerhasspy', 'debug', 'compilation des données jeedom');
		foreach ($formvalues as $key => $value) {
			log::add('jeerhasspy', 'debug', 'model-' . $key);
			$newintentjeedom = $intent;
			$newintentjeedom['filtres']['category'] = $value['category'];
			$newintentjeedom['enable'] = $value['active'];
			for ($i = 0; $i < count($results); $i++) { // si l'intent existe deja on recupere son id
				if ($results[$i]['name'] == $key) {
					$newintentjeedom['id'] = $results[$i]['id'];
					break;
				}
			}
			$newintentjeedom['name'] = $key;
			foreach ($value['objet'] as $i => $objet) { // on ajoute les objets
				$newintentjeedom['filtres']['object'][$i] = "1";
			}
			foreach ((jeeObject::all()) as $object) { // si il ne sont pas dans la liste on les ajoutes avec le statut 0
				$idobject = $object->getId();
				if (!isset($newintentjeedom['filtres']['object'][$idobject])) {
					$newintentjeedom['filtres']['object'][$idobject] = "0";
				}
			}
			foreach ((eqLogic::allType()) as $type) {
				$newintentjeedom['filtres']['plugin'][$type['type']] = "1";
			}
			$newintentjeedom['query'] = implode(' ', $value["build_phrase"][0]);
			$newintentjeedom['query'] = str_replace("]", "|]", str_replace("] [", "|", $newintentjeedom['query']));
			$newintentjeedom['query'] = str_replace(">", "#", str_replace("<", "#", $newintentjeedom['query']));
			for ($i = 0; $i < count($value["build_phrase"][1]); $i++) {
				$splited = explode(" | ", $value["build_phrase"][1][$i]);
				for ($j = 0; $j < count($splited); $j++) {
					for ($k = 0; $k < count($value['saisi_commande'][$j]); $k++) {
						if ($i == 0) {
							$value['saisi_commande'][$j][$k] .= "=" . $splited[$j]; // on ajoute le = pour le premier mot
							if ($i != count($value["build_phrase"][1]) - 1) {
								$value['saisi_commande'][$j][$k] .= ",";
							}
						} elseif ($i == count($value["build_phrase"][1]) - 1) {
							$value['saisi_commande'][$j][$k] .= $splited[$j]; // on ajoute rien de plus pour le dernier mot
						} else {
							$value['saisi_commande'][$j][$k] .= $splited[$j] . ",";
						}
					}
				}
			}
			$newintentjeedom['options']['synonymes'] = "";
			for ($j = 0; $j < count($value['saisi_commande']); $j++) {
				for ($k = 0; $k < count($value['saisi_commande'][$j]); $k++) {
					if ($j == count($value['saisi_commande']) - 1 && $k == count($value['saisi_commande'][$j]) - 1) { // si c'est le dernier on ne met pas de |
						$newintentjeedom['options']['synonymes'] .= $value['saisi_commande'][$j][$k];
					} else {
						$newintentjeedom['options']['synonymes'] .= $value['saisi_commande'][$j][$k] . "|";
					}
				}
			}
			foreach ($liste_equipement as $element) {
				if (array_key_exists($element["object_id"], $value['objet'])) { // si l'equipement est dans un objet selectionné
					log::add('jeerhasspy', 'debug', 'objet trouvé : ' . $element["object_id"]);
					foreach ($element['category'] as $category => $stats) {
						if ($value['category'][$category] == "1") { // si l'equipement est dans une categorie selectionné
							log::add('jeerhasspy', 'debug', 'categorie trouvé : ' . $category);
							$nbr_commande_ok = false;
							foreach ($value['nom_equipement'] as $nom_equipement) {
								log::add('jeerhasspy', 'debug', 'nom_equipement trouvé! : ' . $nom_equipement);
								log::add('jeerhasspy', 'debug', 'nom_equipement trouvé? : ' . $element["name"]);
								if (strpos($element["name"],$nom_equipement) !== false) { // si une partie du nom de l'equipement est dans la liste
									log::add('jeerhasspy', 'debug', 'equipement trouvé : ' . $element["name"]);
									if (!$nbr_commande_ok) {
										$newintentjeedom['options']['synonymes'] .= "|" . $element["name"] . "=" . $nom_equipement;
										$nbr_commande_ok = true;
									} else {
										$newintentjeedom['options']['synonymes'] .= "," . $nom_equipement;
									}
								}
							}
							break;
						}
					}
				}
			}
			$newintentjeedom['reply'] = $value['retour'];
			$interact_json = jeedom::fromHumanReadable($newintentjeedom); // sauvegarde de l'intent jeedom
			if (isset($interact_json['id'])) {
				$interact = interactDef::byId($interact_json['id']);
			}
			if (!isset($interact) || !is_object($interact)) {
				$interact = new interactDef();
			}
			utils::a2o($interact, $interact_json);
			sleep(1);
			if ($interact->save()) {
				log::add('jeerhasspy', 'debug', 'model-' . $key . ' sauvegardé');
				config::save('formulaire-'.$key, json_encode($formvalues[$key]), 'jeerhasspy');
			} else {
				log::add('jeerhasspy', 'debug', 'model-' . $key . ' erreur lors de la sauvegarde');
			}
		}
		log::add('jeerhasspy', 'debug', 'fin de la compilation');
	}
}
?>