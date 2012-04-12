<?php

   /**
   
   * Ajout dans CUPS et branche Printer de LDAP
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: config_printer.php

  */	



   
// Configuration d'un nouvelle imprimante
// Ecriture dans CUPS et LDAP 

include "entete.inc.php";
include "ldap.inc.php";    //
include "ihm.inc.php";   // pour enleveaccents();
include "printers.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

$nom_imprimante=$_POST['nom_imprimante'];
$nom_printer=$_POST['nom_printer'];
$lieu_printer=$_POST['lieu_printer'];
$info_printer=$_POST['info_printer'];
$dev_mode=$_POST['dev_mode'];
$driver_printer=$_POST['driver_printer'];
$uri_printer=$_POST['uri_printer'];

$config_printer=$_POST['config_printer'];
$protocole=$_POST['protocole'];
$driver=$_POST['driver'];
$lieu_imprimante=$_POST['lieu_imprimante'];
$info_imprimante=$_POST['info_imprimante'];
$uri_imprimante=$_POST['uri_imprimante'];
$lieu_imprimante=$_POST['lieu_imprimante'];
$modif_imprimante=$_POST['modif_imprimante'];
$fabriquant = $_POST['fabriquant'];
if (isset ($_POST['imp_mode']))
	$imp_mode="on";
else	$imp_mode="off";

