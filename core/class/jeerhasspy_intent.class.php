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

class jeerhasspy_intent
{
	private $id;
	private $name;
	private $isEnable;
	private $configuration;
	private $isInteract;
	private $scenario;
	private $tags;

	public static function all($_onlyEnable=false)
	{
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM jeerhasspy_intent';
		if ($_onlyEnable) {
			$sql .= ' WHERE isEnable=1';
		}
		return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function byId($_id)
	{
		$values = array(
			'id' => $_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM jeerhasspy_intent
		WHERE id=:id';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function byName($_name)
	{
		$values = array(
			'name' => $_name,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM jeerhasspy_intent
		WHERE name=:name';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}

	public function save()
	{
		return DB::save($this);
	}

	public function remove()
	{
		DB::remove($this);
	}

	//Get intent eq scenario:
    public function exec_callback_scenario($payload=null)
    {
        $callback_settings = $this->getScenario();
        if (!is_array($callback_settings)) {
            RhasspyUtils::logger('No scenario defined for this intent.');
            return false;
        }
        $_scenarioId = $callback_settings['id'];
        $_scenarioAction = $callback_settings['action'];

        if (!is_object(scenario::byId($_scenarioId))) {
            RhasspyUtils::logger('scenario: id '. $_scenarioId .' does not exist', 'error');
            return false;
        } else {
        	RhasspyUtils::logger('scenario: '.scenario::byId($_scenarioId)->getName());
        }

        $options = array();
        $options['scenario_id'] = $_scenarioId;
        $options['action'] = $_scenarioAction;
        $options['tags'] = $this->get_all_scenario_tags($payload);

        return scenarioExpression::createAndExec('action', 'scenario', $options);
    }
    //Set scenario tags for scenario exec:
    public function get_all_scenario_tags($_payload)
    {
        $settingsTags = $this->getTags();
        $tags = array();
        $userTags = $settingsTags['user'];

        $userTags = arg2array($userTags);
        foreach ($userTags as $key => $value) {
            $tags['#' . trim(trim($key), '#') . '#'] = $value;
        }

        if ($settingsTags['intent'] == '1') $tags['#intent#'] = $_payload['intent']['name'];
        if ($settingsTags['confidence'] == '1') $tags['#confidence#'] = $_payload['intent']['confidence'];
        if ($settingsTags['wakeword'] == '1') $tags['#wakeword#'] = $_payload['wakeword_id'];
        if ($settingsTags['query'] == '1') $tags['#query#'] = $_payload['text'];
        if ($settingsTags['siteid'] == '1') $tags['#siteId#'] = $_payload['site_id'];

        //get slots and entities. slots are always unique, entities can be multiple.
        $tagValues = array();
        if ($settingsTags['slots'] == '1') {
            foreach ($_payload['slots'] as $slot => $value) {
            	$tagValues[$slot] = array($value);
            }
        }

        if ($settingsTags['entities'] == '1') {
            foreach ($_payload['entities'] as $entity) {
            	if (isset($tagValues[$entity['entity']])) {
            		array_push($tagValues[$entity['entity']], $entity['value']);
            	} else {
            		$tagValues[$entity['entity']] = array($entity['value']);
            	}
            }
        }

        foreach ($tagValues as $tag => $arValue) {
        	$arValue = array_unique($arValue);
        	$tags['#'.$tag.'#'] = implode(',', $arValue);
        }

        RhasspyUtils::logger('out:scenario tags: '.json_encode($tags));
        return $tags;
    }

	/*     * **********************Getteur Setteur*************************** */
	public function getId() {
		return $this->id;
	}
	public function setId($_id) {
		$this->id = $_id;
	}

	public function getName() {
		return $this->name;
	}
	public function setName($_name) {
		$this->name = $_name;
	}

	public function getIsEnable($_default=0) {
		if ($this->isEnable == '' || !is_numeric($this->isEnable)) {
			return $_default;
		}
		return $this->isEnable;
	}
	public function setIsEnable($_isEnable) {
		$this->isEnable = $_isEnable;
		return $this;
	}

	public function getConfiguration($_key='', $_default='')
	{
		return utils::getJsonAttr($this->configuration, $_key, $_default);
	}
	public function setConfiguration($_key, $_value)
	{
		$this->configuration = utils::setJsonAttr($this->configuration, $_key, $_value);
	}

	public function getIsInteract($_default=0) {
		if ($this->isInteract == '' || !is_numeric($this->isInteract)) {
			return $_default;
		}
		return $this->isInteract;
	}
	public function setIsInteract($_isInteract) {
		$this->isInteract = $_isInteract;
		return $this;
	}

	public function getScenario($_key='', $_default='')
	{
		return utils::getJsonAttr($this->scenario, $_key, $_default);
	}
	public function setScenario($_key, $_value)
	{
		$this->scenario = utils::setJsonAttr($this->scenario, $_key, $_value);
	}

	public function getTags($_key='', $_default='')
	{
		return utils::getJsonAttr($this->tags, $_key, $_default);
	}
	public function setTags($_key, $_value)
	{
		$this->tags = utils::setJsonAttr($this->tags, $_key, $_value);
	}
}