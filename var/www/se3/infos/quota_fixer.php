<?php


   /**
   
   * Page permettant de fixer des quotas en dur pour les utilisateurs de se3 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lacroix (Olikin)

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Utilise le script quota_fixer_mysql.sh qui appelle quota.sh
   * @note contributions: Franck Molle, Stephane Boireau. Remerciements tous particuliers a Franck Molle. ;-)
   
   */

   /**

   * @Repertoire: /
   * file: quota_fixer.php

  */	


require("entete.inc.php");

//Verification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'authentification is_admin
require("ihm.inc.php");

// Internationalisation
require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

//aide
$_SESSION["pageaide"]="Quotas#Fixer_les_quotas";


$partition=$_POST['partition'];
if($partition=="") { $partition=$_GET['partition']; }
$quota=$_POST['quota'];
$depassement=$_POST['depassement'];
$browser=$_POST['browser'];
$grace=$_POST['grace'];
$valider_var_se3=$_GET['valider_var_se3'];
$valider_home=$_GET['valider_home'];
$classe_gr=$_POST['classe_gr'];
$equipe_gr=$_POST['equipe_gr'];
$matiere_gr=$_POST['matiere_gr'];
$autres_gr=$_POST['autres_gr'];
$user=$_POST['user'];
$messtxt_home=$_POST['messtxt_home'];
$messtxt_var_se3=$_POST['messtxt_var_se3'];
$nom=$_GET['nom'];
$suppr=$_GET['suppr'];


//AUTHENTIFICATION
if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

//EVALUE SI UNE SAISIE A ETE EFFECTUEE: AUTO-APPEL DE LA PAGE APRES FORMULAIRE REMPLI
if (isset($browser)) {
	//rajoute indirectement l'avertissement sur /home mais supprime dans la suite de la page si non choisi
        $browser = preg_replace('/\\\++/','/', $browser); //gestion de \ qui passe mal (suppression des \\ en les rempla&#231;ant par un /)
        exec("sudo /usr/share/se3/scripts/warn_quota.sh $browser");
        $query="UPDATE params SET value=\"$browser\" WHERE name=\"quota_browser\";";
	mysqli_query($GLOBALS["___mysqli_ston"], $query);
}

