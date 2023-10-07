<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function jeerhasspy_install() {
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
    DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
	include((dirname(__FILE__) . '/formulaire.config.php'));
}

function jeerhasspy_update() {
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
    DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
	include((dirname(__FILE__) . '/formulaire.config.php'));

    $eqLogics = eqLogic::byType('jeerhasspy');
	foreach ($eqLogics as $eqLogic) {
		if ($eqLogic->getConfiguration('type') == 'intent') {
			$intent = new jeerhasspy_intent();
			$intent->setName($eqLogic->getName());
			$intent->setIsEnable($eqLogic->getIsEnable());
			$intent->setConfiguration('group', $eqLogic->getConfiguration('group'));
			if ($eqLogic->getConfiguration('isInteract')) {
				$intent->setIsInteract(1);
			} else {
				$intent->setIsInteract(0);
				//scenario:
				$eqScenario = $eqLogic->getConfiguration('callbackScenario');
				$intentScenario = array();
				$intent->setScenario('id', $eqScenario['scenario']);
				$intent->setScenario('action', $eqScenario['action']);
				$intent->setScenario('minConfidence', $eqScenario['minConfidence']);

				//tags:
				$intent->setTags('intent', $eqScenario['isTagIntent']);
				$intent->setTags('confidence', $eqScenario['isTagConfidence']);
				$intent->setTags('wakeword', $eqScenario['isTagWakeword']);
				$intent->setTags('query', $eqScenario['isTagQuery']);
				$intent->setTags('siteid', $eqScenario['isTagSiteId']);
				$intent->setTags('entities', $eqScenario['isTagEntities']);
				$intent->setTags('slots', $eqScenario['isTagSlots']);
				$intent->setTags('user', $eqScenario['user_tags']);
			}
			$intent->save();
			$eqLogic->remove();
		}
	}
	//delete rhasspy intents object. TTS devices will go into None object
	$obj = jeeObject::byId(config::byKey('intentObjectId', 'jeerhasspy'));
	if (is_object($obj)) {
		$obj->remove();
	}
	config::remove('intentObjectId', 'jeerhasspy');
}

function jeerhasspy_remove() {
	$sql = 'DROP TABLE jeerhasspy_intent';
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

	$sql = "DELETE FROM `config` WHERE `key` LIKE '%jeerhasspy%'";
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
}

?>
