<?
// $Id$ // 

$name_obj="Sauvegarde";  // Nom qui apparait dans l'interface
$basesql_obj="backuppc";   // Nom dans la base SQL qui permet de désactiver cet objet
$name_id="sauvegarde";

// Test l'etat du serveur de sauvegarde
if (EtatBackupPc ()== "1") {
        $icone="enabled.png";
	$etat="1";
} else {
	$icone="disabled.png";
	$etat="0";
}

$objet = array();
// Doit relancer ou stopper backuppc
$objet['etat'] = array($icone,'Etat du serveur de sauvegarde','return_list(\'backuppc_etat\',\'backuppc_etat\',\'img_only\')','backuppc_etat');

// Doit configurer backuppc
$objet['conf'] = array('travaux.png','Configurer la sauvegarde','popuprecherche(\'../sauvegarde/config_defaut.php\',\'popuprecherche\',\'scrollbars=yes,width=500,height=500\');','backuppc_conf');
		
// Ajout d'une nouvelle machine		
$objet['add'] = array('computer.png','Ajouter une nouvelle machine à sauvegarder','popuprecherche(\'../sauvegarde/new_host.php\',\'popuprecherche\',\'scrollbars=yes,width=500,height=500\')','backuppc_add');

$objet['show'] = array('zoom.png','Serveur de sauvegarde','popuprecherche(\'../backuppc/index.cgi\',\'popuprecherche\',\'scrollbars=yes,width=500,height=500\')','backuppc');

?>
