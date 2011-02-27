<?php



   /**
   
   * Supprime un groupe dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @auteurs Adrien CRESPIN Stage Lycee Valadon Limoges

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: del_group_user.php
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

$user_tmp = $user;
$uid=$_GET[uid];
if ($uid=="") {$uid=$_POST[uid];}
$members=$_POST[members];
$group_del_group=$_POST['group_del_group'];


list($user, $groups)=people_get_variables($uid, true);
aff_trailer ("31");

if (is_admin("Annu_is_admin",$login)=="Y")  {
	echo "<h4>".gettext("Suppression de")." $uid ".gettext(" de diff&#233;rents groupes")."</h4>\n";
      	if ( !$group_del_group || ( $group_del_group && !count($members) ) ) {
        	?>
          	<form action="del_group_user.php" method="post">
          	<p><?php echo gettext("S&#233;lectionnez les groupes dont vous voulez supprimer l' utilisateur :"); ?></p>
          	<p><select size="5" name="<?php echo "members[]"; ?>" multiple="multiple">
              	<?php
                 for ($loop=0; $loop < count ($groups) ; $loop++)
                 	echo "<option value=".$groups[$loop]["cn"].">".$groups[$loop]["cn"];
              	?>
            	</select></p>
            	<input type="hidden" name="uid" value="<?php echo $uid ?>">
            	<input type="hidden" name="group_del_group" value="true">
            	<input type="reset" value="<?php echo gettext("R&#233;initialiser la s&#233;lection"); ?>">
	    	<input type="submit" value="<?php echo gettext("Valider"); ?>">
          	</p>
        </form>
        <?php

        // Affichage message d'erreur
        if ($group_del_group && !count($members) ) {
          	echo "<div class=error_msg>".gettext("Vous devez s&#233;lectionner au moins un groupe &#224; enlever !")."</div>\n";
        }
      } else {
        	// suppression des groupes selectionnes
        	for ($loop=0; $loop < count ($members) ; $loop++) {
          		exec ("/usr/share/se3/sbin/groupDelUser.pl $uid $members[$loop] ",$AllOutPut,$ReturnValue);
          		
			$ReturnCode =  $ReturnCode + $ReturnValue;
        	}
        	// Compte rendu de suppression
        	if ($ReturnCode == "0") {
           		echo "<div class=error_msg>".gettext("Les groupes s&#233;lectionn&#233;s ont &#233;t&#233; supprim&#233;s pour ");
                      	echo "<font color='#0080ff'><A href='people.php?uid=$uid'> $uid </A></font>";
                      	echo gettext("avec succ&#232;s.")."</div><br>\n";
        	} else {
          		echo "<div class=error_msg>".gettext("Echec, les groupes s&#233;lectionn&#233;s n'ont pas &#233;t&#233; supprim&#233;s pour");
                    	echo "<font color='#0080ff'>$uid</font>&nbsp;!<BR> (".gettext("type d'erreur :")." $ReturnValue), ";
			echo gettext(" Veuillez contacter")."&nbsp;<A HREF='mailto:$MelAdminLCS?subject=PB creation groupe'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
        	}
      }

} else  {
    	echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur LCS !")."</div>";
}
  
include ("pdp.inc.php");
?>
