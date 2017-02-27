<?php

   /**
   
   * Permet de definir des alertes de supervision 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Philippe Chadefaux
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: alertes 
   * file: alertes.php

  */	

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
//require "dbconfig.inc.php";
if ($inventaire=="1") { // Si inventaire on on inclu le fichier de conf
    include_once "dbconfig.inc.php";  
}

$action=$_POST['action'];
if (!$action) { $action=$_GET['action']; }

foreach($_GET as $key => $valeur)
	$$key = $valeur;

if (is_admin("computers_is_admin",$login)=="Y") {

  //aide 
  $_SESSION["pageaide"]="L\'interface_web_administrateur#Gestion_des_alertes";

  echo "<H1>".gettext("Cr&#233;ation d'alertes")."</H1>\n";


  // Supprime une alerte perso
  if ($action=="suppr") {
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
	$query_info="SELECT * FROM alertes WHERE ID='$ID';";
	$result_info=mysqli_query($authlink, $query_info);
	$row = mysqli_fetch_array($result_info);

	$query_suppr="DELETE FROM alertes WHERE ID='$ID'";
	$result_suppr=mysqli_query($authlink, $query_suppr) or die("Erreur lors de la suppression de l'alerte");
	if ($result_suppr) {
		echo "<center>";
		echo gettext("L'alerte ").$row['NAME'].gettext(" a &#233;t&#233; supprim&#233;e.");
		echo "</center>";
	} else {
		echo "<center>";
		echo gettext("La suppression de l'alerte a &#233;hou&#233;e");
		echo "</center>";
	}
  }


  // Selectionne le type de alerte a ajouter (Systeme ou inventaire)
  if ($action == "new_perso_select") {
	echo "<BR><BR>";
	echo "<CENTER><TABLE border=1 width=\"60%\">";
	
	echo "<TR>\n";
	  echo "<TD class=\"menuheader\" height=\"30\" align=center colspan=\"5\">".gettext("Type d'alertes")."</TD>\n";
	echo "</TR>\n";
	
	echo "<TR>\n";
	  echo "<td align=\"center\">".aide('Pour d&#233;finir une nouvelle alerte',"<IMG border=\"0\" src=\"../elements/images/edit.png\" alt=\"Help\">")."</td>\n";
	  echo "<TD>".gettext("Alerte syst&#232;me")."</TD>\n";
	  echo "<TD align=center colspan=2>";
	  
	  echo "<FORM ACTION=\"config_alert_system.php\" method=\"get\" name=\"form_action\">\n";
	  echo "<input type=\"hidden\" name=\"action\" value=\"new\">\n";
	  echo "<input value=\"Ajouter\" name=\"action_image\" type=\"submit\"></FORM></TD>";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Les alertes syst&#232;mes</B> sont les alertes qui permettent de surveiller le serveur, &#233;ventuellement d\'autres machines.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u></TD>\n";  
	echo "</TR>\n";

	include_once("config.inc.php");
	if ($inventaire=="1") { // Si inventaire desactive plus d'alerte
	
		echo "<TR>\n";
		echo "<td align=\"center\">".aide('Pour d&#233;finir une nouvelle alerte',"<IMG border=\"0\" src=\"../elements/images/edit.png\" alt=\"Help\">")."</td>\n";
		echo "<TD>".gettext("Alerte sur l'inventaire")."</TD><TD align=center colspan=2>";
		
		echo "<FORM ACTION=\"config_alert.php\" method=\"get\" name=\"form_action_2\">\n";
		echo "<input type=\"hidden\" name=\"action_hidden\" value=\"new\">\n";
		echo "<input value=\"Ajouter\" name=\"action_image\" type=\"submit\"></FORM></TD>\n";
		echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Les alertes inventaire</B> vous permettent de surveiller les clients &#224; partir de l\'inventaire remont&#233;, par exemple quel client ne dispose pas de l\'anti-virus ou bien quel client a msn d\'install&#233;.')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u></TD>\n";
		echo "</TR>\n";
	}
	
	echo "</TABLE></CENTER>\n";	
	
	include ("pdp.inc.php");
	exit;
  } 	


  // Ajout - modification
  if ($action=="mod2") {
	if(($name_alert=="") || ($text_alert=="") || ($script_alert=="")) {
		echo "<center>";
		echo gettext("Erreur une donn&#233;e est manquante");
		echo "</center>";
	} else	{
		// Verifie si le script existe dans /usr/share/se3/scripts-alertes
		list($script_alert_exist, $options)=explode(" ",$script_alert);
		if( ! file_exists("/usr/share/se3/scripts-alertes/$script_alert_exist")) {
			echo "<center>";
			echo gettext("Erreur le script ne semble pas exister dans /usr/share/se3/scripts-alertes");
			echo "</center>";
		} else {
			//tout est ok on peut accepter
			$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
			@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
	
	
			if ($ID !="") {
				$query="UPDATE alertes SET NAME='$name_alert', TEXT='$text_alert', MAIL='$mail_alert' ,SCRIPT='$script_alert' ,ACTIVE='$active_alert',FREQUENCE='$frequence_alert',MAIL_FREQUENCE='$frequence_mail' WHERE ID='$ID';";
			} else {
				$query="INSERT INTO alertes (`ID`,`NAME`,`MAIL`,`Q_ALERT`,`VALUE`,`CHOIX`,`TEXT`,`AFFICHAGE`,`VARIABLE`,`PREDEF`,`MENU`,`ACTIVE`,`SCRIPT`,`PARC`,`FREQUENCE`,`PERIODE_SCRIPT`,`MAIL_FREQUENCE`) VALUES ('NULL','$name_alert','$mail_alert','$q_alert_alert','$value_alert','$choix_alert','$text_alert','1','$variable_alert','0','','$active_alert','$script_alert','','$frequence_alert','',$frequence_mail)";

			}
			$result=mysqli_query($authlink, $query) or die("Erreur lors de la modification de l'alerte");

			if ($result) {
				echo "<center>";
				echo gettext("L'alerte ")." $name_alert. ".gettext(" a &#233;t&#233; modifi&#233;e.");
				echo "</center>";
			} else {
				echo "<center>";
				echo gettext("La modification de l'alerte a &#233;chou&#233;e");
				echo "</center>";
			}
		}
	}	
  }

  // Modifie la table pour rendre une alerte active ou non
  if ($action == "conf_mail") {
	// connexion a la base
    	include "config.inc.php";
    	$auth = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
    	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
    	$query="select NAME,ID from alertes where PREDEF='1'";
    	$resultat=mysqli_query($auth, $query);
    	$ligne=mysqli_num_rows($resultat);
    	if ($ligne > "0") {
		while($row = mysqli_fetch_array($resultat)) {
            		$test = $$row[1];
            		if ($test == "on")   { $active="1"; } 
            		if ($test == "")  { $active="0"; }
            		$query1="UPDATE alertes SET ACTIVE='$active' WHERE ID='$row[1]' AND PREDEF='1'";
			$result=mysqli_query($GLOBALS["___mysqli_ston"], $query1);
        	}
    	}
  }


  // Test si l'expedition de mail est configuree
  $fichier="/etc/ssmtp/ssmtp.conf";

  if ( ! file_exists($fichier)) {
	echo "<CENTER><font color=\"#FFA500\"><u>".gettext("Attention :")." </u>".gettext("Il n'est pas possible d'envoyer des messages via la messagerie.<BR> Vous devez");
    	echo"<a href=../conf_smtp.php>"; 
    	echo gettext(" configurer l'exp&#233;dition des mails,");
    	echo "</a>"; 
    	echo gettext(" pour pouvoir utiliser cette fonctionnalit&#233;")."</font><BR>";
	echo "</CENTER>";
  }  




// Affichage sous forme de 2 tableaux (alertes predef et alertes perso 
include "config.inc.php";
$auth = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
echo "<BR><BR>";

echo "<FORM ACTION=\"alertes.php\" method=\"get\" >";
echo "<INPUT TYPE=\"hidden\" NAME=\"action\" VALUE=\"conf_mail\">";
echo "<CENTER><TABLE border=1 width=\"60%\">";

echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"4\">".gettext("Alertes pr&#233;d&#233;finies")."</TD></TR>\n";
$query="select ID,TEXT,VARIABLE,ACTIVE,NAME  from alertes where PREDEF='1'";
$resultat=mysqli_query($auth, $query);
$ligne=mysqli_num_rows($resultat);
if ($ligne > "0") {
	while($row = mysqli_fetch_array($resultat)) {
	        $ajout=""; $statut=""; 
        	if ($row['ACTIVE'] == "1") {   
			$ajout=" CHECKED"; 
                	$statut=aide('D&#233;cocher la case de droite pour d&#233;sactiver cette alerte',"<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\" ALT=\"Alerte active\">");
       		}   else { 
        		$statut=aide('Cocher la case de droite pour activer cette alerte',"<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/disabled.png\" ALT=\"Alerte inactive\">");
       		}
       		echo "<TR><td>$statut</td><TD>$row[1]</TD><TD align=center><INPUT TYPE=CHECKBOX NAME=\"$row[0]\" $ajout></TD></TR>\n";
	}
}   

echo "<TR><TD colspan=\"4\" align=center><INPUT TYPE=\"submit\" value=\"".gettext("Valider")."\"></TD></TR>\n";
echo "</TABLE></CENTER><BR><BR>\n"; 
echo "</FORM>\n";

/* Creation d'alertes */
echo "<CENTER><TABLE border=1 width=\"60%\">";
echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"4\">".gettext("Mes alertes")."</TD></TR>\n";
echo "<TR><td align=\"center\">".aide('Pour d&#233;finir une nouvelle alerte',"<IMG border=\"0\" src=\"../elements/images/edit.png\" alt=\"Editer\">")."</td><TD>".gettext("Ajout d'une alerte")."</TD><TD align=center colspan=2>";

echo "<FORM ACTION=\"alertes.php\" method=\"get\" name=\"form_action\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"new_perso_select\">\n";
echo "<input value=\"Ajouter\" name=\"action_image\" type=\"submit\">\n";
echo "</FORM>\n";
echo "</TD></TR>\n";

// Les alertes existantes
//mysql_close();
// $authlink = mysql_connect($dbhost,$dbuser,$dbpass);
// @mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");
$query_info="SELECT * FROM alertes where PREDEF='0';";
$result_info=mysqli_query($authlink, $query_info);
while ($row = mysqli_fetch_array($result_info)) {
       // Si l'alete est active ou non
       if ($row["ACTIVE"]=="1") {
               $statut=aide('L\&#039;alerte est actuellement activ&#233;e.Pour d&#233;activer cette alerte, cliquer sur l\&#039;icone Modifier <IMG  style=\&#034;border: 0px solid ;\&#034; SRC=\&#034;../elements/images/zoom.png\&#034; ALT=\&#034;Modifier\&#034;> et choisissez Alerte active, Non',"<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\" ALT=\"Alerte active\"></a>");
       } else {
		$statut=aide('L\&#039;alerte est actuellement d&#233;sactiv&#233;e.Pour activer cette alerte, cliquer sur l\&#039;icone Modifier <IMG  style=\&#034;border: 0px solid ;\&#034; SRC=\&#034;../elements/images/zoom.png\&#034; ALT=\&#034;Modifier\&#034;> et choisissez Alerte active, Oui',"<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/disabled.png\" ALT=\"Alerte inactive\">");
       }
       
       	echo "<tr><td align=\"center\">$statut&nbsp;</td><td>".$row["TEXT"]."</td>\n";
       	echo "<TD align=\"center\"><a href=\"alertes.php?action=suppr&amp;ID=".$row["ID"]."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/edittrash.png\" ALT=\"Supprimer\"></a></TD>\n";

	// Selectionne le type d'alertes systeme ou inventaire
	if ($row["MENU"]=="inventaire") {
		echo "<TD align=\"center\"><a href=\"config_alert.php?action_hidden=mod&amp;ID=".$row["ID"]."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/zoom.png\" ALT=\"Modifier\"></a></TD>\n";
        } else {
		echo "<TD align=\"center\"><a href=\"config_alert_system.php?action=new&amp;ID=".$row["ID"]."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/zoom.png\" ALT=\"Modifier\"></a></TD>\n";
	}	
	echo "</tr>\n";
  }
  echo "</table></center>";        


}

include ("pdp.inc.php");
?>
