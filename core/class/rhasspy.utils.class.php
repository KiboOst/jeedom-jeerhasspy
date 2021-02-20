<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class RhasspyUtils
{
	protected static $_curlHdl = null;
	protected static $_uri = false;

	public function getURI($_siteId=null)
	{
		$_uri = None;
		if (is_null($_siteId) || $_siteId == config::byKey('masterSiteId', 'jeerhasspy')) {
			$Addr = config::byKey('rhasspyAddr', 'jeerhasspy');
			if (substr($Addr, 0, 4) != 'http') $Addr = 'http://'.$Addr;
			$port = config::byKey('rhasspyPort', 'jeerhasspy');
			if ($port == '') $port = '12101';
			$_uri = $Addr.':'.$port;
		} else {
			$eqLogics = eqLogic::byType('jeerhasspy');
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getConfiguration('type') != 'satDevice') continue;
				if ($eqLogic->getLogicalId() == 'TTS-'.$_siteId) $_uri = $eqLogic->getConfiguration('addr');
			}
		}
		if (($_uri==None) && ($_siteId != config::byKey('masterSiteId', 'jeerhasspy'))) self::getURI(config::byKey('masterSiteId', 'jeerhasspy'));
		return $_uri;
	}

	public static function logger($str = '', $level = 'debug')
	{
		if (is_array($str)) $str = json_encode($str);
		$function_name = debug_backtrace(false, 2)[1]['function'];
		$class_name = debug_backtrace(false, 2)[1]['class'];
		$msg = '['.$class_name.'] '. $function_name .'() '.$str;
		log::add('jeerhasspy', $level, $msg);
	}

	public function loadAssistant($_cleanIntents="0") #called from ajax -> /api/...
	{
		self::logger('cleanIntents: '.$_cleanIntents);
		$_uri = self::getURI();

		//load assistant version, checking right url:
		$url = $_uri.'/api/version';
		$answer = self::_request('GET', $url);
		if ( isset($answer['error']) ) {
			$answer['error'] = __('Impossible de se connecter à votre Rhasspy !', __FILE__);
			return $answer;
		}
		self::logger('version: '.$answer['result']);
		config::save('assistantDate', date('Y-m-d H:i:s'), 'jeerhasspy');
		if ( version_compare($answer['result'], '2.5.0') >= 0 ) {
			config::save('assistantVersion', $answer['result'], 'jeerhasspy');
		} else {
			config::save('assistantVersion', 'unsupported', 'jeerhasspy');
			$answer['error'] = __('Version de Rhasspy non supportée. jeeRhasspy nécessite Rhasspy v2.5 ou plus.', __FILE__);
			return $answer;
		}

		//load profile site_id
		$url = $_uri.'/api/profile';
		$profile = self::_request('GET', $url);
		self::logger('profile: '.$profile['result']);
		$profile = json_decode($profile['result'], true);
		if (isset($profile['mqtt']['site_id'])) {
			$masterName = explode(',', $profile['mqtt']['site_id'])[0];
		} else {
			$masterName = 'Rhasspy';
		}
		self::createDeviceEqlogic($masterName, 'masterDevice');

		//load profiles, default language:
		$url = $_uri.'/api/profiles';
		$profiles = self::_request('GET', $url);
		$profiles = json_decode($profiles['result'], true);
		config::save('defaultLang', $profiles['default_profile'], 'jeerhasspy');

		self::createIntentObject();

		//load intents:
		$url = $_uri.'/api/intents';
		$jsonIntents = self::_request('GET', $url);
		self::logger('intents: '.$jsonIntents['result']);
		$jsonIntents = json_decode($jsonIntents['result'], true);
		$intents = [];
		foreach($jsonIntents as $intentName => $intent) {
			self::logger('found intent: '.$intentName);
			array_push($intents, $intentName);
		}
		//create intents eqlogics:
		self::createIntentEqlogic($intents, $_cleanIntents);

		//update satellites:
		$eqLogics = eqLogic::byType('jeerhasspy');
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') == 'satDevice') {
				$siteId = str_replace('TTS-', '', $eqLogic->getLogicalId());
				self::createDeviceEqlogic($siteId, 'satDevice', $eqLogic->getConfiguration('addr'));
			}
		}
	}

	public function deleteIntents()
	{
		$eqLogics = eqLogic::byType('jeerhasspy');
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') != 'intent') continue;
			$eqLogic->remove();
		}
	}

	public function test($_siteId=null) #called from ajax
	{
		self::logger('_siteId: '.$_siteId);
		$_options = array(
			'title' => $_siteId,
			'message' => null,
		);
		$result = self::textToSpeech($_options, null, $_siteId);
		if ( isset($result['error']) ) {
			return $result;
		}
		return true;
	}

	public function evalDynamicString($_string)
	{
		if (strpos($_string, '{') !== false AND strpos($_string, '}') !== false)
		{
			try {
				preg_match_all('/{(.*?)}/', $_string, $matches);
				foreach ($matches[0] as $expr_string)
				{
					$expr = substr($expr_string, 1, -1);
					$exprAr = explode('|', $expr);
					$value = $exprAr[0];
					array_shift($exprAr);

					$valueString = '';
					foreach ($exprAr as $thisExpr)
					{
						$evaluateString = 'return ';
						$parts = explode(':', $thisExpr);
						if ( $parts[0][0] != '<' AND $parts[0][0] != '>') $parts[0] = '=='.$parts[0];

						$test = eval("return ".$value.$parts[0].";");
						if ($test)
						{
							 $valueString = $parts[1];
						}

						if ($valueString != '') break;
					}

					$_string = str_replace($expr_string, $valueString, $_string);
				}

				return $_string;
			} catch (Exception $e) {
				return $_string;
			}
		}
		else return $_string;
	}

	public function sanitizeString($_string) {
		$lang = config::byKey('defaultLang', 'jeerhasspy');

		if ($lang == 'fr') {
			$_string = preg_replace('/ -(\d+)/', ' moins $1', $_string);
			$_string = preg_replace('/([0-9]+)\.([0-9]+)/', '$1 virgule $2', $_string);
			$_string = preg_replace('/([0-9]+),([0-9]+)/', '$1 virgule $2', $_string);
		}

		return $_string;
	}

	/* Calling HTTP API functions */
	public function textToSpeech($_options=null) #api/text-to-speech (siteId | lang)
	{
		if (!is_array($_options)) return;
		//get either siteId/lang or get master one:
		$_lang = null;
		$_siteId = null;
		if ($_options['title'] != '') {
			$_string = $_options['title'];
			if (strpos($_string, ':') !== false) {
				$_siteId = explode(':', $_string)[0];
				$_lang = explode(':', $_string)[1];
			} else {
				$_siteId = $_options['title'];
			}
		}
		if (is_null($_siteId) || $_siteId == '') {
			$_siteId = config::byKey('masterSiteId', 'jeerhasspy');
		}

		//get either text or test:
		$_text = $_options['message'];
		if (is_null($_text)) {
			$_text = $_siteId.', ceci est un test.';
		} else {
			$_text = self::sanitizeString($_text);
		}

		//language:
		if ($_lang && $_lang == '') {
			$_lang = null;
		} else {
			switch ($_lang) {
				case 'fr':
					$_lang = 'fr-FR';
					break;
				case 'en':
					$_lang = 'en-US';
					break;
				case 'es':
					$_lang = 'es-ES';
					break;
				case 'de':
					$_lang = 'de-DE';
					break;
			}
		}

		$_siteId = str_replace(' ', '', $_siteId);
		self::logger('_text: '.$_text.' | _siteId: '.$_siteId.' | lang: '.$_lang);

		if (strpos($_siteId, ',') !== false) {
			$_uri = self::getURI(explode(',', $_siteId)[0]);
		} else {
			$_uri = self::getURI($_siteId);
		}
		$url = $_uri.'/api/text-to-speech?siteId='.$_siteId;
		if ($_lang) {
			$url .= '&language='.$_lang;
		}

		$answer = self::_request('POST', $url, $_text);
		if ( isset($answer['error']) && strpos($_siteId, ',') === false) {
			self::logger('jeeRhasspy:textToSpeech error -> '.$answer['error'], 'error');
			return false;
		}

		return true;
	}

	public function speakToAsk($_siteId=null, $_options=null) #api/listen-for-command (entity) | get direct curl answer to set variable
	{
		if (!is_array($_options)) return;
		$_uri = self::getURI($_siteId);

		self::logger($_siteId.'-> ask data: '.$_options['askData']);

		$url = $_uri.'/api/listen-for-command?entity=askData&value='.$_options['askData'];
		$answer = self::_request('POST', $url);
		if ( isset($answer['error']) ) {
			self::logger('jeeRhasspy:speakToAsk error -> '.$answer['error'], 'error');
			return false;
		}

		//Intent answer:
		$payload = json_decode($answer['result'], true);
		self::logger($payload);

		$data = explode('::', $_options['askData']);
        $answerEntity = $data[0];
        $answerVariable = $data[1];
        $answer = false;

		$intentName = $payload['intent']['name'];
		if ($intentName != '') {
            if (isset($payload['entities'])) {
            	foreach ($payload['entities'] as $entity) {
                    if ($entity['entity'] == $answerEntity) {
                        $answer = $entity['value'];
                        break;
                    }
                }
                if ($answer) {
                	scenario::setData($answerVariable, $answer);
                    self::logger('Ask answer received, set answer variable: '.$answer);
                }
            }
		}
		if (!$answer) {
			scenario::setData($answerVariable, '--No Answer--');
			self::logger('Ask answer not received');
		}

		return true;
	}

	public function setLEDs($_state=1, $_siteId=null) #/api/mqtt/hermes/ (topic)
	{
		if (is_null($_siteId) || $_siteId == '') {
			$_siteId = config::byKey('masterSiteId', 'jeerhasspy');
		}
		$_uri = self::getURI($_siteId);
		if ($_state == 0) {
			$url = $_uri.'/api/mqtt/hermes/leds/toggleOff';
		} else {
			$url = $_uri.'/api/mqtt/hermes/leds/toggleOn';
		}
		$payload = '{"siteId":"'.$_siteId.'"}';

		$answer = self::_request('POST', $url, $payload);
		if ( isset($answer['error']) ) {
			self::logger('jeeRhasspy:setLEDs error -> '.$answer['error'], 'error');
			return false;
		}
		return true;
	}

	public function setVolume($_level=100, $_siteId=null) #/api/set-volume (siteId | level)
	{
		if (is_null($_siteId) || $_siteId == '') {
			$_siteId = config::byKey('masterSiteId', 'jeerhasspy');
		}
		if ($_level > 100) $_level = 100;
		if ($_level < 0) $_level = 0;
		$_level = round($_level / 100, 2);
		$_uri = self::getURI($_siteId);
		$url = $_uri.'/api/set-volume?siteId='.$_siteId;

		self::logger('jeeRhasspy:setVolume  -> '.$url);

		$answer = self::_request('POST', $url, $_level);
		if ( isset($answer['error']) ) {
			self::logger('jeeRhasspy:setVolume error -> '.$answer['error'], 'error');
			return false;
		}
		$eqLogic = eqLogic::byLogicalId('TTS-'.$_siteId, 'jeerhasspy');
		$cmd = $eqLogic->getCmd(null, 'volume');
		if (is_object($cmd)) $cmd->event($_level * 100);
		return true;
	}

	public function repeatTTS($_siteId=null) #/api/text-to-speech?repeat=true&siteId= (siteId)
	{
		if (is_null($_siteId) || $_siteId == '') {
			$_siteId = config::byKey('masterSiteId', 'jeerhasspy');
		}
		$_uri = self::getURI($_siteId);
		$url = $_uri.'/api/text-to-speech?repeat=true&siteId='.$_siteId;

		self::logger('jeeRhasspy:repeatTTS  -> '.$url);

		$answer = self::_request('POST', $url, $_level);
		if ( isset($answer['error']) ) {
			self::logger('jeeRhasspy:repeatTTS error -> '.$answer['error'], 'error');
			return false;
		}
		return true;
	}

	public function playFinished($_siteId=null) #/api/mqtt/hermes/ (topic)
	{
		if (is_null($_siteId) || $_siteId == '') {
			$_siteId = config::byKey('masterSiteId', 'jeerhasspy');
		}
		$_uri = self::getURI($_siteId);

		$url = $_uri.'/api/mqtt/hermes/audioServer/'.$_siteId.'/playFinished';
		$payload = '{"siteId":"'.$_siteId.'"}';

		self::logger('jeeRhasspy:playFinished  -> '.$_siteId);

		$answer = self::_request('POST', $url, $payload);
		if ( isset($answer['error']) ) {
			self::logger('jeeRhasspy:playFinished error -> '.$answer['error'], 'error');
			return false;
		}
		return true;
	}


	/* * ***************************Create Jeedom object, eqlogics, commands********************************* */
	static function createIntentObject()
	{
		$obj = jeeObject::byId(config::byKey('intentObjectId', 'jeerhasspy'));
		if (!is_object($obj)) {
			$obj = jeeObject::byName('Rhasspy-Intents');
		}
		if (!is_object($obj)) {
			$obj = new jeeObject();
			$obj->setName('Rhasspy-Intents');
			$obj->setIsVisible(0);
			$obj->setDisplay('icon', '<i class="fas fa-microphone"></i>');
			$obj->save();
		}
		config::save('intentObjectId', $obj->getId(), 'jeerhasspy');
		RhasspyUtils::logger('Created object: Rhasspy-Intents');
	}

	static function getIntentObject()
	{
		$obj = jeeObject::byId(config::byKey('intentObjectId', 'jeerhasspy'));
		if (!is_object($obj)) {
			self::createIntentObject();
			$obj = jeeObject::byId(config::byKey('intentObjectId', 'jeerhasspy'));
		}
		return $obj;
	}

	public function createIntentEqlogic($_intentsNames='', $_cleanIntents="0")
	{
		self::logger(json_encode($_intentsNames));
		$filterIntents = config::byKey('filterJeedomIntents', 'jeerhasspy');
		self::logger('filterIntents: '.$filterIntents.' clean: '.$_cleanIntents);

		if ($_intentsNames == '') return false;
		if (!is_array($_intentsNames)) return false;

		if ($_cleanIntents == "1") {
		  self::logger('cleaning intents');
		  $eqLogics = eqLogic::byType('jeerhasspy');
		  foreach($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') != 'intent') continue;
			$name = $eqLogic->getLogicalId();
			if (!in_array($name, $_intentsNames)) {
			  $eqLogic->remove();
			}
		  }
		}

		$_parentObjectId = self::getIntentObject()->getId();
		foreach($_intentsNames as $intentName) {
			//filter jeedom intent ?
			if ($filterIntents == '1') {
				if (substr(strtolower($intentName), -6) != 'jeedom') continue;
			}

			$eqLogic = eqLogic::byLogicalId($intentName, 'jeerhasspy');
			if (!is_object($eqLogic))
			{
				self::logger('creating eqlogic '.$intentName);
				$eqLogic = new jeerhasspy();
				$eqLogic->setEqType_name('jeerhasspy');
				$eqLogic->setLogicalId($intentName);
				$eqLogic->setName($intentName);
				$eqLogic->setIsVisible(0);
				$eqLogic->setIsEnable(1);
				$eqLogic->setObject_id($_parentObjectId);
				$eqLogic->setConfiguration('type', 'intent');
			} elseif ($eqLogic->getObject_id() != $_parentObjectId) {
				$eqLogic->setObject_id($_parentObjectId);
			}
			$eqLogic->setIsVisible(0);
			$eqLogic->save();
		}
	}

	public function createDeviceEqlogic($_deviceName='', $_type='masterDevice',  $_fullUrl=null)
	{
		self::logger($_deviceName.' | '.$_type.' | '.$_fullUrl);
		if ($_deviceName == '') return false;

		$_parentObjectId = self::getIntentObject()->getId();

		if ($_type == 'masterDevice') {
			$eqLogics = eqLogic::byType('jeerhasspy');
			$eqMaster = null;
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getConfiguration('type') != 'masterDevice') continue;
				$eqMaster = $eqLogic;
			}
			//only one master:
			if ($eqMaster) {
				$eqMaster->setLogicalId('TTS-'.$_deviceName);
				if (substr($eqMaster->getName(), 0, 4 ) === 'TTS-') {
					$eqMaster->setName('TTS-'.$_deviceName);
				}
			} else {
				$eqMaster = new jeerhasspy();
				$eqMaster->setEqType_name('jeerhasspy');
				$eqMaster->setLogicalId('TTS-'.$_deviceName);
				$eqMaster->setName('TTS-'.$_deviceName);
				$eqMaster->setIsVisible(1 );
				$eqMaster->setIsEnable(1);
				$eqMaster->setObject_id($_parentObjectId);
			}
			$eqMaster->setConfiguration('type', $_type);
			$eqMaster->save();
			config::save('masterSiteId', $_deviceName, 'jeerhasspy');
		}

		if ($_type == 'satDevice') {
			$eqLogic = eqLogic::byLogicalId('TTS-'.$_deviceName, 'jeerhasspy');
			if (!is_object($eqLogic)) {
				$eqLogic = new jeerhasspy();
				$eqLogic->setEqType_name('jeerhasspy');
				$eqLogic->setLogicalId('TTS-'.$_deviceName);
				$eqLogic->setName('TTS-'.$_deviceName);
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setObject_id($_parentObjectId);
			}
			$eqLogic->setConfiguration('type', $_type);
			$eqLogic->setConfiguration('addr', $_fullUrl);
			$eqLogic->save();
		}

		//Create commands:
		$eqLogic = eqLogic::byLogicalId('TTS-'.$_deviceName, 'jeerhasspy');

		//dynamicString cmd:
		$speakCmd = $eqLogic->getCmd(null, 'dynspeak');
		if (!is_object($speakCmd)) {
			$speakCmd = new jeerhasspyCmd();
			$speakCmd->setName('dynamic Speak');
			$speakCmd->setIsVisible(1);
			$speakCmd->setOrder(0);
		}
		if ($_type == 'satDevice') {
			$speakCmd->setDisplay('title_disable', 1);
		} else {
			$speakCmd->setDisplay('title_placeholder', 'siteId:lang');
		}
		$speakCmd->setDisplay('message_placeholder', 'TTS dynamic');
		$speakCmd->setEqLogic_id($eqLogic->getId());
		$speakCmd->setLogicalId('dynspeak');
		$speakCmd->setType('action');
		$speakCmd->setSubType('message');
		$speakCmd->setIsVisible(0);
		$speakCmd->save();

		//speak cmd:
		$speakCmd = $eqLogic->getCmd(null, 'speak');
		if (!is_object($speakCmd)) {
			$speakCmd = new jeerhasspyCmd();
			$speakCmd->setName('Speak');
			$speakCmd->setIsVisible(1);
			$speakCmd->setOrder(1);
		}
		if ($_type == 'satDevice') {
			$speakCmd->setDisplay('title_disable', 1);
		} else {
			$speakCmd->setDisplay('title_placeholder', 'siteId:lang');
		}
		$speakCmd->setDisplay('message_placeholder', 'TTS text');
		$speakCmd->setEqLogic_id($eqLogic->getId());
		$speakCmd->setLogicalId('speak');
		$speakCmd->setType('action');
		$speakCmd->setSubType('message');
		$speakCmd->save();

		//ask cmd:
		$askCmd = $eqLogic->getCmd(null, 'ask');
		if (!is_object($askCmd)) {
			$askCmd = new jeerhasspyCmd();
			$askCmd->setName('Ask');
			$askCmd->setIsVisible(1);
			$askCmd->setOrder(2);
		}
		$askCmd->setEqLogic_id($eqLogic->getId());
		$askCmd->setLogicalId('ask');
		$askCmd->setType('action');
		$askCmd->setSubType('message');
		$askCmd->setDisplay('title_placeholder', 'Intent');
		$askCmd->setDisplay('message_placeholder', 'Question');
		$askCmd->setIsVisible(0);
		$askCmd->save();

		//LEDs on:
		$ledOnCmd = $eqLogic->getCmd(null, 'ledOn');
		if (!is_object($ledOnCmd)) {
			$ledOnCmd = new jeerhasspyCmd();
			$ledOnCmd->setName('ledOn');
			$ledOnCmd->setIsVisible(1);
			$ledOnCmd->setOrder(3);
		}
		$ledOnCmd->setEqLogic_id($eqLogic->getId());
		$ledOnCmd->setLogicalId('ledOn');
		$ledOnCmd->setType('action');
		$ledOnCmd->setSubType('other');
		$ledOnCmd->save();

		//LEDs off:
		$ledOffCmd = $eqLogic->getCmd(null, 'ledOff');
		if (!is_object($ledOffCmd)) {
			$ledOffCmd = new jeerhasspyCmd();
			$ledOffCmd->setName('ledOff');
			$ledOffCmd->setIsVisible(1);
			$ledOffCmd->setOrder(4);
		}
		$ledOffCmd->setEqLogic_id($eqLogic->getId());
		$ledOffCmd->setLogicalId('ledOff');
		$ledOffCmd->setType('action');
		$ledOffCmd->setSubType('other');
		$ledOffCmd->save();

		//Volume info
		$volCmd = $eqLogic->getCmd(null, 'volume');
		if (!is_object($volCmd)) {
			$volCmd = new jeerhasspyCmd();
			$volCmd->setName('volume');
			$volCmd->setIsVisible(1);
			$volCmd->setIsHistorized(0);
			$volCmd->setTemplate('dashboard', 'core::horizontal');
			$volCmd->setTemplate('mobile', 'core::horizontal');
			$volCmd->setOrder(6);
		}
		$volCmd->setEqLogic_id($eqLogic->getId());
		$volCmd->setLogicalId('volume');
		$volCmd->setType('info');
		$volCmd->setSubType('numeric');
		$volCmd->setConfiguration('minValue', 0);
        $volCmd->setConfiguration('maxValue', 100);
		$volCmd->save();

		//setVolume:
		$setVolCmd = $eqLogic->getCmd(null, 'setvol');
		if (!is_object($setVolCmd)) {
			$setVolCmd = new jeerhasspyCmd();
			$setVolCmd->setName('setVolume');
			$setVolCmd->setIsVisible(1);
			$setVolCmd->setOrder(5);
		}
		$setVolCmd->setEqLogic_id($eqLogic->getId());
		$setVolCmd->setLogicalId('setvol');
		$setVolCmd->setType('action');
		$setVolCmd->setSubType('slider');
		$setVolCmd->setConfiguration('minValue', 0);
        $setVolCmd->setConfiguration('maxValue', 100);
        $setVolCmd->setConfiguration('infoId', $volCmd->getId());
		$setVolCmd->save();

		//repeatTTS:
		$repeatCmd = $eqLogic->getCmd(null, 'repeatTTS');
		if (!is_object($repeatCmd)) {
			$repeatCmd = new jeerhasspyCmd();
			$repeatCmd->setName('repeatTTS');
			$repeatCmd->setIsVisible(1);
			$repeatCmd->setOrder(7);
		}
		$repeatCmd->setEqLogic_id($eqLogic->getId());
		$repeatCmd->setLogicalId('repeatTTS');
		$repeatCmd->setType('action');
		$repeatCmd->setSubType('other');
		$repeatCmd->save();
	}

	public function addSatellite($_adrss) #called from ajax
	{
		self::logger($_adrss);

		//check url:
		$url = $_adrss.'/api/version';
		$answer = self::_request('GET', $url);
		if (isset($answer['error'])) {
			$answer['error'] = __('Impossible de se connecter à ce satellite.', __FILE__);
			return $answer;
		}
		self::logger('version: '.$answer['result']);
		if (version_compare($answer['result'], '2.5.0') < 0) {
			$answer['error'] = __('Version de Rhasspy non supportée. jeeRhasspy nécessite Rhasspy v2.5 ou plus.', __FILE__);
			return $answer;
		}

		//get siteId:
		$url = $_adrss.'/api/profile';
		$profile = self::_request('GET', $url);
		self::logger('profile: '.$profile['result']);
		$profile = json_decode($profile['result'], true);
		if (isset($profile['mqtt']['site_id'])) {
			$_siteId = explode(',', $profile['mqtt']['site_id'])[0];
		} else {
			$answer['error'] = __('Impossible de récupérer le siteId de ce satellite.', __FILE__);
			return $answer;
		}

		self::createDeviceEqlogic($_siteId, 'satDevice', $_adrss);
	}

	/* * **********************************Modify Rhasspy user profile**************************************** */
	public function configureRhasspyProfile($_siteId, $_url, $_configRemote=True, $_configWake=False) #called from ajax
	{
		self::logger($_siteId.' _configRemote: '.$_configRemote.' _configWake: '.$_configWake);
		self::logger('_url: '.$_url);
		$_uri = self::getURI($_siteId);
		$url = $_uri.'/api/profile?layers=profile';
		$profile = self::_request('GET', $url);
		if ( isset($answer['error']) ) {
			$answer['error'] = __('Impossible de se connecter à ce Rhasspy.', __FILE__);
			return $answer;
		}
		$profile = json_decode($profile['result'], true);

		//change settings:
		$_newProfile = $profile;

		if ($_configRemote) {
			$_newProfile['handle']['system'] = 'remote';
			$_newProfile['handle']['remote']['url'] = $_url;
			$url = $_uri.'/api/profile';
			$result = self::_request('POST', $url, json_encode($_newProfile, JSON_UNESCAPED_SLASHES));

			//check applied settings
			$url = $_uri.'/api/profile?layers=profile';
			$profile = self::_request('GET', $url);
			$profile = json_decode($profile['result'], true);
			if ($profile['handle']['remote']['url'] != $_url) {
				$answer['error'] = __("Impossible d'appliquer les paramètres à ce Rhasspy.", __FILE__);
				return $answer;
			}
		}

		if ($_configWake) {
			$_newProfile = $profile;
			$_newProfile['webhooks']['awake'][0] = $_url;
			$url = $_uri.'/api/profile';
			$result = self::_request('POST', $url, json_encode($_newProfile, JSON_UNESCAPED_SLASHES));

			//check applied settings
			$url = $_uri.'/api/profile?layers=profile';
			$profile = self::_request('GET', $url);
			$profile = json_decode($profile['result'], true);
			if ($profile['webhooks']['awake'][0] != $_url) {
				$answer['error'] = __("Impossible d'appliquer les paramètres à ce Rhasspy.", __FILE__);
				return $answer;
			}
		}

		self::_request('POST', $_uri.'/api/restart', 'configureRhasspyProfile');
	}

	//CALLING FUNCTIONS===================================================
	protected function _request($method, $url, $post=null)
	{
		self::logger($method.' | '.$url.' | '.$post);
		//init curl handle if necessary:
		if (!isset(self::$_curlHdl))
		{
			self::$_curlHdl = curl_init();
			curl_setopt(self::$_curlHdl, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt(self::$_curlHdl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(self::$_curlHdl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt(self::$_curlHdl, CURLOPT_HTTPHEADER, array(
																	'content-type: application/json'
																	));
		}

		curl_setopt(self::$_curlHdl, CURLOPT_URL, $url);
		curl_setopt(self::$_curlHdl, CURLOPT_CUSTOMREQUEST, $method);

		//is POST:
		curl_setopt(self::$_curlHdl, CURLOPT_POSTFIELDS, '');
		curl_setopt(self::$_curlHdl, CURLOPT_POST, 0);
		if (isset($post)) {
			curl_setopt(self::$_curlHdl, CURLOPT_POSTFIELDS, $post);
		}

		//send request:
		$response = curl_exec(self::$_curlHdl);
		$httpCode = curl_getinfo(self::$_curlHdl, CURLINFO_HTTP_CODE);
		if ($httpCode == 500) {
			return array('result'=>null, 'error'=>'500');
		}
		if ($httpCode != 200)
		{
			self::logger('errno: '.curl_errno(self::$_curlHdl));
			return array('result'=>null, 'error'=>curl_errno(self::$_curlHdl));
		}
		return array('result'=>$response, 'error'=>null);
	}

}