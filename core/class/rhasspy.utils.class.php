<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class RhasspyUtils
{
    protected static $_curlHdl = null;
    protected static $_uri = false;

    public function init()
    {
        $Addr = config::byKey('rhasspyAddr', 'jeerhasspy');
        if (substr($Addr, 0, 4) != 'http') $Addr = 'http://'.$Addr;
        $port = config::byKey('rhasspyPort', 'jeerhasspy');
        if ($port == '') $port = '12101';
        self::$_uri = $Addr.':'.$port;
    }

    public function logger($str = '', $level = 'debug')
    {
        if (is_array($str)) $str = json_encode($str);
        $function_name = debug_backtrace(false, 2)[1]['function'];
        $class_name = debug_backtrace(false, 2)[1]['class'];
        $msg = '['.$class_name.'] <'. $function_name .'> '.$str;
        log::add('jeerhasspy', $level, $msg);
    }

    public function loadAssistant($_cleanIntents="0") {
        self::logger('cleanIntents: '.$_cleanIntents);
        self::init();

        //load assistant version, checking right url:
        config::save('assistantVersion', '0.0.0', 'jeerhasspy');
        $url = self::$_uri.'/api/version';
        $answer = self::_request('GET', $url);
        if ( isset($answer['error']) ) {
            $answer['error'] = 'Could not connect to Rhasspy!';
            return $answer;
        }
        if ( version_compare($answer['result'], '0.0.1', '>=' ) >= 0 ) {
            self::logger('version: '.$answer['result']);
            config::save('assistantVersion', $answer['result'], 'jeerhasspy');
            config::save('assistantDate', date('Y-m-d H:i:s'), 'jeerhasspy');
        } else {
            $answer['error'] = 'Invalid Rhasspy version, please check your plugin configuration.';
            return $answer;
        }

        //load profile site_id
        $url = self::$_uri.'/api/profile';
        $profile = self::_request('GET', $url);
        $profile = json_decode($profile['result'], true);
        if (isset($profile['mqtt']['site_id'])) {
            $masterName = $profile['mqtt']['site_id'];
        } else {
            $masterName = 'Rhasspy';
        }
        self::create_rhasspy_deviceEqlogic($masterName, 'masterDevice');
        //Should support future satellites:
        //self::create_rhasspy_deviceEqlogic($satName, 'satDevice');

        //load profiles, default language:
        $url = self::$_uri.'/api/profiles';
        $profiles = self::_request('GET', $url);
        $profiles = json_decode($profiles['result'], true);
        config::save('defaultLang', $profiles['default_profile'], 'jeerhasspy');

        self::create_rhasspy_intentObject();

        //load intents:
        $url = self::$_uri.'/api/intents';
        $jsonIntents = self::_request('GET', $url);
        self::logger('intents: '.$jsonIntents['result']);
        if ($jsonIntents['error'] == '500') {
            //not /api/intents yet:
            $url = self::$_uri.'/api/sentences';
            $sentences = self::_request('GET', $url);
            self::logger('sentences: '.$sentences['result']);
            $sentences = '  '.$sentences['result'];
            preg_match_all("/[\s][\s]\[[^\]]*\]/", $sentences, $matches);
            $intents = [];
            foreach($matches[0] as $match) {
                $match = trim($match);
                $match = str_replace(array('[', ']'), '', $match);
                self::logger('found intent: '.$match);
                array_push($intents, $match);
            }
        } else {
            $jsonIntents = json_decode($jsonIntents['result'], true);
            $intents = [];
            foreach($jsonIntents as $intentName => $intent) {
                self::logger('found intent: '.$intentName);
                array_push($intents, $intentName);
            }
        }
        //create intents eqlogics:
        self::create_rhasspy_intentEqlogics($intents, $_cleanIntents);
    }

    public function deleteIntents()
    {
        $eqLogics = eqLogic::byType('jeerhasspy');
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('type') != 'intent') continue;
            $eqLogic->remove();
        }
    }

    public function test($_siteId=null)
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

    public function textToSpeech($_options=null)
    {
        if (!is_array($_options)) return;
        self::init();

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

        self::logger('_text: '.$_text.' | _siteId: '.$_siteId.' | lang: '.$_lang);

        $uri = self::$_uri;

        /* rhasspy should support sending tts on satellites:
        if (isset($_siteId)) {
            $eqLogics = eqLogic::byType('jeerhasspy');
            $TTSeq = null;
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type') == 'intent') continue;
                if ($eqLogic->getName() == $_siteId) {
                    if ($eqLogic->getConfiguration('type') == 'satDevice') {
                        $uri = null;
                    }
                    //get url from device eqlogic!
                    break;
                }
            }
        }
        */

        if ($_lang) {
            $url = $uri.'/api/text-to-speech?language='.$_lang;
        } else {
            $url = $uri.'/api/text-to-speech';
        }
        $answer = self::_request('POST', $url, $_text);
        if ( isset($answer['error']) ) {
            message::add('error', 'jeeRhasspy:textToSpeech error, Could not connect to Rhasspy!');
            $answer['error'] = 'textToSpeech error, Could not connect to Rhasspy!';
            return $answer;
        }
        return true;
    }

    public function speakToAsk($_options=null, $_siteId=null)
    {
        self::init();
        if (!is_array($_options)) return;

        self::logger('ask data: '.$_options['askData']);

        $uri = self::$_uri;
        $url = $uri.'/api/listen-for-command?entity=askData&value='.$_options['askData'];
        $answer = self::_request('POST', $url, $_text);
        if ( isset($answer['error']) ) {
            message::add('error', 'jeeRhasspy:speakToAsk error, Could not connect to Rhasspy!');
            $answer['error'] = 'speakToAsk error, Could not connect to Rhasspy!';
            return $answer;
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

    public function sanitize_text($_text=null)
    {
        if (!isset($_text)) return $_text;
    }

    /* * ***************************Create Jeedom object, eqlogics, commands********************************* */
    static function create_rhasspy_intentObject()
    {
        $obj = jeeObject::byName('Rhasspy-Intents');
        if (!is_object($obj)) {
            $obj = new jeeObject();
            $obj->setName('Rhasspy-Intents');
            $obj->setIsVisible(0);
            $obj->setDisplay('icon', '<i class="fas fa-microphone"></i>');
            $obj->save();
            RhasspyUtils::logger('Created object: Rhasspy-Intents');
        }
    }

    static function get_rhasspy_intentObject()
    {
        $obj = jeeObject::byName('Rhasspy-Intents');
        if (!is_object($obj)) {
            self::create_rhasspy_intentObject();
            $obj = jeeObject::byName('Rhasspy-Intents');
        }
        $objId = $obj->getId();
        return $objId;
    }

    public function create_rhasspy_intentEqlogics($_intentsNames='', $_cleanIntents="0")
    {
        self::logger(json_encode($_intentsNames).' clean:'.$_cleanIntents);
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

        $_parentObjectId = self::get_rhasspy_intentObject();
        foreach($_intentsNames as $intentName) {
            //filter jeedom intent ?
            if (config::byKey('filterJeedomIntents', 'jeerhasspy') == '1') {
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
                $eqLogic->save();
            }
        }
    }

    public function create_rhasspy_deviceEqlogic($_deviceName='', $_type='masterDevice')
    {
        self::logger($_deviceName.' | '.$_type);
        if ($_deviceName == '') return false;

        $_parentObjectId = self::get_rhasspy_intentObject();

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
                $eqMaster->setName('TTS-'.$_deviceName);
                $eqMaster->save();
            } else {
                $eqLogic = new jeerhasspy();
                $eqLogic->setEqType_name('jeerhasspy');
                $eqLogic->setLogicalId('TTS-'.$_deviceName);
                $eqLogic->setName('TTS-'.$_deviceName);
                $eqLogic->setIsVisible(0);
                $eqLogic->setIsEnable(1);
                $eqLogic->setObject_id($_parentObjectId);
                $eqLogic->setConfiguration('type', $_type);
                $eqLogic->save();
            }
            config::save('masterSiteId', $_deviceName, 'jeerhasspy');
        }

        if ($_type == 'satDevice') {
            $eqLogics = eqLogic::byType('jeerhasspy');
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type') != 'satDevice') continue;
            }
        }

        $eqLogic = eqLogic::byLogicalId('TTS-'.$_deviceName, 'jeerhasspy');

        //dynamicString cmd:
        $speakCmd = $eqLogic->getCmd(null, 'dynspeak');
        if (!is_object($speakCmd)) {
            $speakCmd = new jeerhasspyCmd();
            $speakCmd->setName('dynamic Speak');
            $speakCmd->setIsVisible(1);
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
        $speakCmd->setOrder(0);
        $speakCmd->save();

        //speak cmd:
        $speakCmd = $eqLogic->getCmd(null, 'speak');
        if (!is_object($speakCmd)) {
            $speakCmd = new jeerhasspyCmd();
            $speakCmd->setName('Speak');
            $speakCmd->setIsVisible(1);
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
        $speakCmd->setOrder(1);
        $speakCmd->save();

        //ask cmd:
        $askCmd = $eqLogic->getCmd(null, 'ask');
        if (!is_object($askCmd)) {
            $askCmd = new jeerhasspyCmd();
            $askCmd->setName('Ask');
            $askCmd->setIsVisible(1);
        }
        $askCmd->setEqLogic_id($eqLogic->getId());
        $askCmd->setLogicalId('ask');
        $askCmd->setType('action');
        $askCmd->setSubType('message');
        $askCmd->setDisplay('title_placeholder', 'Intent');
        $askCmd->setDisplay('message_placeholder', 'Question');
        $speakCmd->setOrder(2);
        $askCmd->save();

    }

    //CALLING FUNCTIONS===================================================
    protected function _request($method, $url, $post=null)
    {
        self::logger($method.' | '.$url.' | '.$post);
        if (!isset(self::$_curlHdl))
        {
            self::$_curlHdl = curl_init();
            curl_setopt(self::$_curlHdl, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt(self::$_curlHdl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$_curlHdl, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt(self::$_curlHdl, CURLOPT_URL, $url);
        curl_setopt(self::$_curlHdl, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt(self::$_curlHdl, CURLOPT_POSTFIELDS, '');
        if (isset($post)) curl_setopt(self::$_curlHdl, CURLOPT_POSTFIELDS, $post);

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