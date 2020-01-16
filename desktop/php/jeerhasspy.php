<?php
  include_file('core', 'authentification', 'php');
  if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
  }

  $plugin = plugin::byId('jeerhasspy');
  sendVarToJS('eqType', $plugin->getId());
  $eqLogics = eqLogic::byType($plugin->getId());
  sendVarToJS('Core_noEqContextMenu', 1);

  //get intents groups for panels and autocomplete:
  $intentGroups = array();
  $intents = array();
  $intents['None'] = array();
  array_push($intentGroups, 'None');

  foreach ($eqLogics as $eqLogic) {
    if ($eqLogic->getConfiguration('type') != 'intent') continue;
    $group = $eqLogic->getConfiguration('group');
    $intents[$group] = array();
    array_push($intentGroups, $group);
  }
  $intentGroups = array_unique($intentGroups);
  sendVarToJS('intentGroups', $intentGroups);

  foreach ($eqLogics as $eqLogic) {
    if ($eqLogic->getConfiguration('type') != 'intent') continue;
    $group = $eqLogic->getConfiguration('group');
    if ($group == null) $group = 'None';
    array_push($intents[$group], $eqLogic);
  }

  $scenarios = scenario::all();

  $_rhasspyUrl = config::byKey('rhasspyAddr', 'jeerhasspy').':'.config::byKey('rhasspyPort', 'jeerhasspy');
  $_internalURL = network::getNetworkAccess('internal') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';
  $_externalURL = network::getNetworkAccess('external') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';

  function getIntentDisplayCard($intent, $plugin) {
    $opacity = ($intent->getIsEnable()) ? '' : 'disableCard';
    $_div = '';
    $_div .= '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $intent->getId() . '">';
    //callback scenario name tooltip:
    $_confScenario = $intent->getConfiguration('callbackScenario');
    $_confScenarioName = false;
    if (isset($_confScenario['scenario'])) {
      $scenario = scenario::byId($_confScenario['scenario']);
      if ($scenario) {
        $title = ' title="';
        //$_confScenarioName = ' title="'.$scenario->getHumanName().' -> '.$_confScenario['action'].'"';
        $_confScenarioName = $scenario->getHumanName().' -> '.$_confScenario['action'];
        $_tags = array();
        if ($_confScenario['isTagIntent'] == '1') array_push($_tags, 'Intent');
        if ($_confScenario['isTagEntities'] == '1') array_push($_tags, 'Entities');
        if ($_confScenario['isTagSlots'] == '1') array_push($_tags, 'Slots');
        if ($_confScenario['isTagSiteId'] == '1') array_push($_tags, 'SiteId');
        if ($_confScenario['isTagQuery'] == '1') array_push($_tags, 'Query');
        if ($_confScenario['isTagConfidence'] == '1') array_push($_tags, 'Confidence');
        if ($_confScenario['isTagWakeword'] == '1') array_push($_tags, 'Wakeword');
        if (count($_tags) > 0) {
          $_confScenarioName .= '<br>';
          $_confScenarioName .= implode(' | ', $_tags);
        }
        $_confScenarioName = $title.$_confScenarioName.'"';
      }
    }

    $_div .= '<img src="' . $plugin->getPathImgIcon() . '"/>';

    if (!$_confScenarioName) {
      $_div .= '<strong class="label label-warning cursor">Callback &nbsp;&nbsp;&nbsp; <i class="fas fa-times"></i><br></strong>';
    } else {
      $_div .= '<strong class="label label-success cursor"' . $_confScenarioName .'>Callback &nbsp;&nbsp;&nbsp; <i class="fas fa-check"></i><br></strong>';
    }

    $_div .= '<span class="name">';
    $_div .= $intent->getName(true, true).'</span>';

    $_div .= '</div>';
    return $_div;
  }
