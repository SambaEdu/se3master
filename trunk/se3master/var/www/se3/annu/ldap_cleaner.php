<?php


   /**

   * Gestion des comptes orphelins
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL

   * @note
   * @sudo /usr/share/se3/scripts/delHome.pl
   */

   /**

   * @Repertoire: annu
   * file: ldap_cleaner.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "crob_ldap_functions.php";


$DEBUG=="false";

/**
* Recherche les compte dans la branche Trash
* @Parametres $filter filtre ldap de recherche
* @return
*/

// Fonction déplacée vers crob_ldap_functions.php parce qu'aussi utilisée dans /usr/share/se3/scripts/import_comptes.php
/*
function search_people_trash ($filter) {
  global $ldap_server, $ldap_port, $dn, $adminDn, $adminPw;
  global $error;
  $error="";
  global $sambadomain;

  //LDAP attributes

  $ldap_search_people_attr = array(
	"sambaacctFlags",
	"sambapwdMustChange",
	"sambantPassword",
	"sambalmPassword",
        "sambaSID",
        "sambaPrimaryGroupSID",
	"userPassword",
	"gecos",
        "employeenumber",
	"homedirectory",
	"gidNumber",
	"uidNumber",
	"loginShell",
	"objectClass",
	"mail",
	"sn",
	"givenName",
	"cn",
	"uid"
  );

  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = @ldap_bind ( $ds,$adminDn, $adminPw );
    if ($r) {
      // Recherche dans la branche trash
      $result = @ldap_search ( $ds, $dn["trash"], $filter, $ldap_search_people_attr );
      if ($result) {
        $info = @ldap_get_entries ( $ds, $result );
        if ( $info["count"]) {
          for ($loop=0; $loop<$info["count"];$loop++) {
            if ( isset($info[$loop]["employeenumber"][0]) ) {
                    $ret[$loop] = array (
		      "sambaacctflags"      => $info[$loop]["sambaacctflags"][0],
		      "sambapwdmustchange"  => $info[$loop]["sambapwdmustchange"][0],
		      "sambantpassword"     => $info[$loop]["sambantpassword"][0],
		      "sambalmpassword"     => $info[$loop]["sambalmpassword"][0],
                      "sambasid"            => $info[$loop]["sambasid"][0],
                      "sambaprimarygroupsid"   => $info[$loop]["sambaprimarygroupsid"][0],
		      "userpassword"        => $info[$loop]["userpassword"][0],
		      "gecos"               => $info[$loop]["gecos"][0],
                      "employeenumber"      => $info[$loop]["employeenumber"][0],
		      "homedirectory"       => $info[$loop]["homedirectory"][0],
		      "gidnumber"           => $info[$loop]["gidnumber"][0],
		      "uidnumber"           => $info[$loop]["uidnumber"][0],
		      "loginshell"          => $info[$loop]["loginshell"][0],
		      "mail"                => $info[$loop]["mail"][0],
		      "sn"                  => $info[$loop]["sn"][0],
		      "givenname"           => $info[$loop]["givenname"][0],
		      "cn"                  => $info[$loop]["cn"][0],
		      "uid"                 => $info[$loop]["uid"][0],
                    );
            } else {
                    $ret[$loop] = array (
		      "sambaacctflags"      => $info[$loop]["sambaacctflags"][0],
		      "sambapwdmustchange"  => $info[$loop]["sambapwdmustchange"][0],
		      "sambantpassword"     => $info[$loop]["sambantpassword"][0],
		      "sambalmpassword"     => $info[$loop]["sambalmpassword"][0],
                      "sambasid"            => $info[$loop]["sambasid"][0],
                      "sambaprimarygroupsid"   => $info[$loop]["sambaprimarygroupsid"][0],
		      "userpassword"        => $info[$loop]["userpassword"][0],
		      "gecos"               => $info[$loop]["gecos"][0],		      "homedirectory"       => $info[$loop]["homedirectory"][0],
		      "gidnumber"           => $info[$loop]["gidnumber"][0],
		      "uidnumber"           => $info[$loop]["uidnumber"][0],
		      "loginshell"          => $info[$loop]["loginshell"][0],
		      "mail"                => $info[$loop]["mail"][0],
		      "sn"                  => $info[$loop]["sn"][0],
		      "givenname"           => $info[$loop]["givenname"][0],
		      "cn"                  => $info[$loop]["cn"][0],
		      "uid"                 => $info[$loop]["uid"][0],
                    );
            }
          }
        }
        @ldap_free_result ( $result );
      } else $error = "Erreur de lecture dans l'annuaire LDAP";
    } else $error = "Echec du bind en admin";
    @ldap_close ( $ds );
  } else $error = "Erreur de connection au serveur LDAP";
  // Tri du tableau par ordre alphabetique
  if (count($ret)) usort($ret, "cmp_name");
  return $ret;
} // Fin function search_people_trash
*/



