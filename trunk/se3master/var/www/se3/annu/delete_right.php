<?php


   /**
   
   * Detruit les droits des utilisateurs dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: delete_right.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if (ldap_get_right("se3_is_admin",$login)=="Y") {

	$filtrecomp=$_POST['filtrecomp'];
	$old_rights=$_POST['old_rights'];
	$delete_right=$_POST['delete_right'];
	$right=$_POST['right'];
	$type=$_POST['type'];

	//Aide
	$_SESSION["pageaide"]="Annuaire";    
	echo "<h1>".gettext("Annuaire")."</h1>\n";
  	aff_trailer ("1");
    	// Affichage du formulaire de selection de parc
    	if (!isset($right)) {
    		echo "<TABLE><TR><TD>";
        	echo "<H3>".gettext("S&#233;lection du droit &#224; retirer")."</H3>";
		echo "</TD><TD>";
		?>
		<u onmouseover="this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape<?php echo gettext("('<b>se3_is_admin</b> Donne le droit d\'administration sur tout le syst&#232;me. Ce droit l\'emporte sur tous les autres.<BR><b>Annu_is_admin</b> Donne tous les droits sur l\'annuaire (Ajouter, supprimer, modifier des utilisateurs ou des groupes).<BR><b>sovajon_is_admin</b> D&#233;l&#233;gue le droit de changer les mots de passe &#224; un professeur. Il faut que celui-ci soit professeur de la classe.<BR><b>system_is_admin</b> Donne le droit de visualiser les informations syst&#232;me du serveur.<BR><b>computers_is_admin</b> Permet de g&#233;rer les machines clientes (Cr&#233;er ou supprimer des machines des parcs, &#233;tat des machines clientes...)<BR><b>printers_is_admin</b> Gestion des files d\'impression des imprimantes.<BR><b>echange_can_administrate</b> Permet de g&#233;rer les r&#233;pertoires _echanges dans les r&#233;pertoires classes.<BR><b>inventaire_can_read</B> Permet de consulter l\'inventaire<BR><b>annu_can_read</b> Permet de consulter l\'annuaire. Par d&#233;faut les membres du groupe Profs ont ce droit.<BR><b>maintenance_can_write</b> Permet de d&#233;clarer une panne sur une machine dans l\'interface de maintenance.<BR><b>parc_can_view</b> Permet de voir les parcs.<BR><b>parc_can_manage</b> Permet de d&#233;l&#233;guer la gestion d\'un parc &#224; une personne.<BR><b>smbweb_is_open</b> Donne le droit d\'acc&#232;s depuis l\'interface smbwebclient du Slis ou du Lcs (optionnel).')"); ?>"><img name="action_image2"  src="../elements/images/system-help.png"></u>
		<?php
		echo "</TD></TR></TABLE>";
		$list_rights=search_machines("objectclass=groupOfNames","rights");
        	if ( count($list_rights)>0) {
            		echo "<FORM method=\"post\">\n";  
            		echo "<SELECT NAME=\"right\" SIZE=\"1\">";
            		for ($loop=0; $loop < count($list_rights); $loop++) {
                		echo "<option value=".$list_rights[$loop]["cn"].">".$list_rights[$loop]["cn"]."\n";
            		}
            		echo "</SELECT>&nbsp;&nbsp;\n";
            		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
            		echo "</FORM>\n";
        	}  
	} else {
    		// Affichage du formulaire de remplissagge du parc
    		if (!$delete_right ) {
        		// Filtrage des noms
        		echo "<FORM action=\"delete_right.php\" method=\"post\">\n";
        		echo "<P>".gettext("Lister les noms contenant :");
        		echo "<INPUT TYPE=\"text\" NAME=\"filtrecomp\"\n VALUE=\"$filtrecomp\" SIZE=\"8\">";
        		echo "<input type=\"hidden\" name=\"right\" value=\"$right\">\n";
        		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
        		echo "</FORM>\n";
        		// Lecture des membres du droit
        		$mp_all=gof_members($right,"rights",0);
        		// Filtrage selon critere
        		if ("$filtrecomp"=="") $mp=$mp_all;
        		else {
            			$lmloop=0;
            			$mpcount=count($mp_all);
            			for ($loop=0; $loop < count($mp_all); $loop++) {
                			$mach=$mp_all[$loop];
                			if (preg_match("/$filtrecomp/",$mach)) $mp[$lmloop++]=$mach;
            			}
        		}
        		if ( count($mp)>15) $size=15; else $size=count($mp);
        		if ( count($mp)>0) {
            			$form = "<form action=\"delete_right.php\" method=\"post\">\n";
            			$form.="<p>".gettext("S&#233;lectionnez les personnes ou groupes &#224; priver du droit ")." <b>$right</b> :</p>\n";
            			$form.="<p><select size=\"".$size."\" name=\"old_rights[]\" multiple=\"multiple\">\n";
            			echo $form;
            			for ($loop=0; $loop < count($mp); $loop++) { 
                			$value=extract_login($mp[$loop]);
                			if (preg_match("/$groupsRdn/",$mp[$loop])) {
						$type = "groupe";
						$value="$value ($type)";
					} else {
						$type = "utilisateur";
						$value="$value ($type)";
					}
                			echo "<option value=".$mp[$loop].">".$value;
            			}
            			$form="</select></p>\n";
            			$form.="<input type=\"hidden\" name=\"delete_right\" value=\"true\">\n";
            			$form.="<input type=\"hidden\" name=\"right\" value=\"$right\">\n";
	    			$form.="<input type=\"hidden\" name=\"type\" value=\"$type\">\n";
            			$form.="<input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">\n";
            			$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
            			$form.="</form>\n";
            			echo $form;
        		} else {
            			$message =  gettext("Il n'y a rien &#224; supprimer !");
            			echo $message;
        		}
    		} else {
        	// Suppression des drois
            		echo "<H3>".gettext("Modification du droit ")." <U>$right</U></H3>";
            		echo "<P>".gettext("Vous avez s&#233;lectionn&#233; ") . count($old_rights) . gettext(" droit(s)")."<BR>\n";
            		for ($loop=0; $loop < count($old_rights); $loop++) {
                		$pers=$old_rights[$loop];
				$pers=extract_login ($pers);
                		echo gettext("Suppression de")." $pers ".gettext("du droit ")." <U>$right</U><BR>";
                		$pDn = "cn=".$right.",".$rightsRdn.",".$ldap_base_dn;
                		if ($type=="utilisateur") $persDn = "uid=$pers".",".$peopleRdn.",".$ldap_base_dn;
				else $persDn = "cn=$pers".",".$groupsRdn.",".$ldap_base_dn;
				#echo "cn=$pers".",".$groupsRdn.",".$ldap_base_dn;
                		exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$persDn\" \"$pDn\"");
                		echo "<BR>";
            		}
        	}
	}
}

include ("pdp.inc.php");
?>
