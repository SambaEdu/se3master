<?php


   /**
   
   * Ajoute des groupe dans l'annuaire
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
   * file: add_group.php

  */	


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

foreach ($_POST as $cle=>$val) {
    $$cle = $val;
        }

  require_once ("lang.inc.php");
  bindtextdomain('se3-annu',"/var/www/se3/locale");
  textdomain ('se3-annu');

  echo "<h1>".gettext("Annuaire")."</h1>\n";
  $_SESSION["pageaide"]="Annuaire";
  aff_trailer ("6");
  
if (is_admin("Annu_is_admin",$login)=="Y") {
  	// Ajout d'un groupe d'utilisateurs
	if ( (!$add_group) ||( ($add_group) && ( (!$description || !verifDescription($description) ) ||(!$intitule || !verifIntituleGrp ($intitule)) ) ) ) {
      ?>
      		<form action="add_group.php" method="post">
        	<table border="0">
		<tbody>
	    	<tr>
	      	<td><?php echo gettext("Pr&#233;fix :") ?></td>
	      	<td valign="top"><input type="text" name="prefix" size="2">&nbsp;<font color="orange"><u><?php echo gettext("Exemple"); ?></u> : <b>LP, LT</b></font></td>
	    	</tr>
	    	<tr>
	      	<td><?php echo gettext("Cat&#233;gorie :"); ?></td>
	      	<td valign="top">
                 <select name="categorie">
	           <option><?php echo gettext("Classe"); ?></option>
	           <option><?php echo gettext("Cours"); ?></option>
                   <option><?php echo gettext("Equipe"); ?></option>
                   <option><?php echo gettext("Matiere"); ?></option>
                   <option><?php echo gettext("Autre"); ?></option>
                 </select>
              	</td>
	    	</tr>
	    	<tr>
	      	<td><?php echo gettext("Intitul&#233; :"); ?></td>
	      	<td valign="top"><input type="text" name="intitule" size="20"></td>
	    	</tr>
	    	<tr>
	      	<td><?php echo gettext("Description :"); ?></td>
	      	<td valign="top"><input type="text" name="description" size="40"></td>
	    	</tr>
	    	<tr>
	      	<td></td>
	      	<td></td>
	      	<td >
                <input type="hidden" name="add_group" value="true">
                <input type="submit" value=<?php print(gettext("Lancer la requ&#234;te")); ?>>
              	</td>
	    	</tr>
	  	</tbody>
        	</table>
      		</form>
      
      
      		<?php
		// Message d'erreurs de saisie
      		if ( $add_group && (!$intitule || !$description) ) {
      			echo "<div class=error_msg>".gettext("Vous devez saisir un nom de groupe et une description !")."</div><br>\n";
      		} elseif ($add_group && !verifDescription($description)) {
        		echo "<div class=error_msg>".gettext("Le champ description comporte des caract&#232;res interdits !")."</div><br>\n";
      		} elseif ($add_group && !verifIntituleGrp($intitule)) {
        		echo "<div class=error_msg>".gettext("Le champ intitul&#233; ne doit pas commencer ou se terminer par l'expresssion : Classe, Equipe ou Matiere !")."</div><br>\n";
      		}

	} else {
    		$intitule = enleveaccents($intitule);
      		// Construction du cn du nouveau groupe
      		if ($prefix) $prefix=$prefix."_";
      		if ($categorie=="Autre") $categorie=""; else $categorie=$categorie."_";
      		$cn= $categorie.$prefix.$intitule;
      		
		// Verification de l'existance du groupe
      		$groups=search_groups("(cn=$cn)");
      		
		if (count($groups)) {
		        echo "<div class='error_msg'>".gettext("Attention le groupe")." <font color='#0080ff'> <a href='group.php?filter=$cn' style='color:#0080ff' target='_blank'>$cn</a></font>".gettext(" est d&#233;ja pr&#233;sent dans la base, veuillez choisir un autre nom !")."</div><BR>\n";
      		} else {
        		// Ajout du groupe
        		$description = stripslashes($description);
        		// Test de la cat&#233;gorie

        		// if ($categorie == "Equipe_" || $categorie == "Matiere_" ) $groupType = "2"; else $groupType = "1";
			$groupType="1";
        		exec ("/usr/share/se3/sbin/groupAdd.pl $groupType $cn \"$description\"",$AllOutPut,$ReturnValue);
        		if ($ReturnValue == "0") {
				if ($categorie=="Classe_") {
					echo "<div class=error_msg>".gettext("Le groupe")." <a href='add_list_users_group.php?cn=$cn' title=\"Ajouter des membres au groupe\"> $cn </a> ".gettext(" a &#233;t&#233; ajout&#233; avec succ&#232;s.")."</div><br>\n";
				}
				else {
					echo "<div class=error_msg>".gettext("Le groupe")." <a href='aj_ssgroup.php?cn=$cn' title=\"Ajouter des membres au groupe\"> $cn </a> ".gettext(" a &#233;t&#233; ajout&#233; avec succ&#232;s.")."</div><br>\n";
				}

        		} else {
          			echo "<div class=error_msg>".gettext("Echec, le groupe")." <font color='#0080ff'>$cn</font>".gettext(" n'a pas &#233;t&#233; cr&#233;&#233; !")."\n";
          			if ($ReturnValue) echo "(type d'erreur : $ReturnValue),&nbsp;";
          			echo "&nbsp;".gettext("Veuillez contacter")."</div> <A HREF='mailto:$MelAdminLCS?subject=PB creation groupe'>".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
        		}
      		}
    	}

} else {
	echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur LCS !")."</div>";

}

include ("pdp.inc.php");
?>