if (isset($quota) or isset($suppr)) {

	$nomscript=date("Y_m_d_H_i_s");
	$nomscript="tmp_quotas_$nomscript.sh";
	$nbr_user=0;

	system ("echo \"#!/bin/bash\n\" > /tmp/$nomscript");

	//  system ("echo \" \n\" >> /tmp/$nomscript");
	 // chmod ("/tmp/$nomscript",0700);//}
 
 	if (isset($suppr)) {
 		$uids = search_uids ("(cn=".$nom.")");
 	       	$people = search_people_groups ($uids,"(sn=*)","cat");
		$nbr_user=$nbr_user+count($people);
		
 		system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $nom $partition suppr \n\" >> /tmp/$nomscript");
	}


	//EVALUE SI UN NOUVEAU QUOTA A ETE DEMANDE: AUTO-APPEL DE LA PAGE APRES FORMULAIRE REMPLI

	//si aucun quota specifie: quota par defaut de 20%
	if ($depassement == "") {$depassement = "20 %";}

	//transforme le depassement de quota en nombre
	$depassement = preg_replace('/\s%/', '', $depassement);

	//Teste si case quota non vide et si c'est un nombre et si superieur &#224; 10Mo dans le cas de la partition /home (pour eviter les problemes avec les repertories profiles, etc...
 	if ($quota <> "" and $quota >= 0 and $depassement >= 0 and (intval($quota)>=10 or intval($quota)==0 or $partition=="/var/se3")){
		//PB CI DESSUS AVEC LE TEST POUR SAVOIR SI LA CHAINE EST UN ENTIER VALIDE
		//and is_int($quota) => a modifier
  
  		if ($depassement == "") { $depassement="0"; }

		//Si le quota  est valide et les choix valides, fixe les quotas
  		if (count($classe_gr) ) {
			foreach ($classe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				//system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $grp $partition $quota $[$quota *($depassement+100)/100] \n\" >> /tmp/$nomscript");

				$hard_quota = (int) ($quota * ($depassement+100)/100 );
				system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $grp $partition $quota $hard_quota \n\" >> /tmp/$nomscript");

			}
  		}

  		if (count($equipe_gr) ) {
			foreach ($equipe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				
				$hard_quota = (int) ($quota * ($depassement+100)/100 );
				system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $grp $partition $quota $hard_quota \n\" >> /tmp/$nomscript");

			}
  		}


  		if (count($matiere_gr) ) {
			foreach ($matiere_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				
				$hard_quota = (int) ($quota * ($depassement+100)/100 );
				system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $grp $partition $quota $hard_quota \n\" >> /tmp/$nomscript");

			}
  		}
						   

  		if (count($autres_gr) ) {
			foreach ($autres_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				
				$hard_quota = (int) ($quota * ($depassement+100)/100 );
// 				echo $hard_quota;
				system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $grp $partition $quota $hard_quota \n\" >> /tmp/$nomscript");

			}
  		}
	
		//teste si utilisateur saisi pour recherche dans ldap
  		if ($user<>"") {
			//recherche dans ldap si $user est valide
			 $tabresult=search_people("uid=$user");
			 if(count($tabresult)==1){
			 	if($user!="admin" and $user!="root" and $user!="www-se3" and $user!="adminse3"){
		 			//comme $user existe et non admin et non root => fixe le quota!
		 			$nbr_user=$nbr_user+1;
		 			
					$hard_quota = (int) ($quota * ($depassement+100)/100 );
// 					echo $hard_quota;
					system("echo \"sudo /usr/share/se3/scripts/quota_fixer_mysql.sh $user $partition $quota $hard_quota \n\" >> /tmp/$nomscript");

				}
	 		}
  		}

}//fin du if($quota valide)

//le script se supprime a la fin de son exec
//system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
chmod ("/tmp/$nomscript",0700);

// on est alors dans le cas: if(isset($quota or $suppr)) donc on execute le script 
if($nbr_user>100000){
        //execution differee d'une minute pour ne pas attendre la page trop longtemps
	echo "<h3>".gettext("Les nouveaux quotas fix&#233;s concernant de nombreux utilisateurs")." ($nbr_user), ".gettext("ils ne seront effectifs et affich&#233;s que dans quelques minutes: pour actualiser cette page")." <a href=\"quota_fixer.php\"> ".gettext("cliquer ici")."</a></h3>\n";
	system ("echo \"#!/bin/bash\n\" > /tmp/lanceur.sh");
	system ("echo \"/bin/bash /tmp/$nomscript\n\" >> /tmp/lanceur.sh");

	chmod ("/tmp/lanceur.sh",0700);

	system("at -f /tmp/lanceur.sh now + 1 minute");
} else {
	//execution immediate du script /root/tmp_quotas.sh
	exec("/tmp/$nomscript &");
}


// la page est affichee apres avoir clique sur valider: ce n'est pas juste une selection de partition
if ($valider_home == "yes") {
	if ($messtxt_home == "on" and $partition == "/home") {
	        $query="UPDATE params SET value=\"1\" WHERE name=\"quota_warn_home\";";
	        mysqli_query($GLOBALS["___mysqli_ston"], $query);
  	} else {
        	$query="UPDATE params SET value=\"0\" WHERE name=\"quota_warn_home\";";
        	mysqli_query($GLOBALS["___mysqli_ston"], $query);
  	}
        //effet immediat
        exec("sudo /usr/share/se3/scripts/warn_quota.sh");
} //fin du $valider_home=yes


