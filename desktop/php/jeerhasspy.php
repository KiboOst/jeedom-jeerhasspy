<?php
  include_file('core', 'authentification', 'php');
  if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
  }

  $coreVersion = jeedom::version();
  if (substr_count($coreVersion, '.') > 1) {
    $var = explode('.', $coreVersion);
    $coreVersion = $var[0].'.'.$var[1].$var[2];
  }
  sendVarToJS('coreVersion', $coreVersion);

  $plugin = plugin::byId('jeerhasspy');
  sendVarToJS('eqType', $plugin->getId());
  $eqLogics = eqLogic::byType($plugin->getId());
  sendVarToJS('Core_noEqContextMenu', 1);

  //get intents groups for panels and autocomplete:
  $rhasspyIntents = jeerhasspy_intent::all();
  $intentGroups = array();
  $intents = array();
  $intents['None'] = array();
  array_push($intentGroups, 'None');

  foreach ($rhasspyIntents as $rhasspyIntent) {
    $group = $rhasspyIntent->getConfiguration('group');
    $intents[$group] = array();
    array_push($intentGroups, $group);
  }
  $intentGroups = array_unique($intentGroups);
  sendVarToJS('intentGroups', $intentGroups);

  foreach ($rhasspyIntents as $rhasspyIntent) {
    $group = $rhasspyIntent->getConfiguration('group');
    if ($group == null) $group = 'None';
    array_push($intents[$group], $rhasspyIntent);
  }
  $scenarios = scenario::all();

  $_rhasspyUrl = config::byKey('rhasspyAddr', 'jeerhasspy').':'.config::byKey('rhasspyPort', 'jeerhasspy');
  $_internalURL = network::getNetworkAccess('internal') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';
  $_externalURL = network::getNetworkAccess('external') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';

  //load all intents data:
  $dataIntents = jeedom::toHumanReadable(utils::o2a($rhasspyIntents));
  sendVarToJS('dataIntents', $dataIntents);

  function getIntentDisplayCard($_intent, $_plugin) {
    $opacity = ($_intent->getIsEnable()) ? '' : 'disableCard';
    $_div = '';
    $_div .= '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $_intent->getId() . '">';
    //callback scenario name tooltip:
    $_confScenario = $_intent->getScenario();
    $_confScenarioName = false;
    if (isset($_confScenario['id'])) {
      $scenario = scenario::byId($_confScenario['id']);
      if ($scenario) {
        $title = ' title="';
        $_confScenarioName = $scenario->getHumanName().' -> '.$_confScenario['action'];
        $_tags = array();
        if ($_intent->getTags('intent') == '1') array_push($_tags, 'Intent');
        if ($_intent->getTags('entities') == '1') array_push($_tags, 'Entities');
        if ($_intent->getTags('slots') == '1') array_push($_tags, 'Slots');
        if ($_intent->getTags('siteid') == '1') array_push($_tags, 'SiteId');
        if ($_intent->getTags('query') == '1') array_push($_tags, 'Query');
        if ($_intent->getTags('confidence') == '1') array_push($_tags, 'Confidence');
        if ($_intent->getTags('wakeword') == '1') array_push($_tags, 'Wakeword');
        if (count($_tags) > 0) {
          $_confScenarioName .= '<br>';
          $_confScenarioName .= implode(' | ', $_tags);
        }
        $_confScenarioName = $title.$_confScenarioName.'"';
      }
    }

    $_div .= '<img src="' . $_plugin->getPathImgIcon() . '"/>';

    $_div .= '<span class="name">';

    if (!$_confScenarioName) {
      $_div .= '<span class="displayTableRight label label-warning cursor">Callback &nbsp;&nbsp;&nbsp; <i class="fas fa-times" style="color:var(--linkHoverLight-color)!important;"></i><br></span>';
    } else {
      $_div .= '<span class="displayTableRight label label-success cursor"' . $_confScenarioName .'>Callback &nbsp;&nbsp;&nbsp; <i class="fas fa-check" style="color:var(--linkHoverLight-color)!important;"></i><br></span>';
    }


    $_div .= $_intent->getName().'</span>';

    $_div .= '</div>';
    return $_div;
  }
