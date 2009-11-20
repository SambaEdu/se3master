<?php

   /**
   
   * Gestion des cles pour clients Windows (effectue les actions sur la table restrictions c'est a dire sur les templates)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: ajout_cle_groupe.php

  */	




$cat=$_GET['cat'];
if (!$cat) { $cat=$_POST['cat']; }
$sscat=$_GET['sscat'];
if (!$cat) { $cat=$HTTP_COOKIE_VARS["Categorie"]; }
if ($cat) {
	setcookie ("Categorie", "", time() - 3600);
	setcookie("Categorie",$cat,time()+3600);
}

if (!$sscat) { $sscat=$HTTP_COOKIE_VARS["Sous-Categorie"]; }
if ($sscat) {
	setcookie ("Sous-Categorie", "", time() - 3600);
	setcookie("Sous-Categorie",$sscat,time()+3600);
}

require "include.inc.php";
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


echo "<h1>".gettext("Gestion des cl&#233;")."</h1>";

$testniveau=getintlevel();
$act=$_POST['ajoutcle'];
$autre=$_POST['modifcle'];
$salle=$_POST['salles'];
$keygr=$_POST['keygroupe'];

if (!$keygr) { $keygr=$_GET['keygroupe']; }

if (!$salle) { $salle=$_GET['salles']; }

connexion();