if ($valider_var_se3 == "yes") {
	if ($messtxt_var_se3 == "on" and $partition == "/var/se3") {
		$query="UPDATE params SET value=\"1\" WHERE name=\"quota_warn_varse3\";";
	        mysqli_query($GLOBALS["___mysqli_ston"], $query);
        } else {
		$query="UPDATE params SET value=\"0\" WHERE name=\"quota_warn_varse3\";";
        	mysqli_query($GLOBALS["___mysqli_ston"], $query);
  	}
        //effet immediat
        exec("sudo /usr/share/se3/scripts/warn_quota.sh");
} //fin du $valider_var_se3=yes

}//fin du cas if (isset($quota) or isset($suppr))

if (isset($grace)){
	if ($grace>0) {	exec("sudo /usr/share/se3/scripts/quota_grace_delai.sh $grace $partition");}
}



//DEBUT DES AFFICHAGES HTML
echo "<h1>".gettext("Quotas fix&#233;s sur /home et /var/se3")."</h1>\n\n";

// Affichage du contenu de la table en HTML
$arr = array("/home", "/var/se3");

echo "<center>\n";
foreach ($arr as $partit) {
	$query="SELECT type,nom,quotasoft,quotahard FROM quotas WHERE partition='$partit' ORDER BY type DESC, nom";
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);

	echo "<FORM ACTION=\"quota_fixer.php\" method=\"post\">\n";
	echo "<table border=1 width=\"80%\">\n";
	echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"5\">";
	echo gettext("Quotas actuels sur")." $partit</TD></TR>\n";

	$i=0;
	while ($line = mysqli_fetch_assoc($resultat)) {
 		if ($i==0){
 			// ligne d'entetes
  			echo "<tr><td align=center width=\"40%\">".gettext("Nom (utilisateur ou groupe)")."</td><td align=center  width=\"30%\">".gettext("Quota")."</td><td align=center width=\"30%\">".gettext("D&#233;passement temporaire autoris&#233;")."</td>\n";
  		}

  		echo "\t<tr>\n";
  		$i=0;
  		// me sert de repere pour savoir dans quelle colonne du tableau je suis (pour memoriser le nom $nom pour le lien quota_fixer.php?suppr=suppr&nom=$nom&partition=$partit)
  		foreach ($line as $col_value) {
   			if ($i == 0) {$typ= $col_value;}
   			if ($i == 1) {
   				$nom= $col_value;
				if ($typ == "u"){
					#rajouter le ? pour expliquer que l'user est preponderant sur tout groupe
					$col_value ="$col_value &nbsp;&nbsp; <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Les quotas fix&#233;s sur un utilisateur sont pr&#233;pond&#233;rants quelques soient les groupes d\'appartenance.')")."\"><img name=\"action_image6\"  src=\"../elements/images/system-help.png\"></u>\n";
				}
   			}
   			
			if ($i == 2) {
				$soft= $col_value;
   				if ($soft == 0)	{$col_value="Illimit&#233;";}
				else
				{$col_value="$col_value Mo";}
   			}
   
   			if ($i == 3) {
   				if ($col_value==$soft) {
					if ($soft==0) {$col_value="-";}
		 			else
					{$col_value = "Non";}
				} else {
					$col_value=($col_value-$soft)*100/$soft;
		 			$col_value="$col_value %";
				}
   			}
   			
			if ($i>0) {echo "\t\t<td align=center> $col_value </td>\n";}
   			$i=$i+1;
  		}
 		
		echo " <td align=center><a href=\"quota_fixer.php?suppr=suppr";
	 	echo "&amp;partition=$partit";
 		echo "&amp;nom=$nom";
 		echo "\"><img src=\"/elements/images/edittrash.png\" alt=\"".gettext("Supprimer")."\" title=\"".gettext("Supprimer")."\" width=\"16\" height=\"16\" border=\"0\" /></a></td>";
 		echo "\t</tr>\n";
