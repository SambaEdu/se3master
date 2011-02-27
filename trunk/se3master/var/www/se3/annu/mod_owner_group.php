<?php


   /**
   
   * Modifie une entree d'un groupe
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: mod_owner_group.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

 // Aide
$_SESSION["pageaide"]="Annuaire";

echo "<h1>".gettext("Annuaire")."</h1>";

aff_trailer ("3");
if (is_admin("Annu_is_admin",$login)=="Y") {
    $uids = search_uids ("(cn=".$cn.")");
    $people = search_people_groups ($uids,"(sn=*)","cat");
    if ( $owner ) {
    	echo "<h4>".gettext("R&#233;affectation du professeur principal de l'")."$cn</h4>";
    } else {
      	echo "<h4>".gettext("Affectation du professeur principal de l'")."$cn</h4>";
    }
    if ( !$mod_owner_group || !$new_owner ) {
      ?>
        <form action="mod_owner_group.php" method="post">
          <p><?php echo gettext("S&#233;lectionnez le professeur principal :") ?></p>
          <p><select size="5" name="<?php echo "new_owner"; ?>">
              <?php
                for ($loop=0; $loop < count($people); $loop++) {
                  if ( $owner != $people[$loop]["uid"] ) {
                    echo "<option value=".$people[$loop]["uid"].">".$people[$loop]["fullname"];
                  }
                }
              ?>
            </select></p>
            <input type="hidden" name="owner" value="<?php echo $owner ?>">
            <input type="hidden" name="cn" value="<?php echo $cn ?>">
            <input type="hidden" name="mod_owner_group" value="true">
            <input type="reset" value="<?php echo gettext("R&#233;initialiser la s&#233;lection") ?>">
	    <input type="submit" value="<?php echo gettext("Valider") ?>">
        </form>
      <?php
      if ( $mod_owner_group && !$new_owner ) {
          echo "<div class=error_msg>".gettext("Vous devez s&#233;lectionner un professeur principal !")."</div>\n";
      }
    } else {

      // Positionnement de l'entree a modifier
      $entry["owner"] = "uid=".$new_owner.",".$dn["people"];
      // if ($owner ) {
      // Reaffectation de l'entree owner
      $ds = @ldap_connect ( $ldap_server, $ldap_port );
      if ( $ds ) {
        $r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
        if ($r) {
          if (@ldap_modify ($ds, "cn=".$cn.",".$dn["groups"],$entry)) {
            if ( $owner ) {
              echo "<strong>".gettext("Le professeur principal a &#233;t&#233; r&#233;affect&#233; avec succ&#232;s.")."</strong><BR>\n";
            } else {
              echo "<strong>".gettext("Le professeur principal a &#233;t&#233; affect&#233; avec succ&#232;s.")."</strong><BR>\n";
            }
          } else {
            if ( $owner ) {
              echo "<strong>".gettext("Echec de la r&#233;affectation, veuillez contacter ")."</strong><A HREF='mailto:$MelAdminLCS?subject=PB reaffectation professeur principal'>".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
            } else {
              echo "<strong>".gettext("Echec de l'affectation, veuillez contacter ")."</strong><A HREF='mailto:$MelAdminLCS?subject=PB affectation professeur principal'>".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
            }
          }
        }
        @ldap_close ( $ds );
      } else {
        echo gettext("Erreur de connection &#224; l'annuaire, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB connection a l'annuaire'>".gettext("l'administrateur du syst&#232;me")."</A>".gettext("administrateur")."<BR>\n";
      }
    }
} else {
    echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}
  
include ("pdp.inc.php");
?>