switch ($keygr) {
	//par defaut ajout des cles du template $salle
  	//cas 1 : modification d'une cle pour un template
  	//cas 2 : modification de la valeur d'une cle dans un template
  	//cas 3 : Ajout d'une cle pour un groupe : action
  	//cas 4 : incorporation d'un modele a un template
  	//cas 5 : incorporation d'un modele suite
  	//cas 11 : modification de la valeur d'une cle par lien simple
 	//cas 12 : suppression par lien simple
  	//cas 13 : suppression par lien simple dans base et ailleurs


	default:
    	//par defaut ajout des cles du groupe $salle
     	affichelistecat("ajout_cle_groupe.php?salles=$salle",$testniveau,$cat);

     	if (($cat) and !($cat=="tout")) {
    		$ajout=" `categorie` = '$cat'";
    		$ajoutpasaffiche="and";
    		$ajoutpasaffichewhere=" where ";
    		if ($_GET['sscat']) {$ajoutsscat=" AND sscat='$sscat' "; } else {$ajoutsscat=" "; }
    	} else  {
    		echo gettext("Choisissez une cat&#233gorie ci-dessus");
    	}
    
    	if ($cat=="tout") {
    		$ajout="";
    		if ($sscat) {$ajoutsscat=""; }
    	}
    	
	if ($_GET['sscat']) { echo "<h3>Sous cat&#233;gorie $sscat</h3>"; }
   
   	$query="SELECT cleID FROM restrictions WHERE groupe='$salle';";
    	$resultat = mysql_query($query);
    	$rowserv = mysql_fetch_array($resultat);
    	
	if (mysql_num_rows($resultat)) {
    		$values="($rowserv[0]";
        	while ( $rowserv = mysql_fetch_array($resultat)) {
			$values=$values.",$rowserv[0]";
		}
    		
		$values=$values.")";
    		$query = "SELECT cleID,Intitule,OS,chemin,categorie,sscat FROM corresp WHERE  $ajout  $ajoutsscat $ajoutpasaffiche cleID NOT IN $values order by categorie;";
    	} else {
    		$query="SELECT cleID,Intitule,OS,chemin,categorie,sscat FROM corresp $ajoutpasaffichewhere  $ajout $ajoutsscat order by categorie ;";
    	}
     
     	$resultat = mysql_query($query);
      	if (mysql_num_rows($resultat)) {
    		echo "<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\"><table border=\"1\"><tr>";
    		
		if ($cat=="tout") { echo"<td>Categorie</td><td>".gettext("Sous-Categorie")."</td>";}
    		
		echo "<td>".gettext("Prendre")."</td><td>".gettext("OS")."</td><td>".gettext("Intitul&#233")."</td>";
		echo "<td><img src=\"/elements/images/help.png\" alt=\"".gettext("Aide")."\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></td></tr>";
        	
		while ( $row = mysql_fetch_array($resultat)) {
        		$j++;
          		if ($cat=="tout") {echo "<tr><td>$row[4]</td><td>$row[5]</td>";}
        		else {echo "<tr>";}
        		
			echo "<td><INPUT TYPE=\"checkbox\" NAME=\"cle$j\" value=\"$row[0]\"></td><td>$row[2]</td><td>$row[1]</td><td>";
        		echo "<a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[0]','aide','scrollbars=yes,width=600,height=620')\">";
			echo "<img src=\"/elements/images/help.png\" alt=\"$row[3]\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></a>";
        		echo "</td></tr>";
        	}
    		
		echo "</table>";
    		echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">";
		echo "<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$j\">";
    		echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"3\" >";
    		echo "<INPUT TYPE=\"submit\" value=\"ok\"></form>";
    	}  else {
    		echo gettext("Pas de cl&#233s nouvelles pour votre s&#233lection")."</p>";
	}
    
    	echo "<br><a href=\"affiche_restrictions.php?salles=$salle&poser=yes\">";
	echo gettext("J'ai fini les modifications sur ce template")."</a><br>";
    	echo "<h3>".gettext("Attention, si cette cl&#233 n'est pas dans le template base, elle sera automatiquement ajout&#233e aux restrictions du template base et activ&#233es")."</h3>";
	break;


	
	//modification d'une cle pour un groupe
	case "1":
     	$test=0;
    	connexion();
	$liste=$_GET['listemodif'];
	$clemodif=split("-",$liste);
	$listesuppr=$_GET['listesuppr'];
	$clesuppr=split("-",$listesuppr);
	//cas des suppressions
 	for ($i=0;$i < count($clesuppr)+1;$i ++ ) {
   		$suppr=$clesuppr[$i];
   		if ($suppr) {
       			if ($salle=="base") {
       				//recherche de restrictions posees ailleurs que dans base
       				$query="select restrictions.cleID,corresp.Intitule from restrictions,corresp where restrictions.cleID='$suppr' and groupe<>'base' and corresp.CleID=restrictions.cleID";
       				$resultat=mysql_query($query);
       				$row=mysql_fetch_row($resultat);
       				
				if (mysql_num_rows($resultat)) {  
					$testsupprbase++;
          				echo "$row[1]    : ".gettext("ne peut &#234tre supprim&#233e de base")."<br>";
       				} else {
       					$query="DELETE FROM `restrictions` WHERE `cleID`=$suppr AND `groupe`='$salle';";
       					$resultat=mysql_query($query);
       				}
        		} else {
       				$query="DELETE FROM `restrictions` WHERE `cleID`=$suppr AND `groupe`='$salle';";
       				$resultat=mysql_query($query);
       			}
       		}
       	}
	
	//cas des modifications
   	for ($i=0;$i < count($clemodif)+1;$i ++ ) {
   		$cle=$clemodif[$i];
   		if ($cle) {
   			$query="SELECT corresp.Intitule, corresp.cleID, restrictions.valeur,corresp.type,corresp.antidote,corresp.valeur,corresp.OS,corresp.chemin FROM corresp, restrictions WHERE restrictions.cleID = '$cle' AND restrictions.cleID = corresp.cleID AND restrictions.groupe='$salle'";
   			$resultat=mysql_query($query);
   			$row=mysql_fetch_row($resultat);
          		
			if ($row[3]=="restrict") {
            			if ($row[2]==$row[4]) { $new=$row[5];}
            			if ($row[2]==$row[5]) { $new=$row[4];}
            			
				if (($salle=="base") and ($new==$row[4])) {
             				$sql = "SELECT restrictions.valeur FROM restrictions, corresp WHERE restrictions.cleID = '$cle' AND restrictions.cleID = corresp.CleID AND corresp.valeur = restrictions.valeur and groupe <> 'base'";
           				$resultat=mysql_query($sql);
       					
					if (mysql_num_rows($resultat)) {
       						echo $row[0].gettext(" ne peut etre modifi&#233e")."<br>";
       						$testmodifbase++;
       					} else {
       						$new=ajoutedoublebarre($new);
        					$query1 = "UPDATE restrictions SET valeur='$new' WHERE cleID='$cle' and groupe='$salle';";
      						//  echo "query1".$query1;
            					$resultat1=mysql_query($query1);
       					}
             			} else {
               				$new=ajoutedoublebarre($new);
         				
					//    echo "query2".$query1;
            				$query1 = "UPDATE restrictions SET valeur='$new' WHERE cleID='$cle' and groupe='$salle';";
            				$resultat1=mysql_query($query1);
            			}
          		}
          
	  		if ($row[3]=="config") {
          			//pas pris en charge ici : utiliser le mode un par un
         		}

 		}
 	}
 	
	echo "</table>";

	if ($testsupprbase) {
		echo " <br>".gettext("Vous ne pouvez supprimer une des cl&#233s du template de base , il faut d'abord la supprimer dans les autres templates<br> Utiliser la suppression cl&#233 &#224 cl&#233 <br> ");
	}
	
	if ($testmodifbase) {
		echo " <br>".gettext("Vous ne pouvez modifier une des cl&#233s du template de base , il faut d'abord la modifier dans les autres templates <br> Utiliser la modification cl&#233 &#224 cl&#233 <br>");
	}

	if ((!$test) and (!$testmodifbase)and (!$testsupprbase)) {
 		echo"<HEAD>";
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$salle\">";
		echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
		echo gettext("Commandes prises en compte !");
	}

	if ($test) {
		echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"2\" > <input type=\"hidden\" name=\"nombre\" value=\"$n\" />";
             	echo "<INPUT TYPE=\"submit\" value=\"".gettext("Modifier la valeur")."\">";
             	echo "</FORM>";
	}
	
	refreshzrn($salle);

	if ($salle=="base") {
		$handlelecture=opendir('/home/templates');
                while ($file = readdir($handlelecture)) {
                	if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
                        	refreshzrn($file);
                        }
		}
		
		closedir($handlelecture);
	}

 	mysql_close();
 	break;



	//modification de la valeur d'une cle
    	case "2":
    	$nombre=$_POST['nombre'];
 	for ($i=0;$i < $nombre+1;$i ++ ) {
   		$val=$_POST['newval'.$i];
        	$clemodif=$_POST['newkey'.$i];
        	connexion();
        	$query="UPDATE restrictions SET valeur='$val' WHERE cleID='$clemodif' and groupe='$salle';";
        	$insert = mysql_query($query);
 	}
        
	echo "<HEAD>";
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?sscat=$sscat&salles=$salle\">";
	echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
	echo gettext("Commandes prises en compte !");
	refreshzrn($salle);

	if ($salle=="base") {
		$handlelecture=opendir('/home/templates');
                
		while ($file = readdir($handlelecture)) {
             		if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
            			refreshzrn($file);
               		}
		}
   		closedir($handlelecture);
	}

	mysql_close();
    	break;


    	//Ajout d'une cle pour un groupe : action
	case "3":
        $nb=$_POST['nombre'];
        $nb++;
        connexion();
        for ($j=0; $j < $nb; $j++) {
        	$cle[$j]=$_POST['cle'.$j];
                if ($cle[$j]) {
                	$query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle[$j]';";
                	$insert = mysql_query($query);
                	$row = mysql_fetch_row($insert);
                 	$query = "SELECT cleID,resID FROM restrictions WHERE groupe='$salle' ORDER BY cleID ASC;";
                	$resultat = mysql_query($query);
                	$row1 = mysql_fetch_row($resultat);
                	
			if (!$row1[0]) {
                		$query="DELETE FROM `restrictions` WHERE `cleID`=0 AND `groupe`='$salle';";
                 		$insert = mysql_query($query);
                 	}
                     
		     	if ($row[1]) {
                     		if ($row[4]=="config") {
                      			$row[2]=ajoutedoublebarre($row[2]);
                      			$row[3]=$row[2];
                     		}
                      		
				$query1="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row[3]','$row[0]','$salle');";
                      		$insert = mysql_query($query1);
                       		$query2 = "SELECT cleID FROM restrictions WHERE `groupe`='base' and cleID='$cle[$j]';";
                        	$resultat2 = mysql_query($query2);
                       		$row2 = mysql_fetch_row($resultat2);
                        
				if ((!$row2[0]) and ($salle=="base")) {
                        		$query1="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row[2]','$row[0]','base');";
                         		$insert = mysql_query($query1);
                         		$ajoutclebase++;
                            		echo gettext("Ajout de la cl&#233 :")." $row[1] ".gettext("dans base")." <br>";
                          	}
                     	}  else {
                     		echo gettext("rien a faire");
                     	}
                }
	}

	echo "<HEAD>";
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=ajout_cle_groupe.php?salles=$salle\">";
	echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
	
	if (($ajoutclebase) and ($salle<>"base")) {
		echo gettext("Les cl&#233s ont &#233galement &#233t&#233 ajout&#233es au template base")." ($ajoutclebase)<br> ";
		refreshzrn("base");
		
		if ($salle=="base") {
			$handlelecture=opendir('/home/templates');
                        while ($file = readdir($handlelecture)) {
				if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
                         		refreshzrn($file);
                             	}
			}
   			closedir($handlelecture);
		}
	}

	refreshzrn($salle);
	echo gettext("Commandes prises en compte !");
	break;

	
	case "4":
	connexion();
	//incorporation d'un modele
	$retour=$_GET['retour'];
	if (!$retour) { $retour=$_POST['retour'];}
	if (!$retour) { $retour="affiche_restrictions.php";}
 	$query="SELECT `mod` FROM modele GROUP BY `mod`;";
 	$resultat = mysql_query($query);
 	echo gettext("Choisir le modele &#224 incorporer au groupe")." $salle <br><FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >";
 	$i=0;
	while ($row = mysql_fetch_array($resultat)) {    
		//echo" <input type=\"checkbox\" name=\"modele$i\" value=\"$row[0]\"/>$row[0]<br>";
		echo " <input type=\"checkbox\" name=\"modele$i\" id=\"modele$i\" value=\"$row[0]\"/><label for='modele$i'> $row[0]</label><br/>\n";
                $choix[$i]=$row[0];
                $i++;
	}
	echo "</select>";
	echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"5\">";
	echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">";
	echo "<INPUT TYPE=\"hidden\" name=\"retour\" value=\"$retour\">";
	echo "<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$i\">";
	echo "<INPUT TYPE=\"submit\" name=\"inscrire\" value=\"".gettext("Ajouter ces groupes de cl&#233s au template")."\"></FORM>";

	echo "<br>".gettext("Attention, toute cl&#233 non pr&#233sente dans base y sera &#233galement ajout&#233e afin de respecter la coh&#233rence de vos restrictions")." <br>";
	break;


	//incorporation d'un modele suite
	case "5":
	echo gettext("Ajout de groupes de cl&#233s &#224")." $salle<br>";
	$nombre=$_POST['nombre'];
	$retour=$_GET['retour'];
	if (!$retour) { $retour=$_POST['retour'];}

	for ($n=0;$n<$nombre;$n++) {
		$mod=$_POST['modele'.$n];
		$query="SELECT `cle`,`etat` FROM `modele` WHERE `mod`= '$mod' ;";
		$resultat = mysql_query($query);
		while ($row=mysql_fetch_row($resultat)) {
                	$cle=$row[0];
                	$query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle';";
                	$insert = mysql_query($query);
                	$row1 = mysql_fetch_row($insert);
                	$query2 = "SELECT cleID,valeur,resID FROM restrictions WHERE cleID='$cle' AND groupe='$salle';";
                	$verif = mysql_query($query2);
                	$row2=mysql_fetch_row($verif);
                	$query3 = "SELECT cleID,resID FROM restrictions WHERE cleID='$cle' AND groupe='base';";
                	$verif3 = mysql_query($query3);
                	$row3=mysql_fetch_row($verif3);
			$row1[2]=ajoutedoublebarre($row1[2]);
			
			if ($row1[4]=="config") {
				//$row1[2]=ajoutedoublebarre($row1[2]);
				if ($row2[2])	{
					$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
					$insert = mysql_query($query);
					echo gettext("Mise &#224 jour de la cl&#233 :")." $row1[1] : ".gettext("cl&#233 de configuration")." <br>";
				} else {
					$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','$salle');";
					$insert = mysql_query($query);
					echo gettext("Cr&#233ation de la cl&#233 :")." $row1[1] : ".gettext("cl&#233 de configuration")."<br>";
				}
			} else {
               			//cas: cette cle est dans le template, dans le template base et existe bien : mise a jour et pas insertion
                		if (($row3[0]) and ($row1[1]) and ($row2[0])) {
                			//elle est deja dans le modele :
                			//il faut donc appliquer la valeur et pas l'antidote (seulement dans base)
                			//dans un autre template, il faut si la cle est au rouge dans le modele, il faut la supprimer du template
                			//si elle est au vert dans le modele, il faut positionner a vert


                			//ici la cle est au rouge activee
                			if ($row[1]) {

                				//la salle est base : on active dans base
                				if ($salle=="base") {
                					echo gettext("Mise &#224 jour de la cl&#233  dans base :")." $row1[1] ".gettext(" active")."<br>";
                					$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                				} else {
                					//la salle n'est pas base
                					//on doit supprimer la cle du template
                
                					echo gettext("Effacement de la cl&#233  dans")." $salle : $row1[1]  <br>";
                					$query = "DELETE FROM `restrictions` WHERE `cleID`='$cle' AND `groupe`='$salle';";
                				}
                			} else {
                  				//la cle est au vert
                  				//elle n'est  pas activee dans le modele : il faut donc appliquer l'antidote et ceci independamment des salles
                  				echo gettext("Mise &#224 jour de la cl&#233 :")." $row1[1] : ".gettext("inactive")."<br>";
                  				$query = "UPDATE `restrictions` SET `valeur` = '$row1[3]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                  			}
                  		}

                  		//la cle n'est pas dans le template , elle existe bien dans corresp
                  		if (($row1[1]) and (!$row2[0])) {
                  			//il va falloir supprimer l'entree nulle eventuelle
                   			//la cle est active dans le modele
                    			if ($row[1]) {
                    				// on ne l'ajoute que dans base
                   				if ($salle=="base") {
                     					echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active) dans la salle")." $salle <br>";
					  		$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','$salle');";
                    				} else {
                    					//dans une autre salle que base
                    					//la cle n'est pas presente dans base , il faut l'ajouter
                      					if (!$row3[1]) {
                          					$query3="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','base');";
                          					$result3 = mysql_query($query3);
                          					echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active ou cle de config) dans base")." <br>";
                          					$testbase++;
                      					}
                      					//de plus on n'a rien a ajouter dans le template , par defaut c'est la restriction de base qui va s'appliquer
                      				}
                      			} else {
                     				//la cle est inactive dans le modele : on ajoute dans le template la cle desactivee
                     				echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(inactive ou cle de config)")." <br>";
				           	$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[3]','$row[0]','$salle');";
                     				//de plus la cle n'existe pas dans base , on l'ajoute (activee)
                      				if (!$row3[1]) {
                          				$query3="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','base');";
                          				$result3 = mysql_query($query3);
                             				echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active) dans base")." <br>";
                             				$testbase++;
                      				}
                      			}
                      		}
                       		$insert = mysql_query($query);
                	}
              	}
	}

	echo "<HEAD>";
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"10; URL=$retour?salles=$salle&poser=yes\">";
	echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
	echo gettext("Commandes prises en compte !");
	refreshzrn($salle);
	//le template base a ete modifie , on le rafraichit
	
	if ($testbase) { refreshzrn("base");
	
	if ($salle=="base") {
		$handlelecture=opendir('/home/templates');

                while ($file = readdir($handlelecture)) {
			if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
                      		refreshzrn($file);
                  	}
		}
   		
		closedir($handlelecture);
	}
}