// }
	}

	//si aucun quota sur la partition, l'afficher
	//echo "valeur $i";
	if ($i==0){echo "<tr><td align=center>".gettext("Aucun quota fix&#233; sur cette partition")."</td></tr>";}
	echo "</table>\n";
	echo "</FORM>\n";

	//CI DESSUS AFFICHER LA PERIODE DE GRACE ACTUELLE
	echo "<H3> P&#233;riode de gr&#226;ce sur $partit : ";
	system("sudo repquota $partit|grep \"Block grace time\"|cut -b19-25|sed -e \"s/ //g\"|sed -e \"s/days/ jours/g\"|sed -e \"s/;//g\"|sed -e \"s/24:00/1 jour/g\"");

	//echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('P&#233;riode durant laquelle l\'utilisateur est autoris&#233; &#224; d&#233;passer son quota. Au del&#224;, il devra redescendre en dessous de son quota pour r&#233;initialiser (instantan&#233;ment) sa p&#233;riode de gr&#226;ce. Dans le cas contraire, il ne pourra plus rien &#233;crire sur $partit";

	if ( "$partit" == "/home" ) {$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "select value from params where name='quota_warn_home'");}
	else
  	{$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "select value from params where name='quota_warn_varse3'");}
	$line = mysqli_fetch_assoc($resultat);

	//$test_exist=exec("cat /etc/cron.d/se3 | grep \"$(echo \"warn_quota.sh $partit\")\"");

	foreach ($line as $col_value) {
		if ( "$col_value" == "1" ) { 
			echo gettext(" avec message d'avertissement.<br>");
                        $warn="yes";
                } else {
			echo " <font color=\"#FF0000\">sans  message d'avertissement.</font>\n";
    			echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('PARAMETRAGE FORTEMENT DECONSEILLE: en cas de d&#233;passement autoris&#233, l\'utilisateur ne sera pas pr&#233;venu qu\'il se situe au del&#224; de son quota. Il se trouvera bloqu&#233;, sans pr&#233;avis, &#224; la fin de la p&#233;riode de gr&#226;ce... Ce r&#233;glage revient plus ou moins &#224; ne pas autoriser de d&#233;passement temporaire de quota puisque l\'utilisateur n\'est pas averti...')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\"></u>\n";
    		}
	}
	// Insert ou update pour avertissement IE
        if ($warn=="yes") {  
            if (isset($browser)) {
               $browser = preg_replace('/\\\++/','/', $browser); //gestion de \ qui passe mal (suppression des \\ en les rempla&#231;ant par un /)

            } else $browser="$quota_browser";
        // 
            $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule='warnquotas'");
            if(mysqli_num_rows($result)==0){
                    $query = "INSERT INTO corresp VALUES('','warnquotas','$browser $urlse3','','REG_SZ','systeme','','2000,XP,Vista,Seven','HKEY_CURRENT_USER\\\Software\\\Microsoft\\\Windows\\\CurrentVersion\\\Run\\\WarnQuota','Avertissement quota','config')";
                    mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule='warnquotas'");
            } 
    //			
            $row = mysqli_fetch_row($result);
            $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM restrictions WHERE CleID='$row[0]'");
            //echo $row[0];
            if(mysqli_num_rows($result)==0){
                    $query = "INSERT INTO restrictions VALUES('','$row[0]','overfill','$browser $urlse3','')";

            } 
            else {
                    $query = "UPDATE restrictions SET valeur='$browser $urlse3' where CleID='$row[0]'";
            }
            $result=mysqli_query($GLOBALS["___mysqli_ston"], $query);
            //echo $result;
        } 
        else  {
                $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule='warnquotas'");
                if(mysqli_num_rows($result)!=0){
                    $row = mysqli_fetch_row($result);
                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM restrictions WHERE CleID='$row[0]'");
                    if(mysqli_num_rows($result)!=0){
                        $query="DELETE FROM restrictions WHERE cleID='$row[0]'";
                        $result=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    }
                }
            
            }
             
        
        echo "</h3>\n";

//	echo "</center>\n";

}//fin du for partition

echo "<h1>".gettext(" Attribution (Modification) de quotas")."</h1>\n";
echo "<FORM METHOD=\"post\" action=\"quota_fixer.php#attribution\">";

