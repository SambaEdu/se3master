<?
// $Id$ //

$name_obj="Parcs";  // Nom qui apparait dans l'interface
$basesql_obj="";   // Nom dans la base SQL qui permet de désactiver cet objet
$name_id="all_parc";

// Bouton sur barre d'affichage
$objet = array();

$objet['add'] = array('parc.png','Crée un nouveau parc','popuprecherche(\'../parcs/create_parc.php?cp=false\',\'popuprecherche\',\'width=500,height=500\');','parcs_add');

// $objet['recherche'] = array('loupe.png','Rechercher une machine','popuprecherche(\'../inventaire/search_inventaire.php?action=formul\',\'popuprecherche\',\'width=500,height=500\');','parcs_recherche'); // 

$objet['popup'] = array('edit.png','Envoyer un popup à toutes les machines','popuprecherche(\'../popup/index.php\',\'popuprecherche\',\'width=500,height=500\');','parcs_popup'); // 
?>
