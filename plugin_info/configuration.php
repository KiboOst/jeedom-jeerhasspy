<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-lg-4 control-label" >{{Adresse du Rhasspy maître}}
                <sup><i class="fas fa-question-circle" title="{{Adresse IP de votre Rhasspy (maître).}}"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="rhasspyAddr" placeholder="http://127.0.0.1"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label" >{{Port du Rhasspy maître}}
                <sup><i class="fas fa-question-circle" title="{{Port de votre Rhasspy (maître). 12101 par defaut}}"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="rhasspyPort" placeholder="12101"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label" >{{Feedback}}
                <sup><i class="fas fa-question-circle" title="{{Réponse si aucune correspondance n'est trouvée.}}"></i></sup>
            </label>
            <div class="col-lg-4">
                <textarea class="configKey form-control" data-l1key="defaultTTS" placeholder="{{Désolé mais je ne vois pas quoi faire.}}"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label" >{{Filtrer les Intents Jeedom}}
                <sup><i class="fas fa-question-circle" title="{{Importe seulement les Intents de l'assistant dont le nom finit par 'Jeedom'}}"></i></sup>
            </label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey" data-l1key="filterJeedomIntents" checked/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label" >{{Variables rhasspyWakeWord / rhasspyWakeSiteId}}
                <sup><i class="fas fa-question-circle" title="{{Assigne ces deux variables avec le wakeId et le siteId ayant déclenché le wakeword.}}"></i></sup>
            </label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey" data-l1key="setWakeVariables" checked/>
            </div>
        </div>
    </fieldset>
</form>

<script type="text/javascript">
$(function() {
    setTimeout(function() {
        defaultTTSarea = $('textarea[data-l1key="defaultTTS"]')
        if (defaultTTSarea.val() == '') {
            defaultTTSarea.val(defaultTTSarea.attr('placeholder'))
        }
    }, 500)
})
</script>