<?php

   /**	
   * Permet d'envoyer des popup a toutes les personnes connectees 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter Caen 
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: popup
   * file: poptous.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-popup',"/var/www/se3/locale");
textdomain ('se3-popup');

if (is_admin("computer_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	//aide
        $_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";

if (($tri=="") OR (($tri != 0) AND ($tri != 2)) ) $tri=2; // tri par ip par defaut
// modif du tri 
// /usr/bin/smbstatus -S| awk 'NF>6 {print $2,$5,$6}'|sort -u +2
// le +POS de la fin donne le rang de la variable de tri (0,1,2...)
if ("$smbversion" == "samba3") {
       	exec ("/usr/bin/smbstatus -b | grep -v root | grep -v nobody | awk 'NF>4 {print $2,$4,$5}' | sort -u",$out); 
} elseif ($tri == 0) {
    	exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u",$out); 
} else  {
	exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u +2",$out);
}
	
echo "<H1>".gettext("Envoi du Pop Up &#224; toutes les machines")."</H1>\n";
echo "<H3>".gettext("Envoi du Pop Up &#224; "). count($out).gettext(" machines")." </H3>";
echo gettext("Liste des machines destinataires du Pop Up:");


for ($i = 0; $i < count($out) ; $i++) {
    $test=explode(" ",$out[$i]);
    $test[2]=strtr($test[2],"()","  ");
    $test[2]=trim($test[2]);

    exec ("cat /tmp/popup.txt|smbclient -U 'Administrateur Samba Edu 3' -M $test[1]");
    echo "<small><li><b>".$test[1]." </b>(".gettext("session ouverte par")."<b> ".$test[0]." </b>)</li></small> ";

}

require ("pdp.inc.php");
?>
