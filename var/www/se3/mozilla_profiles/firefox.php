<?php

   /**
   
   * Deploiement et modification des profils firefox des postes clients 
   * @Version $Id: firefox.php 9274 2016-03-24 23:39:56Z keyser $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  franck.molle@ac-rouen.fr
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: mozilla_profiles
   * file: firefox.php

  */	




require("entete.inc.php");

//Verification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'autehtification is_admin
require("ihm.inc.php");

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-mozilla',"/var/www/se3/locale");
textdomain ('se3-mozilla');

//AUTHENTIFICATION
if (is_admin("computer_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


//aide
$_SESSION["pageaide"]="Gestion_Mozilla#Mozilla_Firefox";

//debug_var();

$choix=isset($_POST['choix']) ? $_POST['choix'] : (isset($_GET['choix']) ? $_GET['choix'] : "");
$config=isset($_POST['config']) ? $_POST['config'] : (isset($_GET['config']) ? $_GET['config'] : "");
$action=isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : "");

$autres_gr=isset($_POST['autres_gr']) ? $_POST['autres_gr'] : array();
$classe_gr=isset($_POST['classe_gr']) ? $_POST['classe_gr'] : array();
$equipe_gr=isset($_POST['equipe_gr']) ? $_POST['equipe_gr'] : array();
$matiere_gr=isset($_POST['matiere_gr']) ? $_POST['matiere_gr'] : array();

// Je n'ai pas vu a quoi sert $home
$home=isset($_POST['home']) ? $_POST['home'] : "";

$page_dem=isset($_POST['page_dem']) ? $_POST['page_dem'] : "";
$user=isset($_POST['user']) ? $_POST['user'] : "";

$default_page_dem=isset($_POST['default_page_dem']) ? $_POST['default_page_dem'] : "";
$userGroups=isset($_POST['userGroups']) ? $_POST['userGroups'] : "";

$new_proxy_type=isset($_POST['new_proxy_type']) ? $_POST['new_proxy_type'] : "";
$new_proxy_url=isset($_POST['new_proxy_url']) ? $_POST['new_proxy_url'] : "";



// Titre
echo "<h1>".gettext("Configuration des navigateurs  : proxy et page de d&#233;marrage")."</h1>";
//D&#233;ploiement mozilla firefox
//EVALUE SI UNE SAISIE A ETE EFFECTUEE: AUTO-APPEL DE LA PAGE APRES FORMULAIRE REMPLI
if ($config==""||$config=="init") {
		
	$form = "<form action=\"firefox.php?config=init\" method=\"post\">\n";
	// Form de selection d'actions
	$form .="<H3>".gettext("D&#233;ploiement ou  modification  des profils Mozilla Firefox :")." </H3>\n";
	$form .= "<SELECT name=\"choix\" onchange=submit()>\n";
	$form .= "<OPTION VALUE='choix'>-------------------------------".gettext(" Choisir ")."-------------------------------</OPTION>\n";

//	$choix=$_POST['choix'];
	if($choix=="deploy_nosave")  {$form .= "<OPTION SELECTED VALUE='deploy_nosave'>".gettext("D&#233;ployer et / ou remplacer des profils firefox")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='deploy_nosave'>".gettext("D&#233;ployer et / ou remplacer des profils firefox")."</OPTION>\n";}


//	if($choix=="deploy_save")  {$form .= "<OPTION SELECTED VALUE='deploy_save'>".gettext("D&#233;ployer et remplacer les profils mais conserver les bookmarks")."</OPTION>\n";}
//	else {$form .= "<OPTION VALUE='deploy_save'>".gettext("D&#233;ployer et remplacer les profils mais conserver les bookmarks")."</OPTION>\n";}

	if($choix=="modif")  {$form .= "<OPTION SELECTED VALUE='modif'>".gettext("Modifier la page de d&#233;marrage")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='modif'>".gettext("Modifier la page de d&#233;marrage")."</OPTION>\n";}

	if($choix=="modif_proxy")  {$form .= "<OPTION SELECTED VALUE='modif_skel'>".gettext("Param&#233;trer  le proxy sur les navigateurs")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='modif_proxy'>".gettext("Param&#233;trer  le proxy")."</OPTION>\n";}

	$form .= "</SELECT>\n";
	$form.="</form>\n";
	echo $form;
	echo "<br>";


	if($choix=="modif") {
            
            
                if($action=="default_homepage") {
                    //$script="/usr/share/se3/scripts/modif_profil_mozilla_ff.sh"; 
                    echo "<h4>".gettext("Modification de la page de d&#233;marrage de Mozilla Firefox par defaut")."</h4>";
                    //break;
                    $name_params="$userGroups"."_hp";
                    //echo $name_params;
                    $resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('$default_page_dem', '$name_params', 'homepage $userGroups', '1')");
                    if ($resultat == FALSE) {
                            mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$default_page_dem' where name='$name_params'");
                    }
                    
                                  
                    
                    if ($userGroups == "administratifs") $administratifs_hp="$default_page_dem" ; 
                    if ($userGroups == "profs") $profs_hp="$default_page_dem" ; 
                    if ($userGroups == "eleves") $eleves_hp="$default_page_dem" ; 
                    
                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule like '%url de la page%'");

                    $row = mysqli_fetch_row($result);
                    mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$row[0]' AND groupe='$userGroups'");
                    
                    if ($default_page_dem != "") { 
                        $query = "INSERT INTO restrictions VALUES('','$row[0]','$userGroups','$default_page_dem','')";
                        $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    }
                    
                    
                    system("sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh refparams 2>&1");
                    ////On change la page pour les groupe ou le user selectionne
                }
                
            
                echo "<H3> Ajouter une page de d&#233;marrage par d&#233;faut conditionnelle</H3>";
                //$form .= "<form name='formulaire' action=\"firefox.php?config=suite\"  method='post'>\n";
                //echo "<form action=\"firefox.php?config=default_homepage\" name=\"form2\" method=\"post\">\n";
		
                
                $form = "<form action=\"firefox.php?config=init&choix=modif&action=default_homepage\" name=\"form2\" method=\"post\">\n";
		$form .= "<table>\n";
                $form .= "<tr><td align='left'> Si l'utilisateur est membre du groupe: </td>\n";
                $form .= "<td><select name='userGroups' >\n";
                $form .= "<option >administratifs</option>\n";
                $form .= "<option >profs</option>\n";
                $form .= "<option >eleves</option>\n";
                    
                 
                $form .= "</select></td>\n";
                
                
                $form .= "<tr><td>Url : <INPUT TYPE=\"TEXT\" NAME=\"default_page_dem\" size=35></td>\n";
                $form .= "<td><input type='submit' value='Ajouter'></td></tr>\n";
                $form .= "</tr></table><br>";
                if ($administratifs_hp != "") $form .= "Page de d&#233;marrage par d&#233;faut pour le groupe <b>administratifs</b> : $administratifs_hp<br>";
                if ($profs_hp != "") $form .= "Page de d&#233;marrage par d&#233;faut pour le groupe <b>profs</b> : $profs_hp<br>";
                if ($eleves_hp != "") $form .= "Page de d&#233;marrage par d&#233;faut pour le groupe <b>&#233;l&#232;ves</b> : $eleves_hp<br>";
               
                //$form .= "Page de d&#233;marrage par d&#233;faut pour le groupe administratifs : $eleves_homepage";
                $form .= "<br><br></form>";
                
                echo "$form";
                
                echo "<form action=\"firefox.php?config=suite\" name=\"form3\" method=\"post\">\n";
                echo "<h3>Modifier la page de d&#233;marrage des profils existants</h3>\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"$choix\">";
                affiche_all_groups(left,user);
                
                echo "<h3>".gettext("Nouvelle page de d&#233;marrage :")." </h3>\n";
		echo "<INPUT TYPE=\"TEXT\" NAME=\"page_dem\" size=50><br><br>\n";

		echo "
		<h3>".gettext("Cr&#233;er les espaces personnels s'ils n'existent pas sur la partition")." /home ?</h3>\n
		<INPUT TYPE=RADIO NAME=option value=\"create_homes\" checked > Oui <br>\n
		<INPUT TYPE=RADIO NAME=option value=\"no_create\">".gettext(" Non")." <BR><BR>\n";
	
		echo "<input type=\"submit\" value=\"".gettext("valider")."\">\n
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\">\n";

		//echo "<input type=\"text\" name=\"choix\" value=\"$choix\" size=\"30\" />";



		echo "</form>\n";

	}
	elseif($choix=="modif_proxy") {
            $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE sscat='configuration du proxy' AND type='config'");
                  
            if(mysqli_num_rows($result)==0) {
                echo "<font color=red>
                Pour le bon fonctionnement de cette page,<br>
                veuillez au pr&#233;alable mettre a jour la base des cl&#233;s de registre SVP</font><br><br>";
                echo "<a href=\"../registre/cle-maj.php?action=maj\">".gettext("Effectuer la mise a jour de la base de cl&#233s ?")."</a><br>";
                die ("</BODY></HTML>");
            }
            if($action=="set_proxy") {
                    
                    $firefox_use_ie=isset($_POST['firefox_ie']) ? $_POST['firefox_ie'] : "";

                    //$script="/usr/share/se3/scripts/modif_profil_mozilla_ff.sh"; 
                    //echo "<h4>".gettext("Modification de la page de d&#233;marrage de Mozilla Firefox par defaut")."</h4>";
                    //break;
                    //$name_params="$userGroups"."_hp";
                    //echo $name_params;
                    if ($firefox_use_ie == "1") {
                        $yes_ie = "checked";
                    } else {
                        $no_ie = "checked";
                    }
                    $proxy_url = $new_proxy_url;
                    $proxy_type = $new_proxy_type;
                
                    $resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('$new_proxy_url', 'proxy_url', 'url du proxy pour le navigateur', '1')");
                    if ($resultat == FALSE) {
                            mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$new_proxy_url' where name='proxy_url'");
                    }
                    $resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('$firefox_use_ie', 'firefox_use_ie', 'Firefox utilise ou non les param proxy de IE', '1')");
                    if ($resultat == FALSE) {
                            mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$firefox_use_ie' where name='firefox_use_ie'");
                    }
                    $resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('$new_proxy_type', 'proxy_type', 'type du proxy (param IE / aucun / manuel / url auto', '1')");
                    if ($resultat == FALSE) {
                            mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$new_proxy_type' where name='proxy_type'");
                    }
                    
                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule like 'activer le proxy%'");
                    $row = mysqli_fetch_row($result);
                    $proxy_actif_key = $row[0];

                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule like '%entrez les valeurs pour votre proxy%'");
                    $row = mysqli_fetch_row($result);
                    $proxy_valeur_key = $row[0];

                    $result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT CleID FROM corresp WHERE Intitule like '%url du script de configuration automatique du proxy%'");
                    $row = mysqli_fetch_row($result);
                    $proxy_url_key = $row[0];


                    switch ($proxy_type) {
                                              
                        case 0:
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_actif_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_valeur_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_url_key'");
                            
                            //mysql_query("DELETE FROM restrictions WHERE cleID='$val_cleid[3]'");
                            $query = "INSERT INTO restrictions VALUES('','$proxy_url_key','base','','')";
                            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                            $query = "INSERT INTO restrictions VALUES('','$proxy_valeur_key','base','','')";
                            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                            $query = "INSERT INTO restrictions VALUES('','$proxy_actif_key','base','0','')";
                            mysqli_query($GLOBALS["___mysqli_ston"], $query);
                             
                            break;
                        
                        case 1:
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_actif_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_valeur_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_url_key'");
                            $query = "INSERT INTO restrictions VALUES('','$proxy_actif_key','base','1','')";
                            mysqli_query($GLOBALS["___mysqli_ston"], $query);
                            $query = "INSERT INTO restrictions VALUES('','$proxy_valeur_key','base','$new_proxy_url','')";
                            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                            if ($resultat == FALSE)  { 
                                mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE restrictions set value='$new_proxy_url' where CleID='$proxy_valeur_key'"); 
                            }
                            $query = "INSERT INTO restrictions VALUES('','$proxy_url_key','base','','')";
                            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);

                            break;
                        
                        case 2:
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_actif_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_valeur_key'");
                            mysqli_query($GLOBALS["___mysqli_ston"], "DELETE FROM restrictions WHERE cleID='$proxy_url_key'");
                            //mysql_query("UPDATE corresp set value='' WHERE cleID='$row[3]'");
                            $query = "INSERT INTO restrictions VALUES('','$proxy_url_key','base','$new_proxy_url','')";
                            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
                            if ($resultat == FALSE) { 
                                mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE restrictions set value='$new_proxy_url' where CleID='$proxy_url_key'"); 
                                
                                }

                            break;

                        
                    }
                    echo "<b>La modification des profils firefox sera lanc&#233;e en arri&#232;re plan dans 1mn : </b><br>";
                    
                    system("sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh shedule 2>&1");
                    echo "<br>Un mail recapitulatif sera envoy&#233;";
                    //echo "</pre>";
                    $fichier_info=fopen('/var/www/se3/tmp/recopie_profils_firefox.html','w+');
		fwrite($fichier_info,'<html>
<meta http-equiv="refresh" content="2">
<html>
<body>
<h1 align="center">Traitement des profils</h1>
<p align="center">Le traitement va demarrer dans la minute qui vient...<br></p>
</body>
</html>');
		fclose($fichier_info);
	
		# Ouverture d'une fenetre popup:
		echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_firefox.html','Suivi_recopie_profils_Firefox','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
		#=========================================================================
	
                }  
                    
          

                $form = "<form action=\"firefox.php?config=init&choix=modif_proxy&action=set_proxy \" method=\"post\">\n";
		$form .= "<input type=\"hidden\" name=\"choix\" value=\"modif_proxy\">";
		$form .= "<h3>".gettext("Proxy actuellement utilis&#233; :")." </h3>";
                $form .= "<table border=\"1\"  colspan=\"5\" align=\"left\" style='float: none'>\n";
                
                $array_proxy_type = array(
                  //  'none'  => 'Utiliser les param&#232;tres syst&#232;mes (IE)',
                    '0'     => 'Aucun proxy : connexion directe',
                    '1'     => 'Proxy manuel --> ip:port',
                    '2'     => 'Utilisation d\'un fichier .pac'
                    ); 
                
               
                
                $form .= "<tr class=\"menuheader\"><td align='center'> Type de proxy </td>\n";
                $form .= "<td align='center'> Valeur actuelle</td></tr>\n";
                //if ($proxy_type == "") $proxy_url = "Aucun proxy utlis&#233; pour le moment";
                $form .= "<tr><td align='left'> $array_proxy_type[$proxy_type] </td>\n";
                $form .= "<td align='left'> $proxy_url </td></tr>\n";
                $form .= "</table><br>\n";
                
                $form .= "<ul style='margin-left: -20px;' >\n";
                
                if (file_exists("/var/www/se3.pac")) { 
                    $form .= "<li>Un fichier de configuration automatique du proxy <A href=\"http://$se3ip/se3.pac\"><b>se3.pac</b> </A>  existe sur le serveur</li>\n";
                } else {
                    $form .= "<li>Aucun fichier de configuration automatique du proxy <b>se3.pac</b> sur le serveur</li>\n";
                }

                
                 if ($firefox_use_ie == "1") {
                        $yes_ie = "checked";
                        $form .= "<li>Firefox utilise actuellement les param&#232;tres syst&#232;mes (IE) pour d&#233;finir son proxy</li>\n";
                
                        
                    } else {
                        $no_ie = "checked";
                        $form .= "<li>Firefox utilise actuellement son propre fichier de configuration pour d&#233;finir son proxy</li>\n";
                     }
                //if ($firefox_use_ie == "default") $form .= "<li>La configuration actuelle a Firefox utilise actuellement son propre fichier de configuration pour d&#233;finir son proxy</li>\n";  
                //<br><br>
                $form .= "</ul>\n";
                if ($firefox_use_ie == "default") { 
                    $form .= "<font color=red>La configuration actuelle a &#233;t&#233; g&#233;n&#233;r&#233;e automatiquement lors de l'installation ou la mise &#224; jour et ne prend pas en compte la configuration d'internet explorer.<BR>\n";
                    $form .= "Afin de finaliser la configuration du proxy, vous devez la modifier avec vos propres choix ou revalider les param&#232;tres d&#233;tect&#233;s par d&#233;faut s'ils vous conviennent.</font>";
                }
                $form .= "<h3>".gettext("D&#233finir un nouveau proxy et / ou un nouveau type")." </h3>";
		$form .= "<INPUT TYPE=\"TEXT\" NAME=\"new_proxy_url\" size=30>";
		
                
                
                $form .= "<select name='new_proxy_type' >\n";
                foreach( $array_proxy_type as $key => $value ) 
                    $form .= "<option value=\"$key\">$value</option>\n";
                
                $form .= "</select><br>\n";
                
                
                 
                
                $form .= "<h3>".gettext("Firefox utilisera les param&#232;tres syst&#232;mes (IE) et non son propre fichier de configuration")." </h3>\n
		<INPUT TYPE=RADIO NAME=firefox_ie value=\"1\" $yes_ie > Oui <br>\n
		<INPUT TYPE=RADIO NAME=firefox_ie value=\"0\" $no_ie >".gettext(" Non")." <BR><BR>\n";
	
                
                
                $form .= "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">";
		$form .= "<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		//echo "<input type=\"text\" name=\"config\" value=\"\" size=\"30\" />";
		$form .= "</form>\n";
                echo $form;
                
	}
	elseif($choix=="deploy_nosave")	{
		echo "<form action=\"firefox.php?config=suite \" name=\"form2\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_nosave\">";
                affiche_all_groups(left,user);
//                echo "<br><br><br><br><br><br>";
//                echo "<br><br><br><br>";
		echo "<div float: none align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		echo "</form>\n";
        }

		//echo gettext("si vous fonctionnez avec un slis, v&#233;rifier que son ip est bien d&#233;fini sur cette ");
		//echo "<a href=\"../conf_params.php?cat=1\">".gettext("page")."</a>\n";
	
//	elseif($choix=="deploy_save")
//	{
//		echo "<form action=\"./firefox.php?config=suite\" method=\"post\">\n";
//		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_save\">";
//		echo "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
//		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
//		echo "</form>";
//
//
//		echo gettext("si vous fonctionnez avec un slis, v&#233;rifier que son ip est bien d&#233;fini sur cette ");
//		echo "<a href=\"../conf_params.php?cat=1\">".gettext("page")."</a>\n";
//
//	}


	// echo "</body></html>";
} else {

	$nomscript=date("Y_m_d_H_i_s");
	$nomscript="tmp_firefox_$nomscript.sh";
	$nbr_user=0;
	system ("echo \"#!/bin/bash\n\" > /tmp/$nomscript");

        $option=isset($_POST['option']) ? $_POST['option'] : "";
    
	if($choix=="modif_proxy") {
		//system("sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh proxy $proxy_url $proxy_type");
		echo "<h4>".gettext("Modification du proxy de Mozilla Firefox ")."</h4>";
		echo gettext("Le proxy a &#233;t&#233; fix&#233;e &#224;")." <B>\"$new_proxy_url\"</B>,".gettext("type $new_proxy_type")."<br>";
	}



	else {
             
            
            if($choix=="modif") {
                //$script="/usr/share/se3/scripts/modif_profil_mozilla_ff.sh"; 
		echo "<h4>".gettext("Modification de la page de d&#233;marrage de Mozilla Firefox pour le ou les groupes suivants :")."</h4>";
		//On change la page pour les groupe ou le user selectionne
            }
                
                
            if($choix=="deploy_nosave") {
		//$script="/usr/share/se3/scripts/deploy_mozilla_ff_final.sh";
                $page_dem="";
                echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla Firefox dans le ou les espaces personnels selectionn&#233; lanc&#233; en arri&#232;re-plan !")."</h4>";
//		system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh\n\" >> /tmp/$nomscript");
//		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
//		
            }
            if (count($classe_gr) ) {
                    foreach ($classe_gr as $grp){
                            $uids = search_uids ("(cn=".$grp.")");
                            $people = search_people_groups ($uids,"(sn=*)","cat");
                            $nbr_user=$nbr_user+count($people);

                            echo gettext("Traitement en cours pour le groupe Classe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A><br>";

                            system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");

                    }
            }

            if (count($equipe_gr) ) {
                    foreach ($equipe_gr as $grp){
                            $uids = search_uids ("(cn=".$grp.")");
                            $people = search_people_groups ($uids,"(sn=*)","cat");
                            $nbr_user=$nbr_user+count($people);
                            echo gettext("Traitement en cours pour le groupe Equipe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A><br>";
                            //echo gettext("La page de d&#233;marrage pour le groupe Equipe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A>";gettext(" a &#233;t&#233; fix&#233;e &#224; ")."<B>\"$page_dem\"</B><br>";

                            system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");
                    }
            }
            if (count($autres_gr) ) {
                    foreach ($autres_gr as $grp){
                            $uids = search_uids ("(cn=".$grp.")");
                            $people = search_people_groups ($uids,"(sn=*)","cat");
                            $nbr_user=$nbr_user+count($people);
                            echo gettext("Traitement en cours pour le groupe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A><br>";
                            ////echo gettext("La page de d&#233;marrage pour tout le groupe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A>".gettext(" a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B><br>";
                            system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");

                    }
            }

            //teste si utilisateur saisi pour recherche dans ldap
            if ($user!=""&&$user!="skeluser")
            {

                    //recherche dans ldap si $user est valide
                    $tabresult=search_people("uid=$user");
                    if(count($tabresult)!=0)
                    {
                            $nbr_user=$nbr_user+1;
                            echo gettext("La page de d&#233;marrage pour l'utilisateur")." $user ".gettext("a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B><br>";
                            system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $user $page_dem $option \n\" >> /tmp/$nomscript");
                    }
                    else
                    {
                            echo "<h4>".gettext(" Erreur").", \"$user\" ".gettext("n'existe pas !")."<h4>";
                    }
            }
// 			else
// 			{echo "<h4> Erreur, votre s&#233;lection est vide !<h4>";}

		//le script se supprime a la fin de son exec
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);
		
		if($nbr_user>50000){
			//execution differee d'une minute pour ne pas attendre la page trop longtemps
			echo "<h4>".gettext("Requ&#234;te lanc&#233;e en arri&#232;re-plan d'ici &#224; 1mn")."</h4>";
			system("at -f /tmp/$nomscript now + 1 minute");
                        #=========================================================================
		# Ajout: Creation du fichier d'information.
		# Il est modifie par la suite par le script /usr/share/se3/scripts/deploy_mozilla_ff_final.sh
		# Il faut que le dossier /var/www/se3/tmp existe et que www-se3 ait le droit d'y ecrire.
		$fichier_info=fopen('/var/www/se3/tmp/recopie_profils_firefox.html','w+');
		fwrite($fichier_info,'<html>
<meta http-equiv="refresh" content="2">
<html>
<body>
<h1 align="center">Traitement des profils</h1>
<p align="center">Le traitement va demarrer dans la minute qui vient...<br></p>
</body>
</html>');
		fclose($fichier_info);
	
		# Ouverture d'une fenetre popup:
		echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_firefox.html','Suivi_recopie_profils_Firefox','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
		#=========================================================================
	
		}
		else {
			//execution immediate du script
                        echo "<pre>";
			system("/tmp/$nomscript");
                        echo "</pre>";
		}
	}

	
	
		
        
//	elseif($choix=="deploy_save")
//	{
//		echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla Firefox dans les espaces personnels existants lanc&#233; !")."<br>
//		".gettext("S'il existe des fichiers bookmarks.html dans les profils, ceux-ci seront conserv&#233;s.")."</h4>";
//		system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh sauve_book\n\" >> /tmp/$nomscript");
//		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
//		chmod ("/tmp/$nomscript",0700);
//		exec("at -f /tmp/$nomscript now + 1 minute");
//
//		#=========================================================================
//		# Ajout: Creation du fichier d'information.
//		# Il est modifie par la suite par le script /usr/share/se3/scripts/deploy_mozilla_ff_final.sh
//		# Il faut que le dossier /var/www/se3/tmp existe et que www-se3 ait le droit d'y ecrire.
//		$fichier_info=fopen('/var/www/se3/tmp/recopie_profils_firefox.html','w+');
//		fwrite($fichier_info,'<html>
//<meta http-equiv="refresh" content="2">
//<html>
//<body>
//<h1 align="center">Traitement des profils</h1>
//<p align="center">Le traitement va d&#233;marrer dans la minute qui vient...<br></p>
//</body>
//</html>');
//		fclose($fichier_info);
//	
//		# Ouverture d'une fenetre popup:
//		echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_firefox.html','Suivi_recopie_profils_Firefox','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
//		#=========================================================================
//	}
            echo "<A href=\"firefox.php?config=init&choix=modif\">Retour</A><br>";
        }

include("pdp.inc.php");
?>
