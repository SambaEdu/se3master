<?php

   /**
   
   * Permet de mettre en place des alertes (supervison)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   * @auteurs  Philippe Chadefaux
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: alertes 
   * file: config_alertes.php
   */

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "dbconfig.inc.php";
include "fonc_inventaire.php";

//***************D�inition des droits de lecture  et aide en ligne

if (is_admin("computers_is_admin",$login)=="Y")  {

  //aide 
  $_SESSION["pageaide"]="L\'interface_web_administrateur#Gestion_des_alertes";
} else { exit; }

/************************* Declaration des variables ************************************/
$affiche_detail=$_GET["affiche_detail"];
$advanced=$_GET["advanced"];
$id=$_GET["ID"];
$action=$_GET["action_hidden"];
//echo "testaction $action";
$right=$_GET["droit"];
if (!($right)) $right="computers_is_admin";
$table=$_GET[""];
$table_aff=$_GET["table_aff"];
$colonne=$_GET["champs"];
$type=$_GET["type"];
$choix=$_GET["choix"];if (!($choix)) $choix="LIKE";
$parc=$_GET["parc"];
$detail=$_GET["detail"]; if (!$detail) { $detail="yes";}
$affiche_machine=$_GET["machine"];
$nom_alert=$_GET["nom_alert"];
$validation_alert=$_GET["validation_alert"];    //echo $validation_alert;
$name_alert=$_GET["name_alert"];
$choix_compar=$_GET["choix_compar"];
$count_alert=$_GET["count_alert"];
$query=$_GET["query"];
$text_alert=$_GET["text_alert"];
$mail_alert=$_GET["mail_alert"];
$activ_alert=$_GET["activ_alert"];
$parc_alert=$_GET["parc_alert"];
  $fichier="/etc/exim/exim.conf";
   $fichier_sarge="/etc/exim4/exim4.conf";
 
/*************Connexion a la base************************/

$dbnameinvent="ocsweb";

$authlink_invent=@($GLOBALS["___mysqli_ston"] = mysqli_connect($_SESSION["SERVEUR_SQL"], $_SESSION["COMPTE_BASE"], $_SESSION["PSWD_BASE"]));
@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbnameinvent)) or die("Impossible de se connecter &#224; la base $dbnameinvent.");


$base=array('softwares','bios','controllers','drivers','hardware','inputs','memories','modem','monitors','networks','ports','printers','registry','slots','sounds','storages','videos');
$jour=date("Y-m-d G:i:s");
 
/***************definition d'une nouvelle alerte   ***********/
  
