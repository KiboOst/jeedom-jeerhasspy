<?php
  include_file('core', 'authentification', 'php');
  if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
  }

  $plugin = plugin::byId('jeerhasspy');
  sendVarToJS('eqType', $plugin->getId());
  $eqLogics = eqLogic::byType($plugin->getId());
  $scenarios = scenario::all();

  $_rhasspyUrl = config::byKey('rhasspyAddr', 'jeerhasspy').':'.config::byKey('rhasspyPort', 'jeerhasspy');
  $_internalURL = network::getNetworkAccess('internal') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';
  $_externalURL = network::getNetworkAccess('external') . '/core/api/jeeApi.php?plugin=jeerhasspy&apikey=' . jeedom::getApiKey($plugin->getId()) . '&plugin=jeerhasspy&type=jeerhasspy';
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
                          <div class="col-sm-2">
                              <a href="<?php echo $_rhasspyUrl; ?>" class="btn btn-sm btn-success" target="_blank" title="{{Ouvrir l'interface de Rhasspy.}}"><i class="fa fa-arrow-circle-right"></i> {{Rhasspy}}</a>
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
                            <input type="text" class="form-control" data-l1key="name" readonly value="<?php echo $_internalURL; ?>"/>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label">{{URL externe}}
                            <sup><i class="fas fa-question-circle" title="{{URL externe (Jeedom et Rhasspy sur deux réseaux différents) à copier dans Rhasspy, onglet Settings.}}"></i></sup>
                          </label>
                          <div class="col-sm-8 callback">
                            <input type="text" class="form-control" data-l1key="name" readonly value="<?php echo $_externalURL; ?>"/>
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

    <div class="panel-group">
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
                  $siteId = $eqLogic->getName();
                  $siteId = str_replace('TTS-', '', $siteId);
                  echo '<div class="jeeRhasspyDeviceCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-site_id="' . $siteId . '"style="min-height:123px;">';
                  if ($eqLogic->getConfiguration('type') == 'masterDevice') {
                    echo '<i class="fas fa-microphone-alt"></i><br>Master<br>';
                  } else {
                    echo '<i class="fas fa-microphone"></i><br>Satellite<br>';
                  }
                  echo '<span class="name">' . $eqLogic->getName() . '</span>';
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
      <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
      <div class="input-group-btn">
        <a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i> </a>
      </div>
    </div>
    <div id="intentsContainer" class="eqLogicThumbnailContainer">
      <?php
        foreach ($eqLogics as $eqLogic) {
          if ($eqLogic->getConfiguration('type') != 'intent') continue;
          $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
          echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
          echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
          echo '<br>';
          $scenario = $eqLogic->getConfiguration('callbackScenario');
          echo '<span class="name">';
          if (!isset($scenario['scenario'])) {
            echo '<sub style="font-size:22px" class="warning">•</sub>';
          }
          echo $eqLogic->getName(true, true).'</span>';
          echo '</div>';
        }
      ?>
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
              <label class="col-sm-1 control-label">{{Nom}}</label>
              <div class="col-sm-3">
                  <input type="text" class="eqLogicAttr form-control" id="intentId" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control input-sm" id="intentName" data-l1key="name"  readonly/>
              </div>
              <div class="col-sm-3">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
              </div>
          </div>

          <legend><i class="fas fa-cogs"></i> {{Callback Scenario}}</legend>
          <div class="form-group">
              <label class="col-sm-1 control-label">{{Scenario}}</label>
              <div class="col-sm-4">
                  <select class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="scenario">
                      <option value="-1">{{None}}</option>
                      <?php
                      foreach ($scenarios as $scenario) {
                        echo '<option value="'.$scenario->getId().'">'.$scenario->getName().'</option>';
                      }
                      ?>
                  </select>
              </div>
              <label class="col-sm-1 control-label">{{Action}}</label>
              <div class="col-sm-2">
                  <select class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="action">
                      <option value="start">{{Start}}</option>
                      <option value="startsync">{{Start (sync)}}</option>
                      <option value="stop">{{Stop}}</option>
                      <option value="activate">{{Activer}}</option>
                      <option value="deactivate">{{Désactiver}}</option>
                      <option value="resetRepeatIfStatus">{{Remise à zero des SI}}</option>
                  </select>
              </div>
              <label class="col-sm-1 control-label">{{Tags}}</label>
              <div class="col-sm-2">
                  <textarea class="eqLogicAttr" style="height: 30px;width: 236px;" data-l1key="configuration" data-l2key="callbackScenario" data-l3key="user_tags"></textarea>
              </div>
              <div class="col-sm-4"></div>
          </div>

          <div class="form-group">
              <label class="col-sm-1 control-label">{{Tags}}
                <sup><i class="fas fa-question-circle" title="{{Sélectionnez les informations de l'Intention passées sous forme de tag.}}"></i></sup>
              </label>
              <div class="col-sm-5">
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