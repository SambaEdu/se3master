<?php


   /**
   
   * Supprime les utilisateurs des groupes
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @auteurs oluve olivier.le_monnier.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: del_user_group.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

//Aide
$_SESSION["pageaide"]="Annuaire";

$cn=$_POST['cn'];
if ($cn=="") {$cn=$_GET['cn'];}
$group_del_user=$_POST['group_del_user'];
$members=$_POST['members'];

echo "<h1>".gettext("Annuaire")."</h1>";


if (is_admin("Annu_is_admin",$login)=="Y") {


	$filter="8_".$cn;
	aff_trailer ("$filter");
    	if ( $cn !="Eleves" && $cn !="Profs" && $cn !="Administratifs" ) {
      		$uids = search_uids ("(cn=".$cn.")");
      		$people = search_people_groups ($uids,"(sn=*)","cat");
      		echo "<h4>".gettext("Modification des membres du groupe")." $cn</h4>\n";
		if ( !$group_del_user || ( $group_del_user && !count($members) ) ) {
        ?>
       			<form action="del_user_group.php" method="post">
       			<p><?php echo gettext("S&#233;lectionnez les membres &#224; supprimer :"); ?></p>
       			<p><select size="5" name="<?php echo "members[]"; ?>" multiple="multiple">
              <?php
       		      	for ($loop=0; $loop < count($people); $loop++) {
               			echo "<option value=".$people[$loop]["uid"].">".$people[$loop]["fullname"];
               		}
              ?>
       			</select></p>
       			<input type="hidden" name="cn" value="<?php echo $cn ?>">
       			<input type="hidden" name="group_del_user" value="true">
       			<input type="reset" value="<?php echo gettext("R&#233;initialiser la s&#233;lection"); ?>">
    			<input type="submit" value="<?php echo gettext("Valider"); ?>">
       			</p>
       			</form>
        	<?php
        
			// Affichage message d'erreur
       			if ($group_del_user && !count($members) ) {
          			echo "<div class=error_msg>".gettext("Vous devez s&#233;lectionner au moins un membre &#224; supprimer !")."</div>\n";
       			}
		} else {
       			// suppression des utilisateurs selectionnes
       			for ($loop=0; $loop < count($members); $loop++  ) {
       				exec ("/usr/share/se3/sbin/groupDelUser.pl $members[$loop] $cn",$AllOutPut,$ReturnValue);
       				$ReturnCode =  $ReturnCode + $ReturnValue;
       			}
        	
			// Compte rendu de suppression
       			if ($ReturnCode == "0") {
       				echo "<div class=error_msg>".gettext("Les membres s&#233;lectionn&#233;s ont &#233;t&#233; supprim&#233; du groupe ")."<font color='#0080ff'><A href='group.php?filter=$cn'>$cn</A></font>".gettext(" avec succ&#232;s.")."</div><br>\n";
       			} else {
       				echo "<div class=error_msg>".gettext("Echec, les membres s&#233;lectionn&#233;s n'ont pas &#233;t&#233; supprim&#233; du groupe")."<font color='#0080ff'>$cn</font>";
               			echo "&nbsp;!<BR>".gettext("(type d'erreur :")." $ReturnValue), ".gettext("veuillez contacter");
               			echo "&nbsp;<A HREF='mailto:$MelAdminLCS?subject=PB creation groupe'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
       			}
      		}
    	} else {
      		echo "<div class=error_msg>".gettext("La suppression d'un utilisateur de son  groupe principal (Eleves, Profs, Administratifs) n'est pas autoris&#233;e !")."</div>";
    	}
} else {
	echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur !")."</div>";
}

include ("pdp.inc.php");
?>