/***************choix des machines touch�s par l'alerte***********/
if ($action=="new") {
	echo "<H1>".gettext("D&#233;finition d'une nouvelle alerte");
	// if ($advanced) { echo gettext(" avanc&#233;e "); }
	if ($parc) { echo gettext(" pour ")." $parc";}
	echo "</H1>\n";
	echo "<BR><BR>";

    	if (!$table_aff) { $table_aff="S&#233;lectionner"; }
    	echo "<form action=\"config_alert.php\" method=\"get\">\n";


    	echo "<TABLE><TR><TD>";
        echo gettext("Ajouter une alerte sur le parc <I>(Si pas de choix, toutes les machines)</I> : ");
    	echo "</TD><TD>";
        $list_parcs=search_machines("objectclass=groupOfNames","parcs");
        if ( count($list_parcs)>0) {
            //echo "<FORM action=\"search_inventaire.php\" name=\"choix_action\" method=\"get\">\n";
            //  echo "Visualiser le parc: \n";
            echo "<SELECT NAME=\"parc\" SIZE=\"1\" onchange=submit() ><option></option>";
            for ($loop=0; $loop < count($list_parcs); $loop++) {
            	echo "<option value=\"".$list_parcs[$loop]["cn"]."\"";
        	if ($parc == $list_parcs[$loop]["cn"]) {
           		echo "selected";
        	}   
        
        	echo ">".$list_parcs[$loop]["cn"]."\n";
            }
            echo "</SELECT></TD></TR>\n";

        }
    

    	echo "<TR><TD>".gettext("S&#233;lectionner une table pour votre alerte :")." </TD><TD>\n";
    	echo "<select name=\"table_aff\" size=\"1\" onchange=\"submit()\">\n\n" ;
    	echo "<option>S&#233;lectionner</option>";   
        foreach ($base as $table_base) {
        	echo"<option";
        	if ($table_aff == $table_base) {
            		echo " selected";
        	}   
        	echo ">$table_base</option>\n";
	}
        echo"</select>\n";
    	echo "</TD><TD>\n";

        echo "<input type=\"hidden\" name=\"action_hidden\" value=\"new\" />";
        echo "</FORM>\n"; 

    	echo "</TD></TR>\n";

	if ((!$colonne) and ($table_aff != "S&#233;lectionner")) {
	echo "<TR><TD>";
    	echo gettext("Pr&#233;ciser la recherche sur cette table :");
    	echo "</TD><TD>\n";
//     	$affiche=affiche($table_aff);
    	$query="SELECT * FROM `$table_aff` ORDER BY NAME LIMIT 1;";
    	$result=mysqli_query($GLOBALS["___mysqli_ston"], $query);
    	if ($result) {
        	$fields=(($___mysqli_tmp = mysqli_num_fields($result)) ? $___mysqli_tmp : false);
        	echo "<form action=\"config_alert.php\" method=\"get\">\n
            	<input type=\"hidden\" name=\"table_aff\" value=\"$table_aff\" />
            	<input type=\"hidden\" name=\"parc\" value=\"$parc\" />\n
            	<select name=\"champs\" size=\"1\" >" ;
                $i=0;
                while ($i<$fields){
                	$nomcolonne=((($___mysqli_tmp = mysqli_fetch_field_direct($result, $i)->name) && (!is_null($___mysqli_tmp))) ? $___mysqli_tmp : false);
			// Ajoute apres
	//		$affiche=$nomcolonne;
        //            	if (in_array($nomcolonne,$affiche)) echo"<option>$nomcolonne</option>";
         
                    	echo"<option>$nomcolonne</option>";
	 		$i++;
                }
                echo "</select>\n";
                echo "<select name=\"choix\" size=\"1\"><option>LIKE</option><option>=</option><option><</option><option>></option><option>NOT LIKE</option></select>\n";
                echo"<input type=\"text\" name=\"type\" value=\"$type\" size=\"20\" /></tr>\n";
                echo"<tr><td>".gettext("Je veux afficher les machines concern&#233;es :")." </td><td><input type=\"checkbox\" name=\"affiche_detail\" /></TD></tr>\n";
		//on veut faire une recherche avancee
          	echo "<tr><td>".gettext("Alerte avanc&#233;e :")." </td><td><input type=\"checkbox\" name=\"advanced\" /></TD></tr>\n";
              	echo"<input type=\"hidden\" name=\"action_hidden\" value=\"new_suite\" size=\"20\" />";

              	echo"<tr><td></TD><td><input type=\"submit\" name=\"submit\" value=\"".gettext("Envoyer")."\" /></TD></TR></table>\n";

            	echo"</form>\n";
    
	}
    }
}


