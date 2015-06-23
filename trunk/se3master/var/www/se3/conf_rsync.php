<?php

   /**
   
   * Permet configurer rsync afin de pouvoir sauvegarder le serveur depuis un autre
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: conf_rsync.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

// Fonction pour obtenir les valeurs deja definies dans rsyncd.conf

/**
* Fonction pour obtenir les valeurs deja definies dans rsyncd.conf

* @Parametres $name 
* @Return Retourne  la valeur de name contenue dans rsyncd.conf
	
*/
	

function variable ($Name) { // retourne la valeur de Name
	if (file_exists("/etc/rsyncd.conf")) {
		$lignes = file("/etc/rsyncd.conf");
		foreach ($lignes as $num => $ligne) {
                        if (preg_match ("/$Name=(.*)/",$ligne,$reg)) {
				$var = trim($reg[1]);
				return $var;
			}
		}
	}	
} // fin function
		

//aide 
$_SESSION["pageaide"]="Sauvegarde_client_Linux#Cas_particulier_:_Sauvegarder_un_serveur_Se3_distant";

echo "<H1>".gettext("Configure client sauvegarde ")."</H1>\n";

if (is_admin("system_is_admin",$login)=="Y") {
	
	// Stop ou start rsync
   	if ($_GET['action']=="stop") {
   		exec("sudo /usr/share/se3/scripts/mk_rsyncconf.sh stop");
		sleep(5);
   	} elseif($_GET['action']=="start") {	
   		exec("sudo /usr/share/se3/scripts/mk_rsyncconf.sh start");
		sleep(10);
	}	
   
   	// Creation du fichier de conf de rsyncd.conf 
   	elseif ($_GET['action'] == "rsync_mod") {

		if ($_GET['dc_read']!="no") {$_GET['dc_read']="yes"; }
	
		$fichier = "/tmp/rsyncd.conf";
		$fp=fopen("$fichier","w+");
		$DEFAUT = "
uid=root
gid=root
use chroot=no
syslog facility=local5
pid file=/var/run/rsyncd.pid
auth users=".$_GET['dc_user']."
secrets file=/etc/rsyncd.secret
hosts allow=".$_GET['dc_serveur']."
read only=".$_GET['dc_read']."";


		// Creation des modules a partir des repertoires a sauvegarder
		$modules = preg_split("/;/",$_GET['dc_modules'],-1);
		for ($i=0; $i < count($modules); $i++) {
		
			$rep_module = "$modules[$i]";
			$nom_module = str_replace("/","",$modules[$i]);
			$DEFAUT .= "

## $nom_module ; $rep_module 
[$nom_module]
	comment = repertoire $rep_module
	path = $rep_module";
	
		}	
	
		fwrite($fp,$DEFAUT);
		fclose($fp);
		
		
		// On lance le script de conf
   		exec("sudo /usr/share/se3/scripts/mk_rsyncconf.sh start '$_GET[dc_user]' '$_GET[dc_pass]'");
		unset($action);
   	}

	// test la presence du paquet
	$rsync = exec("dpkg -l | grep rsync  > /dev/null && echo ok");

	// Si deja installe
	if ($rsync == "ok") {
		echo "<br><br>";

		echo "<form method=\"get\" action=\"conf_rsync.php\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"rsync_mod\">";
		echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\" >\n";
		
		echo "<tr><td colspan=\"3\" align=\"center\"  class=\"menuheader\" height=\"30\">".gettext("Activation du client de sauvegarde ")."</td></tr>\n";


		// test si rsync est actif
		$rsync_actif = exec("netstat -na | grep 0.0.0.0:873  > /dev/null && echo ok");
		
		echo "<tr>\n";
		echo "<td>".gettext("Etat")."</td>\n";
		echo "<td align=\"center\">";
		if ($rsync_actif=="ok") {
 			echo "<u onmouseover=\"this.T_WIDTH=200;return escape".gettext("('<b>Etat : actif</b><br>Permet de bloquer la sauvegarde, sans supprimer la configuration. Cela peut g&#233;n&#233;rer des messages d\'erreur sur le serveur.')")."\">";
         		echo "<a href=conf_rsync.php?action=stop><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\"  alt=\"Enabled\"></a>";
	         	echo "</u>";
		 } else {
		         echo "<u onmouseover=\"this.T_WIDTH=200;return escape".gettext("('<b>Etat : inactif</b><br>Permet de r&#233;activer la sauvegarde, sans changer la configuration.')")."\">";
			 echo "<a href=conf_rsync.php?action=start><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\"  alt=\"Disabled\"></a>";
			 echo "</u>";
		}

		echo "<td align=\"center\"><u onmouseover=\"this.T_WIDTH=200;return escape".gettext("('Permet d\'activer ou de d&#233;sactiver le client de sauvegarde.')")."\"><img name=\"action_image1\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u></td>";
		echo "</tr>\n";
		
		echo "<tr><td colspan=\"3\" align=\"center\"  class=\"menuheader\" height=\"30\">".gettext("Configuration du client de sauvegarde ")."</td></tr>\n";
		$dc_user = variable ("auth users");
		echo "<tr>";
     		echo "<td>".gettext("Compte de connexion :")."</td>";
      		echo "<td align=\"center\"><input name=\"dc_user\" type=\"text\" size=\"40\" value=\"$dc_user\"  ></td>\n";
		echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici un compte de connexion. Il devra &#234;tre indiqu&#233; avec le mot de passe sur le serveur Backuppc.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"  alt=\"Help\"></u></td>";
      		echo "</tr>\n";
		     
		$dc_pass = exec("sudo /usr/share/se3/scripts/mk_rsyncconf.sh pass"); 
		echo "<tr>\n";
        	echo "<td>".gettext("Mot de passe")." :</td>";
       // 	if ($dc_smarthost == "") { $dc_smarthost = "$slisip"; }
        	echo "<td align=\"center\"><input name=\"dc_pass\" type=\"text\" size=\"40\" value=\"$dc_pass\"  ></td>\n";
		echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici le mot de passe associ&#233; avec le compte de connexion.<br>Il devra &#234;tre indiqu&#233; sur le serveur Backuppc.')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\"  alt=\"Help\"></u></td>";
        	echo "</tr>\n";
		      
		$dc_serveur = variable ("hosts allow");
		echo "<tr>\n";
        	echo "<td>".gettext("Serveur Backuppc")." :</td>";

       //		$dc_serveur = variable ("hosts allow");
       		echo "<td  align=\"center\"><input name=\"dc_serveur\" type=\"text\" size=\"40\" value=\"$dc_serveur\" ></td>";
		echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer l\'adresse IP du serveur backuppc autoris&#233; &#224; faire la sauvegarde de cette machine.')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"  alt=\"Help\"></u></td>";
      		echo "</tr>\n";

		// Permet de restaurer
		$dc_read = variable ("read only");
		echo "<tr>\n";
        	echo "<td>".gettext("Restaurer")." :</td>";
       		echo "<td align=\"center\"><input name=\"dc_read\" type=\"checkbox\" value=\"no\"";
		if($dc_read=="no") {echo " checked"; }
		echo "></td>";
		echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('<b>Autoriser la restauration :</b><br>La croix indique que la restauration est autoris&#233;e.<br>Par mesure de s&#233;curit&#233;, il est souhaitable de ne l\'activer qu\'en cas de besoin.')")."\"><img name=\"action_image5\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u></td>";
      		echo "</tr>\n";


		echo "<tr><td colspan=\"3\" align=\"center\"  class=\"menuheader\" height=\"30\">".gettext("R&#233;pertoires &#224; sauvegarder")."</td></tr>\n";
		// Les modules existants
		if (file_exists("/etc/rsyncd.conf")) {
			$lignes = file("/etc/rsyncd.conf");
			$dc_modules="";
			foreach ($lignes as $num => $ligne) {
                                if (preg_match ("/##(.*)/",$ligne,$reg)) {
					$var = trim($reg[1]);
					list($nom_module,$rep_module)=preg_split('/;/',$var);
                                        if ($nom_module != "") {	
						echo "<tr>\n";
        					echo "<td> $nom_module</td>";
						echo "<td align=\"center\">$rep_module</td>";

						echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('<b>Nom du module</b><br>Vous devez indiquer le nom <b>$nom_module</b> dans l\'interface du serveur de sauvegarde')")."\"><img   src=\"../elements/images/system-help.png\" alt=\"Help\"></u></td>";
						echo "</tr>\n";	
						if ($dc_modules!="") {$dc_modules.=";"; }
						if ($rep_module != "") {
							$dc_modules.=trim($rep_module);
						}
					}	
				}
			}	
		}
		
		// Les repertroires
		echo "<tr>\n";
        	echo "<td>".gettext("R&#233;pertoires &#224; sauvegarder")." :</td>";
		if($dc_modules=="") {$dc_modules="/home;/etc;/var/se3";}
       		echo "<td  align=\"center\"><input name=\"dc_modules\" type=\"text\" size=\"40\" value=\"$dc_modules\" ></td>";
		echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer les r&#233;pertoires qui peuvent &#234;tre sauvegard&#233;s.<br><br>On conseille par d&#233;faut /home, /var/se3 et /etc.<br>Le s&#233;parateur est le  point virgule.')")."\"><img name=\"action_image7\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u></td>";
      		echo "</tr>\n";

			
		echo "</table>\n";
		echo "<br><br>";
		echo "<center><input type=\"submit\"  value=\"".gettext("Valider")."\"></center>";
		echo "</form>\n";      
	} else {
		echo "<br><br>";
		echo "<center>";
		echo gettext("Le paquet rsync ne semble pas install&#233; sur la machine");
		echo "<BR><BR>";
		echo gettext("Vous devez d'abord ex&#233;cuter sur le serveur un apt-get install rsync ");
		echo "</center>";
	}	
} else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");
?>