?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration du plugin}}</span>
      </div>
      <div id="bt_loadAssistant" class="cursor eqLogicAction">
        <i class="fab fa-cloudsmith"></i>
        <br>
        <span>{{Importer l'Assistant}}</span>
      </div>
      <div id="bt_deleteIntents" class="cursor eqLogicAction warning">
        <i class="far fa-trash-alt"></i></i>
        <br>
        <span>{{Supprimer les intentions}}</span>
      </div>
      <div id="bt_showIntentsSummary" class="cursor eqLogicAction logoSecondary">
        <i class="fas fa-list"></i>
        <span class="txtColor"><center>{{Vue d'ensemble}}</center></span>
      </div>
      <div id="bt_gotoRhasspy" class="cursor eqLogicAction logoSecondary" data-url="<?php echo $_rhasspyUrl; ?>">
        <i class="fas fa-server"></i>
        <span class="txtColor"><center>{{Rhasspy}}</center></span>
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
                          <label class="col-sm-2 control-label">{{Adresse}}</label>
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
                          <div class="col-sm-2">
                              <a id="bt_configureIntRemote" class="btn btn-sm btn-warning" target="_blank" title="{{Configurer votre profil Rhasspy.}}"><i class="fas fa-users-cog"></i> {{Configurer}}</a>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{URL externe}}
                            <sup><i class="fas fa-question-circle" title="{{URL externe (Jeedom et Rhasspy sur deux réseaux différents) à copier dans Rhasspy, onglet Settings.}}"></i></sup>
                          </label>
                          <div class="col-sm-8 callback">
                            <input type="text" class="form-control" data-urlType="url_ext" readonly value="<?php echo $_externalURL; ?>"/>
                          </div>
                          <div class="col-sm-2">
                              <a id="bt_configureExtRemote" class="btn btn-sm btn-warning" target="_blank" title="{{Configurer votre profil Rhasspy.}}"><i class="fas fa-users-cog"></i> {{Configurer}}</a>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{Wake event}}
                            <sup><i class="fas fa-question-circle" title="{{Nécessaire avec l'option Variables rhasspyWakeWord / rhasspyWakeSiteId (Configuration).}}"></i></sup>
                          </label>
                          <div class="col-sm-2">
                              <a id="bt_configureWakeEvent" class="btn btn-sm btn-warning" target="_blank" title="{{Configurer votre profil Rhasspy.}}"><i class="fas fa-users-cog"></i> {{Configurer}}</a>
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
                  if ($eqLogic->getConfiguration('type') == 'intent') continue;
                  $siteId = str_replace('TTS-', '', $eqLogic->getName());
                  echo '<div class="jeeRhasspyDeviceCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-site_id="' . $siteId . '"style="min-height:123px;">';
                  if ($eqLogic->getConfiguration('type') == 'masterDevice') {
                    echo '<i class="fas fa-microphone-alt"></i><br>Master<br>';
                  } else {
                    echo '<i class="fas fa-microphone"></i><br>Satellite<br>';
                  }
                  echo '<strong class="name">' . $eqLogic->getName() . '</strong>';
                  echo '</div>';
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
        </a><a class="btn roundedRight" id="bt_closeAll"><i class="fas fa-folder"></i></a>
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
  </div>

<div class="col-xs-12 eqLogic" style="display: none;">
  <div class="input-group pull-right" style="display:inline-flex">
    <span class="input-group-btn">
      <a class="btn btn-sm btn-success eqLogicAction roundedLeft" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
      </a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
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
          <legend><i class="fas fa-microphone"></i> {{Intention}}</legend>
          <div class="form-group">
              <label class="col-sm-2 control-label">{{Nom}}</label>
              <div class="col-sm-3">
                  <input type="text" class="eqLogicAttr form-control" id="intentId" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" id="intentName" data-l1key="name"  readonly/>
              </div>
              <div class="col-sm-3">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
              </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" >{{Groupe}}</label>
            <div class="col-sm-3">
              <input class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="group" type="text" placeholder="{{Groupe de l'Intention}}"/>
            </div>
          </div>

          <legend><i class="fas fa-cogs"></i> {{Callback Scenario}}</legend>
          <div class="form-group">
              <label class="col-sm-2 control-label">{{Scenario}}</label>
              <div class="col-sm-4">
                  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="scenario">
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
                  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="action">
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
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagIntent"> {{#intent#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagEntities"> {{#entities#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagSlots"> {{#slots#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagSiteId"> {{#siteId#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagQuery"> {{#query#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagConfidence"> {{#confidence#}}
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="callbackScenarioTags">
                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="isTagWakeword"> {{#wakeword#}}
                  </div>
                </div>
              </div>

              <label class="col-sm-1 control-label">{{Confidence}}
                <sup><i class="fas fa-question-circle" title="{{Confidence minimale pour éxécuter le scénario.}}"></i></sup>
              </label>
              <div class="col-sm-5" style="margin-bottom: 4px;">
                  <input type="number" value="0" min="0" max="1" step="0.1" class="eqLogicAttr input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="minConfidence" />
              </div>

              <label class="col-sm-1 control-label">{{Tags}}
                <sup><i class="fas fa-question-circle" title="{{Ajoutez ici des tags utilisateur lors de l'éxécution du scénario (#tagName#=tagValue).}}"></i></sup>
              </label>
              <div class="col-sm-5">
                  <textarea class="eqLogicAttr" style="height: 30px;width: 95%;" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="user_tags" placeholder="#tagName#=tagValue"></textarea>
              </div>

          </div>

      </fieldset>
    </form>
  </div>

  </div>
</div>

<?php
  include_file('desktop', 'jeerhasspy', 'js', 'jeerhasspy');
  include_file('core', 'plugin.template', 'js');
?>