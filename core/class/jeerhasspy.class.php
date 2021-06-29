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
require_once dirname(__FILE__) . '/jeerhasspy_intent.class.php';

class jeerhasspy extends eqLogic {
    //rhasspy called endpoint forwarded by jeeAPI:
    public static function event() {
        RhasspyUtils::logger('__EVENT__: '.file_get_contents('php://input'));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = json_decode(file_get_contents('php://input'), true);

            //wakeword received:
            if (isset($payload['modelId']) && !isset($payload['intent'])) {
                if (config::byKey('setWakeVariables', 'jeerhasspy') == '1') {
                    $siteId = explode(',', $payload['siteId'])[0];
                    scenario::setData('rhasspyWakeWord', $payload['modelId']);
                    scenario::setData('rhasspyWakeSiteId', $siteId);
                    RhasspyUtils::logger('--Awake -> set variables: rhasspyWakeWord->'.$payload['modelId'].' | rhasspyWakeSiteId->'.$siteId);
                }
                return;
            }

            $_answerToRhasspy = array('speech' => array('text' => ''));
            $speakDefault = false;

            //intent received:
            if (isset($payload['intent']) && isset($payload['intent']['name'])) {
                $intentName = $payload['intent']['name'];
                $payload['site_id'] = explode(',', $payload['site_id'])[0];

                if ($intentName != '') {
                    RhasspyUtils::logger('--Intent Recognized: '.$payload['text'].' --> '.json_encode($payload['intent']));

                    $jrIntent = jeerhasspy_intent::byName($intentName);
                    if (is_object($jrIntent) && $jrIntent->getIsEnable() == 1) {
                        //interact
                        if ($jrIntent->getIsInteract()) {
                            RhasspyUtils::logger('--Send query to interact engine!');
                            $reply = interactQuery::tryToReply($payload['text']);
                            if (trim($reply['reply']) != '') {
                                $_options = array();
                                $_options['title'] = $payload['site_id'];
                                $_options['message'] = $reply['reply'];
                                RhasspyUtils::textToSpeech($_options);
                            }
                            return;
                        }

                        //callback scenario
                        $_exec = $jrIntent->exec_callback_scenario($payload);
                        //no scenario executed, if no wakeword_id should be ask answer.
                        if (!$_exec && $payload['wakeword_id'] != null) {
                            $speakDefault = true;
                        } else {
                            RhasspyUtils::playFinished($payload['site_id']);
                        }
                    } else {
                        $speakDefault = true;
                    }

                    if ($speakDefault) $_answerToRhasspy['speech']['text'] = config::byKey('defaultTTS', 'jeerhasspy');
                } else {
                    RhasspyUtils::logger('--Unrecognized payload.');
                }
                //always answer to rhasspy:
                header('Content-Type: application/json');
                echo json_encode($_answerToRhasspy);
                return;
            }
        }
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
        RhasspyUtils::logger($eqlogic->getLogicalId().'.'.$this->getLogicalId().'() | '.json_encode($options));
        switch ($this->getLogicalId()) {
            case 'speak':
                $this->speak($options);
                break;
            case 'dynspeak':
                $this->dynamicSpeak($options);
                break;
            case 'ask':
                $this->ask($options);
                break;
            case 'ledOn':
                $this->setLEDs(1);
                break;
            case 'ledOff':
                $this->setLEDs(0);
                break;
            case 'setvol':
                $this->setVolume($options);
                break;
            case 'repeatTTS':
                $this->repeatTTS($options);
                break;
        }
    }

    public function speak($options=array())
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger(json_encode($options).' siteId: '.$siteId);
        if ($options['title'] == '') {
            $options['title'] = $siteId;
        } elseif (substr($options['title'], 0, 1) == ':') {
            $options['title'] = $siteId.$options['title'];
        }
        RhasspyUtils::textToSpeech($options);
    }

    public function dynamicSpeak($options=array())
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger(json_encode($options).' siteId: '.$siteId);
        if ($options['title'] == '') {
            $options['title'] = $siteId;
        } elseif (substr($options['title'], 0, 1) == ':') {
            $options['title'] = $siteId.$options['title'];
        }
        $options['message'] = RhasspyUtils::evalDynamicString($options['message']);
        RhasspyUtils::textToSpeech($options);
    }

    public function ask($options=array())
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger(json_encode($options).' siteId: '.$siteId);

        $answer_entity = $options['answer'][0];
        $answer_variable = $options['variable'];
        $options['title'] = $siteId;
        RhasspyUtils::textToSpeech($options);

        $options['askData'] = $answer_entity.'::'.$answer_variable;
        RhasspyUtils::speakToAsk($siteId, $options);
    }

    public function setLEDs($state=1)
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger($state.' siteId: '.$siteId);

        RhasspyUtils::setLEDs($state, $siteId);
    }

    public function setVolume($options=array())
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger(json_encode($options).' siteId: '.$siteId);

        RhasspyUtils::setVolume($options['slider'], $siteId);
    }

    public function repeatTTS($options=array())
    {
        $eqName = $this->getEqLogic()->getLogicalId();
        $siteId = str_replace('TTS-', '', $eqName);
        RhasspyUtils::logger(json_encode($options).' siteId: '.$siteId);

        RhasspyUtils::repeatTTS($siteId);
    }
}