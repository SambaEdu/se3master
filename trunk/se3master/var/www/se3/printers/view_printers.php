<?php


   /**
   
   * Permet une gestion individuelle des imprimantes
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: view_printers.php

  */	




// Affiche les parametres de chaque imprimante
include "entete.inc.php";
include "printers.inc.php";
include "ihm.inc.php";     // pour is_admin()
//include "ldap.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

if (is_admin("printers_is_admin",$login)=="Y") {
	if ($_POST['one_printer'] != ""){
		$one_printer= $_POST['one_printer'];
	} elseif($_GET['one_printer'] != ""){
		$one_printer= $_GET['one_printer'];
	} else {
		$one_printer= '*';
	}
	
	$num = $_POST['num'];
	$status = $_POST['status'];
	$queue = $_POST['queue'];
	$period = $_POST['period'];
	$pages = $_POST['pages'];
	$printer = $_POST['printer'];
	$quota = $_POST['quota'];
	$valids = $_POST['valids'];
	$validq = $_POST['validq'];

	$all_printers=search_printers ("printer-name=".$one_printer);
	$nb_printers=count($all_printers);

	if (isset($quota)) {
		if ($quota == "Valider") {
    			$period_sec=$period*86400;
    		} else {
			$pages=$period_sec=0;
		}
		exec("/usr/sbin/lpadmin -p $printer -o job-page-limit=$pages -o job-quota-period=$period_sec");
	}
	if (isset($valids)){
		$able="cups".$status;
        
		exec ("/usr/sbin/$able {$all_printers[$num]['printer-name']}");
	} elseif (isset($validq)) {
        $able="cups".$queue;
        exec ("/usr/sbin/$able {$all_printers[$num]['printer-name']}");
	}

    // Bug Lenny
//     if (isset($_GET['disable_lenny_bug'])) {
//         system ("sudo /usr/share/se3/scripts/disable_lenny_bug.sh");
//     }

	//Recuperation des champs Printers,QuotaPeriod,PageLimit de /etc/cups/printers.conf
	$result1=exec("/usr/bin/sudo /usr/share/se3/scripts/printless.sh /etc/cups/printers.conf | grep \"<*[^/ ]Printer\" | sed s/^.*Printer' '/\"\"/g",$nom_imprim);
	$result2=exec("/usr/bin/sudo /usr/share/se3/scripts/printless.sh /etc/cups/printers.conf | grep PageLimit | cut -c 11-",$nb_p);
	$result3=exec("/usr/bin/sudo /usr/share/se3/scripts/printless.sh /etc/cups/printers.conf | grep QuotaPeriod | cut -c 13-",$nb_s);
	//L'ordre de listage des imprimantes dans printers.conf ne correspond pas necessairement a celui de ldap
	// d'ou la necessite de lister celui de printers.conf conformement a celui de ldap de facon a ce que les quotas
	// correspondent aux bonnes imprimantes
	$n=count($nom_imprim);
	for ($i=0;$i<$nb_printers;$i++) {
    		$j=0;
    		while ( ( $all_printers[$i]['printer-name'].">" != $nom_imprim[$j]) && ($j <= $n) ) {
        		$j++;
    		}
    		$nb_pages[$i]=$nb_p[$j];
    		$nb_sec[$i]=$nb_s[$j];
	}
	//Affichage du navigateur d'imprimantes si non $one_printer :
	if ($one_printer == "*") {
		echo "<H1>".gettext("Gestion des imprimantes")."</H1>";
		if (count($all_printers)) { 
			if($_GET['lieu']==1) { usort($all_printers, "cmp_location"); } else {usort($all_printers, "cmp_printer"); }
		}	
		// Test serveur cups
		$status=exec("LC_ALL=C /usr/bin/lpstat -r");
		 echo "\n<br>\n<CENTER>\n";
		 echo "<TABLE border=1 width=\"60%\">\n";
		 
		 echo "<tr class=menuheader style=\"height: 30\">\n";
		 echo "<td colspan=\"5\" valign=\"middle\" align=\"center\">";
		 echo "Serveur d'impression ";
		 if ($status=="scheduler is running") {
		 	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Serveur cups en marche')")."\">";
			echo "<IMG style=\"border: 0px solid;\" SRC=\"../elements/images/enabled.png\" >";
			echo "</u>\n";
		} else {
			echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Serveur cups stopp&#233;')")."\">";
			echo "<IMG style=\"border: 0px solid;\" SRC=\"../elements/images/disabled.png\" >";
			echo "</u>\n";
		}
		echo "</td>";
		echo "</tr>";
		echo "<tr class=menuheader style=\"height: 30\">\n";

	         echo "<td align=\"center\"></td>\n";
		 echo "<td align=\"center\"><a href=view_printers.php>Imprimantes</a></td>\n";
		 echo "<td align=\"center\">Information</td>\n";
		 echo "<td align=\"center\"><a href=view_printers.php?lieu=1>Lieu</a></td>\n";
		 echo "<td align=\"center\">Parc</td>\n";
		 echo "</tr>";

		for ($loop=0; $loop<$nb_printers; $loop++) {
			$printer=$all_printers[$loop]['printer-name'];

			echo "<TR>";
		        echo "<td align=\"center\"><img style=\"border: 0px solid ;\" src=\"../elements/images/printer.png\" title=\"Imprimante\" alt=\"Imprimante\">";
			echo "</TD><TD>";
			if($nb_printers<6) {
				echo "<A HREF=\"#tag[$loop]\">$printer</A>";
			} else {
				echo "<A href='view_printers.php?one_printer=$printer'>$printer</A>";
			}
			echo "</TD><TD>";
			echo $all_printers[$loop]['printer-info'];
			echo "</TD><TD>";
			echo $all_printers[$loop]['printer-location'];
			echo "</TD><TD>";
			$list_parcs=search_machines("objectclass=groupOfNames","parcs");
	                $pass=0;
			if ( count($list_parcs)>0) {
                                sort($list_parcs);
			        for ($loopp=0; $loopp < count($list_parcs); $loopp++) {
			 	       $parc=$list_parcs[$loopp]["cn"];
				       $imp=gof_members($parc,"parcs",1);
				       if (count($imp)>0) {
					       for ($loopmp=0; $loopmp < count($imp);$loopmp++) {
					       		$comp=trim($imp[$loopmp]);
						        $printer=trim($printer);
						        if ("$comp" == "$printer") {
						        	echo "<A href=../parcs/show_parc.php?parc=".$list_parcs[$loopp]["cn"].">";
							        echo $list_parcs[$loopp]["cn"];
							        echo "</A>";
							        echo "<br>";
							        $pass=1;
							}
						}
					}
				}
			}
			if($pass==0) { echo "Sans parc"; }
		 	echo "</TD></TR>";
		        
		}
		echo "</TABLE><br>\n";
	}

	// Si trop d'imprimante (>6) on ne les affiche plus
	if (($nb_printers>5) && ($_GET['action'] != "all")) {
		echo "<br><hr><center>";
		echo "<A href='view_printers.php?action=all'>".gettext("D&#233;tail de toutes les imprimantes")."</A> ";
		echo " <u onmouseover=\"return escape".gettext("('Permet de voir le d&#233;tail de toutes les imprimantes. Cela peut &#234;tre tr&#232;s long &#224; afficher si vous en avez beaucoup.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u> ";
		echo "</center>";

		include "pdp.inc.php";
		exit;
	}
	if($_GET['action']=="all") {
		echo "<HR>\n";
	}
	for ($loop=0; $loop<$nb_printers; $loop++) {
  		$printer=$all_printers[$loop]['printer-name'];

		if((one_printer!="") && ($_GET['action'] !="all")) {
			echo "<H1>".gettext("Liste des imprimantes")."</H1>";
		}
		//  echo $printer;
		echo "<TABLE width=\"90%\"><TR><TD width=\"80%\">";
  		echo "<FONT SIZE=5><A NAME=\"tag[$loop]\"><B>$printer</B></A></FONT>\n";
		echo "</TD>\n";
		// Ajout pour pouvoir modifier
		echo "<TD>";
		echo "<FORM ACTION=\"config_printer.php\" METHOD=\"post\">\n";
		echo "<INPUT TYPE=\"hidden\" VALUE=\"$printer\" NAME=\"nom_imprimante\">\n";
		echo "<INPUT TYPE=\"hidden\" VALUE=\"".$all_printers[$loop]['printer-uri']."\" NAME=\"uri_printer\">\n";
		echo "<INPUT TYPE=\"hidden\" VALUE=\"".$all_printers[$loop]['printer-location']."\" NAME=\"lieu_printer\">\n";
		echo "<INPUT TYPE=\"hidden\" VALUE=\"".$all_printers[$loop]['printer-info']."\" NAME=\"info_printer\">\n";
		echo "<INPUT TYPE=\"hidden\" VALUE=\"".$all_printers[$loop]['printer-more-info']."\" NAME=\"dev_mode\">\n";

		echo "<INPUT TYPE=\"hidden\" VALUE=\"".$all_printers[$loop]['nprinthardwarequeuename']."\" NAME=\"driver_printer\">\n";
		if (is_admin("se3_is_admin",$login)=="Y") {
			echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Modifier")."\" NAME=\"modifs\">\n";
		}
		echo "</FORM>\n";
  		echo "</TD>\n";
 
		echo "</TR>\n</TABLE>\n";
		$URI=preg_replace("/:[^:]*@/", ":*******@", $all_printers[$loop]['printer-uri']);
		echo "<BR><BR>\n";
  		echo "<TABLE BORDER=0>\n";
  		echo "<TR><TD BGCOLOR=\"cornflowerblue\"><B>URI:</B></TD><TD WIDTH=300 BGCOLOR=\"cornflowerblue\">$URI</TD></TR>\n";
  		echo "<TR><TD BGCOLOR=\"cornflowerblue\"><B>".gettext("Emplacement:")."</B></TD><TD WIDTH=300 BGCOLOR=\"cornflowerblue\">{$all_printers[$loop]['printer-location']}</TD></TR>\n";
  		echo "<TR><TD BGCOLOR=\"cornflowerblue\"><B>".gettext("Description:")."</B></TD><TD WIDTH=300 BGCOLOR=\"cornflowerblue\">{$all_printers[$loop]['printer-info']}</TD></TR>\n";
  		echo "<TR><TD BGCOLOR=\"cornflowerblue\"><B>".gettext("Travaux en cours:")."</B></TD>\n";
  		$sys= exec("LC_ALL=C /usr/bin/lpstat -o $printer");
  		if ($sys != "") {
    			echo "<TD BGCOLOR=\"cornflowerblue\"><BLINK>".gettext("OUI")."</BLINK></TD></TR>\n";
     		} else {
    			echo "<TD BGCOLOR=\"cornflowerblue\">".gettext("NON")."</TD></TR>\n";
  		}
  		echo "<TR><TD BGCOLOR=\"lightsteelblue\"><B>".gettext("Etat:")."</B></TD>\n";
  		$sys= exec("LC_ALL=C /usr/bin/lpstat -p $printer | grep enabled");
  		if ($sys != "") {
    			echo "<TD BGCOLOR=\"lightsteelblue\"><FONT COLOR=\"green\">".gettext("Active")."</FONT></TD>\n";
    			$status="disable";
     		} else {
    			echo "<TD BGCOLOR=\"lightsteelblue\"><FONT COLOR=\"red\">".gettext("Inactive")."</FONT></TD>\n";
    			$status="enable";
  		}
  		echo "<TD BGCOLOR=\"lightsteelblue\">\n";
  		echo "<FORM ACTION=\"view_printers.php\" METHOD=\"post\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$loop\" NAME=\"num\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$status\" NAME=\"status\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$one_printer\" NAME=\"one_printer\">\n";
  		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Basculer")."\" NAME=\"valids\">\n";
  		echo "</FORM></TD>\n";
  		echo "<TD VALIGN=\"top\" BGCOLOR=\"lightsteelblue\">".gettext("Activer/D&#233;sactiver l'imprimante")."</TD></TR>\n";
  		echo "<TR><TD BGCOLOR=\"lightsteelblue\"><B>".gettext("Travaux d'impression:")."</B></TD>\n";
  		$sys= exec("LC_ALL=C /usr/bin/lpstat -a $printer | grep not");
  		if ($sys != "") {
    			echo "<TD BGCOLOR=\"lightsteelblue\"><FONT COLOR=\"red\">".gettext("Rejette")."</FONT></TD>\n";
    			$queue="accept";
  		} else {
    			echo "<TD BGCOLOR=\"lightsteelblue\"><FONT COLOR=\"green\">".gettext("Accepte")."</FONT></TD>\n";
    			$queue="reject";
  		}
  		echo "<TD BGCOLOR=\"lightsteelblue\">\n";
  		echo "<FORM ACTION=\"view_printers.php\" METHOD=\"post\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$loop\" NAME=\"num\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$queue\" NAME=\"queue\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$one_printer\" NAME=\"one_printer\">\n";
  		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Basculer")."\" NAME=\"validq\">\n";
  		echo "</FORM></TD>\n";
  		echo "<TD VALIGN=\"top\" BGCOLOR=\"lightsteelblue\">".gettext("Accepter/Rejeter les travaux")."</TD></TR>\n";
  		echo "</TABLE>\n";
  		echo "<BR>";
  		//Affiche le bouton pour basculer sur la page travaux d'impression
  		echo "<FORM ACTION=\"printer_jobs.php\" METHOD=\"post\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$printer\" NAME=\"printer\">\n";
		// AJOUT: boireaus pour permettre un retour apres consultation des travaux
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"tag[$loop]\" NAME=\"tag\">\n";
  		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Travaux")."\" NAME=\"travaux\">\n";
  		echo "&nbsp;".gettext("Voir les travaux");
  		echo "</FORM>\n";

 	 	//Affichage du formulaire de quota
  		$nb_jours[$loop]=round(($nb_sec[$loop])/86400);
  		echo "<FORM ACTION=\"view_printers.php\" METHOD=\"post\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$printer\" NAME=\"printer\">\n";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$loop\" NAME=\"num\">\n";
  		echo gettext("D&#233;finir un quota:");
  		echo "&nbsp;".gettext("Nombre de pages: ");
  		echo "<INPUT TYPE=\"texte\" VALUE=\"$nb_pages[$loop]\" NAME=\"pages\" SIZE=\"6\">\n";
  		echo "&nbsp;".gettext("tous les: ");
  		echo "<INPUT TYPE=\"texte\" VALUE=\"$nb_jours[$loop]\" NAME=\"period\" SIZE=\"5\">\n";
  		echo "&nbsp;".gettext("jours")." &nbsp;&nbsp;&nbsp;";
  		echo "<INPUT TYPE=\"hidden\" VALUE=\"$one_printer\" NAME=\"one_printer\">\n";
  		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\" NAME=\"quota\">\n";
  		echo "&nbsp;&nbsp;";
  		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Aucun")."\" NAME=\"quota\">\n";
  		echo "</FORM>\n";
//         system("sudo /usr/share/se3/scripts/lenny_bug.sh $printer", $ret);
//         if ($ret == "1") {
//             echo "<h2>Cette imprimante ne semble pas partag&#233;e avec le bon nom, si c'est le cas, cliquez <a href=view_printers.php?disable_lenny_bug>ici</a> pour la r&#233;activer</h2>";
//         }
  		echo "<HR>\n";

	}
}

include "pdp.inc.php";
?>
