<?php


   /**
   
   * Permet d'ajouter des imprimantes a des parcs
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: add_printer.php

  */	


   
   
//Affichage de la page pour ajouter des imprimantes a des parcs

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

	// Affichage du formulaire de selection de parc
    	if (!isset($parc)) {
        	echo "<H1>".gettext("S&#233lection du parc &#224 alimenter")."</H1>";
        	$list_parcs=search_machines("objectclass=groupOfNames","parcs"); //Liste des parcs existants
        	if ( count($list_parcs)>0) {
                        sort($list_parcs);
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
        	// Lecture des membres du parc
        	$mp=gof_members($parc,"parcs",1);
        	// Creation d'un tableau des nouvelles imprimantes a integrer
        	$list_imprimantes=search_imprimantes("(&(printer-name=*)(objectClass=printerService))","printers"); 
        	// tri des imprimantes deja presentes dans le parc
        	$lmloop=0;
        	$mpcount=count($mp);
        	for ($loop=0; $loop < count($list_imprimantes); $loop++) {
            		$loop1=0;
            		$imp=$list_imprimantes[$loop]["printer-name"];      
            		while (("$mp[$loop1]" != "$imp") && ($loop1 < $mpcount)) $loop1++;
            		if ("$mp[$loop1]" != "$imp") $list_new_imprimantes[$lmloop++]=$imp;
         	}                                         
        	// Affichage de la page de selection des imprimantes a ajouter au  parc
        	echo "<H1>".gettext("S&#233lection des imprimantes")."</H1>";
        	if (count($list_new_imprimantes)>0) {
                        sort($list_new_imprimantes);
            		// Filtrage des noms
            		echo "<FORM ACTION=\"add_printer.php\" METHOD=\"post\">\n";
            		echo "<P>".gettext("Lister les noms contenant:")." </P>";
            		echo "<INPUT TYPE=\"text\" NAME=\"filtre_imp\"\n VALUE=\"$filtre_imp\" SIZE=\"8\">";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n"; 
	    		echo "<INPUT TYPE=\"hidden\" NAME=\"filtre\" VALUE=\"$filtre\">\n"; 
            		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";       
	    		echo "</FORM>\n";
        	}
    		// Affichage du formulaire de liste des imprimantes 
		if ( count($list_new_imprimantes)>15) $size=15; else $size=count($list_new_imprimantes);
		if ( count($list_new_imprimantes)>0) {
	    		echo "<FORM ACTION=\"add_printer.php\" METHOD=\"post\">\n";
            		echo "<P>".gettext("S&#233lectionnez les nouvelles imprimantes &#224� int&#233grer au parc:")."</P>\n";
            		echo "<p><SELECT SIZE=\"".$size."\" NAME=\"new_printers[]\" MULTIPLE=\"multiple\">\n";
            		for ($loop=0; $loop < count($list_new_imprimantes); $loop++) {
	        		echo "<OPTION VALUE=\"".$list_new_imprimantes[$loop]."\">".$list_new_imprimantes[$loop];
           	 	} 
            		echo "</SELECT></P>\n";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"add_print\" VALUE=\"true\">\n";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n";
            		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
            		echo "</FORM>\n";
		} else {
	    		$message =  gettext("Il n'y a pas de nouvelle imprimante &#224� ajouter !");
	    		echo $message;
		}
    	} else {
        	// Ajout des imprimantes dans le parc selectionne
        	echo "<H1>".gettext("Alimentation du parc")." <U>$parc</U></H1>";
        	echo "<P>".gettext("Vous avez s&#233lectionn&#233 "). count($new_printers).gettext(" imprimante(s)")."<BR>\n";
        	for ($loop=0; $loop < count($new_printers); $loop++) {
	    		$printer=$new_printers[$loop];
	    		exec ("/usr/share/se3/sbin/printerAddPark.pl $printer $parc",$AllOutPutValue,$ReturnValue);
            		if ($ReturnValue==0) {
                		echo gettext("Ajout de l'imprimante")." <B>$printer</B> ".gettext("au parc")." <B>$parc</B> ".gettext("effectu&#233")."<BR>";
            		} else {
                		echo "<B>".gettext("ECHEC")."</B>".gettext(" de l'ajout de l'imprimante")." <B>$printer</B> ".gettext("au parc")." <B>$parc</B><BR>";    
            		}         
         	}
	}
}

include ("pdp.inc.php");
?>
