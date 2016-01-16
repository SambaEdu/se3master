<?php

   /**
   
   * Page permettant de visualiser des quotas en dur pour les utilisateurs de se3 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteur : Olivier Lacroix (Olikin)

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Utilise le script quota_fixer_mysql.sh (qui appelle quota.sh) et des fichiers generes par warn_quota.sh
   * @note contributions: Franck Molle, Stephane Boireau. Remerciements tous particuliers a Franck Molle. ;-)
   
   */

   /**

   * @Repertoire: infos
   * file: quota_visu.php

  */	

require("entete.inc.php");

//Vrification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'authentification is_admin
require("ihm.inc.php");

// Internationalisation
require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

//aide
$_SESSION["pageaide"]="Quotas#Gestion_des_quotas";

//AUTHENTIFICATION
if (is_admin("system_is_admin",$login)!="Y")
   die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


$partition=$_POST['partition'];
if($partition=="") { $partition=$_GET['partition']; }
$classe_gr=$_POST['classe_gr'];
$equipe_gr=$_POST['equipe_gr'];
$matiere_gr=$_POST['matiere_gr'];
$autres_gr=$_POST['autres_gr'];
$user=$_POST['user'];


if ( file_exists("/tmp/tmp_quota_K") or file_exists("/tmp/tmp_quota_H")) {
//J'utilise le script warn_quota.sh en le patchant a deux endroits pour creer un fichier dans /tmp: ce fichier est efface immediatement si l'admin n'avait pas fixe d'avertissement en cas de depassement de quota... Pas elegant mais cela ecomise un script et warnquota.sh est rapide...
// AFFICHAGE D'ALERTE DES USERS en depassement de quota
        exec("sudo /usr/share/se3/scripts/warn_quota.sh");
        
  	echo "<h1>".gettext("Attention!")."</h1>";

  	$arr = array("/home", "/var/se3");
  	foreach ($arr as $partit) { 
		if ( $partit == "/home" ) { $disque="K"; }
    		else 
      		{$disque="H"; }
    
    		if (file_exists("/tmp/tmp_quota_$disque")) {
      			echo "<h2>".gettext("Liste des utilisateurs en d&#233;passement de quota sur")." $partit : <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Visualisation de tous les utilisateurs en p&#233;riode de gr&#226;ce (orange) ou ayant leur p&#233;riode de gr&#226;ce expir&#233;e (rouge): ces derniers ne peuvent plus rien &#233;crire sur")." $partit ".gettext("d\'o&#151; maints dysfontionnements possibles...')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></h2>";
      			echo "<TABLE  align='center' border='1'>\n";
      			echo "<TR><TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Utilisateur&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </TD>
			<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("Espace utilis&#233; &nbsp;(Mo)")."&nbsp;&nbsp;&nbsp; </TD>
			<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;".gettext("Quota fix&#233; &nbsp;(Mo)")."&nbsp;&nbsp;&nbsp;</TD>
			<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("D&#233;lai de gr&#226;ce &nbsp;(Jours)")."&nbsp;&nbsp;&nbsp; </TD></TR>";
			if ( $partit == "/home" ) {
        			exec("cat /tmp/tmp_quota_$disque|gawk -F \"\t\"  '{print \"<tr align='center'><td><a name=ancre_\"$1\"_alerte href=#ancre_\"$1\"_alerte onclick=REPERE1 \"$1\" REPERE2> \" $1 \"</a></td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\" > /tmp/result_quota");
        			exec("sed -i /tmp/result_quota -e \"s!REPERE1 !popuprecherche(\'stats_user.php?partition=$partit\&uid=!\"");
        			exec("sed -i /tmp/result_quota -e \"s! REPERE2!','popuprecherche','width=800,height=500');!\"");
        			system("cat /tmp/result_quota");
        			exec("rm /tmp/result_quota");
        		} else {
				system("cat /tmp/tmp_quota_$disque|gawk -F \"\t\"  '{print \"<tr align='center'><td> \" $1 \"</td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\"");
        		}
      			
			echo "</table>";
      		}
  	}
} else {
//effacer les messages d'avertissements crees en trop par ce script si non desires
exec("sudo /usr/share/se3/scripts/warn_quota.sh");
}

echo "<h1>".gettext("Visualisation des quotas effectivement attribu&#233;s")."</h1>";

//FILTRAGE
echo "<FORM ACTION=\"quota_visu.php\" METHOD=\"post\">
 <h2>".gettext("Quotas sur")." &nbsp;
	<select name=\"partition\">
	 <option>/home</option>
	 <option>/var/se3</option>
	</select>
 </h2> ";

