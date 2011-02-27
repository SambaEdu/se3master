<?
// $Id$ //
$name_obj="Computers";  // Nom qui apparait dans l'interface
$basesql_obj="";   // Nom dans la base SQL qui permet de désactiver cet objeta
$name_id="computers";

$objet = array();

// $objet['recherche'] = array('loupe.png','Rechercher une machine','popuprecherche(\'../inventaire/search_inventaire.php?action=formul\',\'popuprecherche\',\'width=500,height=500\');'); //

$objet['popup'] = array('edit.png','Envoyer un popup à toutes les machines','popuprecherche(\'../popup/index.php\',\'popuprecherche\',\'width=500,height=500\');','computers_popup'); //

// Affiche toutes les machines
$objet['add'] = array('parc.png','Affiche toutes les machines','return_list(\'all\',\'computers\',\'affiche\')','affiche');
?>
