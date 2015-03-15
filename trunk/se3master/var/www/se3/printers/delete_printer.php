<?php


   /**
   
   * Suppression des imprimantes conformement au souhait de l'utilisateur
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: delete_printer.php

  */	




// Suppression des imprimantes conformement au souhait de l'utilisateur:  //
//     -Retrait d'imprimantes du parc selectionne  ( Supprimee comme membre d'un parc seulement)
// ou  -Suppression definitive d'une imprimante ( Ne presente plus aucune trace ni dans LDAP, ni dans CUPS)

include "entete.inc.php";
include "ldap.inc.php";    // pour fonction search_machines ()
include "ihm.inc.php";    // pour fonction is_admin()
include "printers.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";


   /**

   * Fonction qui vire une machine d'un parc, supprime le parc si la machine est la derniere dedans.
   * @Parametres On donne le nom de la machine et le parc
   * @Return 
   
   */


function supprime_machine_parc($mpenc,$parc) {
	include "config.inc.php";
	// On compte si la demande ne porte pas sur toutes les machines
	$mp_all=gof_members($parc,"parcs",1);
	$mpcount=count($mp_all);

	// Si la demande porte sur la derniere machine du parc
	// On vire le parc

	if ($mpcount == "1") {
		$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn; 
     		exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");
	}
	if ($mpcount > "1") {
		$resultat=search_imprimantes("printer-name=$mpenc","printers");
		$suisje_printer="non";
		for ($loopp=0; $loopp < count($resultat); $loopp++) {
			if ($mpenc==$resultat[$loopp]['printer-name']) {
				$suisje_printer="yes";	
				continue;
			}	
		}

        	$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
		if ($suisje_printer=="yes") {
			// je suis une imprimante
			$cDn = "cn=".$mpenc.",".$printersRdn.",".$ldap_base_dn;
		} else {
			// je suis un ordianteur
			$cDn = "cn=".$mpenc.",".$computersRdn.",".$ldap_base_dn;
        	}
		// on supprime
		exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
	}
}

