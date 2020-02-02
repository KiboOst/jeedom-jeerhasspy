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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	require_once dirname(__FILE__) . '/../class/rhasspy.utils.class.php';

	include_file('core', 'authentification', 'php');
	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'loadAssistant') {
		$_cleanIntents = init('mode', null);
		$result = RhasspyUtils::loadAssistant($_cleanIntents);
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success($result);
	}

	if (init('action') == 'deleteIntents') {
		$result = RhasspyUtils::deleteIntents();
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success();
	}

	if (init('action') == 'deleteSatellite') {
		$_id = init('id', null);
		$eqLogic = eqLogic::byId($_id);
		$result = $eqLogic->remove();
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success();
	}

	if (init('action') == 'addSatellite') {
		$_addr = init('addr', null);
		$_siteId = init('siteId', null);
		$result = RhasspyUtils::create_rhasspy_deviceEqlogic($_siteId, 'satDevice', $_addr);
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success();
	}


	if (init('action') == 'test') {
		$siteId = init('siteId', null);
		$result = RhasspyUtils::test($siteId);
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success();
	}

	if (init('action') == 'configureRhasspyProfile') {
		$_siteId = init('siteId', null);
		$_url = init('url', null);
		$_configRemote = init('configRemote', false);
		$_configWake = init('configWake', false);
		$result = RhasspyUtils::configureRhasspyProfile($_siteId, $_url, $_configRemote, $_configWake);
		if ( isset($result['error']) ) {
			ajax::error($result['error']);
		}
		ajax::success($result);
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}