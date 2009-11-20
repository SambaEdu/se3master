<?
// $Id$ //

$name_obj="Imprimantes";  // Nom qui apparait dans l'interface
$basesql_obj="";   // Nom dans la base SQL qui permet de désactiver cet objeta
$name_id="printer";

// Test si cups marche
if (test_cups()=="1") {
        $icone_cups="enabled.png";
} else {
	$icone_cups="disabled.png";
}
		

$objet = array();

$objet['cups'] = array($icone_cups,'Etat du serveur cups','return_list(\'cups\',\'cups\',\'img_only\')','cups');

$objet['add'] = array('printer.png','Créer une nouvelle imprimante','popuprecherche(\'../printers/config_printer.php?parc=Imprimante\',\'popuprecherche\',\'scrollbars=yes,width=500,height=500\');','printers_add');

$objet['admin'] = array('command.png','Gérer les imprimantes','popuprecherche(\'../printers/view_printers.php\',\'popuprecherche\',\'scrollbars=yes,width=500,height=500\');','printers_admin');

?>
