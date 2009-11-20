<?php


   /**
   
   * Permet configurer le serveur en mode sans echec :)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs « oluve » olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs Olivier LECLUSE « wawa »  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Plouf sudoification

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: setup/
   * file: index.php

  */	


require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

require ("config.inc.php");
require ("functions.inc.php");

//if ($SCRIPT_NAME != "/setup/index.php") {
//	require ("entete.inc.php");
//	if (ldap_get_right("se3_is_admin",$login)!="Y")
//        die (gettext("Vous n'avez pas les droits suffisants pour acceder a cette fonction"));
//	require ("pdp.inc.php");
//} else {
//	require("entete.inc.php");
//	echo "<H1>".gettext("Parametrage general de SambaEdu3")."</H1>\n";
//}

//Aide
//$_SESSION["pageaide"]="La_base_MySQL";

if (!isset($cat)) $cat=0;

if ((!isset($submit)) and (!isset($queri))) {
// Affichage du form de mise a jour des parametres
	print "<form method=\"post\" action=\"index.php\">\n";
	if (($cat==0) || ($cat==1)) mktable(gettext("Configuration g&#233;n&#233;rale"),aff_param_form(1));
	if (($cat==0) || ($cat==2)) mktable(gettext("Param&#232;tres LDAP"),aff_param_form(2));
	if (($cat==0) || ($cat==3)) mktable(gettext("Chemins"),aff_param_form(3));
	if (($cat==0) || ($cat==5)) mktable(gettext("Parametres sauvegarde"),aff_param_form(5));
    	if (($cat==0) || ($cat==4)) mktable(gettext("Params cach&#233;s"),aff_param_form(4));
	if (($cat==0) || ($cat==6)) mktable(gettext("Parametres systeme"),aff_param_form(6));
	if (($cat==0) || ($cat==7)) mktable(gettext("Parametres DHCP"),aff_param_form(7));
	print "<br /><div align =\"center\">";
	print "<input type=\"submit\" value=\"".gettext("Valider")."\" /></div>";
	print "<input type=\"hidden\" value=\"$cat\" name=\"submit\" />\n";
	print "</form>\n";
}


if (isset($_POST['submit'])) {
	$submit=$_POST['submit'];
	// Traitement du Form
	$query="SELECT * from params";
	if ($submit != 0) $query .= " WHERE cat=$submit";
	$result=mysql_query($query);
	if ($result) {
		$i=0;
		$modif=0;
		$ldap_modify="";

		while ($r=mysql_fetch_array($result)) {
			// Exclusion de deux valeurs particulieres de la table params
			if(($r["name"]!='dernier_import')&&($r["name"]!='imprt_cmpts_en_cours')){
				$formname="form_".$r["name"];

				// Si ancienne valeur n'est pas egale a la nouvelle
				if ($_POST["$formname"]!=$r["value"]) {
				// Mise a jour de la base de donnees
					$queri="UPDATE params SET value=\"".$_POST["$formname"]."\" WHERE name=\"".$r["name"]."\"";
					$result1=mysql_query($queri);

					if ($result1) {
						print gettext("Modification du param&#232;tre ")."<em><font color=\"red\">".$r["name"]."</font></em> ". gettext("de ")."<strong>".$r["value"]."</strong>".gettext(" en ")."<strong>".$_POST["$formname"]."</strong>"."<br />\n";
						$modif="1";
					} else
						print gettext("oops: la requete ") . "<strong>$queri</strong>" . gettext(" a provoqu&#233; une erreur");
					// Preparation des modifs sur les fichiers de conf de ldap
					if (($r["cat"]==2) && ($r["name"] != "yala_bind")) {
								if ($r["name"]=="adminPw") {
							$ldap_modify=1;
						}
						if ($r["name"]=="ldap_server") {
							$ldap_modify="1";
						}
						// Mise a jour des variables du config
						$$r["name"]=$_POST["$formname"];
						$i++;
					}
                                        // preparation des modifs a faire avec le correctSID.sh
                                        if ($r["name"]=="domainsid") {
                                            $sid_modify="1";
                                            // Mise à jour des variables du config
                                            $$r["name"]=$$formname;
                                            $i++;
                                        }
				}
			}
		}

		if ($i>0) {
			// Des parametres ont ete modifies. Mise a jour des fichiers de conf
			if ($ldap_modify != "") {
				// Mise a jour de la conf LDAP
				exec('/usr/bin/sudo /usr/share/se3/scripts/mkSlapdConf.sh');

			}
                        if ($sid_modify != "") {
				// Correction du SID dans le secrets.tdb et l'annuaire en fonction du domainsid de mysql 
				exec('/usr/bin/sudo /usr/share/se3/scripts/correctSID.sh -m -q');
			}
		} else {
			if ($modif == "0") {
				echo "<center>";
				print gettext("Aucun param&#232;tre n'a &#233;t&#233; modifi&#233;\n");
				echo "</center>";
			}
		}
		echo "<br /><br /><center><a href=\"./\">".gettext("Retour")."</a></center>";
		mysql_free_result($result);
	} else print gettext ("oops: Erreur inattendue de lecture des anciens param&#232;tres\n");


}

require ("pdp.inc.php");
?>
