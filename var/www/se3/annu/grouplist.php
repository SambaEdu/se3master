<?php


   /**
   
   * Page permettant de creer des listes pour en faire un export de l'annuaire
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
   * file: grouplist.php
   */

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Partie reinitialisation MDP AJAX
if((isset($_POST['reinit_mdp']))&&($_POST['reinit_mdp']=="y")&&(isset($_POST['uid_init']))&&(preg_match("/^[A-Za-z]{1,}/", $_POST['uid_init']))) {
	@session_start();
	$_SESSION["pageaide"]="Table_des_mati&#232;res";

	require("config.inc.php");
	require_once ("functions.inc.php");

	require_once ("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');

	require_once ("traitement_data.inc.php");

	$login=isauth();

	include "ldap.inc.php";
	include "ihm.inc.php";

	//debug_var();

	$uid_init=$_POST['uid_init'];

	$reinit_mdp_autorise=false;

	//echo "login=$login<br />";

	$Annu_is_admin=is_admin("Annu_is_admin",$login);
	$sovajon_is_admin=is_admin("sovajon_is_admin",$login);
	if($Annu_is_admin=="Y") {
		$reinit_mdp_autorise=true;
	}
	elseif(($sovajon_is_admin=="Y")&&are_you_in_group($login, "Profs")&&are_you_in_group($uid_init, "Eleves")) {
		// Vérifier que le prof et l eleve sont lies par une Equipe/Classe ou un cours

		$mes_infos=people_get_variables($login, true);
		/*
		echo "<pre>";
		print_r($mes_infos);
		echo "</pre>";
		*/

		if((isset($mes_infos[1]))&&(count($mes_infos[1])>0)) {
			for($loop=0;$loop<count($mes_infos[1]);$loop++) {
				if(isset($mes_infos[1][$loop]["cn"])) {
					if(preg_match("/^Equipe_/", $mes_infos[1][$loop]["cn"])) {
						$nom_grp=preg_replace("/^Equipe_/", "Classe_", $mes_infos[1][$loop]["cn"]);
						if(are_you_in_group($uid_init, $nom_grp)) {
							$reinit_mdp_autorise=true;
							break;
						}

					}
					elseif(preg_match("/^Cours_/", $mes_infos[1][$loop]["cn"])) {
						$nom_grp=$mes_infos[1][$loop]["cn"];
						if(are_you_in_group($uid_init, $nom_grp)) {
							$reinit_mdp_autorise=true;
							break;
						}
					}
				}
			}
		}
	}

	if($reinit_mdp_autorise) {
		//echo "1";
		// Recherche d'utilisateurs dans la branche people
		$filter="(uid=$uid_init)";
		$ldap_search_people_attr = array("gecos","givenName","sn");

		$ds = @ldap_connect ( $ldap_server, $ldap_port );
		if ( $ds ) {
			$r = @ldap_bind ( $ds ); // Bind anonyme
			if ($r) {
				// Recherche dans la branche people
				$result = @ldap_search ( $ds, $dn["people"], $filter, $ldap_search_people_attr );
				if ($result) {
					//echo "2";
					$info = @ldap_get_entries ( $ds, $result );
					if ( $info["count"]) {
						//echo "3";
						for ($loop=0; $loop<$info["count"];$loop++) {
							//echo "<br />loop=$loop";
							$gecos = $info[0]["gecos"][0];

							$prenom = $info[0]["givenname"][0];
							$nom = $info[0]["sn"][0];
							$tmp = preg_split ("/,/",$info[0]["gecos"][0],4);
							$date_naiss=$tmp[1];

							//echo "<a href='people.php?uid=$uid_init' title=\"Retour à la fiche de l'utilisateur $nom $prenom.\">$nom $prenom</a>&nbsp;: ";

							$message_title="";
							switch ($pwdPolicy) {
								case 0:		// date de naissance
									$userpwd=$date_naiss;
									$message_title=gettext("Mot de passe r&#233;initialis&#233; &#224; la date de naissance : ");
									break;
								case 1:		// semi-aleatoire
									exec("/usr/share/se3/sbin/gen_pwd.sh -s", $out);
									$userpwd=$out[0];
									$message_title=gettext("Mot de passe r&#233;initialis&#233; &#224; : ");
									break;
								case 2:		// aleatoire
									exec("/usr/share/se3/sbin/gen_pwd.sh -a", $out);
									$userpwd=$out[0];
									$message_title=gettext("Mot de passe r&#233;initialis&#233; &#224; : ");
									break;
							}

							exec("/usr/share/se3/sbin/userChangePwd.pl '$uid_init' '$userpwd'", $AllOutPut, $ReturnValue);
							if ($ReturnValue == "0") {
								echo "<img src='../elements/images/enabled.gif' height='20' width='20' alt='OK' title=\"".$message_title.$userpwd."\" />";
							}
							else {
								echo "<img src='../elements/images/disabled.gif' height='20' width='20' alt='KO' title=\"".$message_title.$userpwd."\nERREUR !\" />";
							}
							//"<br><br>";
							//userChangedPwd($uid_init, $userpwd);

							/*
							// VOIR OU COLLER LE listing
							
							// ajouter vérification de doublon en cas de modifs successives pour un même uid.
							$doublon = false;
							foreach($_SESSION['comptes_crees'] as &$key) {
								if ($key['uid'] == $uid_init){  // doublon : mise à jour pwd
									$doublon = true;
									$key['pwd'] = $userpwd;
									break;
								}
							}
							if (!$doublon) {
								$nouveau = array('nom'=>"$nom", 'pre'=>"$prenom", 'uid'=>"$uid_init", 'pwd'=>"$userpwd");
								$_SESSION['comptes_crees'][]=$nouveau;
							}
							$doublon = false;

							include("listing.inc.php");
							*/
						}
					}

					@ldap_free_result ( $result );
				} else {
					$error = gettext("Erreur de lecture dans l'annuaire LDAP");
				}

			} else {
				$error = gettext("Echec du bind anonyme");
			}
			@ldap_close ( $ds );
		} else {
			$error = gettext("Erreur de connection au serveur LDAP");
		}

		if(isset($error)) {
			echo $error;
		}

	}
	else {
		echo "Reinitialisation mdp non autorisee.";
	}

	die();
}
// Fin de la partie reinitialisation MDP AJAX
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// Aide
$_SESSION["pageaide"]="Annuaire";

$filter=$_GET['filter'];

$Annu_is_admin=is_admin("Annu_is_admin",$login);
$sovajon_is_admin=is_admin("sovajon_is_admin",$login);
if (($Annu_is_admin=="Y") || ($sovajon_is_admin=="Y")) {
	$group=search_groups ("(cn=".$filter.")");
	$uids = search_uids ("(cn=".$filter.")");
	/*
	echo "uids<pre>";
	print_r($uids);
	echo "</pre>";
	echo "<hr />";
	*/
	$people = search_people_groups ($uids,"(sn=*)","cat");
	/*
	echo "people<pre>";
	print_r($people);
	echo "</pre>";
	*/

	$tab_uid_mes_eleves=array();
	if(are_you_in_group($login, "Profs")) {
		$tab_uid_mes_eleves=get_tab_uid_eleves_du_prof($login);
		/*
		echo "tab_uid_mes_eleves<pre>";
		print_r($tab_uid_mes_eleves);
		echo "</pre>";
		*/
	}

  	#$TimeStamp_1=microtime();
  	#############
  	# DEBUG     #
 	#############
  	#echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR><BR>";
  	#############
  	# Fin DEBUG #
  	#############
	if (count($people)) {
		// affichage des r?sultats
		// Nettoyage des _ dans l'intitul? du groupe
		$intitule =  strtr($filter,"_"," ");
		echo "<H1><U>".gettext("Groupe")."</U> : $intitule <font size=\"-2\">".$group[0]["description"]."</font></H1>\n";
		echo gettext("Il y a ").count($people).gettext(" membre");
		if ( count($people) >1 ) echo "s";
		echo gettext(" dans ce groupe")."<BR>\n";
		echo "<TABLE border=1>
			<TR>
				<TD ALIGN='Center'>Nom</TD>
				<TD ALIGN='Center'>login</TD>
				<TD ALIGN='Center'>".gettext("Date naiss")."</TD>
				<TD ALIGN='Center'>".gettext("Pass")."</TD>
				<TD ALIGN='Center'>".gettext("Reinit")."</TD>
			</TR>";
		for ($loop=0; $loop < count($people); $loop++) {
			echo "
			<TR>
				<TD>\n";
			if (($people[$loop]["cat"] == "Equipe") or ($people[$loop]["prof"]==1)) {
				echo "<img src=\"images/gender_teacher.gif\" alt=\"Professeur\" width=18 height=18 hspace=1 border=0>\n";

			} else {
				if ($people[$loop]["sexe"]=="F") {
					echo "<img src=\"images/gender_girl.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
				} else {
					echo "<img src=\"images/gender_boy.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
				}
			}
			preg_match("/([0-9]{8})/",$people[$loop]["gecos"],$naiss);
			if(($people[$loop]["prof"]==1)) {
				echo $people[$loop]["fullname"];
			}
			else {
				echo "<a href='people.php?uid=".$people[$loop]["uid"]."' title=\"Voir la fiche eleve.\">".$people[$loop]["fullname"]."</a>";
			}
			echo "
				</TD>
				<TD>".$people[$loop]["uid"]."</TD>";
			$valeur_naiss="########";
			if(($Annu_is_admin=="Y")||($people[$loop]["eleve"]==1)) {
				$valeur_naiss=$naiss[0];
			}

			$lien_reinit="";
			if(($Annu_is_admin=="Y")||
			(($people[$loop]["eleve"]==1)&&(in_array($people[$loop]["uid"], $tab_uid_mes_eleves)))) {
				$lien_reinit="<a href='pass_user_init.php?uid=".$people[$loop]["uid"]."' title=\"Reinitialiser le mot de passe a la date de naissance\" onclick=\"reinit_mdp($loop, '".$people[$loop]["uid"]."');return false;\"><img src='../elements/images/logrotate.png' width='22' height='22' alt='Reinit' /></a>";
			}

			echo "
				<TD id='td_naiss_".$loop."'>".$valeur_naiss."</TD>
				<TD id='td_pass_".$loop."' align='center'>######</TD>
				<TD align='center'>".$lien_reinit."</TD>
			</TR>\n";
		}
		echo "</TABLE>\n";

		echo "<script type='text/javascript'>
		// <![CDATA[
			function reinit_mdp(loop, uid_init) {
				document.getElementById('td_pass_'+loop).innerHTML=\"<img src='../elements/images/spinner.gif' width='16' height='16' alt='Wait...' />\";
				new Ajax.Updater($('td_pass_'+loop),'grouplist.php',{method: 'post', parameters: '?uid_init='+uid_init+'&reinit_mdp=y' });
			}
		//]]>
		</script>\n";



		echo "<p>G&#233;n&#233;rer un <a href='grouplist_csv.php?filter=$filter' target='blank'>export CSV du groupe</a></p>\n";
  	} else {
    		echo " <STRONG>".gettext("Pas de membres")." </STRONG> ".gettext(" dans le groupe")." $filter.<BR>";
  	}
}
include ("pdp.inc.php");
?>