/**
* Retourne un tableau d'affichage
* @Parametres
* @return
*/


function draw_table_result ( $msg_cat, $type1, $type2, $type3, $mode="" ) {
	$html="<table style='margin-left: 200px; text-align: left; width: 450px;' border='1' cellpadding='1' cellspacing='1'>\n";
  	$html.="<tbody>\n";
    	$html.="<tr>\n";
      	$html.="<td style='text-align: center; width: 300px; height: 20px; ' colspan='1' rowspan='2'>Utilisateur</td>\n";
      	$html.="<td style='text-align: center; width: 150px; height: 20px; ' colspan='3' rowspan='1'>$msg_cat</td>\n";
    	$html.="</tr>\n";
    	$html.="<tr>\n";
        if($mode=='') {
            $html.="<td style='text-align: center; width: 50px; height: 20px; '>$type1</td>\n";
            $html.="<td style='text-align: center; width: 50px; height: 20px;'>$type2</td>\n";
            $html.="<td style='text-align: center; width: 50px;  height: 20px;'>$type3</td>\n";
        }
        else {
            $html.="<td style='text-align: center; width: 50px; height: 20px; '>$type1<br />";
            $html.="<a href='#' onclick=\"coche('1');return false;\"><img src='../elements/images/enabled.png' border='0' /></a>";
            $html.=" / ";
            $html.="<a href='#' onclick=\"decoche('1');return false;\"><img src='../elements/images/disabled.png' border='0' /></a>";
            $html.="</td>\n";

            $html.="<td style='text-align: center; width: 50px; height: 20px; '>$type2<br />";
            $html.="<a href='#' onclick=\"coche('2');return false;\"><img src='../elements/images/enabled.png' border='0' /></a>";
            $html.=" / ";
            $html.="<a href='#' onclick=\"decoche('2');return false;\"><img src='../elements/images/disabled.png' border='0' /></a>";
            $html.="</td>\n";

            $html.="<td style='text-align: center; width: 50px; height: 20px; '>$type3<br />";
            $html.="<a href='#' onclick=\"coche('3');return false;\"><img src='../elements/images/enabled.png' border='0' /></a>";
            $html.=" / ";
            $html.="<a href='#' onclick=\"decoche('3');return false;\"><img src='../elements/images/disabled.png' border='0' /></a>";
            $html.="</td>\n";
        }
        $html.="</tr>\n";
    	$html.="<tr>\n";
	echo $html;
} // Fin function draw_table_result



/**
* Fonction qui affiche les info-bulles
* @Parametres  msg le n° du message a afficher
* @return l'info-bulle
*/


function msgaide($msg) {
    return ("&nbsp;<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('".$msg."')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>");
}
$msg1="Recherche les comptes des utilisateurs qui ne sont plus affect&#233;s &#224; un groupe principal et transfert ces comptes &#224; la corbeille.";
$msg2="Visualise la liste des comptes transf&#233;r&#233;s dans la corbeille.";
$msg3="Permet de r&#233;activer un ou des comptes sous reserve que les uid et/ou les uidNumber de ces comptes soient encore libre.";
$msg4="Efface les r&#233;pertoires homes des utilisateurs situ&#233;s dans la corbeille.";

$msg4bis="D&#233;place les r&#233;pertoires homes des utilisateurs situ&#233;s dans la corbeille.<br />";
$msg4bis.="Cela donne un d&#233;lais avant effacement et laisse la place libre dans /home/ pour un nouveau compte de meme uid.";

$msg5="Supprime les comptes de la corbeille !<br/><strong>ATTENTION</strong> : Ne supprimer les comptes de la corbeille que lorsque vous avez effectu&#233; l\'effacement des homes sur l\'ensemble des serveurs qui partagent votre annuaire avec votre LCS ou votre SE3.";
$msg6="Ce compte, n\'est pas r&#233;cup&#233;rable car il poss&#232;de un uid ou un uidnumber d&#233;sormais occup&#233;.";