if ($action=="new_suite") {
	echo "<H1>".gettext("D&#233;finition d'une nouvelle alerte");
	if ($advanced) { echo gettext("avanc&#233;e "); }
	if ($parc) { echo gettext("limit&#233;e au parc $parc");}
	echo "</H1>";

	if ($table_aff<>"hardware")  {
        	$query="SELECT DISTINCT hardware.NAME,$table_aff.$colonne FROM hardware,$table_aff WHERE $table_aff.$colonne $choix '%$type%' and hardware.ID=$table_aff.HARDWARE_ID;";
    	} else {
        	$query="SELECT DISTINCT hardware.NAME,$table_aff.$colonne FROM $table_aff WHERE $table_aff.$colonne $choix '%$type%';";
    	}

	//fin de la configuration de la nouvelle alerte : plac� avant l'affichage des r�ultats
	if ($advanced) {
   		echo "<CENTER><TABLE border=1 width=\"80%\">";
         	echo "<FORM ACTION=\"config_alert.php\" method=\"get\">";
        
        	echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"4\">".gettext("Ajout de l'alerte correspondant &#224; cette recherche")."</TD></tr>\n";
        	echo "<tr><td>".gettext("Nom de l'alerte")."</td>\n";
		echo "<TD colspan=\"2\"><INPUT size=\"80\" TYPE=texte NAME=name_alert></TD></TR>\n";
        	echo "<tr><td>".gettext("Commentaire")."</td>\n";
		echo "<TD><INPUT size=\"80\" TYPE=texte NAME=text_alert></TD></TR>\n";
        	echo "<TR><TD>".gettext("Le nombre de machines concern&#233;es par cette alerte doit &#234;tre")."</td>";
		echo "<td><select name=\"choix_compar\">\n
        		<option value=\"egal a;\">&#233;gal &#224;</option>\n
        		<option value=\"inferieur a;\">inf&#233;rieur &#224;</option>\n
        		<option value=\"superieur \">sup&#233;rieur &#224;</option>\n
        		<option value=\"au maximum\">&#233;gal au nombre de machines r&#233;pertori&#233;es dans l'inventaire</option></select>\n
        		</TD><TD><INPUT TYPE=texte NAME=count_alert></TD></TR>\n
         		<input type=\"hidden\" name=\"type\" value=\"$type\" size=\"20\" />\n
          		<input type=\"hidden\" name=\"action_hidden\" value=\"fin_alert\" size=\"20\" />
   			<input type=\"hidden\" name=\"parc\" value=\"$parc\" size=\"20\" />\n ";

      		echo "<input type=\"hidden\" name=\"query\" value=\"$query\" size=\"20\" />\n ";
        	echo "<TR><TD colspan=\"4\" align=center><INPUT TYPE=\"submit\" value=\"Valider\"></TD></TR></table></table></form>\n";
        } else {
 		echo "<CENTER><TABLE border=1 width=\"80%\">";
         	echo "<FORM ACTION=\"config_alert.php\" method=\"get\">";
        
        	echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"4\">".gettext("Ajout de l'alerte correspondant &#224; cette recherche")."</TD></tr>\n";
        	echo "<tr><td>".gettext("Nom de l'alerte")."</td>\n";
		echo "<TD><INPUT size=\"80\" TYPE=texte NAME=name_alert></TD></TR>\n";
        	echo "<tr><td>Commentaire</td><TD><INPUT size=\"80\" TYPE=texte NAME=text_alert></TD></TR>\n";
        	echo "<tr><td>".gettext("Je veux que ma s&#233;lection soit pr&#233;sente ");
       		if ($parc) { echo gettext("dans le parc")." $parc"; }else { echo gettext("pour l'ensemble des machines de l'inventaire"); }
       		echo "</td><td><table><tr><TD width=\"92%\">".gettext("Pour toutes les machines")." </td><td><input type=\"radio\" name=\"count_alert\" value=\"max\" /> </td></tr>\n";
                echo "<tr><td width=\"92%\">".gettext("Pour aucune machine")."</td>\n";
		echo "<td><input type=\"radio\" name=\"count_alert\" value=\"0\" /></td>\n";
		echo "</TR></table></td></TR>\n";
                echo "<input type=\"hidden\" name=\"choix_compar\" value=\"egal a\" />";
       		echo"  <input type=\"hidden\" name=\"type\" value=\"$type\" size=\"20\" />\n
          	<input type=\"hidden\" name=\"action_hidden\" value=\"fin_alert\" size=\"20\" />
   		<input type=\"hidden\" name=\"parc\" value=\"$parc\" size=\"20\" />\n";

  		echo "<input type=\"hidden\" name=\"query\" value=\"$query\" size=\"20\" />\n";

       	 	echo "<TR><TD colspan=\"4\" align=center><INPUT TYPE=\"submit\" value=\"Valider\"></TD></TR></table></table></form>\n";
   
	}

/**************** configuration del'alerte********************/

  if ($table_aff<>"hardware")  {
        $query="SELECT DISTINCT hardware.NAME,$table_aff.$colonne FROM hardware,$table_aff WHERE $table_aff.$colonne $choix '%$type%' and hardware.ID=$table_aff.HARDWARE_ID;";
    }else {
        $query="SELECT DISTINCT hardware.NAME,$table_aff.$colonne FROM $table_aff WHERE $table_aff.$colonne $choix '%$type%';";
    }
 //   echo $query;
$query_number="SELECT COUNT(NAME) FROM hardware;";
$result_number=mysqli_query($GLOBALS["___mysqli_ston"], $query_number);
$count_total=mysqli_fetch_row($result_number);
echo "<table>";
$result=mysqli_query($GLOBALS["___mysqli_ston"], $query);
       if ($result) {

          //construction du tableau des machines du parc $parc
                $parc_array=array();
                $parc_traite=array();
                if ($parc) {
                  $mp_all=gof_members($parc,"parcs",1);
                  for ($loop=0; $loop < count($mp_all); $loop++) {
                  array_push($parc_array,strtoupper(urlencode($mp_all[$loop])));
                    }
                    }
                    else
                    {
                    $mp_all=search_machines("(&(!(l=maitre))(!(l=esclave))(objectclass=ipHost))","computers");
                  for ($loop=0; $loop < count($mp_all); $loop++) {
                  array_push($parc_array,strtoupper(urlencode($mp_all[$loop]["cn"])));
                    }
                
                    }
         $fields=mysqli_num_rows($result);
         if ($fields>1) { echo "<h2>L'alerte sera pos&#233;e pour la valeur \" $type \" dans la table $table_aff</h2>"; }
         while ($row=mysqli_fetch_row($result))
         {
           $affiche_new_li="";
           if ($old<>$row[0]) {
           $affiche_new_li="<td><li><a href=\"info_machine.php?mpenc=$row[0]&tout=1&cat=$table_aff\"><font color=grey>$row[0]</font></a></li></td>";
           }else {
           $affiche_new_li="<td>&nbsp;</td>";
           }
          $old=$row[0];
      

           if ($detail=="yes") {
           $affichage_detail="<td><a href=\"search_inventaire.php?table_aff=$table_aff&parc=$parc&champs=$colonne&choix=$choix&type=".urlencode($row[1])."\">$row[1]</a></td>";
           }
        
          if ($parc) {
         if (in_array(strtoupper($row[0]),$parc_array)) {
//        if ($affiche_detail) { echo "<tr>$affiche_new_li $affichage_detail <td>".dernier_modif($row[0])."</td></tr> "; }

        if ($affiche_detail) { echo "<tr>$affiche_new_li $affichage_detail <td></td></tr> "; }
	array_push($parc_traite,strtoupper(urlencode($row[0])));
             $count++;
           }
         }
         else
         {
//       if ($affiche_detail) {  echo "<tr>$affiche_new_li $affichage_detail <td>".dernier_modif($row[0])."</td></tr> "; }

       if ($affiche_detail) {  echo "<tr>$affiche_new_li $affichage_detail <td></td></tr> "; }
	$count++;
         array_push($parc_traite,strtoupper(urlencode($row[0])));
         }
         }
       
          echo "</table>";
         $parc_traite_unique=array_unique($parc_traite);

        if ($count) {
       echo"<h2>R&#233;sultats trouv&#233;s sur ".count($parc_traite_unique)." machine"; if (count($parc_traite_unique)>1) echo "s";
          $diff_machine=array_unique(array_diff($parc_array,$parc_traite));
          $machine_ignore=implode("|",$diff_machine);
          if (count($parc_traite_unique)<>count($parc_array))
          { $color="red";
        // if ($affiche_detail) { $ajoutlien="<a href=\"search_inventaire.php?table_aff=$table_aff&parc=$parc&champs=$colonne&choix=$choix&type=$type&machine=$machine_ignore \">Voir les machines ignor�s</a><br>";  }
          }
          if ($parc) { $finphrase="dans le parc $parc";}else { $finphrase="dans tout le domaine";}
         echo "<FONT color=$color>&nbsp;&nbsp;(" .count($parc_array)." $finphrase ) ";
        // if (!$affiche_machine) { echo $ajoutlien;   }
echo"</h2></FONT>";
         }


if (!$parc)  {  echo "<h2>   $fields r&#233;sultats trouv&#233;s actuellement sur $count_total[0] ";
             //   if ($count_total[0]==1) { echo "machine r�ertori� dans l'inventaire </h2>"; } else {
             echo "machines r&#233;pertori&#233;es dans l'inventaire </h2>";
             //}

          }

}
}
//}


