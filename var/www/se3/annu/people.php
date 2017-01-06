<?php


   /**
   
   * Affiche les utilisateur a partir de l'annuaire
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
   * file: people.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// Aide
$_SESSION["pageaide"]="Annuaire#Voir_ma_fiche";

echo "<h1>".gettext("Annuaire")."</h1>\n";

$uid = isset($_GET['uid']) ? $_GET['uid'] : "";

if($uid=='') {
	echo "<p style='color:red;'>ERREUR: uid non choisi.</p>";
	include ("pdp.inc.php");
	die();
}

aff_trailer ("3");
#$TimeStamp_0=microtime();
// correctif provisoire
if(isset($user)) {
	$user_tmp = $user;
	// fin correctif
}

list($user, $groups)=people_get_variables($uid, true);

#$TimeStamp_1=microtime();
#############
# DEBUG     #
#############
#echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
#############
# Fin DEBUG #
#############
echo "<a href='people.php?uid=".$user["uid"]."' title=\"Rafraichir la page\"><H3>".$user["fullname"]."</H3></a>\n";

if((ldap_get_right("Annu_is_admin",$login)=="Y")&&(isset($_GET['create_home']))&&($_GET['create_home']=='y')) {
    echo "<p><b>Cr&#233;ation du dossier personnel de ".$user["uid"]."&nbsp;: </b>";
    exec("sudo /usr/share/se3/shares/shares.avail/mkhome.sh ".$user["uid"],$ReturnValue2);
    if(count($ReturnValue2)==0) {
        echo "<span style='color:green'>OK</span></p>";
    }
    else {
        echo "</p><pre style='color:red'>";
        foreach($ReturnValue2 as $key => $value) {
            echo "$value";
        }
    }
    echo "</pre>";
}

echo "<table width=\"80%\"><tr><td>";  
	if ($user["description"]) echo "<p>".$user["description"]."</p>";
  	if ( count($groups) ) {
    		echo "<U>Membre des groupes</U> :<BR><UL>\n";
    		for ($loop=0; $loop < count ($groups) ; $loop++) {

			// Si les bons droits on place un lien sur les groupes
			echo "<LI>";
			if ((ldap_get_right("annu_can_read",$login)=="Y") or (ldap_get_right("Annu_is_admin",$login)=="Y") or (ldap_get_right("sovajon_is_admin",$login)=="Y")) {
      				echo "<A href=\"group.php?filter=".$groups[$loop]["cn"]."\">";
      			}
      			if ($groups[$loop]["type"]=="posixGroup") echo "<STRONG>".$groups[$loop]["cn"]."</STRONG>";
      			else
        			echo $groups[$loop]["cn"];
			if ((ldap_get_right("annu_can_read",$login)=="Y") or (ldap_get_right("Annu_is_admin",$login)=="Y") or (ldap_get_right("sovajon_is_admin",$login)=="Y")) {
      				echo "</A>";
			}	
			echo "<font size=\"-2\"> ".$groups[$loop]["description"];
      			//$login1=split ("[\,\]",ldap_dn2ufn($groups[$loop]["owner"]),2);
      			$login1=preg_split ("/,/",ldap_dn2ufn($groups[$loop]["owner"]),2);
      			if ( $uid == $login1[0] ) echo "<strong><font color=\"#ff8f00\">&nbsp;(".gettext("professeur principal").")</font></strong>";
      			echo "</font></LI>\n";
       			echo "</font></li>";
      
      			// modif propos&#233;e par MC Marques
      			if (is_admin("Annu_is_admin",$login) == "Y" ) {
        		?>
        			&nbsp;&nbsp;&nbsp;&nbsp;<a href="del_user_group_direct.php?uid=<?php echo $user["uid"]?>&cn=<?php echo $groups[$loop]["cn"] ?>" onclick= "return getconfirm();"><font size="2"><?php echo gettext("retirer du groupe"); ?></a></font><br>
        			<?php
        			//R&#233;cup&#233;ration de tous les groupes de l'utilisateur
         			if(isset($groups[$loop]["cn"])) {
						// A quoi sert ce $cn ???
						if(!isset($cn)) {$cn="";}
						$cn=$cn."&cn".$loop."=".$groups[$loop]["cn"];
					}
      			}
      			
			//fin modif 
      
    		}
    		echo "</UL>";
  	}
  	//echo "<br>Pages perso : <a href=\"../~".$user["uid"]."/\"><tt>".$baseurl."~".$user["uid"]."</tt></a><br>\n";
  	// echo "Adresse m&#232;l : <a href=\"mailto:".$user["email"]."\"><tt>mailto:".$user["email"]."</a></tt><br>\n";
   	// modif propos&#233;e par MC Marques
   	if (is_admin("Annu_is_admin",$login) == "Y" ) {
      	?>
	      <ul style="color: red;">
	      <li><a href="add_user_group.php?uid=<?php echo $user["uid"] ?>"><?php echo gettext("Ajouter &agrave; des groupes"); ?></a><br>
	      <li><a href="del_group_user.php?uid=<?php echo $user["uid"] ?>"><?php echo gettext("Enlever de certains groupes"); ?></a><br>
	      </ul>
	      <?php
	}
	// fin modifs


	echo gettext("Adresse m&#233;l")." : <a href=\"mailto:".$user["email"]."\"><tt>".$user["email"]."</a></tt><br>\n";

	// Affichage Menu people_admin
	if (is_admin("Annu_is_admin",$login) == "Y" ) {
		echo "
	<br>
	<u>".gettext("Autres actions possibles")."</u>&nbsp;: <br />
	<ul style=\"color: red;\">
		<li><a href=\"mod_user_entry.php?uid=".$user["uid"].">".gettext("Modifier le compte")."</a><br />
		<li><a href=\"pass_user_init.php?uid=".$user["uid"].">".gettext("R&#233;initialiser le mot de passe")."</a><br />";

		//si compte actif
		if ("$smbversion" == "samba3") {
			$test_desac=search_people("(&(uid=".$user["uid"].") (sambaAcctFlags=[U ]))");
		} else {
			$test_desac=search_people("(&(uid=".$user["uid"].") (acctFlags=[U ]))");
		}

		if (count($test_desac)==1) {
			echo "
		<li><a href=\"desac_user_entry.php?uid=".$user["uid"]."\" onclick= return getconfirm()>".gettext("D&#233;sactiver ce compte")." </a><br />";
			if(file_exists('/home/'.$user["uid"])) {
				echo "
		<li><span style='color:black'>Le dossier personnel existe.</span></li>";
			}
			else {
				echo "
		<li>Le dossier personnel n'existe pas.<br /><a href='people.php?uid=".$user["uid"]."&amp;create_home=y'>Cr&#233;er le dossier personnel maintenant</a><br />(<em>sinon, il sera cr&#233;&#233; lors de la premiere connexion de l'utilisateur</em>)</li>";
			}

		} else {
			//si compte desactive
			echo "
		<li><a href=\"desac_user_entry.php?uid=".$user["uid"]."&action=activ\" >".gettext("Activer ce compte")." </a><br>\n";
			if(!file_exists('/home/'.$user["uid"])) {
				echo "
		<li>Le dossier personnel n'existe pas.<br /><a href='people.php?uid=".$user["uid"]."&amp;create_home=y'>Cr&#233;er le dossier personnel maintenant</a><br />(<em>sinon, il sera cr&#233;&#233; lors de la premiere connexion de l'utilisateur</em>)</li>";
			}
		}
		?>

		<li><a href="del_user.php?uid=<?php echo $user["uid"] ?>" onclick= "return getconfirm();"><?php echo gettext("Supprimer le compte"); ?></a><br>
		<li><a href="del_nt_profile.php?uid=<?php echo $user["uid"] ?>&action=del" onclick= "return getconfirm();"><?php echo gettext("Reg&#233;n&#233;rer le profil errant Windows"); ?></a><br>
		<?php exec ("/usr/share/se3/sbin/getUserProfileInfo.pl $user[uid]",$AllOutPut,$ReturnValue);

		if ($AllOutPut[0]=="lock") {
			echo "
		<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=unlock\">".gettext("D&#233;verrouiller le profil Windows...")."</a><br>\n";
		} else {
			echo "
		<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=lock\">".gettext("Verrouiller le profil Windows...")."</a><br>\n";
		}?>

		<li><a href="pop_user.php?uid=<?php echo $user["uid"] ?>"><?php echo gettext("Envoyer un Pop Up"); ?></a><br>

		<!--li><a href="html/AdminUserBdd.html">Ouvrir la base de donne&eacute;es</a><br-->
		<!--<li><a href="html/AdminUserWeb.html">Activer l'espace <em>Web</em></a>-->
		<?php       
	} // Fin affichage menu people_admin
  
  	if (ldap_get_right("se3_is_admin",$login)=="Y") {
    		echo "<li><a href=\"add_user_right.php?uid=" . $user["uid"] ."\">".gettext("G&#233;rer les droits")."</a><br>"; 
    		echo "<li><a href=\"../parcs/show_histo.php?selectionne=3&amp;user=$uid\">".gettext("Voir les connexions")."</a><br>"; // Ajout leb
  	}
  	echo "</ul>";
  	
	
	
	// Test de l'appartenance a la classe pour le droit  sovajon_is_admin
	// Afin d'eviter les doublons si le mec est admin_is_admin il ne peut pas
	// voir cette partie puisqu'il peut la voir par ailleurs

	// si les droits étendus du groupe profs sont activés, le test sur la classe n'est pas nécessaire
	$acl_group_profs_classes = exec("cd /var/se3/Classes; /usr/bin/getfacl . | grep group:Profs >/dev/null && echo 1");
	
	if (is_admin("Annu_is_admin",$login) != "Y") {
  		if ((tstclass($login,$user["uid"])==1) or ($acl_group_profs_classes == 1) and (ldap_get_right("sovajon_is_admin",$login)=="Y") and ($login != $user["uid"])) {
  		   // On teste si $user[uid] n'est pas un prof
            if (are_you_in_group($user["uid"],"Eleves")=="true") {
			echo "<br>\n";
  			echo "<ul style=\"color: red;\">\n";

    			echo "<li><a href=\"pass_user_init.php?uid=".$user["uid"]."\">".gettext("R&#233;initialiser le mot de passe")."</a><br>";
  			echo "<li><a href=\"mod_user_entry.php?uid=".$user["uid"]."\">".gettext("Modifier le compte de mon &#233;l&#232;ve ...")."</a><br>\n";
 			$test_desac=search_people("(uid=".$user["uid"].")&(acctFlags=[U           ])");
  			if (count($test_desac)==1) {
 				//si compte active
 	 			echo "<li><a href=\"desac_user_entry.php?uid=".$user["uid"]."\" onclick= return getconfirm()>".gettext("D&#233;sactiver ce compte")." </a><br>\n";
  			} else  {
  				//si compte desactive
  	 			echo "<li><a href=\"desac_user_entry.php?uid=".$user["uid"]."&action=activ\" >".gettext("Activer ce compte")." </a><br>\n";
   			}

  			echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=del\">".gettext("Reg&#233;n&#233;rer le profil Windows de mon &#233;l&#232;ve...")."</a><br>\n";

  			exec ("/usr/share/se3/sbin/getUserProfileInfo.pl $user[uid]",$AllOutPut,$ReturnValue);
  			if ($AllOutPut[0]=="lock") {
        			echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=unlock\">".gettext("D&#233;verrouiller le profil Windows...")."</a><br>\n";
  			} else {
        			echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=lock\">".gettext("Verrouiller le profil Windows...")."</a><br>\n";
  			}
  			echo "</ul>\n";
  		   } // Fin test si prof
		}
  	}
	
	
	// test du cas ou on veut modifier son propre compte 
  	if ($login==$user["uid"]) {
  		echo "<br>\n";
  		echo "<ul style=\"color: red;\">\n";
  		echo "<li><A HREF=\"../parcs/show_histo.php?selectionne=3&user=" . $user["uid"] ."\">".gettext("Voir mes connexions")."</A>";
  		echo "<li><A HREF=\"../infos/du.php?wrep=/home/$login&uid=$login\">".gettext("Espace occup&#233; par mon Home")."</A>";  
  		echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=del\">".gettext("Regenerer mon profil Windows...")."</a><br>\n";
  		exec ("/usr/share/se3/sbin/getUserProfileInfo.pl $user[uid]",$AllOutPut,$ReturnValue);
		if ($AllOutPut[0]=="lock") {
     			echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=unlock\">".gettext("D&#233;verrouiller mon profil Windows...")."</a><br>\n"; 
  		} else {
         		echo "<li><a href=\"del_nt_profile.php?uid=".$user["uid"]."&action=lock\">".gettext("Verrouiller mon profil Windows...")."</a><br>\n";
  		}

		if(ldap_get_right("fond_can_change",$login)) {
			echo "<li><a href=\"../fond_ecran/fond_perso.php\">".gettext("Personnaliser mon fond d'&#233;cran")."</a><br>\n";
		}

  		echo "</ul>\n";
  	}
	
	//modif proposee par Laurent COOPER
	if ((are_you_in_group($user["uid"],"Profs")=="true") && file_exists("/var/www/se3/annu/teacher.php")) {
	echo " \n <ul style=\"color:red;\"> \n <li> <a href=\"teacher.php\"> Mes services </a> </li> </ul>\n";
	}

	// Affichage des photos si presence du trombinoscope
        $tab_type=array("gif","png","jpg","jepg");
        echo "</td><td align=\"left\" valign=\"top\">";
        for ($j=0;$j<count($tab_type);$j++) {
                $photo="/var/se3/Docs/trombine/".$user["uid"].".".$tab_type[$j];
                // Supprime le 0 devant s'il existe
                $employeeNumber_gepi = preg_replace('/^[0]/','',$user["employeeNumber"]);
				if(!isset($rep_trombine)) {$rep_trombine="";} // A quoi sert le $rep_trombine ; Il a l'air de n'etre jamais initialise
                $photo_employeeNumber="$rep_trombine"."$employeeNumber_gepi".".".$tab_type[$j];

                if (file_exists("$photo")) {
                        echo "<IMG src=\"trombine/".$user["uid"].".".$tab_type[$j]." \" width=\"70\" height=\"90\" alt=\"$employeeNumber_gepi\">";
                } elseif (file_exists("/var/se3/Docs/trombine/$photo_employeeNumber")) {
                        echo "<IMG src=\"trombine/".$employeeNumber_gepi.".".$tab_type[$j]." \" width=\"70\" height=\"90\" alt=\"$employeeNumber_gepi\">";
                }

        }


	echo "</td></tr></table>";

	include ("pdp.inc.php");
?>
