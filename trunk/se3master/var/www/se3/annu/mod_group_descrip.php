<?php


   /**
   
   * Modifie une entree description d'un groupe
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
   * file: mod_group_descrip.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$_SESSION["pageaide"]="Annuaire";

echo "<h1>".gettext("Annuaire")."</h1>";

$cn=$_GET['cn'];
if ($cn=="") { $cn=$_POST['cn']; }
$mod_descrip=$_POST['mod_descrip'];

if (is_admin("Annu_is_admin",$login)=="Y") {
	$filter="8_".$cn;
	aff_trailer ("$filter");
	$group=search_groups("cn=".$cn);
    	if ((!$mod_descrip) || ( $mod_descrip && (!$description || !verifDescription($description)))) {
      		echo gettext("Modification de la description du groupe :")." <b>".$group[0]["cn"]."</b>\n";
      		?>
      		<form action="mod_group_descrip.php" method="post">
        	  <table border="0" width="90%" align="center">
          	    <tbody>
	    		<tr>
	      		  <td><?php echo gettext("Description :"); ?></td>
	      		  <td width="73%" colspan="2"><input type="text" name="description" value="<?php echo $group[0]["description"] ?>" size="60"></td>
	      		  <td></td>
	    		</tr>
	      		  <td align="left">
                		<input type="hidden" name="cn" value="<?php echo $cn ?>">
                		<input type="hidden" name="mod_descrip" value="true">
                		<input type="submit" value="<?php echo gettext("Lancer la requ&#234;te"); ?>">
              		  </td>
	    		</tr>
	  	    </tbody>
        	  </table>
      		</form>
      		<?php
      		if ( $mod_descrip ) {
        		if ( !$description ) {
          			echo "<div class=\"error_msg\">".gettext("Vous devez saisir une description pour ce groupe !")."</div><BR>\n";
        		} elseif (!verifDescription($description)) {
          			echo "<div class=error_msg>".gettext("Le champ description comporte des caract&#232;res interdits !")."</div><br>\n";
        		}
      		}
    	} else {
      	#DEBUG
      	#echo "Debug : ".$group[0]["cn"]." ".$description."<BR>\n";
      		$entry["description"]=utf8_encode(stripslashes($description));
      		// Modification de la description
      		$ds = @ldap_connect ( $ldap_server, $ldap_port );
      		if ( $ds ) {
        		$r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
        		if ($r) {
          			if (@ldap_modify ($ds, "cn=".$group[0]["cn"].",".$dn["groups"],$entry)) {

            				echo gettext("La description du groupe")."&nbsp;<strong>".$group[0]["cn"]."</strong>&nbsp;".gettext("&#224; &#233;t&#233; modifi&#233;e avec succ&#232;s.")."</br>\n";
            				echo "<u>".gettext("Nouvelle description")."</u> :&nbsp;".stripslashes($description)."<BR>\n";

          			} else {
            				echo "<strong>".gettext("Echec de la modification du groupe ").$group[0]["cn"].gettext(" veuillez contacter ")."</strong><A HREF='mailto:$MelAdminLCS?subject=".gettext("PB modification de la description d'un groupe").">".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
          			}
        		}
        		@ldap_close ( $ds );
      		} else {
        		echo gettext("Erreur de connection &#224; l'annuaire, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB connection a l'annuaire'>".gettext("l'administrateur du syst&#232;me")."</A>".gettext("administrateur")."<BR>\n";
      		}
    	}

} else {
	echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");
?>