//************************ajout de l'alerte dans la table*****************
if ($action=="fin_alert") {

	echo "<H1>";
	echo gettext("Configuration des alertes");
	echo "</H1>\n";
	//pour l'instant par defo
	$mail="computers_is_admin";
	//une alerte doit etre ajout�
	$texte=gettext("L'alerte")." $name_alert ".gettext("est d&#233;finie pour")." $type. ".gettext("Cette valeur doit &#234;tre")." $choix_compar $count_alert.";
	if ($parc) $texte="$texte ".gettext("Elle est restreinte aux machines du parc")." $parc.";
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
	$query_insert="INSERT INTO alertes (ID,NAME,MAIL,Q_ALERT,VALUE,CHOIX,TEXT,PARC,MENU,ACTIVE) VALUES ('','$name_alert','$mail','$query','$count_alert','$choix_compar','$text_alert','$parc','inventaire','1');";
	//echo $query_insert;
	$result=mysqli_query($authlink, $query_insert);

	//envoi de mail
	if ((file_exists($fichier)) ||  (file_exists($fichier_sarge))) {
		echo alerte_mail($mail,"[SE3] : Ajout de l'alerte $name_alert",$texte);
   		echo "<center><font color=\"orange\">".gettext("Un message a &#233;t&#233; envoy&#233; aux membres de computers_is_admin<BR>Si vous ne le recevez pas, v&#233;rifier que l'exp&#233;dition des mails est bien configur&#233;e sur votre serveur SambaEdu")."<BR></font></center>";
	} else {     
		echo "<CENTER><font color=\"orange\"><u>".gettext("Attention :")." </u>".gettext("Il n'est pas possible d'envoyer des messages via la messagerie.<BR> Vous devez configurer exim pour pouvoir utiliser cette fonctionnalit&#233;")."</font><BR>";
	}

	echo $texte;
	echo "<p><a href=\"alertes.php?action_hidden=config\">".gettext("Retour")."</a>";
	exit;
}
//}