break;

	
	
	//modification de la valeur d'une cle par lien simple
	case "11":
	connexion();

	$cle=$_GET['modif'];
	$query="SELECT corresp.Intitule, corresp.cleID, restrictions.valeur,corresp.type,corresp.antidote,corresp.valeur,corresp.OS,corresp.chemin FROM corresp, restrictions  WHERE restrictions.cleID = '$cle' AND restrictions.cleID = corresp.cleID AND restrictions.groupe='$salle'";
	$resultat=mysql_query($query);
	$row=mysql_fetch_row($resultat);
	if ($row[3]=="restrict") {
		//cle desactivee on la reactive
		if ($row[2]==$row[4]) { $new=$row[5];}
          	//cle activee on la desactive
            	if ($row[2]==$row[5])  { $new=$row[4];}
            	//pour la desactiver il faut etre dans base
              	if ($new==$row[4]) {
                	if ($salle=="base") {
				$new=ajoutedoublebarre($new);
				//echo "Attention, la modification de cette valeur est repercutee sur tous les templates afin d'assurer la coherence de vos restrictions<br> Elle est desactivee sur l'ensemble des templates";
				$query1= "UPDATE restrictions SET valeur='$new' WHERE cleID='$cle' and '$salle'=groupe;";
				$alert++;
			}
		// il ne s'agit pas d'une desactivation
		} else {
			$new=ajoutedoublebarre($new);
            		$query1 = "UPDATE restrictions SET valeur='$new' WHERE cleID='$cle' and groupe='$salle';";  }
            		$resultat1=mysql_query($query1);
            		//echo "Un template autre que base ne peut avoir des restrictions actives<br>
            		//Pour poser une restriction, poser la sur le template base";
            		echo "<HEAD>";
			echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=affiche_restrictions.php?salles=$salle&poser=yes\">";
			echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
			echo "Commandes prises en compte !";

			refreshzrn($salle);
			
			if ($salle=="base") {
				$handlelecture=opendir('/home/templates');
                           	while ($file = readdir($handlelecture)) {
                            		if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
                         			refreshzrn($file);
                             		}
                             	}
   				closedir($handlelecture);
			}
 		}
         	
		//cas des cles de configuration
          	if ($row[3]=="config") { 
			echo "<table border=\"1\" ><tr><td><h4>?</h4></td><td><DIV ALIGN=CENTER>".gettext("Intitul&#233")."</DIV></td><td>".gettext("OS")."</td><td>".gettext("Ancienne valeur")."</td><td>".gettext("Nouvelle valeur")."</td></tr>";

            		echo "<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" name=\"modifcle\" >";
              		echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">";
			echo "<img src=\"/elements/images/help.png\" alt=\"".gettext("Aide")."\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></a>";
			echo "</td><td><DIV ALIGN=CENTER>$row[0]</DIV></td><td><DIV ALIGN=CENTER>&nbsp;$row[6]</DIV>";
              		echo "</td><td>&nbsp;$row[2]</td><td><INPUT TYPE=\"text\" NAME=\"newval0\" value=\"$row[2]\" size=\"50\" ></td></tr>";
             		echo "<INPUT TYPE=\"hidden\" value=\"$cle\" name=\"newkey0\">";
             		echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">";
             		echo "</table><INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"2\" >";
			echo "<input type=\"hidden\" name=\"nombre\" value=\"1\" />";
             		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Modifier la valeur")."\">";
             		echo "</tr></FORM></table>";

          	}

		break;

	
	//suppression par lien simple
	case "12":
	connexion();
	$cle=$_GET['suppr'];
	if ($salle=='base') {
		echo "<h2>".gettext("Attention , vous allez supprimer la cl&#233 dans tous les templates en la supprimant du template base")."</h2><br>";
		echo "<form name=\"ajout_cle_groupe.php\" method=\"get\">";
		echo "<input type=\"submit\" name=\"submit\" value=\"".gettext("Je confirme la suppression de cette cl&#233 dans tous les templates")."\" />";
		echo "<input type=\"hidden\" name=\"suppr\" value=\"$cle\" />";
		echo "<input type=\"hidden\" name=\"salles\" value=\"$salle\" />";
		echo "<input type=\"hidden\" name=\"keygroupe\" value=\"13\" /></form>";
		
		echo "<br> <br>";
		echo gettext("D'&#233ventuelles restrictions peuvent rester sur les postes , il est plus prudent de laisser la cl&#233 d&#233sactiv&#233e un certain temps avant de la supprimer de ce template")."<br><br>";
	} else {
		$query="DELETE FROM `restrictions` WHERE `cleID`=$cle AND `groupe`='$salle';";
		$resultat=mysql_query($query);
		echo"<HEAD>";
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$salle\">";
		echo "</HEAD>Modification effectu&#233;e pour le template : $salle<br>";
		echo gettext("Commandes prises en compte !");
		
		refreshzrn($salle);
		
		if ($salle=="base") {
			$handlelecture=opendir('/home/templates');
                        while ($file = readdir($handlelecture))  {
                        	if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
                         		refreshzrn($file);
                             	}
			}
			closedir($handlelecture);
		}
	}
	break;


	
	//suppression par lien simple dans base et ailleurs
	case "13":
	connexion();
	$cle=$_GET['suppr'];
	$query="DELETE FROM `restrictions` WHERE `cleID`=$cle ;";
       	$resultat=mysql_query($query);
	echo "<HEAD>";
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$salle\">";
	echo "</HEAD>".gettext("Modification effectu&#233e pour le template :")." $salle<br>";
	echo gettext("Commandes prises en compte !");
	
	
	refreshzrn($salle);
	if ($salle=="base") {
		$handlelecture=opendir('/home/templates');
		while ($file = readdir($handlelecture)) {
			if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') {
				refreshzrn($file);
			}
		}
   		closedir($handlelecture);
	}
	break;

}
retour();

include("pdp.inc.php");
?>