//si 1er affichage, afficher /home
if (!isset($partition)) {$partition ="/home";}

	//nouvelle presentation sous forme de tableau
	//preparation du tableau de parametres
	$parametre[1] = gettext("Modifier la partition :");
	$objet_var="<select name=\"partition\" onchange=submit() >\n";
	//creation du tableau des partitions
	$arr_parts = array("/home", "/var/se3");
	foreach ($arr_parts as $part) {
  		$objet_var="$objet_var <option ";
  		if ($part == $partition) {$objet_var="$objet_var selected"; }
  		$objet_var="$objet_var> $part </option>\n";
	}
	$objet_var="$objet_var </select>\n";
	$objet[1] = "$objet_var"; 
	if ($partition == "/var/se3") {$help[1] = gettext("Rappel: fixer un quota sur /var/se3 limite &#224 la fois l\'espace disponible dans les lecteurs r&#233;seaux Classes et Docs qui constituent une seule et unique partition.");}
	else {$help[1] = gettext("Il est interdit de fixer un quota inf&#233;rieur &#224; 10 Mo sur la partition /home. En effet, cela pourrait cr&#233;er des dysfonctionnements: les quotas sont fix&#233;s pour tous les utilisateurs des groupes choisis sur la partition enti&#232;re: cela inclut notamment les r&#233;pertoires profile, etc...");}

	$parametre[2] = gettext("Indiquer l'espace maximum tol&#233;r&#233; en Mo (0 pour illimit&#233;) :");
	$objet[2] = "<INPUT TYPE=\"TEXT\" NAME=\"quota\" size=4>\n";
	if ($partition == "/var/se3") {$help_var=gettext("Saisir un entier strictement positif.");}
	else {$help_var=gettext("Il est interdit de fixer un quota inf&#233;rieur &#224; 10Mo sur la partition /home. En effet, cela pourrait cr&#233;er des dysfonctionnements: les quotas sont fix&#233;s pour tous les utilisateurs des groupes choisis sur la partition enti&#232;re: cela inclut notamment les r&#233;pertoires profile, etc...");}
	$help[2] = "$help_var";

	$parametre[3] = gettext("D&#233;passement temporaire autoris&#233; :");
	$arr_pourc = array("0 %", "10 %", "20 %", "30 %", "40 %", "50 %", "60 %", "70 %", "80 %", "90 %", "100 %");
	$objet_var="<select name=\"depassement\" >\n";
	foreach ($arr_pourc as $pourc) {
  		$objet_var="$objet_var <option ";
  		if ($pourc == "20 %") {$objet_var="$objet_var selected"; }
  		$objet_var="$objet_var > $pourc </option>\n";
	}
	$objet_var="$objet_var </select>\n";
	$objet[3] = "$objet_var";
	$help[3] = gettext("Si le d&#233;passement est fix&#233; &#224; 0 %, l\'utilisateur sera bloqu&#233; au quota fix&#233; sans aucun pr&#233;avis et sans moyen d\'enregistrer son travail. A &#233;viter!");

	$parametre[4] = gettext("P&#233;riode de gr&#226;ce en jour(s) :");
	//$objet[4] = "<INPUT TYPE=\"TEXT\" NAME=\"grace\" size=4 value=".system("sudo repquota $partition|grep 'Block grace time'|cut -b19-25|sed -e 's/;//g'|sed -e 's/ //g'|sed -e 's/days//g'|sed -e 's/24:00/1/g'")." >"; 
	$help[4] = gettext("VALABLE POUR TOUTE LA PARTITION.");

	$parametre[5] = gettext("Avertir les utilisateurs en cas de d&#233;passement :");

 	$objet_var = "<input type=\"checkbox\" checked";
 
 	//$test_exist=exec("cat /etc/cron.d/se3 | grep \"$(echo \"warn_quota.sh $partition\")\"");
	// PERMET L'AFFICHAGE DE CE QU'IL Y A DEJA DE REGLE DANS MYSQL POUR LA PARTITION CONSIDEREE
	if ( "$partition" == "/home" ) {$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "select value from params where name='quota_warn_home'");}
	else
  	{$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "select value from params where name='quota_warn_varse3'");}
	
	$line = mysqli_fetch_assoc($resultat);