?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration du plugin}}</span>
      </div>
      <div id="bt_loadAssistant" class="cursor eqLogicAction logoSecondary">
        <i class="fab fa-cloudsmith"></i>
        <br>
        <span>{{Importer l'Assistant}}</span>
      </div>
      <div id="bt_addsatellite" class="cursor eqLogicAction logoSecondary">
        <i class="fas fa-microphone-alt"></i>
        <br>
        <span>{{Ajouter un satellite}}</span>
      </div>
      <div id="bt_showIntentsSummary" class="cursor eqLogicAction logoSecondary">
        <i class="fas fa-list"></i>
        <span class="txtColor"><center>{{Vue d'ensemble}}</center></span>
      </div>
      <div id="bt_deleteIntents" class="cursor eqLogicAction warning">
        <i class="far fa-trash-alt"></i>
        <br>
        <span>{{Supprimer les intentions}}</span>
      </div>
    </div>
    <br>
    <div>
      <?php
          if (config::byKey('rhasspyAddr', 'jeerhasspy') == '') {
            echo '<label class="warning">{{Vous n\'avez pas renseigné l\'adresse de Rhasspy (Configuration).}}</label>';
          } else {
            if (config::byKey('assistantVersion', 'jeerhasspy') == '') {
              echo '<label class="info">{{Veuillez importer votre assistant Rhasspy.}}</label><br>';
            } else {?>
              <div class="panel-group">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h3 class="panel-title">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="" aria-expanded="false" href="#assistantPanel">
                        <i class="fas fa-microphone-alt"></i> {{Assistant}}
                      </a>
                    </h3>
                  </div>
                  <div id="assistantPanel" class="panel-collapse collapse">
                    <div class="panel-body">
                      <form class="form-horizontal">
                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{Importé le}}</label>
                          <div class="col-sm-2">
                              <input type="text" class="form-control" readonly value="<?php echo config::byKey('assistantDate', 'jeerhasspy'); ?>"/>
                          </div>
                          <div class="col-sm-6">
                          <span></span>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{Adresse (maître)}}</label>
                          <div class="col-sm-2">
                              <input type="text" class="form-control" readonly value="<?php echo $_rhasspyUrl; ?>"/>
                          </div>

                          <label class="col-sm-1 control-label">{{Version}}</label>
                          <div class="col-sm-2">
                              <input type="text" class="form-control" readonly value="<?php echo config::byKey('assistantVersion', 'jeerhasspy'); ?>"/>
                          </div>

                          <label class="col-sm-1 control-label">{{Langue}}</label>
                          <div class="col-sm-2">
                              <input type="text" class="form-control" readonly value="<?php echo config::byKey('defaultLang', 'jeerhasspy'); ?>"/>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{URL interne}}
                            <sup><i class="fas fa-question-circle" title="{{URL interne (Jeedom et Rhasspy sur le même réseau) à copier dans Rhasspy, onglet Settings.}}"></i></sup>
                          </label>
                          <div class="col-sm-8 callback">
                            <input type="text" class="form-control" data-urlType="url_int" readonly value="<?php echo $_internalURL; ?>"/>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{URL externe}}
                            <sup><i class="fas fa-question-circle" title="{{URL externe (Jeedom et Rhasspy sur deux réseaux différents) à copier dans Rhasspy, onglet Settings.}}"></i></sup>
                          </label>
                          <div class="col-sm-8 callback">
                            <input type="text" class="form-control" data-urlType="url_ext" readonly value="<?php echo $_externalURL; ?>"/>
                          </div>
                        </div>

                      </form>
                    </div>
                  </div>
                </div>
              </div>
            <?php }
            }
      ?>
    </div>

    <div id="devicesPanel" class="panel-group">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="" aria-expanded="false" href="#devicesContainer">
              <i class="fas fa-egg"></i> {{Devices}}
            </a>
          </h3>
        </div>
        <div id="devicesContainer" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="eqLogicThumbnailContainer">
              <?php
                foreach ($eqLogics as $eqLogic) {
                  if ($eqLogic->getConfiguration('type') != 'masterDevice') continue;
                  $siteId = str_replace('TTS-', '', $eqLogic->getLogicalId());
                  $icon = '<i class="fas fa-microphone" style="font-size:36px;margin-bottom:5px;"></i><br>Master<br>';
                  $siteUrl = $_rhasspyUrl;

                  $card = '';
                  $card .= '<div class="jeeRhasspyDeviceCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-site_id="' . $siteId . '" data-site_url="' . $siteUrl . '" style="min-height:123px;min-width:140px;">';
                  $card .= $icon;
                  $card .= '<strong class="name">' . $eqLogic->getName() . '</strong>';

                  $card .= '<br><center>';
                  $card .= '<div class="input-group">';
                    $card .= '<a class="bt_configure warning" style="padding: 0 6px;" title="{{Configurer le profile Rhasspy.}}"><i class="fas fa-users-cog"></i></a>';
                    $card .= '<a class="bt_speakTest info" style="padding: 0 6px;" title="{{Test TTS sur le Rhasspy maître.}}"><i class="fas fa-headphones"></i></i></i></a>';
                    $card .= '<a class="bt_goToDevice roundedRight success" style="padding: 0 6px;" title="{{Ouvrir l\'interface du Rhasspy maître.}}"><i class="fas fa-server"></i></a>';
                  $card .= '</div>';
                  $card .= '</center></div>';
                  echo $card;
                }
                foreach ($eqLogics as $eqLogic) {
                  if ($eqLogic->getConfiguration('type') != 'satDevice') continue;
                  $siteId = str_replace('TTS-', '', $eqLogic->getName());
                  $icon = '<i class="fas fa-microphone-alt" style="font-size:36px;margin-bottom:5px;"></i><br>Satellite<br>';
                  $siteUrl = $eqLogic->getConfiguration('addr');

                  $card = '';
                  $card .= '<div class="jeeRhasspyDeviceCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-site_id="' . $siteId . '" data-site_url="' . $siteUrl . '" style="min-height:123px;min-width:140px;">';
                  $card .= $icon;
                  $card .= '<strong class="name">' . $eqLogic->getName() . '</strong>';

                  $card .= '<br><center>';
                  $card .= '<div class="input-group">';
                    $card .= '<a class="bt_deleteSat roundedLeft danger" style="padding: 0 6px;" title="{{Supprimer ce satellite.}}"><i class="fas fa-minus-circle"></i></a>';
                    $card .= '<a class="bt_configure warning" style="padding: 0 6px;" title="{{Configurer le profile Rhasspy de ce satellite.}}"><i class="fas fa-users-cog"></i></a>';
                    $card .= '<a class="bt_speakTest info" style="padding: 0 6px;" title="{{Test TTS sur ce satellite.}}"><i class="fas fa-headphones"></i></i></i></a>';
                    $card .= '<a class="bt_goToDevice roundedRight success" style="padding: 0 6px;" title="{{Ouvrir l\'interface de ce satellite.}}"><i class="fas fa-server"></i></a>';
                  $card .= '</div>';
                  $card .= '</center></div>';
                  echo $card;
                }
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <legend><i class="fas fa-graduation-cap"></i> {{Intentions}}</legend>
    <div class="input-group" style="margin-bottom:5px;">
      <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="input_searchEqlogic"/>
      <div class="input-group-btn">
        <a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i>
        </a><a class="btn" id="bt_openAll"><i class="fas fa-folder-open"></i>
        </a><a class="btn" id="bt_closeAll"><i class="fas fa-folder"></i>
        </a><a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
      </div>
    </div>
    <div id="intentsContainer">
      <div id="intentsPanels" class="panel-group" id="accordionIntent">
      <?php
        $i = 0;
        $div = '';
        foreach ($intentGroups as $group) {
          if ($group == '') continue;
          $c = count($intents[$group]);
          if ($c == 0) continue;
          $groupName = $group;
          if ($group == 'None') $groupName = "{{Aucun}}";
          $div .= '<div class="panel panel-default">';
          $div .= '<div class="panel-heading">';
          $div .= '<h3 class="panel-title">';
          $div .= '<a class="accordion-toggle" data-toggle="collapse" data-parent="" aria-expanded="false" href="#config_' . $i . '">' . $groupName . ' - ';
          $div .= $c. ($c > 1 ? ' intentions' : ' intention').'</a>';
          $div .= '</h3>';
          $div .= '</div>';
          $div .= '<div id="config_' . $i . '" class="panel-collapse collapse">';
          $div .= '<div class="panel-body">';
            $div .= '<div class="eqLogicThumbnailContainer">';
            foreach ($intents[$group] as $intent) {
              $_div = getIntentDisplayCard($intent, $plugin);
              $div .= $_div;
            }
            $div .= '</div>';
          $div .= '</div>';
          $div .= '</div>';
          $div .= '</div>';
          $i += 1;
        }
        $div .= '</div>';
        echo $div;
      ?>
      </div>
    </div>

    <!-- Intent page -->
    <div id="intentPage" class="col-xs-12 eqLogic" style="display: none;">
      <div class="input-group pull-right" style="display:inline-flex">
        <span class="input-group-btn">
          <a id="bt_intentSave" class="btn btn-sm btn-success eqLogicAction roundedLeft"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
          </a><a id="bt_intentRemove" class="btn btn-danger btn-sm eqLogicAction roundedRight"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
        </span>
      </div>

      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Intentions}}</a></li>
      </ul>

      <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">

      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br/>
        <form class="form-horizontal">
          <fieldset>
            <div id="intentCommon">
              <legend><i class="fas fa-microphone"></i> {{Intention}}</legend>
              <div class="form-group">
                  <label class="col-sm-2 control-label">{{Nom}}</label>
                  <div class="col-sm-3">
                      <input type="text" class="intentAttr form-control" id="intentId" data-l1key="id" style="display:none;" />
                      <input type="text" class="intentAttr form-control" id="intentName" data-l1key="name"  readonly/>
                  </div>
                  <div class="col-sm-3">
                    <label class="checkbox-inline"><input type="checkbox" class="intentAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                  </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" >{{Groupe}}</label>
                <div class="col-sm-3">
                  <input class="form-control intentAttr" data-l1key="configuration" data-l2key="group" type="text" placeholder="{{Groupe de l'Intention}}"/>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" >{{Interaction}}
                  <sup><i class="fas fa-question-circle" title="{{Utilise le moteur d'interaction de Jeedom au lieu d'un scénario.}}"></i></sup>
                </label>
                <div class="col-sm-3">
                  <input type="checkbox" class="intentAttr" data-l1key="isInteract"/>
                </div>
              </div>
            </div>

            <div id="intentScenario">
              <legend><i class="fas fa-cogs"></i> {{Callback Scenario}}</legend>
              <div class="form-group">
                  <label class="col-sm-2 control-label">{{Scenario}}</label>
                  <div class="col-sm-4">
                      <select class="intentAttr form-control" data-l1key="scenario" data-l2key="id">
                          <option value="-1">{{None}}</option>
                          <?php
                          foreach ($scenarios as $scenario) {
                            echo '<option value="'.$scenario->getId().'">'.$scenario->getHumanName().'</option>';
                          }
                          ?>
                      </select>
                  </div>

                  <label class="col-sm-1 control-label">{{Action}}</label>
                  <div class="col-sm-2">
                      <select class="intentAttr form-control" data-l1key="scenario" data-l2key="action">
                          <option value="start">{{Start}}</option>
                          <option value="startsync">{{Start (sync)}}</option>
                          <option value="stop">{{Stop}}</option>
                          <option value="activate">{{Activer}}</option>
                          <option value="deactivate">{{Désactiver}}</option>
                          <option value="resetRepeatIfStatus">{{Remise à zero des SI}}</option>
                      </select>
                  </div>

                  <div class="col-sm-2">
                      <a class="btn btn-sm btn-success bt_openScenario" target="_blank" title="{{Aller sur la page du scénario.}}"><i class="fa fa-arrow-circle-right"></i> {{Scénario}}</a>
                      <a class="btn btn-sm btn-success bt_logScenario" target="_blank" title="{{Ouvrir le log du scénario.}}"><i class="far fa-file-alt"></i> {{Log}}</a>
                  </div>
              </div>

              <div class="form-group">
                  <label class="col-sm-2 control-label">{{Tags Rhasspy}}
                    <sup><i class="fas fa-question-circle" title="{{Sélectionnez les informations de l'Intention passées sous forme de tag.}}"></i></sup>
                  </label>

                  <div class="col-sm-4">
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="intent"> #intent#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="entities"> #entities#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="slots"> #slots#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="siteid"> #siteId#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="query"> #query#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="confidence"> #confidence#
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="callbackScenarioTags">
                        <input type="checkbox" class="intentAttr" data-l1key="tags" data-l2key="wakeword"> #wakeword#
                      </div>
                    </div>
                  </div>

                  <label class="col-sm-1 control-label">{{Confidence}}
                    <sup><i class="fas fa-question-circle" title="{{Confidence minimale pour éxécuter le scénario.}}"></i></sup>
                  </label>
                  <div class="col-sm-5" style="margin-bottom: 4px;">
                      <input type="number" value="0" min="0" max="1" step="0.1" class="intentAttr input-sm" data-l1key="scenario" data-l2key="minConfidence" />
                  </div>

                  <label class="col-sm-1 control-label">{{Tags}}
                    <sup><i class="fas fa-question-circle" title="{{Ajoutez ici des tags utilisateur lors de l'éxécution du scénario (#tagName#=tagValue).}}"></i></sup>
                  </label>
                  <div class="col-sm-5">
                      <textarea class="form-control intentAttr ta_autosize"  data-l1key="tags" data-l2key="user" placeholder="#tagName#=tagValue"></textarea>
                  </div>
              </div>
            </div>
          </fieldset>
        </form>
      </div>

      </div>
    </div>

  </div>

  <div id="addSatelliteFormContainer" class="hidden">
    <i class="fa fa-exclamation-triangle warning"></i> {{Ajout d'un satellite.}}
    <br><br>
    <form id="addSatelliteForm" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-sm-3">{{Adresse}}</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="addr" placeholder="http://192.168.0.10:12101" />
            </div>
        </div>
    </form>
  </div>

  <div id="configDeviceFormContainer" class="hidden">
    <i class="fa fa-exclamation-triangle warning"></i> {{Configuration du profile Rhasspy.}}
    <br><br>
    <form id="configDeviceForm" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-sm-3">{{Adresse}}</label>
            <div class="col-sm-8">
                <select name="configUrl">
                  <option value="url_int">{{Utiliser l'url interne}}</option>
                  <option value="url_ext">{{Utiliser l'url externe}}</option>
              </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-6">{{Configurer l'event Wakeword Detected}}</label>
            <div class="col-sm-6">
                <input type="checkbox" class="form-control" name="configWakeEvent" checked/>
            </div>
        </div>
    </form>
  </div>
</div>

<?php
  include_file('desktop', 'jeerhasspy', 'js', 'jeerhasspy');
  include_file('core', 'plugin.template', 'js');
?>