if (is_admin("se3_is_admin",$login)=="Y") {
	
	$choix = $_POST['choix'];
	$parc = $_POST['parc'];
	$filtre_imp = $_POST['filtre_imp'];
	$old_printers = $_POST['old_printers'];
	$mp = $_POST['mp'];
	$delete_printer = $_POST['delete_printer'];

	// Affichage de la page de selection du parc dans le cas du retrait d'imprimante(s) pour un parc.
    	if ( ($choix=="option1") && !isset($parc) ) {
        	echo "<H1>".gettext("S&#233lection du parc")."</H1>";
        	$list_parcs=search_machines("objectclass=GroupOfNames","parcs");  // !!!! Fonction search _machines ambigue !!
        	if ( count($list_parcs)>0) {					// elle liste ici les parcs (meme fonc utilisee)
	    		echo "<FORM METHOD=\"post\">\n"; 
	    		echo "<P>".gettext("Effacer des imprimantes du parc")."\n</P>"; 
	    		echo "<SELECT NAME=\"parc\" SIZE=\"1\">";
	    		for ($loop=0; $loop < count($list_parcs); $loop++) {
	        		echo "<option value=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."\n";
	    		}
	    		echo "</SELECT>&nbsp;&nbsp;\n";
            		echo "<INPUT TYPE=\"hidden\" NAME=\"choix\" VALUE=\"option1\">";
	    		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
	    		echo "</FORM>\n";
        	}                     
    	}
    	if ( isset($parc) || ($choix=="option2") ) {
        	// Affichage de la page de selection des imprimantes a supprimer.
        	if ( !$delete_printer ) {
            		// Lecture des membres du parc
            		if ( $choix=="option1" ) {     // Cas d'une suppression par parc
                    		$mp_all=printers_members($parc,"parcs",1);
            		} else {             // Cas d'une suppression definitive
                    		$mp_x=search_printers("printer-name=*");      // search_printers() renvoie un tableau a 2 dimensions dont la
                    		for ( $loop=0;$loop<count($mp_x);$loop++ ) {  // deuxieme concerne les attributs LDAP, d'ou la necessite d'isoler
                        		$mp_all[$loop]=$mp_x[$loop]['printer-name']; //le nom pour avoir un tableau a une seule dimension compatible
                    		}                                            // avec le reste du programme      
            		}
            		if (count($mp_all)>0) {
                		echo "<H1>".gettext("S&#233lection des imprimantes &#224 supprimer")."</H1>";
                		// Filtrage des noms
                		//Affichage de la boite de saisie du nom d'imprimante a filtrer 
                		echo "<FORM ACTION=\"delete_printer.php\" METHOD=\"post\">\n";
                		echo "<P>".gettext("Lister les noms contenant:");
                		echo "<INPUT TYPE=\"text\" NAME=\"filtre_imp\"\n VALUE=\"$filtre_imp\" SIZE=\"8\">";
                		echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n";
                		if ($choix=="option1") {
                    			echo "<INPUT TYPE=\"hidden\" NAME=\"choix\" VALUE=\"$option1\">";
                		} else {
                    			echo "<INPUT TYPE=\"hidden\" NAME=\"choix\" VALUE=\"$option2\">";
                		}
                		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
                		echo "</FORM>\n";
                		// Filtrage selon critere indique par l'utilisateur
                		if ("$filtre_imp"=="") $mp=$mp_all;
                		else {
                    			$lmloop=0;
                    			$mpcount=count($mp_all);
                    			for ($loop=0; $loop < count($mp_all); $loop++) {
                    				$imp=$mp_all[$loop];
                                                if (preg_match("/$filtre_imp/",$imp)) $mp[$lmloop++]=$imp;
                    			}
                		}
                		if ( count($mp)>15) $size=15; else $size=count($mp); // Definition de la taille du formulaire liste
                		if ( count($mp)>0) {        // Dans le cas ou il y'a desimprimantes
                    			// Affichage du formulaire liste des imprimantes valides.
                    			echo "<FORM ACTION=\"delete_printer.php\" method=\"post\">\n";
                    			if ($choix=="option1") {
                        			echo "<P>".gettext("S&#233lectionnez les imprimantes &#224 enlever du parc")." $parc:</P>\n";
                    			} else {
                        			echo "<P>".gettext("S&#233lectionnez les imprimantes que vous souhaitez supprimer:")."</P>\n";
                        			echo "<P>".gettext("ATTENTION !! La suppression effacera int&#233gralement les informations de configuration pour les imprimantes s&#233l&#233ction&#233es !")."</P>";
                    			}  
                    			echo "<P><SELECT SIZE=\"".$size."\" NAME=\"old_printers[]\" MULTIPLE=\"multiple\">\n";
                    			for ($loop=0; $loop < count($mp); $loop++) {
                        			echo "<OPTION VALUE=\"$mp[$loop]\">$mp[$loop]";
                    			}
                    			echo "</SELECT></P>\n";
                    			echo "<INPUT TYPE=\"hidden\" NAME=\"delete_printer\" VALUE=\"true\">\n";
                    			echo "<INPUT TYPE=\"hidden\" NAME=\"parc\" VALUE=\"$parc\">\n";
                    			echo "<INPUT TYPE=\"hidden\" NAME=\"choix\" VALUE=\"$choix\">";
                    			echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\" ONCLICK= \"return getconfirm();\">\n";
                    			echo "</FORM>\n";
                		}
            		} else {    // A fortiori quand il n'y'a pas d'imprimantes a supprimer
				echo "<h1>Suppression d'imprimantes</h1>\n";
                		$message =  gettext("Il n'y a pas d'imprimantes &#224 supprimer !");
                		echo $message;
				echo "<br><br><center>";
				echo "<a href=\"delete_printer_choice.php\">Retour</a>";
            		}
        	} else {  // Affichage de la page de configmation des suppressions.         
            		// Suppression des imprimantes dans le parc
            		if ($choix=="option1") {
                		echo "<H1>".gettext("Suppression d'imprimantes dans le parc")." <U>$parc</U></H1>";
                		echo "<P>".gettext("Vous avez s&#233lectionn&#233 "). count($old_printers).gettext(" imprimante(s)")."<BR><BR>\n";
                		for ($loop=0; $loop < count($old_printers); $loop++) {
                    			$printer=$old_printers[$loop];	
                    			supprime_machine_parc($printer,$parc);	
					// exec ("/usr/share/se3/sbin/printerDelPark.pl $printer $parc",$AllOutPutValue,$ReturnValue);
                    			// if ($ReturnValue==0) {
                        			echo gettext("Suppression de l'imprimante")." <B>$printer</B> ".gettext("du parc")." <B>$parc</B> ".gettext("effectu&#233e")."<BR>";
                    			// } else {
                        		//	echo "<B>ECHEC</B>".gettext(" de la suppression de l'imprimante")." <B>$printer</B> ".gettext("du parc")." <B>$parc</B><BR>";    
                    			// }
                		}
				echo "<BR><P>".gettext("N'oubliez pas de d&#233sinstaller les pilotes sur chaque poste du parc")." $parc</P>";
            		} else {
                		echo "<H1>".gettext("Suppression d&#233finitive d'imprimantes")."</H1>";
                		echo "<P>".gettext("Vous avez s&#233lectionn&#233 "). count($old_printers). gettext(" imprimante(s)")."<BR><BR>\n";
                		for ($loop=0; $loop < count($old_printers); $loop++) {
                    			$printer=$old_printers[$loop];
					// On supprime d'abord des parcs
					// meme si le script perl le fait, il ne gere pas le probleme de 
					// la derniere machine d'un parc

					$list_parcs=search_machines("objectclass=groupOfNames","parcs");
					if ( count($list_parcs)>0) {
						sort($list_parcs);
						for ($loopp=0; $loopp < count($list_parcs); $loopp++) {
							$parc_list = $list_parcs[$loopp]["cn"];
	 			               		supprime_machine_parc($printer,$parc_list); 
						}
					}	
                    			exec ("/usr/share/se3/sbin/printerDel.pl $printer",$AllOutPutValue,$ReturnValue);
                    			if ($ReturnValue==0) {
                        			echo gettext("Suppression de l'imprimante")." <B>$printer</B> ".gettext("effectu&#233e")."<BR>";
                    			} else {
                        			echo "<B>".gettext("ECHEC")."</B> ".gettext("de la suppression de l'imprimante")." <B>$printer</B><BR>";    
                    			} 
               			}
				echo "<BR><P>".gettext("N'oubliez pas de d&#233sinstaller les pilotes sur chaque poste")."</P>";
             		}		
        	}
    	}
}

include ("pdp.inc.php");
?>
