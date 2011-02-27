<?php
/* $Id$ */

/* =============================================
   Projet SE3 : Ajout imprimante par defaut
   printers/default_printer.php
   Permet d'ajouter une imprimante par defaut
   Patrice André <h.barca@free.fr>
   Cedric Bellegarde <cbellegarde@ac-nantes.fr>
   Carip-Académie de Lyon -avril-juin-2004
   Distribué selon les termes de la licence GPL
   ============================================= */
   
   
   
//Affichage de la page pour ajouter des imprimantes à des parcs

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "printers.inc.php";             

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";


if (is_admin("se3_is_admin",$login)=="Y") { 
	
	$parc = $_POST['parc'];
	$filtre_imp = $_POST['filtre_imp'];
	$filtre = $_POST['filtre'];
	$new_printers = $_POST['new_printers'];
	$add_print = $_POST['add_print'];
    $default_printer = $_POST['default_printer'];

	// Affichage du formulaire de sélection de parc
    	if (!isset($parc)) {
        	echo "<H1>".gettext("S&#233lection du parc")."</H1>";
        	$list_parcs=search_machines("objectclass=groupOfNames","parcs"); //Liste des parcs existants
        	if ( count($list_parcs)>0) {
            		echo "<FORM METHOD=\"post\">\n";
            		echo "<SELECT NAME=\"parc\" SIZE=\"10\">";        
	    		for ($loop=0; $loop < count($list_parcs); $loop++) {
	        		echo "<OPTION VALUE=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."\n";
	    		}
	    		echo "</SELECT>&nbsp;&nbsp;\n";
	    		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
	    		echo "</FORM>\n";
        	}
    	} elseif (!$add_print) {
        	// Creation d'un tableau des nouvelles imprimantes à  intégrer
        	$list_imprimantes=search_imprimantes("(&(printer-name=*)(objectClass=printerService))","printers"); 
		
        	echo "<H1>".gettext("S&#233lection de l'imprimante")."</H1>";
  /*      	if (count($list_imprimantes)>0) {
            		// Filtrage des noms
            		echo "<FORM ACTION=\"default_printer.php\" METHOD=\"post\">\n";
            		echo "<P>".gettext("Lister les noms contenant:")." </P>";
            		echo "<INPUT TYPE=\"text\" NAME=\"filtre_imp\"\n VALUE=\"$filtre_imp\" SIZE=\"8\">";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n"; 
	    		echo "<INPUT TYPE=\"hidden\" NAME=\"filtre\" VALUE=\"$filtre\">\n"; 
            		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";       
	    		echo "</FORM>\n";
        	}*/
    		// Affichage du formulaire de liste des imprimantes 
		if ( count($list_imprimantes)>15) $size=15; else $size=count($list_imprimantes);
		if ( count($list_imprimantes)>0) {
	    		echo "<FORM ACTION=\"default_printer.php\" METHOD=\"post\">\n";
            		echo "<P>".gettext("S&#233lectionnez l'imprimante par defaut:")."</P>\n";
            		echo "<p><SELECT SIZE=\"".$size."\" NAME=\"default_printer\">\n";
            		for ($loop=0; $loop < count($list_imprimantes); $loop++) {
	        		echo "<OPTION VALUE=\"".$list_imprimantes[$loop]["printer-name"]."\">".$list_imprimantes[$loop]["printer-name"];
           	 	} 
            		echo "</SELECT></P>\n";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"add_print\" VALUE=\"true\">\n";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n";
            		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
            		echo "</FORM>\n";
		} else {
	    		$message =  gettext("Il n'y a pas d'imprimante dans ce parc !");
	    		echo $message;
		}
    	} else {
        	// Ajout des imprimantes dans le parc séléctionné
        	echo "<H1>".gettext("Imprimante par defaut du parc")." <U>$parc</U></H1>";
     		exec ("/usr/share/se3/sbin/printerAddDefault.sh $default_printer $parc",$AllOutPutValue,$ReturnValue);
		if ($ReturnValue==0) {
	                 echo gettext("Ajout de l'imprimante par defaut")." <B>$printer</B> ".gettext("au parc")." <B>$parc</B> ".gettext("effectu&#233")."<BR>";
		} else {
			echo "<B>".gettext("ECHEC")."</B>".gettext(" de l'ajout de l'imprimante par defaut")." <B>$printer</B> ".gettext("au parc")." <B>$parc</B><BR>";
		}

	}
}

include ("pdp.inc.php");
?>