//**************cas ou l'on veut voir les alertes******************************
if ($action=="view") {
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");

	$query_info="SELECT * FROM alertes where PREDEF='0' and MENU='inventaire';";
	$result_info=mysqli_query($authlink, $query_info);
    	echo "<CENTER><TABLE border=1 >";
    	echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"4\">ALERTES</TD></TR>";
    
        while ($row = mysqli_fetch_array($result_info)) {
       		if ($row["ACTIVE"]=="1") {
       			$statut="<IMG style=\"border: 0px solid ;\" SRC=\"../elements/temp/recovery.png\" ALT=\"Alerte active\">";
       		} else {
       			$statut="<IMG style=\"border: 0px solid ;\" SRC=\"../elements/temp/disabled.png\" ALT=\"Alerte inactive\">";
       		}
        	echo "<tr><td>$statut&nbsp;</td><td>".$row["NAME"]."</td>
        	<TD><a href=\"config_alert.php?action_hidden=suppr&ID=".$row["ID"]."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/edittrash.png\" ALT=\"Supprimer\"></a></TD>
        	<TD><a href=\"config_alert.php?action_hidden=mod&ID=".$row["ID"]."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/temp/zoom.png\" ALT=\"Modifier\"></a></TD>

        	</tr>";
       	}
	//</td>$row["Q_ALERT"]<td>

	echo "</table>\n";
}


