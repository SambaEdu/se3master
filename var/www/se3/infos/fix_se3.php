<?php

   /**
   
   * Correction de problemes
   * @Version $Id: savstatus.php 4187 2009-06-19 09:22:12Z gnumdk $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Cedric Bellegarde

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: se3_fix.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

echo "<h1>".gettext("Correction de probl&#232;mes")."</h1>\n";
if (isset($_GET['action'])) {
    if ($_GET['action'] == "rmprofiles") {
        echo "<h2>".gettext("Nettoyage des profils Windows...")."</h2>";
        system("sudo /usr/share/se3/scripts/clean_profiles.sh");
    }
    if ($_GET['action'] == "permse3") {
        echo "<h2>".gettext("Remise en place des droits syst&#232;me...")."</h2>";
        system("sudo /usr/share/se3/scripts/permse3");
        echo "Termin&#233;.";
    }
    if ($_GET['action'] == "restore_droits") {
        echo "<h2>".gettext("Remise en place des droits sur les comptes utilisateurs...")."</h2>";
        system("sudo /usr/share/se3/scripts/restore_droits.sh --home html");
    }
    if ($_GET['action'] == "restore_droits_full") {
        echo "<h2>".gettext("Remise en place de tous les droits...")."</h2>";
        system("sudo /usr/share/se3/scripts/restore_droits.sh acl_default auto html");
    }
}
else {
    echo "<a href=\"fix_se3.php?action=rmprofiles\" onclick=\"return getlongconfirm();\">".gettext("Supprimer l'ensemble des profils Windows")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des lenteurs de connexions')")."\"><img name=\"action_image1\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=permse3\" onclick=\"return getlongconfirm();\">".gettext("Remise en place des droits syst&#232;me par d&#233;faut")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des disfonctionnement dans l\'interface ou lors des connexions')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=restore_droits\" onclick=\"return getlongconfirm();\">".gettext("Remise en place des droits sur les comptes utilisateurs")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des problemes de droits pour les utilisateurs')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=restore_droits_full\" onclick=\"return getlongconfirm();\">".gettext("Remise en place de tous les droits")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des problemes de droits')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
}
require ("pdp.inc.php");
?>