if (is_admin("se3_is_admin",$login)=="Y") { 
	//Affichage de la page de saisie des parametres l'imprimante

   	echo "<H1>".gettext("Configuration de l'imprimante")."</H1>\n";
	if ( !$nom_imprimante || !$uri_imprimante  ||  !$lieu_imprimante ||  !$info_imprimante ) {
		
		// Pour une modif
		if($nom_printer) {
			$nom_imprimante=$_POST['nom_printer']; 
			$modif_imprimante="1";	
		}
		if($lieu_printer) {$lieu_imprimante=$_POST['lieu_printer']; }
		if($info_printer) {$info_imprimante=$_POST['info_printer']; }
		if($uri_printer) {
			if(preg_match('/^ipp/',$_POST['uri_printer'])) {
				$protoc="custom";  
				$uri_imprimante=$_POST['uri_printer'];
			}
			if(preg_match('/^smb/',$_POST['uri_printer'])) {
				$protoc="smb"; 
				list(,,,$uri_imp,$imp)=preg_split('!/!',$uri_printer);
				if ($imp!="") { $uri_imprimante="$uri_imp"; } else { $uri_imprimante="$uri_imp"; }
			}
			if(preg_match('/^socket/',$_POST['uri_printer'])) {
				$protoc="socket"; 
				list(,,$uri_imp_1)=preg_split('!/!',$uri_printer);
				list($uri_imp,)=preg_split('/:/',$uri_imp_1);
			        $uri_imprimante="$uri_imp";
			}
			if(preg_match('/^parallel/',$_POST['uri_printer'])) {
				$protoc="parallel"; 
				list(,$uri_imp,)=preg_split('!/!',$uri_printer);
				$uri_imprimante="$uri_imp";
			}
			if(preg_match('/^http/',$_POST['uri_printer'])) {
				if(preg_match('/printers/',$_POST['uri_printer'])) {$protoc="ipp";} 
				else {$protoc="http";} 
				list(,,$uri_imp_1)=preg_split('!/!',$_POST['uri_printer']);
				list($uri_imp,)=preg_split('/:/',$uri_imp_1);
				$uri_imprimante="$uri_imp";
			}
			if(preg_match('/^lpd/',$_POST['uri_printer'])) {
				$protoc="lpd"; 
				list(,,$uri_imp_1)=preg_split('!/!',$_POST['uri_printer']);
				list($uri_imp,)=preg_split('/:/',$uri_imp_1);
				$uri_imprimante="$uri_imp";
			}
			if(preg_match('/^usb/',$_POST['uri_printer'])) {
				$protoc="usb"; 
				list(,$uri_imp,)=preg_split('!/!',$_POST['uri_printer']);
				$uri_imprimante="$uri_imp";
			}
			
		}	
		//Affichage du formulaire de la liste des pilotes CUPS
    		echo "<FORM NAME = \"auth\" ACTION=\"config_printer.php\" METHOD=\"post\">\n";
    		echo "<TABLE BORDER=\"0\">\n";
    		echo "<TR>\n";
    		echo "<TD>".gettext("Nom")." :</TD>\n";
		
		// Si une modif on ne peut pas changer le nom
		if($nom_imprimante) {
			echo "<INPUT TYPE=\"hidden\" NAME=\"modif_imprimante\" VALUE=\"1\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"nom_imprimante\" VALUE=\"$nom_imprimante\">\n";
			echo "<TD COLSPAN=\"2\" VALIGN=\"top\">$nom_imprimante</TD>\n";
    			echo "<TD><u onmouseover=\"return escape".gettext("('Le nom de l\'imprimante ne peut pas &#234;tre chang&#233;..<br>Pour pouvoir le faire vous devez supprimer et recr&#233;er l\'imprimante')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></TD>\n";
			
		} else {	
    			echo "<TD COLSPAN=\"2\" VALIGN=\"top\"><INPUT TYPE=\"text\" MAXLENGTH=\"8\" SIZE=\"8\" NAME=\"nom_imprimante\" VALUE=$nom_imprimante></TD>\n";
    			echo "<TD><u onmouseover=\"return escape".gettext("('Indiquer un nom pour l\'imprimante.<BR>Celui-ci doit &#234;tre unique et limit&#233; &#224; 8 caract&#232;res.<BR>Dans le cas d\'une imprimante partag&#233;e, indiquez le nom de partage')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></TD>\n";
			
		}
    		echo "</TR>\n";

    		echo "<TR>\n";
    		echo "<TD>URI :</TD>\n";
    		echo "<TD COLSPAN=\"2\" VALIGN=\"top\"><INPUT TYPE=\"text\" SIZE=\"20\" NAME=\"uri_imprimante\" VALUE=$uri_imprimante></TD>\n";
    		echo "<TD><u onmouseover=\"return escape".gettext("('Indiquer ici l\'adresse IP ou le port local en fonction du protocole utilis&#233;.<BR>Dans le cas d\'une imprimante partag&#233;e, indiquez le nom du poste qui partage l\'imprimante. <br>Pour une imprimante IPP, vous pouvez egalement entrer l\'URI complete, par exemple ipp://172.16.100.113:631/A21-CA3 <br> dans ce cas choisissez <b>Personnalise</b> pour le protocole')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></TD>\n";
		
    		echo "</TR>\n";
    		echo "<TR>\n";
    		echo "<TD>".gettext("Emplacement")." :</TD>\n";
    		echo "<TD COLSPAN=\"2\" VALIGN=\"top\"><INPUT TYPE=\"text\" SIZE=\"20\" NAME=\"lieu_imprimante\" VALUE=$lieu_imprimante></TD>\n";
    		echo "<TD><u onmouseover=\"return escape".gettext("('Indiquer ici le lieu o&#151; l\imprimante est install&#233;e.<br>Cette information n\'est qu\'indicative.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></TD>\n";
    		echo "</TR>\n";
    		echo "<TR>\n";
    		echo "<TD>".gettext("Description")." :</TD>\n";
    		echo "<TD COLSPAN=\"2\" VALIGN=\"top\"><INPUT TYPE=\"text\"  SIZE=\"20\" NAME=\"info_imprimante\" VALUE=$info_imprimante></TD>\n";
    		echo "<TD><u onmouseover=\"return escape".gettext("('Description obligatoire')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></TD>\n";
		
    		echo "</TR>\n";
    		echo "</TABLE>\n";
    		echo "<BR>";
    		echo "<P><b>".gettext("S&#233lectionnez votre protocole : ");
		echo "<u onmouseover=\"this.T_STICKY=1;return escape".gettext("('Indiquer ici le protocole utilis&#233;.<br>Dans le cas d\'une imprimante partag&#233;e, indiquez Samba.<br>Dans le cas d\'une imprimante r&#233;seau, TCP/IP doit fonctionner dans la majorit&#233; des cas mais se r&#233;f&#233;rer &#224; la documentation de l\'imprimante permettra de choisir le meilleur protocole.<br>Si vous savez ce que vous faites, vous pouvez aussi<br>utiliser <b>Personnalis&#233</b> pour les imprimantes IPP :<br> dans ce cas entrez l\'URI exacte de l\'imprimante dans le champ URI, Par exemple ipp://172.16.1.1:631/truc <br>Vous pouvez &#233;galement consulter la <a href=http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Imprimantes TARGET=_blank>documentation Se3 en ligne</a> pour plus d\'informations.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u> ";
		echo "</b></P>\n";
		
		
        	echo "<SELECT NAME=\"protocole\">\n";
		echo "<OPTION VALUE=\"parallel\"";
		if($protoc=="parallel") { echo " selected";}
       		echo ">parall&#233;le</OPTION>";
		echo "<OPTION VALUE=\"usb\"";
		if($protoc=="usb") { echo " selected";}
       		echo ">Usb</OPTION>";
		echo "<OPTION VALUE=\"socket\"";
		if(($protoc=="socket") || ($protoc=="")) { echo " selected";}
       		echo ">TCP/IP</OPTION>";

		echo "<OPTION VALUE=\"http\"";
		if($protoc=="http") { echo " selected";}
       		echo ">HTTP</OPTION>";

		echo "<OPTION VALUE=\"ipp\"";
		if($protoc=="ipp") { echo " selected";}
       		echo ">IPP</OPTION>";

		echo "<OPTION VALUE=\"smb\"";
		if($protoc=="smb") { echo " selected";}
       		echo ">Samba</OPTION>";
		
		echo "<OPTION VALUE=\"lpd\"";
		if($protoc=="lpd") { echo " selected";}
       		echo ">Lpd/Lpr</OPTION>";
		
		echo "<OPTION VALUE=\"custom\"";
		if($protoc=="custom") { echo " selected";}
       		echo ">Personnalise</OPTION>";
		echo "</select>\n";

    		echo "<BR>";

		//drivers
    		echo "<P><b>".gettext("Choix du pilote d'impression : ");
		echo "</b>";
		echo "<u onmouseover=\"return escape".gettext("('S&#233;lectionner la fa&#231;on dont vous aller installer le driver :<br><b>Pilote windows du client d&#233ployable :</B> Soit le pilote de l\'imprimante est plac&#233; sur le serveur SambaEdu dans le partage drivers et s\'installe automatiquement ou bien vous l\'installez manuellement sur chaque poste, au choix<br><b>Pilote CUPS :</b> Vous utilisez le pilote CUPS, qui est d\'une qualit&#233; inf&#233;rieur (d&#233;conseill&#233;).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></P>";
    		echo "<TABLE BORDER=\"0\">\n";

		if($driver_printer) { 
			// client windows
    			if($driver_printer=="raw") {
			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" CHECKED VALUE=\"raw\"";
			echo ">".gettext("Pilote Windows du client")."<BR></TD>\n";
    			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Option obsolete, pr&#233f&#233rez le client d&#233ployable")."</FONT></TD></TR>\n";
    			}
			
			// client windows deployable
    			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" VALUE=\"dep\"";
			if($driver_printer=="dep") {echo " CHECKED ";}
			echo ">".gettext("Pilote Windows du client deployable")."<BR></TD>\n";
    			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Permet de deployer &#224; partir du serveur le client windows")."</FONT></TD></TR>\n";
			
			// client cups
			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" VALUE=\"cups\">";
			echo gettext("Pilote du serveur d'impression CUPS")."<BR></TD>\n";
    			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Qualit&#233 d'impression en g&#233n&#233rale inf&#233rieure")."</FONT></TD></TR>\n";

			// Ancien client cups
    			if (($driver_printer!="raw") && ($driver_printer!="dep")) { // un driver cups existe deja
				list(,$fabric_old,$driver_old) = preg_split('///',$driver_printer);
				list($driver_only,,) = preg_split('/./',$driver_old);
				echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" VALUE=\"$driver_printer\"";
				if($driver!="raw") {echo " CHECKED ";}
				echo ">".gettext("Pilote CUPS existant ").$driver_printer."<BR></TD>\n";
    				echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Qualit&#233 d'impression en g&#233n&#233rale inf&#233rieure")."</FONT></TD></TR>\n";
			}
		} else { // On cree une nouvelle imprimante
			// client windows
//     			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" VALUE=\"raw\">";
// 			echo gettext("Pilote Windows du client")."<BR></TD>\n";
//     			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Option par d&#233faut, installation manuelle du pilote")."</FONT></TD></TR>\n";
			
			// client windows deployable
    			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" CHECKED VALUE=\"dep\"";
			echo ">".gettext("Pilote Windows du client deployable")."<BR></TD>\n";
    			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Permet de deployer &#224; partir du serveur le client windows")."</FONT></TD></TR>\n";
			
			// client cups
    			echo "<TR><TD><INPUT TYPE=\"radio\" NAME=\"driver\" VALUE=\"cups\">";
			echo gettext("Pilote du serveur d'impression CUPS")."<BR></TD>\n";
    			echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Qualit&#233 d'impression en g&#233n&#233rale inf&#233rieure")."</FONT></TD></TR>\n";
		}		
		
		echo "</TABLE>\n";

		
    		echo "<P><b>".gettext("Correction de probl&#232;mes : ");
		echo "</b>";
		echo "<u onmouseover=\"return escape".gettext("('Permet de corriger d\'&#233;ventuels probl&#232;mes d\'impression')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></P>";
		echo "<TABLE BORDER=\"0\">\n";
		echo "<TR><TD><INPUT TYPE=\"checkbox\" NAME=\"imp_mode\"";
		if ($dev_mode == "on") echo " checked";
		echo ">";
                echo gettext("Correction pilote d&#233;faillant")."<BR></TD>\n";
                echo "<TD><FONT COLOR=\"orange\">&nbsp;".gettext("Dans le cas d'un pilote d&#233;ployable, si lors de la mise en place du pilote<BR>explorer.exe plante, cochez cette case...")."</FONT></TD></TR>\n";
		echo "</TABLE>\n";
    		echo "<BR>";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"config_printer\" VALUE=\"true\">\n";
    		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">\n";
    		echo "</FORM>\n";
    		if ($config_printer) {
        		if ( (!$nom_imprimante)||(!$uri_imprimante)||(!$lieu_imprimante) ) {
            			echo "<div class='error_msg'>".gettext("Vous devez obligatoirement renseigner les champs: Nom, URI, Emplacement, Description !")."</div><br>\n";
        		} elseif (1==1) {
				echo "<div class='error_msg'>".gettext("Vous devez saisir une adresse IP valide !")."</div><BR>\n";
        		}
    		}
	}
	//Affichage de la page de confirmation de l'installation de l'imprimante 
	elseif ($driver != "cups")  {
    		// Verification de l'existence de cette imprimante
		$nom_imprimante = stripslashes($nom_imprimante);
    		$printer_name =utf8_encode($nom_imprimante);
    		$printer_exist=search_printers("(&(printer-name=$printer_name)(objectClass=printerService))");
	
        	// Suppression des accents et des espaces que l'utilisateur a entres dans les champs
        	$nom_imprimante=enleveaccents($nom_imprimante);
        	$info_imprimante=enleveaccents($info_imprimante);
        	$lieu_imprimante=enleveaccents($lieu_imprimante);
		// Cas d'une modification
		if(($_POST["modif_imprimante"]=="1") && (count($printer_exist))) {
        		exec("/usr/share/se3/sbin/printerMod.pl $nom_imprimante $uri_imprimante $lieu_imprimante $info_imprimante $protocole $driver $imp_mode",$AllOutPut,$ReturnValue);
			


// echo "/usr/share/se3/sbin/printerMod.pl $nom_imprimante $uri_imprimante $lieu_imprimante $info_imprimante $protocole $driver";

			// Compte rendu de creation
			if ($ReturnValue==0) {
				exec("/usr/share/se3/sbin/printers_group.pl");
            			echo gettext("L'imprimante")." <B>$nom_imprimante</B> ".gettext("a &#233;t&#233; reconfigur&#233;e avec succ&#232;s")."<BR>";
				echo "<br><center>";
				echo "<a href=view_printers.php?one_printer=$nom_imprimante>Retour</a>";
				echo "</center>";
        		} else {
				echo "<div class='error_msg'>".gettext("Erreur lors de la modification de l'imprimante")." <B>$nom_imprimante</B><font color='black'>(".gettext("type d'erreur")." : $ReturnValue) </font>,".gettext("veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=".gettext("PB creation nouvelle imprimante Se3")."'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
       			}

		} else { // Si ce n'est pas une modification
			if (count($printer_exist))  {
				// Si la machine existe deja il faut changer le nom.
				echo "<div class='error_msg'>".gettext("Echec de cr&#233;ation : L'imprimante")." <font color=\"black\"> $nom_imprimante</font> ".gettext("est d&#233;ja pr&#233;sente dans l'annuaire.")."</div><BR>\n";
				echo "<br><center>";
				echo "<a href=\"config_printer.php\">Retour</a>";
				echo "</center>";
    			} else {
				// Sinon on la cree
        			// Ecriture de la configuration dans CUPS et LDAP

//      echo "/usr/share/se3/sbin/printerAdd.pl $nom_imprimante $uri_imprimante $lieu_imprimante $info_imprimante $protocole $driver";
				exec("/usr/share/se3/sbin/printerAdd.pl $nom_imprimante $uri_imprimante $lieu_imprimante $info_imprimante $protocole $driver $imp_mode",$AllOutPut,$ReturnValue);
        			// Compte rendu de creation
				if ($ReturnValue==0) {
            				echo gettext("L'imprimante")." <B>$nom_imprimante</B> ".gettext("a &#233;t&#233; configur&#233;e avec succ&#232;s")."<BR>";
					echo "<A HREF=add_printer.php>";
					echo gettext("Ajouter l'imprimante &#224; un parc");
					echo "</A>";
        			} else {
					echo "<div class='error_msg'>".gettext("Erreur lors de la cr&#233;ation de l'imprimante ")." <B>$nom_imprimante</B><font color='black'> (".gettext(" type d'erreur")." : $ReturnValue) </font>,<br>".gettext("veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=".gettext("PB creation nouvelle imprimante Se3")."'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";

					echo "<br><center>";
					echo "<a href=config_printer.php>Retour</a>";
					echo "</center>";
        			}
    			}
		}


	// A partir d'ici on propose le choix d'un driver CUPS
	} elseif(isset($driver) && ($driver=="cups")) {
        	// Retourne le nombre de pilotes
        	$nb_drivers=exec("/usr/sbin/lpinfo -m | wc -l");
        	// Retourne les fabiquants des pilotes
		$return=exec ("/usr/sbin/lpinfo -m | cut -d\" \" -f2",$fab_drivers);
		//Affichage du formulaire de selection du fabriquant.
		if (!isset($fabriquant)) {
			echo "<H3>".gettext("S&#233lectionnez la marque de l'imprimante")."</H3>\n";
        		echo "<FORM ACTION=\"config_printer.php\" METHOD=\"post\">\n";
        		echo "<SELECT NAME=\"fabriquant\" SIZE=\"15\">\n";
        		for ($i=1;$i<$nb_drivers;$i++) {
				$fab_drivers[$i]=strtoupper($fab_drivers[$i]);
				if ($fab_drivers[$i] != $fab_drivers[$i-1]) {
					echo "<OPTION VALUE=\"$fab_drivers[$i]\">$fab_drivers[$i]";
            				echo "</OPTION>";
        			}
			}
        		echo "</SELECT>\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$info_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"uri_imprimante\" VALUE=\"$uri_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"nom_imprimante\" VALUE=\"$nom_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"lieu_imprimante\" VALUE=\"$lieu_imprimante\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"driver\" VALUE=\"cups\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"protocole\" VALUE=\"$protocole\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"modif_imprimante\" VALUE=\"$modif_imprimante\">\n";
       			echo "<BR><BR>\n";
        		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\"><BR>\n";
        		echo "</FORM>\n";
		}
		//Affichage du formulaire de selection des pilotes pour le fabriquant choisi.
		else {
			echo "<H3>".gettext("S&#233lectionnez le pilote de l'imprimante")."</H3>\n";		
			$return=exec ("/usr/sbin/lpinfo -m | cut -d\" \" -f1",$ppd_drivers);
			$return=exec ("/usr/sbin/lpinfo -m | cut -d\" \" -f3-",$name_drivers);
			echo "<FORM ACTION=\"config_printer.php\" METHOD=\"post\">\n";
        		echo "<SELECT NAME=\"driver\" SIZE=\"15\">\n";
        		for ($i=0;$i<$nb_drivers;$i++) {
				$fab_drivers[$i]=strtoupper($fab_drivers[$i]);
				if ($fabriquant == $fab_drivers[$i]) { 
					echo "<OPTION VALUE=\"$ppd_drivers[$i]\">$name_drivers[$i]";
            				echo "</OPTION>\n";
				}
        		}
        		echo "</SELECT>\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$info_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"uri_imprimante\" VALUE=\"$uri_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"nom_imprimante\" VALUE=\"$nom_imprimante\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"lieu_imprimante\" VALUE=\"$lieu_imprimante\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"protocole\" VALUE=\"$protocole\">\n";
			echo "<INPUT TYPE=\"hidden\" NAME=\"modif_imprimante\" VALUE=\"$modif_imprimante\">\n";
        		echo "<BR><BR>\n";
        		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\"><BR>\n";
        		echo "</FORM>\n";
		}
	}
} else {
	echo "<div class=error_msg>".gettext("Cette application, n&#233cessite les droits d'administrateur du serveur Se3 !")."</div>";
}

include ("pdp.inc.php");
?>