if ($action=="suppr") {
	//$ID=$_GET['ID'];
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
	$query_info="SELECT * FROM alertes WHERE ID='$id';";
	$result_info=mysqli_query($authlink, $query_info);
	$row = mysqli_fetch_array($result_info);

	$query_suppr="DELETE FROM alertes WHERE ID='$id'";
	$result_suppr=mysqli_query($authlink, $query_suppr) or die("Erreur lors de la suppression de l'alerte");
	if ($result_suppr) { $texte="L'alerte ".$row['NAME']." a &#233;t&#233; supprim&#233;e.";
   	if ((file_exists($fichier)) ||  (file_exists($fichier_sarge))) {
   		echo alerte_mail($row['MAIL'],"[SE3] : Suppression de l'alerte ".$row['NAME'],$texte);
   		echo "<center><font color=\"orange\">".gettext("Un message a &#233;t&#233; envoy&#233; aux membres de computers_is_admin<BR>Si vous ne le recevez pas, v&#233;rifier que l'exp&#233;dition des mails est bien configur&#233;e sur votre serveur SambaEdu")."<BR></font></center>";
  
 	} else {     
		echo "<CENTER><font color=\"orange\"><u>".gettext("Attention :")." </u>".gettext("Il n'est pas possible d'envoyer des messages via la messagerie.<BR> Vous devez configurer exim pour pouvoir utiliser cette fonctionnalit&#233;")."</font><BR>";
  	}
	echo "<br>".gettext("Suppression de l'alerte "). $row['NAME'] .gettext(" effectu&#233;e.");
	$query_log = "INSERT INTO logocs (ID,NAME,ETAT,LOGDATE,REP) VALUES ('NULL','$name_alert','suppr','$jour','TOUS')";
	$result_log = mysqli_query($GLOBALS["___mysqli_ston"], $query_log);
	echo "<p><a href=\"alertes.php?action_hidden=config\">".gettext("Retour")."</a>";
} else {
	echo gettext("La suppression de l'alerte a &#233;hou&#233;e");
}

}


