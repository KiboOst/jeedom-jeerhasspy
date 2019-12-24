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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/rhasspy.utils.class.php';

class jeerhasspy extends eqLogic {
    public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

    //rhasspy called endpoint forwarded by jeeAPI:
    public static function event() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            RhasspyUtils::logger('POST: '.$input);
            $payload = json_decode($input, true);
            $answer = array('speech' => array('text' => ''));
            if (isset($payload['intent']) && isset($payload['intent']['name'])) {
                $intentName = $payload['intent']['name'];
                if ($intentName != '') {
                    $eqLogic = eqLogic::byLogicalId($intentName, 'jeerhasspy');
                    if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1)
                    {
                        $eqLogic->get_callback_scenario(null,null,null,$payload);
                    } else {
                        $answer['speech']['text'] = config::byKey('defaultTTS', 'jeerhasspy');
                    }
                }
            }
            header('Content-Type: application/json');
            echo json_encode($answer);
         }
    }

    //Get intent eq scenario:
    public function get_callback_scenario($scenario_id=null, $scenario_action=null, $scenario_tags=null, $payload=null)
    {
        $callback_settings = $this->getConfiguration('callbackScenario');
        RhasspyUtils::logger('callback_settings: '.json_encode($callback_settings));

        if (!is_array($callback_settings) || $callback_settings['scenario'] == '-1') {
            return false;
        }

        $_scenarioId = $callback_settings['scenario'];
        $_scenarioAction = $callback_settings['action'];

        if (!is_object(scenario::byId($_scenarioId))) {
            RhasspyUtils::logger('scenario: id '. $_scenarioId .' does not exist', 'error');
            return false;
        }
        $options = array();
        $options['scenario_id'] = $_scenarioId;
        $options['action'] = $_scenarioAction;
        $options['tags'] = $this->get_all_scenario_tags($callback_settings, $payload);

        return scenarioExpression::createAndExec('action', 'scenario', $options);
    }
    //Set scenario tags for scenario exec:
    public function get_all_scenario_tags($_options, $_payload)
    {
        RhasspyUtils::logger('_options: '.json_encode($_payload));
        $tags = array();
        $userTags = $_options['user_tags'];

        $userTags = arg2array($_options['user_tags']);
        foreach ($userTags as $key => $value) {
            $tags['#' . trim(trim($key), '#') . '#'] = $value;
        }

        if ($_options['isTagIntent'] == '1') $tags['#intent#'] = $_payload['intent']['name'];
        if ($_options['isTagConfidence'] == '1') $tags['#confidence#'] = $_payload['intent']['confidence'];
        if ($_options['isTagWakeword'] == '1') $tags['#wakeword#'] = $_payload['wakeId'];
        if ($_options['isTagQuery'] == '1') $tags['#query#'] = $_payload['text'];
        if ($_options['isTagSiteId'] == '1') $tags['#siteId#'] = $_payload['siteId'];

        if ($_options['isTagEntities'] == '1') {
            foreach ($_payload['entities'] as $entity) {
                $tags['#'.$entity['entity'].'#'] = $entity['value'];
            }
        }
        if ($_options['isTagSlots'] == '1') {
            foreach ($_payload['slots'] as $slot) {
                $tags['#'.$slot['slot'].'#'] = $slot['value'];
            }
        }

        return $tags;
    }

    /*     * *********************Méthodes d'instance************************* */
    public function preInsert() {}
    public function postInsert() {}
    public function preSave() {}
    public function postSave() {}
    public function preUpdate() {}
    public function postUpdate() {}
    public function preRemove() {}
    public function postRemove() {}

    /*     * **********************Getteur Setteur*************************** */
}

class jeerhasspyCmd extends cmd {

    public function execute($options = array())
    {
        $eqlogic = $this->getEqLogic();
        RhasspyUtils::logger();
        switch ($this->getLogicalId()) {
            case 'speak':
                $this->rhasspy_speak($options);
                break;
            case 'dynspeak':
                $this->rhasspy_dynamicSpeak($options);
                break;
            case 'ask':
                $this->rhasspy_ask($options);
                break;
        }
    }

    public function rhasspy_speak($options = array())
    {
        RhasspyUtils::logger($options['message'], 'info');
        RhasspyUtils::textToSpeech($options);
    }

    public function rhasspy_dynamicSpeak($options = array())
    {
        RhasspyUtils::logger($options['message'], 'info');
        $_message = RhasspyUtils::evalDynamicString($options['message']);
        $options['message'] = $_message;
        RhasspyUtils::textToSpeech($options);
    }

    public function rhasspy_ask($options = array())
    {
        RhasspyUtils::logger($options['message'], 'info');
    }
}