// Messages
$msg_confirm = "Avant de vider la corbeille, assurez vous d'avoir pr&#233;alablement nettoy&#233; les homes des comptes orphelins sur l'ensemble des serveurs qui partagent votre annuaire avec SE3.<br>";
$msg_confirm .= "<a href=\"ldap_cleaner.php?do=4&phase=1\" target=\"main\">Nettoyage !</a>";

echo "<html>\n";
echo "	<head>\n";
echo "		<title>...::: Interface d'administration Serveur SE3 :::...</title>\n";

// Initialisation variables :
$PHP_SELF = $_SERVER['PHP_SELF'];
// Methode POST
$filtre = $_POST['filtre'];
//$filter_type = $_POST['filter'];
$filter_type = $_POST['filter_type'];
$nbr = $_POST['nbr'];
$cat = $_POST['cat'];
// Methodes POST ou GET
if ( isset($_POST['phase']) )
    $phase = $_POST['phase'];
elseif ( isset($_GET['phase']) )
    $phase = $_GET['phase'];

if ( isset($_POST['do']) )
    $do = $_POST['do'];
elseif ( isset($_GET['do']) )
    $do = $_GET['do'];

$mode_clean=isset($_POST['mode_clean']) ? $_POST['mode_clean'] : (isset($_GET['mode_clean']) ? $_GET['mode_clean'] : NULL);

// Redirection vers phase suivante, gestion du «sablier»
### DEBUG  echo "debug1 do:$do phase:$phase<br>";
// Cas 1 : Transfert des utilisateurs dans la Trash
if( $do==1 && $phase!=1 ) {
	### DEBUG echo "debug2 do:$do phase:$phase<br>";
	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=1&phase=1'\">\n";
}
// Cas 2 : Examiner le contenu de la corbeille
if( $do==2 && $phase!=1 ) {
	### DEBUG echo "debug2 do:$do phase:$phase<br>";
	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=2&phase=1'\">\n";
}
// Cas 3 : Effacer les «homes» des comptes orphelins
if( $do==3 && $phase!=1 ) {
	### DEBUG echo "debug2 do:$do phase:$phase<br>";
    if(isset($mode_clean)) {
    	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=3&phase=1&mode_clean=$mode_clean'\">\n";
    }
    else {
    	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=3&phase=1'\">\n";
    }
}
// Cas 4 : Vider la corbeille
if( $do==4 && $phase==1 ) {
	### DEBUG echo "debug2 do:$do phase:$phase<br>";
	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=4&phase=2'\">\n";
}
// Cas 10 : Recuperation des utilisateurs de Trash vers People
if( $do==10 && $phase==2 ) {
	### DEBUG echo "debug2 $do $phase<br>";
	echo "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"1;url='$PHP_SELF?do=10&phase=3'\">\n";
}
// Fin traitement des redirections
echo "	</head>\n";
echo "	<body>\n";

