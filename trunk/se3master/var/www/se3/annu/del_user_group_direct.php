<?php


   /**
   
   * Supprime un utilisateur d'un groupe
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Adrien CRESPIN Stage Lycee Valdon Limoges

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: del_user_group_direct.php
   */





include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// Aide
$_SESSION["pageaide"]="Annuaire";


echo "<h1>".gettext("Annuaire")."</h1>\n";

$uid=$_GET["uid"];
$cn=$_GET["cn"];

aff_trailer ("3");
if (is_admin("Annu_is_admin",$login)=="Y") {
        // suppression des utilisateurs selectionnes
          exec ("/usr/share/se3/sbin/groupDelUser.pl $uid $cn",$AllOutPut,$ReturnValue);
          $ReturnCode =  $ReturnCode + $ReturnValue;

        // Compte rendu de suppression
        if ($ReturnCode == "0") {
           	echo "<div class=error_msg>
                     <a href='people.php?uid=$uid'>$uid </a>".gettext(" a &#233;t&#233; supprim&#233; du groupe")."
                      <font color='#0080ff'><A href='group.php?filter=$cn'>$cn</A></font>".gettext("avec succ&#232;s.")."</div><br>\n";

        } else {
          	echo "<div class=error_msg>";
          	echo gettext("Echec").", ".$uid . gettext(" n'a pas &#233;t&#233; supprim&#233; du groupe")."
		<font color='#0080ff'>$cn</font>
                    &nbsp;!<BR> (".gettext("type d'erreur :")." $ReturnValue),".gettext(" veuillez contacter")."
                    &nbsp;<A HREF='mailto:$MelAdminLCS?subject=PB creation groupe'>".gettext("l'administrateur du syst&#232;me")."</A>
                </div><BR>\n";
        }
      
  }  else   {
    	echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite des droits d'administration SE3 !")."</div>";
  }

include ("pdp.inc.php");
?>