if ($action=="mod") {

	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));

	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");
	$query_info="SELECT * FROM alertes WHERE ID='$id';";
	$result_info=mysqli_query($authlink, $query_info);
	$row = mysqli_fetch_array($result_info);
	
	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
        if ( count($list_parcs)>0) {
		$liste_deroulante_parc=$liste_deroulante_parc."<SELECT NAME=\"parc_alert\" SIZE=\"1\"  ><option>TOUS</option>";
            	for ($loop=0; $loop < count($list_parcs); $loop++) {
                	$liste_deroulante_parc=$liste_deroulante_parc."<option value=\"".$list_parcs[$loop]["cn"]."\"";
        		if ($row['PARC'] == $list_parcs[$loop]["cn"]) {
            			$liste_deroulante_parc=$liste_deroulante_parc."selected";
        		}   
        		$liste_deroulante_parc=$liste_deroulante_parc.">".$list_parcs[$loop]["cn"]."\n";
            	}
            	$liste_deroulante_parc=$liste_deroulante_parc."</SELECT></TD></TR>\n";
	}


	echo "<H1>".gettext("Modification de l'alerte "). $row['NAME'] ."</H1>\n";
	echo "<CENTER>\n";
	echo "<form action=\"config_alert.php?action_hidden=mod2&ID=$id\" method=get><table border=1>";
	echo "<TR><TD class=\"menuheader\" height=\"30\" align=center colspan=\"2\">".gettext("Modification de l'alerte "). $row['NAME'] ." </TD></TR>\n";
	
	echo "<tr><td class=\"menuheader\">".gettext("Nom")."</td><td><input type=\"text\" name=\"name_alert\" value=\"".$row['NAME']."\" size=\"30\" /></td></tr>\n";
	echo "<tr><td class=\"menuheader\">".gettext("Commentaires")."</td><td><input type=\"text\" name=\"text_alert\" value=\"".$row['TEXT']."\" size=\"30\" /></td></tr>\n";
	echo "<tr><td class=\"menuheader\">Mail</td>\n";
	echo "<td><select name=\"mail_alert\" size=\"1\">\n";
	echo "<option ";
	if($row['MAIL']=="se3_is_admin") {echo " selected";}
	echo ">se3_is_admin</option>\n";
	echo "<option ";
	if($row['MAIL']=="computers_is_admin") {echo " selected"; }
	echo ">computers_is_admin</option>\n";
	echo "<option";
	if($row['MAIL']=="lcs_is_admin") {echo " selected";}
	echo ">lcs_is_admin</option>\n";
	echo "<option";
	if($row['MAIL']=="maintenance_can_write") {echo " selected";}
	echo ">maintenance_can_write</option>\n";
	echo "</select></td></tr>\n";
	
	echo "<tr><td class=\"menuheader\">".gettext("Parc")."</td>\n";
	//if (!$row['PARC']) {echo "TOUS";}else { echo $row['PARC'];}
	
	echo "<td>$liste_deroulante_parc</td></tr>\n";
	echo "<tr><td class=\"menuheader\">".gettext("Alerte active")."</td>\n";
	// if ($row['ACTIVE']==1) {echo "Oui";} else {echo "Non";}
	echo "<td><select name=\"activ_alert\" size=\"1\">\n<option value=1 ";
	if($row['ACTIVE']=="1") {echo " selected"; }
	echo ">".gettext("Oui")."</option>\n<option value=0 ";
	if($row['ACTIVE']=="0") {echo " selected";}
	echo ">".gettext("Non")."</option>\n";
	echo "</select></td></tr>\n";
	
	echo "<tr><td colspan=3  align=center><input type=\"submit\" value=\"".gettext("Modifier")."\" /><INPUT value=\"mod2\" name=\"action_hidden\" type=\"hidden\"><INPUT value=\"$id\" name=\"ID\" type=\"hidden\"></td></tr></table>\n";

}


if ($action=="mod2") {
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");

	$query_update="UPDATE alertes SET NAME='$name_alert', TEXT='$text_alert', MAIL='$mail_alert' ,PARC='$parc_alert' ,ACTIVE='$activ_alert' WHERE ID='$id';";
	$result_update=mysqli_query($authlink, $query_update) or die("Erreur lors de la modification de l'alerte");
	
	echo "<H1>".gettext("Gestion des alertes")."</H1>";
	echo "<CENTER>";
	if ($result_update) { 
		$texte=gettext("L'alerte ")." $name_alert. ".gettext(" a &#233;t&#233; modifi&#233;e.");
		echo "<br>".gettext("Modification de l'alerte ")." $name_alert. ".gettext(" effectu&#233;e.");
		echo "<p><a href=\"alertes.php?action_hidden=config\">".gettext("Retour")."</a>";
	} else {
		echo gettext("La modification de l'alerte a &#233;chou&#233;e");
	}
}

include("pdp.inc.php");
