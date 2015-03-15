<?php

   /**
   
   * Retourne des statistiques sur les repertoires utilisateurs
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: stats_user.php

  */	

require ("entete.inc.php");

require ("entete.inc.php");
require ("ihm.inc.php");
require ("config.inc.php");
require ("ldap.inc.php");

// Internationalisation
require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');


    
$partition=$_GET['partition'];
$uid=$_GET['uid'];

$login=isauth();
if ($login == "") die (gettext("Vous n'avez pas les droits suffisants pour acc&#233der &#224 cette fonction")."</BODY></HTML>");
//header("Location:$urlauth");

echo  "<h1>".gettext("Statistiques sur le dossier")." $partition ".gettext("de")." $uid </h1>";

// =======================================
// Affichage d'un lien de rafraichissement du cadre.
if(file_exists('/etc/se3/temoin_test_refresh.txt')){
	echo "<div style='position:fixed; top:5px; left:5px; width:20px; height:20px; border:1x solid black;'>\n";
	echo "<a href='".$_SERVER['PHP_SELF']."?partition=$partition&amp;uid=$uid'><img src='../elements/images/rafraichir.png' width='16' height='16' border='0' alt='Rafraichir' /></a>\n";
	echo "</div>\n";
}
// =======================================

if (is_admin("system_is_admin",$login)!="Y") {  //securite pour empecher un non admin de voir l'espace occupe par un autre que lui
	echo "<U>".gettext("Taille des fichiers sur")." $partition</U> :<BR>";
	system ("sudo /usr/share/se3/scripts/stats_user.sh $partition $login ");
} else   {
	list($user, $groups)=people_get_variables($uid, true);
  	echo "<H3>".$user["fullname"]."</H3>\n";
  	if ($user["description"]) echo "<p>".$user["description"]."</p>";
  	if ( count($groups) ) {
    		echo "<U>".gettext("Membre des groupes")."</U> :<BR><UL>\n";
    		for ($loop=0; $loop < count ($groups) ; $loop++) {
      		//echo "<LI>";
      		//if (is_admin("Annu_is_admin",$login) == "Y" ) echo "<A href=\"../annu/group.php?filter=".$groups[$loop]["cn"]."\">";
      			if ($groups[$loop]["type"]=="posixGroup")
        			echo "<STRONG>".$groups[$loop]["cn"]."</STRONG>";
      			else
        			echo $groups[$loop]["cn"];
        			//if (is_admin("Annu_is_admin",$login) == "Y" ) echo "</A>";
        			echo ", ";
      			if (is_admin("Annu_is_admin",$login) == "Y" ) {
        			//Recuperation de tous les groupes de l'utilisateur
         			$cn=$cn."&cn".$loop."=".$groups[$loop]["cn"];
      			}
    		}
    		echo "</UL>";
  	}
  	echo "<hr><U>".gettext("Taille des fichiers sur")." $partition</U> :<BR>";
  	system ("sudo /usr/share/se3/scripts/stats_user.sh $partition $uid");
}

require ("pdp.inc.php");
?>
