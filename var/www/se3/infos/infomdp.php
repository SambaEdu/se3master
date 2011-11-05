<?php

   /**
   
   * Test si les mots de passe ont ete change
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: infomdp.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");

require("config.inc.php");
require("ldap.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

// aide en ligne
$_SESSION["pageaide"]="Annuaire";


if (is_admin("annu_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
echo "<H1>".gettext("Test des mots de passe")."</H1>";

$classe_gr=$_POST['classe_gr'];
$equipe_gr=$_POST['equipe_gr'];
$matiere_gr=$_POST['matiere_gr'];
$autres_gr=$_POST['autres_gr'];

// creation de smbwebopen_pwd_chg dans mysql table params si besoin
$resultat=mysql_query("select value from params where name='smbwebopen_pwd_chg'");
$line = mysql_fetch_assoc($resultat);
if ( $line == "" )
  {mysql_query("INSERT INTO params VALUES ('', 'smbwebopen_pwd_chg', '0', '0', 'Droit smbweb_is_open si mot de passe chang&#233;', '5')");}

// actualisation mysql pour l'option smbweb_open_for_passwd_changed en fonction du choix utilisateur
$validation=$_GET['validation'];
$smbwebisopenforpasswdchanged=$_POST['smbwebisopenforpasswdchanged'];
if ($smbwebisopenforpasswdchanged == "on") {
	        $query="UPDATE params SET value=\"1\" WHERE name=\"smbwebopen_pwd_chg\";";
	        mysql_query($query);
  	} else {
            if (isset($validation)) {
        	$query="UPDATE params SET value=\"0\" WHERE name=\"smbwebopen_pwd_chg\";";
        	mysql_query($query);
            }
  	}


// on teste les mdp pour chaque groupe demande !!!
                if (count($classe_gr) ) {
			foreach ($classe_gr as $grp){
                                echo "<h4>".gettext("Liste des membres du groupe ".$grp." n'ayant jamais chang&#233; leur mot de passe&nbsp;:")."</h4>";
				echo "<PRE class=listing>";
                                system ("/usr/share/se3/sbin/testmdp.sh $grp");
                                echo "</PRE>";
                                echo "<hr>";
			}
                }

  		if (count($equipe_gr) ) {
			foreach ($equipe_gr as $grp){
                                echo "<h4>".gettext("Liste des membres du groupe ".$grp." n'ayant jamais chang&#233; leur mot de passe&nbsp;:")."</h4>";
				echo "<PRE class=listing>";
                                system ("/usr/share/se3/sbin/testmdp.sh $grp");
                                echo "</PRE>";
                                echo "<hr>";
			}
                }


  		if (count($matiere_gr) ) {
			foreach ($matiere_gr as $grp){
                                echo "<h4>".gettext("Liste des membres du groupe ".$grp." n'ayant jamais chang&#233; leur mot de passe&nbsp;:")."</h4>";
				echo "<PRE class=listing>";
                                system ("/usr/share/se3/sbin/testmdp.sh $grp");
                                echo "</PRE>";
                                echo "<hr>";
			}
  		}
						   

  		if (count($autres_gr) ) {
			foreach ($autres_gr as $grp){
                                echo "<h4>".gettext("Liste des membres du groupe ".$grp." n'ayant jamais chang&#233; leur mot de passe&nbsp;:")."</h4>";
				echo "<PRE class=listing>";
                                system ("/usr/share/se3/sbin/testmdp.sh $grp");
                                echo "</PRE>";
                                echo "<hr>";
			}
  		}


// on propose de tester d'autres groupes en bas de page
        echo "<FORM ACTION=\"infomdp.php?validation=yes\" method=\"post\">\n";
	echo "<h4>".gettext("Lister, parmi les groupes suivants, les utilisateurs ayant conserv&#233; leur date de naissance comme mot de passe&nbsp;:");
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('ATTENTION: cette op&#233;ration est assez longue... Ciblez votre recherche si possible.')")."\"><img name=\"action_image5\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</h4>\n";
        
        
//option supplementaire proposee par le script smbweb_is_open_for_passwd_changed.sh
        echo "<h3>".gettext("Attribuer automatiquement le droit smb_web_is_open pour tout utilisateur ayant chang&#233; son mot de passe initial&nbsp;:");
        echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('L attribution de ce droit est actualis&#233;e tous les soirs vers 18H30. Un d&#233;lai de 24H maximum est donc n&#233;cessaire pour acc&#233;der aux documents depuis internet. Remarque: ce droit est retir&#233; en cas de r&#233;initialisation du mot de passe !')")."\"><img name=\"action_image5\"  src=\"../elements/images/system-help.png\"></u>";
        
 	$objet_var="<input type=\"checkbox\"";
        $resultat=mysql_query("select value from params where name='smbwebopen_pwd_chg'");
        $line = mysql_fetch_assoc($resultat);
	foreach ($line as $col_value) {
		if ( "$col_value" == "1" )
                $objet_var="$objet_var checked ";
	}
 	$objet_var="$objet_var name=\"smbwebisopenforpasswdchanged\">";
        echo "$objet_var";
        echo "</h3>";
// fin de l'option smbweb_is_open_for_passwd_changed


	// Etablissement des listes des groupes disponibles
	affiche_all_groups(center, none);

echo "<div id=\"attribution\" align='center'><input type=\"submit\" value=\"".gettext("Valider")."\">
<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
echo "</form>";
echo "</center>";

include ("pdp.inc.php");

?>