if (is_admin("se3_is_admin",$login)=="Y") {

	//Aide
	$_SESSION["pageaide"]="Annuaire#Nettoyage_des_comptes";


	$html = "<div style=\"margin-bottom: 15%\"><H1>Gestion des comptes orphelins</H1>\n";

	if ($do !="1") 	$html .= "<li><a href=\"ldap_cleaner.php?do=1\" target=\"main\">Transfert des comptes orphelins dans la corbeille</a>".msgaide($msg1)."</li>\n";
	if ($do !="2") 	$html .= "<li><a href=\"ldap_cleaner.php?do=2\" target=\"main\">Examiner le contenu de la corbeille</a>".msgaide($msg2)."</li>\n";
			$html .= "<li><a href=\"ldap_cleaner.php?do=10\" target=\"main\">R&#233;cup&#233;ration de comptes orphelins depuis la corbeille</a>".msgaide($msg3)."</li>\n";
	//if ($do !="3") 	$html .= "<li><a href=\"ldap_cleaner.php?do=3\" target=\"main\">Effacer les «homes» des comptes orphelins</a>".msgaide($msg4)."</li>\n";
	if ($do !="3") {
    	$html .= "<li><a href=\"ldap_cleaner.php?do=3\" onclick=\"return getconfirm();\" target=\"main\">Effacer les «homes» des comptes orphelins</a>".msgaide($msg4)."<br />\n";
	    $html .= "ou <a href=\"ldap_cleaner.php?do=3&amp;mode_clean=mv\" target=\"main\">Deplacer les «homes» des comptes orphelins vers un dossier temporaire</a>".msgaide($msg4bis)."</li>\n";
    }
	if ($do !="4") 	$html .= "<li><a href=\"ldap_cleaner.php?do=4\" target=\"main\">Vider la corbeille</a>".msgaide($msg5)."</li>\n";
	$html .="<p></p>";
	echo $html;

	// Actions
	switch ($do) {
		case 1:
			// Transfert des comptes orphelins dans la corbeille
			if ( $phase != 1 )
				// Affichage du sablier
				echo "<div align='center'><img src=\"images/wait.gif\" title=\"Patientez...\" align=\"middle\" border=\"0\">&nbsp;Transfert des comptes orphelins dans la corbeille en cours. Veuillez patienter...</div>";
			else {
				// On commence par controler s'il n'y a pas des utilisateurs qui ne sont plus ni dans Profs ni dans Eleves alors qu'ils ont le droit no_Trash_user

				$tmp_tab_no_Trash_user=gof_members("no_Trash_user","rights",1);
				if(count($tmp_tab_no_Trash_user)>0) {
					echo "<p>Controle des titulaires du droit no_Trash_user avant d'effectuer le transfert des comptes orphelins.</p>\n";
					echo "<blockquote>\n";
					$cpt_retablissement_no_trash=0;
					$attribut=array("cn");
					for($loop=0;$loop<count($tmp_tab_no_Trash_user);$loop++) {
						$uid=$tmp_tab_no_Trash_user[$loop];
						//echo "\$tmp_tab_no_Trash_user[$loop]=$uid<br />";

						/*
						$grp_no_Trash="";
						$tabtmp=get_tab_attribut("groups", "(&(cn=Profs)(memberuid=$uid))", $attribut);
						if(count($tabtmp)>0) {
							$grp_no_Trash="Profs";
						}
						else {
							$tabtmp=get_tab_attribut("groups", "(&(cn=Eleves)(memberuid=$uid))", $attribut);
							if(count($tabtmp)>0) {
								$grp_no_Trash="Eleves";
							}
						}
						*/
						// S'ils ont ete supprimes de Eleves et Profs, on ne peut plus les reaffecter dans le bon groupe
						// Par defaut, on les declare Profs (parce qu'il y a plus de chance qu'on mette un Prof en no_Trash_user qu'un eleve) et on alerte.
						$grp_no_Trash="Profs";
						//echo "\$grp_no_Trash=$grp_no_Trash<br />";

						//if($grp_no_Trash!="") {
						if(($grp_no_Trash!="")&&($uid!="admin")) {
							$attribut=array("uid");
							$compte_existe=get_tab_attribut("people", "uid=$uid", $attribut);
							if(count($compte_existe)==0) {
								// Le compte n'existe plus... et on a oublié de nettoyer no_Trash_user
								$attributs=array();
								$attributs["member"]="uid=$uid,".$dn["people"];

								echo "Le compte $uid n'existe plus.<br />Suppression de l'association de $uid au droit no_Trash_user: ";
								if(modify_attribut("cn=no_Trash_user", "rights", $attributs, "del")) {
									echo "<font color='green'>SUCCES</font>";
								}
								else {
									echo "<font color='red'>ECHEC</font>";
								}
							}
							else {
								// On contrôle si le compte est membre du groupe $grp_no_Trash
								$attribut=array("memberuid");
								$memberUid=get_tab_attribut("groups", "(&(cn=$grp_no_Trash)(memberuid=$uid))", $attribut);
								if(count($memberUid)>0) {
									echo "$uid est déjà membre du groupe $grp_no_Trash.";
								}
								else {
									echo "R&#233;tablissement de $uid comme membre du groupe $grp_no_Trash: ";
									$attributs=array();
									$attributs["memberuid"]=$uid;
									if(modify_attribut ("cn=$grp_no_Trash", "groups", $attributs, "add")) {
										echo "<font color='green'>SUCCES</font>";
									}
									else {
										echo "<font color='red'>ECHEC</font>";
									}
									$cpt_retablissement_no_trash++;
								}
							}
							echo "<br />\n";
						}
					}
					if($cpt_retablissement_no_trash>0) {
						echo "<p>Un ou des utilisateurs ont &#233;t&#233; r&#233;tablis comme membres du groupe Profs pour &#233;viter une mise à la corbeille.<br />Si ce n'&#233;tait pas leur groupe d'appartenance initiale, il faudra corriger manuellement dans Annuaire/Acc&#232;s &#224; l'annuaire</p>\n";
					}
					echo "</blockquote>\n";
				}


				// Transfert des comptes orphelins dans la corbeille
        			exec ("/usr/share/se3/sbin/searchAndDelete.pl" ,$AllOutPut,$ReturnValue);
        			if ($ReturnValue == "0")
					echo "Le transfert des  comptes orphelins dans la corbeille s'est d&#233;roul&#233; avec succ&#232;s.<br>";
				else
          				echo "<div class=error_msg>Echec du tansfert des  comptes orphelins dans la corbeille !</div>";
			}
			break;
		case 2 :
			//Examiner le contenu de la corbeille
	                if ( $phase != 1 )
	                         // Affichage du sablier
	                         echo "<div align='center'><img src=\"images/wait.gif\" title=\"Patientez...\" align=\"middle\" border=\"0\">&nbsp;Examen du contenu de la poubelle. Veuillez patienter...</div>";
	                else {
			         $users = search_people_trash ("cn=*");
			         echo "<p><img src=\"images/";
			         if (count($users) == 0 ) echo "Poubelle_vide.png";
			         else echo "Poubelle_pleine.png";
			         echo "\" alt=\"Corbeille\" width=\"51\" height=\"65\" align=\"middle\" border=\"0\">&nbsp;Il y a <STRONG>".count($users)."</STRONG> utilisateur";
			         if (count($users) > 1 ) echo "s";
			         echo "&nbsp;dans la corbeille.</p>\n";
      			         echo "<UL>\n";
      			         for ($loop=0; $loop<count($users);$loop++)
        			    echo "<LI>".utf8_decode($users[$loop]["cn"])."</LI>\n";
      			         echo "</UL>\n";
	                }
			break;
		case 3 :
			// Nettoyage des repertoires home
			if ( $phase != 1 )
				// Affichage du sablier
				echo "<div align='center'><img src=\"images/wait.gif\" title=\"Patientez...\" align=\"middle\" border=\"0\">&nbsp;Le nettoyage des r&#233;pertoires «homes» est en cours. Veuillez patienter...</div>";
			else {
                //echo "\$_GET['mode_clean']=".$_GET['mode_clean']."<br />";
                if($mode_clean=='mv') {
                    echo "<h4>Deplacement des Homes...</h4>";
                    exec ("/usr/bin/sudo /usr/share/se3/scripts/mv_Trash_Home.sh" ,$AllOutPut,$ReturnValue);
                    //echo "\$ReturnValue=$ReturnValue<br />";
                    if($ReturnValue!="0") {echo "<div class='error_msg'>Une erreur s'est produite???</div>";}
                    for($loop=0;$loop<count($AllOutPut);$loop++) {
                        //echo "\$AllOutPut[$loop]=".$AllOutPut[$loop]."<br />";
                        echo $AllOutPut[$loop]." ";
                    }
                    echo "<p>Termin&#233;.</p>\n";
                }
                else {
                    exec ("/usr/bin/sudo /usr/share/se3/scripts/delHome.pl" ,$AllOutPut,$ReturnValue);
                    if ($ReturnValue == "0") {
                        echo "Le nettoyage des r&#233;pertoires «home» s'est d&#233;roul&#233; avec succ&#232;s.<br>";
                    }
                    else {
                        echo "<div class='error_msg'>Echec du nettoyage des r&#233;pertoires «home» !</div>";
                    }
                }
            }
			break;
		case 4;
		 	// Vidage de la corbeille
			if ( $phase != 1 && $phase != 2 )
				// Affichage du message de confirmation
				echo "<div class=error_msg>$msg_confirm</div>";
			elseif ($phase == 1 )
				// Affichage du sablier
				echo "<div align='center'><img src=\"images/wait.gif\" title=\"Patientez...\" align=\"middle\" border=\"0\">&nbsp;Vidage de la corbeille en cours. Veuillez patienter...</div>";
			elseif ($phase == 2 ) {
				echo "Le nettoyage de la corbeille s'est d&#233;roul&#233; avec succ&#232;s.<br>";
				$users = search_people_trash ("cn=*");
      				for ($loop=0; $loop<count($users);$loop++) {
			        	$entry="uid=".$users[$loop]["uid"].",".$dn["trash"];
					exec ("/usr/share/se3/sbin/entryDel.pl $entry" ,$AllOutPut,$ReturnValue);
      				}
				$users = search_people_trash ("cn=*");
				if (count($users) == 0 ) echo "Le nettoyage de la corbeille s'est d&#233;roul&#233; avec succ&#232;s.<br>";
				else echo "<div class=error_msg>Echec du nettoyage de la corbeille !</div>";
			}
			break;
		case 10;
			// Recuperation de comptes orphelins
			// Choix d'un filtre de recherche
			if ( $phase != 1 && $phase != 2 && $phase != 3) {
				$html="<p><u>Recherche des comptes orphelins &#224; transf&#233;rer</u> :</p>\n";
				$html.="<div style='margin-left: 40px'>\n";
				$html.="<form action='ldap_cleaner.php?do=10' method = 'post'>\n";
				$html.="Filtre de recherche&nbsp;";
				$html.="<select name='filter_type'>\n";
				$html.="<option value='contient'>contient</option>\n";
				$html.="<option value='commence'>commence par</option>\n";
				$html.="<option value='finit'>finit par</option>\n";
	      			$html.="</select>\n";
				$html.="<input type='text' name='filtre'>\n";
				$html.="<input type='hidden' name='phase' Value='1'>\n";
				$html.="<input type='submit' Value='Rechercher'>\n";
				$html.="</form></div>\n";
				echo $html;
			} elseif ( $phase == 1 ) {
				// Affichage de la liste des comptes orphelins
				// Interpretation du type de filtre
				if ($filter_type == "contient" ) if ($filtre!="*") $filtre="*".$filtre."*";
				if ($filter_type == "commence" ) $filtre=$filtre."*";
				if ($filter_type == "finit" ) $filtre="*".$filtre;
				// Recherche des utilisateurs repondant au critere
				$users = search_people_trash ("cn=$filtre");
				echo "<div align='center'><img src=\"images/";
				if (count($users) == 0 ) echo "Poubelle_vide.png";
				else echo "Poubelle_pleine.png";
				echo "\" alt=\"Corbeille\" width=\"51\" height=\"65\" align=\"middle\" border=\"0\">&nbsp;Il y a <STRONG>".count($users)."</STRONG> utilisateur";
				if ( count($users) >= 2 ) echo "s";
				echo "&nbsp;dans la corbeille qui r&#233;pond";
				if ( count($users) >= 2 ) echo "ent";
				echo " au «<em>filtre</em>» de recherche.</div>\n";
				// Affichage de la liste des utilisateurs a recuperer
				if ( count($users) > 0) {
					$html="<form action='ldap_cleaner.php?do=10' method = 'post'>\n";
					// Tableau d'affichage des resultats
					draw_table_result ("Cat&#233;gorie", "Eleve", "Professeur", "Administratif","waouh");
      					for ($loop=0; $loop<count($users);$loop++) {
						$html.="<tr><td style='width: 300px;'>".utf8_decode( $users[$loop]["cn"] )."</td>\n";
                                                $NoRecup = false;
                                                # test si on peut recuperer le compte
                                                $attribut[0]="uidnumber";
                                                $tab=get_tab_attribut("people", "uid=*", $attribut);
                                                for($i=0;$i<count($tab);$i++){
                                                    if ( $tab[$i] == $users[$loop]["uidnumber"] ) {
                                                        $NoRecup = true;
                                                        break;
                                                    }
                                                }
                                                unset($attribut,$tab);
                                                $attribut[0]="uid";
                                                $tab=get_tab_attribut("people", "uid=*", $attribut);
                                                for($i=0;$i<count($tab);$i++){
                                                    if ( $tab[$i] == $users[$loop]["uid"] ) {
                                                        $NoRecup = true;
                                                        break;
                                                    }
                                                }
                                                if($users[$loop]["employeenumber"]!="") {
                                                    unset($attribut,$tab);
                                                    $attribut[0]="employeenumber";
                                                    $tab=get_tab_attribut("people", "uid=*", $attribut);
                                                    for($i=0;$i<count($tab);$i++){
                                                        if ( $tab[$i] == $users[$loop]["employeenumber"] ) {
                                                            $NoRecup = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                if ( $NoRecup ) {
						  $html.="<td colspan='3' style='text-align: center; width: 150px; font-size:0.7em; font-weight:bold; color:#FDAF4E;'>&nbsp;Ce compte n'est pas r&#233;cup&#233;rable.&nbsp;".msgaide($msg6)."</td>\n";
                                                } else {
						  $html.="<td style='text-align: center; width: 50px;'><input type='radio' id='cat_1_".$loop."' name='cat[$loop]' value='".$users[$loop]["uid"]."@@Eleves'></td>\n";
						  $html.="<td style='text-align: center; width: 50px;'><input type='radio' id='cat_2_".$loop."' name='cat[$loop]' value='".$users[$loop]["uid"]."@@Profs'></td>\n";
						  $html.="<td style='text-align: center; width: 50px;'><input type='radio' id='cat_3_".$loop."' name='cat[$loop]' value='".$users[$loop]["uid"]."@@Administratifs'></td></tr>\n";
                                                }
      					}
					$html.="</tbody>\n</table>\n";
					$html.="<input type='hidden' name='phase' Value='2'>\n";
					$html.="<input type='hidden' name='nbr' Value='$loop'>\n";
					$html.="<div style='margin-left: 200px'>\n";
					$html.="<input type='submit' Value='R&#233;cup&#233;rer'>\n";
					$html.=" <input type='reset' Value='R&#233;initialiser'>\n";
					$html.="</form></div>\n";

                    $html.="<script type='text/javascript'>
    function coche(col) {
        for(i=0;i<".$loop.";i++) {
            if(document.getElementById('cat_'+col+'_'+i)) {
                document.getElementById('cat_'+col+'_'+i).checked=true;
            }
        }
    }

    function decoche(col) {
        for(i=0;i<".$loop.";i++) {
            if(document.getElementById('cat_'+col+'_'+i)) {
                document.getElementById('cat_'+col+'_'+i).checked=false;
            }
        }
    }
</script>\n";

				} else $html = "<div class='alert_msg'>Pas de transfert &#224; effectuer !</div>\n";
				echo $html;
			} elseif ( $phase == 2 ) {
				// Transfert des comptes de trash -> peoples et positionnement des groupes principaux
				// Transfert des utilisateurs selectionne dans /tmp/list_recup
				for ($loop=0; $loop<$nbr;$loop++) {
					if ( isset($cat[$loop]) ) {
						$tmp = $cat[$loop];
						exec ("echo $tmp >> /tmp/list_recup");
					}
				}
				// Affichage du sablier
				echo "<div align='center'><img src=\"images/wait.gif\" title=\"Patientez...\" align=\"middle\" border=\"0\"> R&#233;cup&#233;ration des comptes orphelins en cours. Veuillez patienter...</div>";
			} elseif ( $phase == 3 ) {
				// Recuperation des utilisateurs selectionnes
				if ( file_exists("/tmp/list_recup") ) {
					$fd = fopen("/tmp/list_recup", "r");
					draw_table_result ("R&#233;cup&#233;ration dans la cat&#233;gorie", "Eleve", "Professeur", "Administratif");
					while ( !feof($fd) ) {
						$tmp = fgets($fd, 255);
				        	$trash_member=explode("@@", $tmp);
						// Nettoyage des espaces dans trash_member[1]
						$categorie=trim($trash_member[1]);
						// uid => $trash_member[0]
						// Categorie $trash_member[1]
						if ( $trash_member[0] != "" ) {
							// Lecture des params de l'utilisateur selectionne dans la trash
							$user = search_people_trash ("uid=$trash_member[0]");
							// Positionnement des constantes "objectclass"
							$user[0]["sambaacctflags"]="[U         ]";
							$user[0]["objectclass"][0]="top";
							$user[0]["objectclass"][1]="posixAccount";
							$user[0]["objectclass"][2]="shadowAccount";
							$user[0]["objectclass"][3]="person";
							$user[0]["objectclass"][4]="inetOrgPerson";
							$user[0]["objectclass"][5]="sambaAccount";
                                                        $user[0]["objectclass"][5]="sambaSamAccount";
							### DEBUG
                                                        if ( $DEBUG=="true" ) {
							     echo "------------------------------------------<br>";
	                                                     echo "sambaacctflags :".$user[0]["sambaacctflags"]."<br>";
							     echo "sambapwdmustchange :".$user[0]["sambapwdmustchange"]."<br>";
							     echo "sambantpassword :".$user[0]["sambantpassword"]."<br>";
							     echo "sambalmpassword :".$user[0]["sambalmpassword"]."<br>";
                                                             echo "sambaSID :".$user[0]["sambasid"]."<br>";
                                                             echo "SambaprimaryGroup".$user[0]["sambaprimarygroupsid"]."<br>";
                                                             echo "userPassword :".$user[0]["userpassword"]."<br>";
							     echo "gecos :".$user[0]["gecos"]."<br>";
                                                             echo "employeenumber :".$user[0]["employeenumber"]."<br>";
							     echo "homedirectory :".$user[0]["homedirectory"]."<br>";
							     echo "gidnumber :".$user[0]["gidnumber"]."<br>";
							     echo "uidnumber :".$user[0]["uidnumber"]."<br>";
							     echo "loginshell :".$user[0]["loginshell"]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][0]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][1]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][2]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][3]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][4]."<br>";
							     echo "objectclass :".$user[0]["objectclass"][5]."<br>";
							     echo "mail :".$user[0]["mail"]."<br>";
							     echo "sn :".$user[0]["sn"]."<br>";
							     echo "givenname :".$user[0]["givenname"]."<br>";
							     echo "cn :".$user[0]["cn"]."<br>";
							     echo "uid :".$user[0]["uid"]."<br>";
							     echo "------------------------------------------<br>";
                                                        }
							### FIN DEBUG
							// Modification de l'entree dn ou=Trash -> ou=People
                                                        $ds = @ldap_connect ( $ldap_server, $ldap_port );
	                                                if ( $ds ) {
	          						$r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
        	  						if ($r) {
										// Ajout dans la branche people
              									if ( @ldap_add ($ds, "uid=".$user[0]["uid"].",".$dn["people"],$user[0] ) ) {
											// Suppression de la branche Trash
											@ldap_delete ($ds, "uid=".$user[0]["uid"].",".$dn["trash"] );
											// Ajout au groupe principal
											@exec ("/usr/share/se3/sbin/groupAddUser.pl $trash_member[0] $categorie");
										        $recup=true;
										} else $recup=false;
									}
							}
							ldap_close ( $ds );
							// Affichage des utilisateurs recuperes
		                                        $html="<tr><td style='width: 300px;'>";
							if ( $recup )  $html.="<a href='people.php?uid=".$user[0]["uid"]."'>";
							$html.=utf8_decode( $user[0]["cn"] );
							if ( $recup ) $html.="</a>";
							$html.="</td>\n";
							if ( $recup ) {
								$html.="<td style='text-align: center; width: 50px;'>";
								if ( $categorie == "Eleves" ) $html.="<b>X</b>"; else $html.="&nbsp;";
								$html.="</td>\n";
								$html.="<td style='text-align: center; width: 50px;'>";
								if ( $categorie == "Profs" ) $html.="<b>X</b>"; else $html.="&nbsp;";
								$html.="</td>\n";
								$html.="<td style='text-align: center; width: 50px;'>";
								if ( $categorie == "Administratifs" ) $html.="<b>X</b>"; else $html.="&nbsp;";
								$html.="</td></tr>\n";
							} else $html.="<td colspan='3' style='color: red; text-align: center; width: 50px;'>Compte irr&#233;cup&#233;rable !</td></tr>\n";
							echo $html;
						}
					}
					fclose($fd);
					unlink ("/tmp/list_recup");
					echo "</tbody>\n</table>\n";
				} else  echo "<div class=error_msg>Vous n'avez pas s&#233;lectionn&#233; d'utilisateur(s) &#224; r&#233;cup&#233;rer!</div>";
			}
		break; // Fin case
	}
} else echo "Vous n'avez pas les droits n&#233;cessaires pour cette action...";
include ("pdp.inc.php");
?>