//	foreach ($line as $col_value) {
//		if ( "$col_value" == "1" ) {$objet_var="$objet_var checked ";}
//	}
 
 	//if ( $test_exist != "" ) {$objet_var="$objet_var checked ";}
 
 	$objet_var="$objet_var name=\"";
   	if ($partition == "/home") {
    		$objet_var="$objet_var messtxt_home\">"; 
	} else {
    		$objet_var="$objet_var messtxt_var_se3\">"; 
	}
	
	$objet[5] = "$objet_var";
	$help[5] = gettext("VALABLE POUR TOUTE LA PARTITION: Cr&#233;ation d\'un message d\'avertissement &#224; chaque ouverture de session pour tout utilisateur en d&#233;passement de quota.");

	$parametre[6] = gettext("Chemin du navigateur affichant les messages d'avertissement en cas de d&#233;passement :");
	//$value=exec("cat /home/templates/overfill/registre.zrn | grep \"WarnQuota @@@\" | cut -d \"@\" -f10 |cut -d \" \" -f2");
	$objet[6] = "<INPUT TYPE=\"TEXT\" NAME=\"browser\" value=\"$browser\" size=20>\n";
	$help[6] = gettext("Par d&#233;faut: la chaine de caractere \'iexplore\' fonctionne si internet explorer n\'est pas bloqu&#233; par une clef de registre. Sinon, le navigateur lynx convient en indiquant un chemin correct (par exemple, L:...lynx.exe). ATTENTION: firefox ne fonctionne pas si le quota est d&#233;pass&#233; sur /home car son profil y est stock&#233;. Utiliser firefoxportable sur L: par exemple.");

	// creation du tableau proprement dite
	$i=1;
	echo "<TABLE border=1 width=\"80%\">\n";

	foreach($parametre as $param){
		echo "<TR><td>$param</td><TD align=center>\n";
		// correction d'un bug de la fonction system( cmd, $var de sortie) qui ne fonctionne pas :s
		if ($i==4) {
			echo "<INPUT TYPE=\"TEXT\" NAME=\"grace\" size=4 value=";
  			system("sudo repquota $partition|grep 'Block grace time'|cut -b19-25|sed -e 's/;//g'|sed -e 's/ //g'|sed -e 's/days//g'|sed -e 's/24:00/1/g'");
  			echo " >";
		} else {echo "$objet[$i]";}

		echo "</TD><TD align=center><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$help[$i]')\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u></TD></TR>\n";

		if ($i==1) { 
			echo "</form>\n\n";
  			echo "<FORM ACTION=\"quota_fixer.php?partition=$partition&valider";
    			//cree un repere valider_home ou valider_var_se3 pour savoir s'il faut lancer le script warn_quota_service.sh et sur lequel il faut le lancer
  			if ($partition == "/home") {echo "_home";}
  			else
    			{echo "_var_se3";}
  			echo "=yes\" method=\"post\">\n";
  		}
		
		$i=$i+1;
	}

	echo "</TABLE>\n\n";
	// echo "</center>\n";

	echo "<div align=center>";
	echo "<h4>".gettext("Fixer ce quota pour tous les membres des groupes suivants&nbsp;:");
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Le quota fix&#233; d&#233;pendra des groupes d\'appartenance des utilisateurs: le quota effectif sera le plus grand des quotas applicables aux divers groupes. Par contre, si un quota est appliqu&#233; &#224; un utilisateur sp&#233;cifique, celui-ci est pr&#233;pond&#233;rant quelques soient ses groupes d\'appartenance.')")."\"><img name=\"action_image5\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</h4></div>\n";
        // Etablissement des listes des groupes disponibles
        affiche_all_groups(center,user);
	
echo "<div id=\"attribution\" align='center'><input type=\"submit\" value=\"".gettext("Valider")."\">
<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
echo "</form>";
echo "</center>";
//fin is_admin($login)

include ("pdp.inc.php");

?>
