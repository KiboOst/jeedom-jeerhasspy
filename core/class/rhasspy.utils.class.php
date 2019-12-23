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


        $url = self::$_uri.'/api/profiles';
        $profiles = self::_request('GET', $url);
        $profiles = json_decode($profiles['result'], true);
        config::save('defaultLang', $profiles['default_profile'], 'jeerhasspy');

        self::create_rhasspy_intentObject();

        $url = self::$_uri.'/api/intents';
        $jsonIntents = self::_request('GET', $url);
        self::logger('intents: '.$jsonIntents['result']);
        $jsonIntents = json_decode($jsonIntents['result'], true);
        $intents = [];
        foreach($jsonIntents as $intentName => $intent) {
            self::logger('found intent: '.$intentName);
            array_push($intents, $intentName);
        }
        //$intents = ['lightsTurnOnJeedom', 'shouldNotBeThere']; //DEBUG!!!
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

    public function textToSpeech($_options=null, $_lang=null, $_siteId=null)
    {
        self::init();
        if (!is_array($_options)) return;

        $_text = $_options['message'];
        if (is_null($_text)) {
            $_text = $_siteId.', ceci est un test.';
        }
        self::logger('_text: '.$_text.' | _siteId: '.$_siteId);

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
                $eqMaster->setLogicalId($_deviceName);
                $eqMaster->setName($_deviceName);
                $eqMaster->save();
            } else {
                $eqLogic = new jeerhasspy();
                $eqLogic->setEqType_name('jeerhasspy');
                $eqLogic->setLogicalId($_deviceName);
                $eqLogic->setName($_deviceName);
                $eqLogic->setIsVisible(0);
                $eqLogic->setIsEnable(1);
                $eqLogic->setObject_id($_parentObjectId);
                $eqLogic->setConfiguration('type', $_type);
                $eqLogic->save();
            }
        }

        if ($_type == 'satDevice') {
            $eqLogics = eqLogic::byType('jeerhasspy');
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type') != 'satDevice') continue;

            }
        }

        //speak cmd:
        $eqLogic = eqLogic::byLogicalId($_deviceName, 'jeerhasspy');
        $speakCmd = $eqLogic->getCmd(null, 'speak');
        if (!is_object($speakCmd)) {
            $speakCmd = new jeerhasspyCmd();
            $speakCmd->setName(__('Speak', __FILE__));
            $speakCmd->setIsVisible(1);
        }
        $speakCmd->setEqLogic_id($eqLogic->getId());
        $speakCmd->setLogicalId('speak');
        $speakCmd->setType('action');
        $speakCmd->setSubType('message');
        $speakCmd->save();

        //dynamicString cmd:
        $speakCmd = $eqLogic->getCmd(null, 'dynspeak');
        if (!is_object($speakCmd)) {
            $speakCmd = new jeerhasspyCmd();
            $speakCmd->setName(__('dynamic Speak', __FILE__));
            $speakCmd->setIsVisible(1);
        }
        $speakCmd->setEqLogic_id($eqLogic->getId());
        $speakCmd->setLogicalId('dynspeak');
        $speakCmd->setType('action');
        $speakCmd->setSubType('message');
        $speakCmd->save();

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
        if (curl_errno(self::$_curlHdl))
        {
            self::logger('errno: '.curl_errno(self::$_curlHdl));
            return array('result'=>null, 'error'=>curl_errno(self::$_curlHdl));
        }
        return array('result'=>$response, 'error'=>null);
    }

}