echo "<h2>".gettext("Filtrer les membres des groupes suivants :")." <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Pour afficher tous les quotas, valider directement.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u></h2>";

//echo "<h3>( Pour afficher tous les quotas, valider directement. )</h3>";
// Etablissement des listes des groupes disponibles
affiche_all_groups(center, user);
echo "<div align='center'><input type=\"submit\" value=\"".gettext("Valider")."\">
<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
echo "</form>";

//echo "<FORM METHOD=\"post\" action=\"quota_visu.php?liste_rouge=yes\">";
//echo "<h2>Utilisateurs en depassement de quota : <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('Permet de visualiser tous les utilisateurs en p&#233;riode de grace (orange) ou ayant leur p&#233;riode de grace expir&#233;e (rouge)...')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u> ";
//echo " <input type=\"submit\" value=\"Afficher\"></h2>";
// echo "</form>";

if ($partition<>"") {
	//AFFICHAGE DES RESULTATS
	echo "<hr>";

	//tableau des quotas: un tableau par groupe selection

	if ($classe_gr=="" and $equipe_gr=="" and $matiere_gr == "" and $autres_gr=="" and $user=="") {
		//si aucun filtre
		echo "<h3>".gettext("Le traitement des quotas pour la totalit&#233; de l'annuaire est en cours: veuillez patienter...")."</h3>";
		echo "<h3>P&#233;riode de gr&#226;ce actuelle sur $partition : ";
		system("sudo repquota $partition|grep \"Block grace time\"|cut -b19-25|sed -e \"s/ //g\"|sed -e \"s/days/ jour(s)/g\"|sed -e \"s/;//g\"");
		echo "</h3>";

		echo "<h2>".gettext("Liste de tous les quotas actuels sur")." $partition :</h2>";
		echo "<TABLE  align='center' border='1'>\n";
		echo "<TR><TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".gettext("Utilisateur")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </TD>
		<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("Espace utilis&#233; &nbsp;(Mo)")."&nbsp;&nbsp;&nbsp; </TD>
		<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;".gettext("Quota fix&#233; &nbsp;(Mo)")."&nbsp;&nbsp;&nbsp;</TD>
		<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("D&#233;lai de gr&#226;ce &nbsp;(Jours)")."&nbsp;&nbsp;&nbsp; </TD></TR>";
		//filtre pour garder les lignes intressantes: suppr entte du script repquota_filtre.sh <=> les 7 1res lignes!
		//le script lui, filtre certains utilisateurs comme : root, www-se3 et trie par ordre alpha => voir commentaires script
		exec("sudo /usr/share/se3/scripts/repquota_filtre.sh $partition |tail -n +7 >/tmp/quota_filtre");

		//filtre les tabulations et les remplace par les balises du tableau
		//pour pouvoir mettre la couleur orange des qu'il y a un nombre, je la mets par defaut et la trnnsforme en rouge si delai expire, en transparent si on a un tiret: obligatoire car il y a plein de tiret dans le tableau non distingables
		if ($partition=="/home"){
			//affiche lien vers statistiques d'occupation pour /home
                        //filtre les tabulations et les remplace par les balises du tableau
                        //pour pouvoir mettre la couleur orange des qu'il y a un nombre, je la mets par defaut et la trnnsforme en rouge si delai expire, en transparent si on a un tiret: obligatoire car il y a plein de tiret dans le tableau non distingables
			exec("cat /tmp/quota_filtre|gawk -F \"\t\"  '{print \"<tr align='center'><td><a name=ancre_\"$1\" href=#ancre_\"$1\" onclick=REPERE1 \"$1\" REPERE2> \" $1 \"</a></td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\" > /tmp/result_quota");
			exec("sed -i /tmp/result_quota -e \"s!REPERE1 !popuprecherche(\'stats_user.php?partition=$partition\&uid=!\"");
			exec("sed -i /tmp/result_quota -e \"s! REPERE2!','popuprecherche','width=800,height=500');!\"");
			system("cat /tmp/result_quota");
			exec("rm /tmp/result_quota");
		} else {
			//pas de lien car script stat_user.sh non valide sur /var/se3
			system("cat /tmp/quota_filtre|gawk -F \"\t\"  '{print \"<tr align='center'><td> \" $1 \"</td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\"");
		}

		echo "</table>";
	} else { //si il y a eu une demande de filtrage

		//concatne tous les groupes cherchs dans le tableau liste_sel
		$i =0;
		$liste_sel = array();
		for ($loop=0; $loop < count ($classe_gr) ; $loop++) {
			$liste_sel[$i] = $classe_gr[$loop];
			$i++;
		}
		for ($loop=0; $loop < count ($equipe_gr) ; $loop++) {
			$liste_sel[$i] = $equipe_gr[$loop];
			$i++;
		}
		for ($loop=0; $loop < count ($matiere_gr) ; $loop++) {
			$liste_sel[$i] = $matiere_gr[$loop];
			$i++;
		}
		for ($loop=0; $loop < count ($autres_gr) ; $loop++) {
			$liste_sel[$i] = $autres_gr[$loop];
			$i++;
		}

		$liste_sel[$i] = $user;

		//cherche user pour savoir s'il existe ensuite
		$tabresult=search_people("uid=$user");

		echo "<h3>P&#233;riode de gr&#226;ce actuelle sur $partition : ";
		system("sudo repquota $partition|grep \"Block grace time\"|cut -b19-25|sed -e \"s/ //g\"|sed -e \"s/days/ jours/g\"|sed -e \"s/;//g\"");
		echo "</h3>";

		//affiche tous les tableaux demands
		foreach ($liste_sel as $grp){

			//TESTE si $user EXISTE  OU si $grp est un utilisateur d'un groupe (et non $user) => il faut afficher le tableau!
			if(count($tabresult)!=0 or $grp!=$user){
				if ($grp!=$user){
					echo "<h2>Liste des quotas actuels sur $partition pour $grp :</h2>";
				} else {
					echo "<h2>Quota actuel sur $partition pour l'utilisateur $grp :</h2>";
				}

				echo "<TABLE  align='center' border='1'>\n";
				echo "<TR><TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".gettext("Utilisateur")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </TD>
				<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("Espace utilis&#233;")." &nbsp;(Mo)&nbsp;&nbsp;&nbsp; </TD>
				<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;&nbsp;".gettext("Quota fix&#233;")." &nbsp;(Mo)&nbsp;&nbsp;&nbsp;</TD>
				<TD  class='menuheader'> &nbsp;&nbsp;&nbsp;".gettext("D&#233;lai de gr&#226;ce &nbsp;(Jours)")."&nbsp;&nbsp;&nbsp; </TD></TR>";

				//filtre pour garder les lignes intressantes: suppr entte du script repquota_filtre.sh <=> les 7 1res lignes!
				//le script lui, filtre certains utilisateurs comme : root, www-se3 et trie par ordre alpha => voir commentaires script
				//filtre les tabulations et les remplace par les balises du tableau

				exec("sudo /usr/share/se3/scripts/repquota_filtre.sh $partition $grp|tail -n +7 > /tmp/quota_filtre ");

				//filtre les tabulations et les remplace par les balises du tableau
				//pour pouvoir mettre la couleur orange des qu'il y a un nombre, je la mets par defaut et la trnnsforme en rouge si delai expire, en transparent si on a un tiret

				if ($partition=="/home"){
					//affiche lien vers statistiques d'occupation sur /home
					exec("cat /tmp/quota_filtre|gawk -F \"\t\"  '{print \"<tr align='center'><td><a name=ancre_\"$1\"_$grp href=#ancre_\"$1\"_$grp onclick=REPERE1 \"$1\" REPERE2> \" $1 \"</a></td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\" > /tmp/result_quota");
					exec("sed -i /tmp/result_quota -e \"s!REPERE1 !popuprecherche(\'stats_user.php?partition=$partition\&uid=!\"");
					exec("sed -i /tmp/result_quota -e \"s! REPERE2!','popuprecherche','width=800,height=500');!\"");
					system("cat /tmp/result_quota");
					exec("rm /tmp/result_quota");

				} else {
					//pas de stat sur /var/se3 car script stat_user.sh non valide pour cette partition
					system("cat /tmp/quota_filtre|gawk -F \"\t\"  '{print \"<tr align='center'><td> \" $1 \"</td><td> \" $2 \"</td><td> \" $3 \"</td><td bgcolor=#FF8C00>\" $5 \"</td></tr>\"}'| sed -e \"s+#FF8C00>Expire+#FF0000>Expir\&#233;+g\" | sed -e \"s+<td bgcolor=#FF8C00>-+<td>-+g\"");
				}
				echo "</table>";
			} else { //si utilisateur non valide
				if ($user != "") echo "<h2>".gettext("L'utilisateur sp&#233;cifi&#233;")." \"$user\" ".gettext("n'est pas valide!")."</h2>";
			}
		}//fin du foreach ($liste_sel...
	}//fin du else du if ($classe_gr=="" and $equipe_gr=="" and $autres_gr=="" and $user=="")

	//SUPPRIME LE FICHIER TEMPORAIRE quota_filtre CREE:
	exec("rm /tmp/quota_filtre");
	//fin de visualisation des quotas demande

}//fin du if($partition <> "") et donc de l'affichage des tableaux

include ("pdp.inc.php");
?>

