<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('caldav');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
     <div class="col-xs-12 eqLogicThumbnailDisplay">
       <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
   		<div class="eqLogicThumbnailContainer">
   			<div class="cursor eqLogicAction logoPrimary" data-action="add">
   				<i class="fas fa-plus-circle"></i>
   				<br>
   				<span>{{Ajouter}}</span>
   			</div>
   			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
   				<i class="fas fa-wrench"></i>
   				<br>
   				<span>{{Configuration}}</span>
   			</div>
   		</div>
        <legend><i class='icon divers-calendar2'></i> {{Mes agendas Caldav}}</legend>
      <div class="input-group" style="margin:5px;">
  			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
  			<div class="input-group-btn">
  				<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
  			</div>
  		</div>
  		<!-- Liste des équipements du plugin -->
  		<div class="eqLogicThumbnailContainer">
  			<?php
  			foreach ($eqLogics as $eqLogic) {
  				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
  				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
  				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
  				echo '<br>';
  				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
  				echo '</div>';
  			}
  			?>
  		</div>
  	</div> <!-- /.eqLogicThumbnailDisplay -->

    <div class="col-xs-12 eqLogic" style="display: none;">
  		<!-- barre de gestion de l'équipement -->
  		<div class="input-group pull-right" style="display:inline-flex;">
  			<span class="input-group-btn">
  				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
  				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
  				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
  				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
  				</a>
  			</span>
  		</div>
  		<!-- Onglets -->
  		<ul class="nav nav-tabs" role="tablist">
  			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
  			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
  			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
  		</ul>
  		<div class="tab-content">
  			<!-- Onglet de configuration de l'équipement -->
  			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
  				<br/>
  				<div class="row">
  					<div class="col-lg-7">
  						<form class="form-horizontal">
  							<fieldset>
  								<legend><i class="fas fa-wrench"></i> {{Général}}</legend>
  								<div class="form-group">
  									<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
  									<div class="col-xs-11 col-sm-7">
  										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;"/>
  										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
  									</div>
  								</div>
  								<div class="form-group">
  									<label class="col-sm-3 control-label" >{{Objet parent}}</label>
  									<div class="col-xs-11 col-sm-7">
  										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
  											<option value="">{{Aucun}}</option>
  											<?php
  											$options = '';
  											foreach ((jeeObject::buildTree(null, false)) as $object) {
  												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
  											}
  											echo $options;
  											?>
  										</select>
  									</div>
  								</div>
  								<div class="form-group">
  									<label class="col-sm-3 control-label">{{Catégorie}}</label>
  									<div class="col-sm-9">
  										<?php
  										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
  											echo '<label class="checkbox-inline">';
  											echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
  											echo '</label>';
  										}
  										?>
  									</div>
  								</div>
  								<div class="form-group">
  									<label class="col-sm-3 control-label">{{Options}}</label>
  									<div class="col-xs-11 col-sm-7">
  										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
  										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
  									</div>
  								</div>

  								<br/>
  								<legend><i class="fas fa-cogs"></i> {{Paramètres}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{URL caldav}}</label>
                            <div class="col-xs-11 col-sm-7">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="url"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom d'utilisateur}}</label>
                            <div class="col-xs-11 col-sm-7">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Mot de passe}}</label>
                            <div class="col-xs-11 col-sm-7">
                                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Calendrier}}</label>
                            <div class="col-xs-11 col-sm-7">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="calendrier" id="CalendarsList">
                                </select>
                            </div>
                        </div>
                    </fieldset>
                </form>
           </div>
      </div>
           </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
              <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une pattern caldav}}</a>
              <br/><br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Nom}}</th><th>{{Pattern}}</th><th>{{Valeur par défaut}}</th><th></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
           </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'caldav', 'js', 'caldav'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
