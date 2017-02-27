<?php


   /**
   
   * Gestion des cles pour clients Windows (permet d'ajouter une cle dans la base)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: ajout_cle.php

  */	



require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

?> 


<title>Modification des cl&#233;s s&#233;lectionn&#233;es</title><body>

<head>
<SCRIPT LANGUAGE="JavaScript">


/**
* Fonctions passe a checked tous les champs de type box
* @language Javascript	
* @Parametres 
* @Return  
*/

function checkAll(nombre)
{
for (var j = 1; j < nombre; j++)
    {
    box = eval("document.ajoutcle.cle" + j);
    if (box.checked == false) box.checked = true;
       }
}


/**
* Fonctions passe a unchecked tous les champs de type box
* @language Javascript	
* @Parametres 
* @Return  
*/

function uncheckAll(nombre)
{
    for (var j = 1; j < nombre; j++)
    {
    box = eval("document.ajoutcle.cle" + j);
    if (box.checked == true) box.checked = false;
    }
}

</script>
</head><body>

<?php
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


require "include.inc.php";
echo "<h1>Ajout d'une cl&#233;</h1>";
//connexion a la base de donnees
connexion();
//recuperation de l'action a effectuer
$ajout=$_POST['ajout'];
if (!$ajout) { $ajout=$_GET['ajout']; }
//cas 2: exporter les cles

switch ($ajout) {
	//defaut: preparation de l'ajout d'une cle unique
	//cas 1 : ajout d'une cle unique : insertion des donnees dans la base
	//cas 2 : exporter des cles : permet de selectionner les cles a exporter ancienne methode (obsolete)
	//cas 4 : resultat de l'exportation dans une textearea (obsolete)
	//cas 5 : ajout en nombre de cles  (obsolete)
	//cas 6 : analyse du vrac de l'ajout en nombre (obsolete)
	//cas 7 : confirmation de l'ajout des cles (obsolete)
	//cas 8 : importation d'un .reg
	//cas 9 : premiere analyse du point reg

	//cas 2 :exporter des cles : permet de selectionner les cles a exporter ancienne methode (obsolete)
	case "2":
	//$n : utilise par le javascript
	//$nombre1 : utise pour recuperer le nombre de resultats de la recherche

    	echo gettext("Exporter des cl&#233s");
    	connexion();
    	$query="Select Intitule,cleID,valeur,genre,OS,chemin,categorie,sscat from corresp order by cleID desc";
    	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    	$nombre1 = mysqli_num_rows($resultat);
    	//pour selectionner tout d'un coup necessite de connaitre le nombre de cles existantes
    	echo "<title>".gettext("Liste des cl&#233s enregistr&#233es")."</title><br><br>";
    	echo "<FORM METHOD=POST ACTION=\"ajout_cle.php\" name=ajoutcle >";
    	echo "<table border=\"1\" ><tr><td><img src=\"/elements/images/system-help.png\" alt=\"Aide\" title=\"$row[5]\" width=\"16\" height=\"18\" border=\"0\" /></td><td><DIV ALIGN=CENTER>".gettext("Intitul&#233")."</DIV></td><td>OS</td><td><DIV ALIGN=CENTER>".gettext("Valeur (defaut)")."</DIV></td><td>".gettext("Exporter")."</td></tr>";
    	$row = mysqli_fetch_array($resultat);
    	$n=1;
    	echo"<tr><td><DIV ALIGN=CENTER><a href=\"#\' onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">?</a></td><td>$row[6]</td><td>$row[7]</td><td>$row[0]</DIV></td><td><DIV ALIGN=CENTER>&nbsp;$row[4]</DIV></td><td><DIV ALIGN=CENTER>$row[2]</DIV> </td><td><DIV ALIGN=CENTER><INPUT TYPE=\"checkbox\" NAME=\"cle1\" value=\"$row[1]\"></DIV></td></tr>";
    	//$nombre=$row[1]+1;
    	while ($row = mysqli_fetch_array($resultat)) {
        	$n++;
        	echo"<tr><td><DIV ALIGN=CENTER><a href=\"#\' onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\" ><img src=\"/elements/images/system-help.png\" alt=\"Aide\" title=\"$row[5]\" width=\"16\" height=\"18\" border=\"0\" /></a></td><td>$row[6]</td><td>$row[7]</td><td>$row[0]</DIV></td><td><DIV ALIGN=CENTER>&nbsp;$row[4]</DIV></td><td><DIV ALIGN=CENTER>$row[2]</DIV> </td><td><DIV ALIGN=CENTER><INPUT TYPE=\"checkbox\" NAME=\"cle$n\" value=\"$row[1]\" ></DIV></td></tr>";
        }
    	$n++;
    	echo"</table><INPUT TYPE=\"hidden\" name=\"ajout\" value=\"4\"><INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$nombre1\"><INPUT TYPE=\"submit\" value=\"".egttext("Exporter ces cl&#233s")."\" name=\"ajoutcle\"> <br><input type=button value=\"".gettext("S&#233lectionner tout")."\" onClick=\"checkAll($n)\"><input type=button value=\"".gettext("D&#233s&#233lectionner tout")."\" onClick=\"uncheckAll($n)\"><br></FORM>";
	break;


	//cas 4 :resultat de l'exportation dans une textearea (obsolete)
	case "4":
    	echo gettext("Resultat de l'exportation")."<br>";
    	$nb=$_POST['nombre1'];
    	$nb++;
    	echo"<TEXTAREA ROWS=\"30\" COLS=\"150\" >";
    	for ($j=0; $j < $nb; $j++) {
        	$cle[$j]=$_POST['cle'.$j];
            	if ($cle[$j]) {
                	$query="SELECT Intitule,valeur,antidote,genre,OS,type,chemin,comment,categorie,sscat FROM corresp WHERE cleID='$cle[$j]'";
                	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                	$row = mysqli_fetch_row($resultat);
                	echo "$row[0]--$row[1]--$row[2]--$row[3]--$row[4]--$row[5]--$row[6]--$row[7]--$row[8]--$row[9];&;";
		}
	}
    	echo"</TEXTAREA>";
	break;


	//ajout en nombre de cles  (obsolete)
	case "5":
    	echo gettext("Les champs Intitul&#233;s, valeur, antidote, genre, OS , type (config ou restrict) , chemin (HKEY_CURRENT_USER\...... ) , commentaires, Cat&#233;gorie ,Sous-Cat&#233;gorie doivent &#234;tre s&#233;par&#233;s par -- et chaque cl&#233; par ;&;. <br>Par exemple: <br>Page de d&#233;marrage d'Internet Explorer--www.ac-creteil.fr--www.ac-creteil.fr--REG_SZ--TOUS--config--HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\Main\Start Page--Mon commentaire--Categorie--Souscat&#233;gorie;&;");
	echo "<br><FORM METHOD=POST ACTION=\"ajout_cle.php\"><TEXTAREA ROWS=\"30\" COLS=\"100\" name=\"vrac\" ></textArea>";
	echo "<INPUT TYPE=\"hidden\" name=\"ajout\" value=\"6\"><br><INPUT TYPE=\"submit\" value=\"Ajouter ces cl&#233;s\"></FORM>";

	break;


	//analyse du vrac de l'ajout en nombre (obsolete)
	case "6":
    	$brut1=$_POST['vrac'];
    	echo gettext("Premi&#232;re analyse des cl&#233;s &#224; importer")."<br>";
    	$brutout= enleveantislash($brut1);
    	$result=preg_split("/;&;/",$brutout);
    	$nombre=count($result);
    	$nombre1=$nombre-1;

    	echo "<br><FORM METHOD=POST ACTION=\"ajout_cle.php\" name=\"ajoute\">";
    	connexion();
    	echo "<table border=\"1\">";
    	for ($j=0; $j < $nombre; $j++) {
        	$export[$j]=enlevedoublebarre($result[$j]);
        	$cle=preg_split("/--/",$export[$j]);
        	
		if ($cle[6]) {
                	$cletrim=ajoutedoublebarre(($cle[6]));
                	$query="SELECT chemin FROM corresp WHERE chemin='$cletrim';";
                	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                	$row = mysqli_fetch_row($resultat);
                	if ($row[0]){ 
				$exist++; 
			}  else { 
				$nouv++;
                       		echo "<tr><td><INPUT TYPE=\"checkbox\" NAME=\"test$j\" value=\"$export[$j]\" CHECKED></td><td bgcolor=\"00CC33\" >New</td>";
                       		$cle[6]= enlevedoublebarre($cle[6]);
                       		echo "<td>".$cle[8]."</td><td>".$cle[9]."</td>";
                       		for ($i=0; $i < 8; $i++) { echo "<td>".$cle[$i]."</td>"; }
                       }
           	}
       	}
    	echo"</tr></table><INPUT TYPE=\"hidden\" name=\"ajout\" value=\"7\">";
    	echo"<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$nombre1\">";
    	if ($nouv) {
		echo gettext("Attention, les cl&#233s d&#233j&#224 existantes sont ignor&#233es !!")." ( $exist )<br>";
    		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Pret pour l'importation des cl&#233s nouvelles!")."\"></FORM>";
		
		if ($testniveau<3){ echo "<script language=\"javascript\">document.ajoute.submit()</script>";}
    	} else { echo gettext("Pas de cl&#233s nouvelles !!")."<br>"; }

	break;


	//confirmation de l'ajout des cles (obsolete)
	case "7":
	$test=$_POST['test'];
	echo "<table border=1><tr><td>".gettext("Etat")."</td><td>".gettext("Intitule")."</td><td>".gettext("Valeur")."</td><td>".gettext("Antidote")."</td><td>".gettext("Genre")."</td><td>".gettext("OS")."</td><td>".gettext("Type")."</td><td>".gettext("Chemin")."</td><td>".gettext("Commentaires")."</td><td>".gettext("Categorie")."</td></tr>";
	$nb=$_POST['nombre'];
        for ($j=0; $j < $nb; $j++) {
        	$cle[$j]=$_POST['test'.$j];
                
		if ($cle[$j]) {
                	$cleok=preg_split("/--/",$cle[$j]);
                    	connexion();
                    	if (($cleok[5]=="config") or (!$cleok[2])) {
                     		$cleok[2]=$cleok[1];
                     		$cleok[5]="config";
                     	} else {$cleok[5]="restrict";}
                     	
			$cleok[8]=strtolower($cleok[8]);
                     	$cleok[9]=strtolower($cleok[9]);
                     	$cleok[9]=preg_replace("/([\r\n])/", "", $cleok[9]);
                     	$cleok[8]=trim($cleok[8]);
                     	$cleok[9]=trim($cleok[9]);
                     	$cletrim=ajoutedoublebarre(($cle[6]));
                     	$query="SELECT cleID FROM corresp WHERE '$cletrim'=chemin;";
                     	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                      	$row=mysqli_fetch_array($resultat);
                      	if (!$row[0]) {
                     		//$insert = mysql_query($query);
                    		$query="INSERT INTO corresp (Intitule,valeur,antidote,genre,OS,type,chemin,comment,categorie,sscat) VALUES ('$cleok[0]','$cleok[1]','$cleok[2]','$cleok[3]','$cleok[4]','$cleok[5]','$cleok[6]','$cleok[7]','$cleok[8]','$cleok[9]');";
                    		$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    		//echo "<tr><td>$query  Fait</td></tr>";
                    		if ($cleok[5]=="restrict") {
                     			$query="SELECT cleID FROM corresp WHERE '$cleok[6]'=chemin;";
                     			$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                     			$row=mysqli_fetch_array($resultat);
                    			$query2="INSERT INTO modele( `etat`, `cle`, `mod` ) VALUES ('0','$row[0]','norestrict');";
                     			$insert2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
                     		}
        			
				//insertion dans le modele  norestrict
                  		echo "<tr><td>".gettext("Fait")."</td>";
                    		for ($i=0; $i < 9; $i++) {
                        		$cleok[$i]=enlevedoublebarre($cleok[$i]);
                        		$cleok[$i]=enleveantislash($cleok[$i]);
                        		echo "<td>$cleok[$i]&nbsp;</td>";
                        	}
                    		echo "</tr>";
                    	}
                    	$testclecree++;
		} else { $testcleignoree++; }

	}
        echo "</table>";
        
	if ($testclecree) { echo "<br> $testclecree cl&#233; ont &#233;t&#233; cr&#233;&#233;es <br>"; }

	if ($testniveau<3) { 
		echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=gestion_cle.php \"></HEAD>".gettext("Commandes prises en compte !")."<br>";
	}

	break;


	//ajout d'une cle unique : insertion des donnees dans la base
	case "1":
	echo gettext("Ajout d'une cl&#233")."<br>";
	connexion();
	$intitule=$_POST['Intitule'];
	$valeur=$_POST['Valeur'];
	$genre=$_POST['genre'];
	$OSS=$_POST['OS'];
	$chemin=$_POST['chemin'];
	$comment=$_POST['comment'];
	$type=$_POST['type'];
	$anti=$_POST['antidote'];
	$categorie=$_POST['newcategorie'];
	if (!$categorie) {$categorie=$_POST['categorie'];}
	$sscat=$_POST['sscat'];
	$genre=$_POST['genre'];
	
	$OS="";
	for ($i=0; $i<count($OSS); $i++) {
		$OS=$OS.$OSS[$i];
		if ($i+1 != count($OSS))
			$OS=$OS.",";
	}

	//on verifie que la cle n'est pas deja dans la base
	$query="SELECT chemin FROM corresp WHERE chemin='$chemin';";
	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
	$row = mysqli_fetch_row($resultat);
    	if ($row[0]) { echo "cette cle existe d&#233;j&#224;<br>";} else {
    		//cle de configuration
    		if ($type=="config") {$antidote=$valeur;}

    		$categorie=strtolower($categorie);
    		$sscat=strtolower($sscat);

    		//insertion dans la table corresp
      		$query="INSERT INTO corresp (Intitule,valeur,genre,OS,chemin,comment,type,antidote,categorie,sscat) VALUES ('$intitule','$valeur','$genre','$OS','$chemin','$comment','$type','$anti','$categorie','$sscat');";
      		$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
      		echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_cle.php\"></HEAD>".gettext("Commandes prises en compte !");
      		echo gettext("Insertion effectu&#233e");
                if ($type="restrict") {   //insertion dans le modele generique  norestrict
                	$query="SELECT cleID FROM corresp WHERE '$chemin'=chemin;";
                     	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                     	$row=mysqli_fetch_array($resultat);
                     	$query2="INSERT INTO modele( `etat`, `cle`, `mod` ) VALUES ('0','$row[0]','norestrict');";
                     	$insert2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
               }

    	}
   	echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=affiche_cle.php \"></HEAD>".gettext("Commandes prises en compte !")."<br>";
	break;


	//importation d'un .reg
	case "8":
	echo gettext("Vous pouvez coller ici le contenu d'un .reg, si vous mettez un # (avant le chemin)<br>Il sera pris en compte pour marquer le d&#233but de l'intitul&#233 de la cl&#233 <br>Par exemple<br>Windows Registry Editor Version 5.00<br> #Page de recherche<br>[HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\Main]<br>\"Search Page\"=\"http://www.microsoft.com/isapi/redir.dll?prd=ie&ar=iesearch\"");
	echo "<br><FORM METHOD=POST ACTION=\"ajout_cle.php\"><TEXTAREA ROWS=\"30\" COLS=\"50\" name=\"vrac\" ></textArea>";
	echo "<INPUT TYPE=\"hidden\" name=\"ajout\" value=\"9\"><INPUT TYPE=\"submit\"></FORM>";
	break;


	//premiere analyse du point reg
	case "9":
     	$brut=$_POST['vrac'];
     	$retour=$_POST['retour'];
    	echo gettext("Premi&#232;re analyse du .reg &#224; importer")."<br><form action=\"ajout_cle.php\" name=\"reg\" method=\"post\">";
    	$brutout= enleveantislash($brut);
    	//echo "Detection de l'OS";
    	$brut=$brutout;
   	 
	$list= preg_split ("/\r\n/", $brutout, 15);
    	//echo $list[0];
    	$OS="";
      	if ($list[0] == "Windows Registry Editor Version 5.00")  {  $OS="XP";}
      	if ($list[0] == "REGEDIT4") {  $OS="98"; }

    	for ($i=1;$i<15;$i++) { if (Ord($list[$i]) == 35) { $Intitule = substr($list[$i],1); break; } }

    	for ($i=1;$i<15;$i++) { if (Ord($list[$i]) == 91) { $branche = $list[$i]; $j=$i+1; next;}}

    	$branche = enlevedoublebarre($branche);
    	list($cle,$valeur)=preg_split("/=/", $list[$j], 2);
    	$cle = enleveantislash($cle);
    	$cle = enlevequotes($cle);
    	$branche= enlevecrochets($branche);
    	$branchefin= $branche."\\".$cle;
    	$query="Select chemin from corresp where chemin='$branchefin'";
    	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
    	//la requete ne retourne pas des resultats : on peut creer la cle
    	$num=mysqli_num_rows($resultat);
    	if (!$num) {
    		echo "<table border = 1 ><tr><td>".gettext("Intitul&#233 de la cl&#233 ? A compl&#233ter si n&#233c&#233ssaire:")."</td><td><input type=\"text\" name=\"Intitule\" value=\"$Intitule\" size=\"100\" /> </td></tr>";
    		echo "<tr><td>".gettext("OS d&#233tect&#233")." :</td><td><select name=\"OS[]\" multiple size=\"1\">";


		echo "<option value=\"TOUS\" ";
		if ($OS=="TOUS") { echo "SELECTED"; }
		echo ">TOUS</option>";

		echo "<option value=\"Win9x\" ";
		if ($OS=="Win9x") { echo "SELECTED"; }
		echo ">Type Windows 9X</option>";

		echo "<option value=\"2000\" ";
		if ($OS=="2000") { echo "SELECTED"; }
		echo ">2000</option>";

		echo "<option value=\"XP\" ";
		if ($OS=="XP") { echo "SELECTED"; }
		echo ">XP</option>";

		echo "<option value=\"Vista\" ";
		if ($OS=="Vista") { echo "SELECTED"; }
		echo ">Vista</option>";

		echo "<option value=\"Seven\" ";
		if ($OS=="Seven") { echo "SELECTED"; }
		echo ">Seven</option>";

    		echo "</select></td></tr>";

		//analyse de la cle en fonction de la nature de la valeur
		//type reg_sz
    		if (Ord($valeur) == 34) {
    			$valeur = enlevequotes($valeur);
    			$genre="REG_SZ";
    			$type="config";
		}

		//type reg_dword
    		if (strpos($valeur,"word:")== 1 ) {
    			$genre="REG_DWORD";
    			$valeur= substr($valeur,6);
    			$valeur=DecHex($valeur);
    			$type="restrict";
    		}

    		if (strpos($valeur,"ex:")== 1 ) {
    			$genre="REG_DWORD";
    			$valeur= substr($valeur,4);
    			$valeur=hexdec($valeur);
    			$type="restrict";
    		}

      		//definition de la categorie  (affichage des categories existantes)
      		echo "<tr><td>".gettext("Cat&#233gorie")."</td><td><select name=\"categorie\" size=\"1\">";
      		$query1="Select DISTINCT categorie from corresp group by categorie;";
      		$resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
      		
		while ($row1=mysqli_fetch_row($resultat1)) { 
			if ($row1[0]){echo"<option value=\"$row1[0]\">$row1[0]</option>";} }

     			//affichage des sous-categories
     			$query2="Select DISTINCT sscat from corresp group by sscat;";
     			$resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
     			echo "</select></td></tr><tr><td>".gettext("Sous-Categorie")."</td><td><select name=\"sscat\" size=\"1\"><option ></option> ";
			
     			while ($row2=mysqli_fetch_row($resultat2)) {
				if ($row2[0]){echo"<option value=\"$row2[0]\" >$row2[0]</option>"; } 
			}

			//affichage des autres informations a remplir
      		echo "</select></td></tr><td>".gettext("Genre de la cl&#233 ?")." </td><td><select name=\"genre\" size=\"1\"><option> $genre </option><option>REG_SZ</option><option>REG_DWORD</option><option>REG_BINARY</option><option>REG_EXPAND_SZ</option></select></td>";
			echo "</tr><tr><td>".gettext("Valeur de la cl&#233 ( &#224 mettre en d&#233cimal)")." </td><td><input type=\"text\" name=\"Valeur\" value=\"$valeur\" size=\"100\" /></td></tr>";
			echo "<tr><td>".gettext("Antidote")."</td><td>Valide si cl&#233 de restriction: SUPPR pour supprimer la cl&#233<br/><input type=\"text\" name=\"antidote\" value=\"$valeur\" size=\"20\" /></td></tr>";
          		echo "<tr><td>".gettext("Type de la cl&#233 : restriction ou configuration ?")."</td><td>";
			echo "<select name=\"type\" size=\"1\"><option>$type</option><option>config</option><option>restrict</option></select>";
			echo "<tr><td>".gettext("Commentaires ?")."</td><td><textarea name=\"comment\" rows=\"4\" cols=\"60\"></textarea></td></tr></table>";
          		echo "<input type=\"hidden\" name=\"ajout\" value=\"1\" /><br><br>";
			echo "<input type=\"submit\" name=\"Submit\" value=\"Go\" />";
          		echo "</form>";

    		} else { //la cle existe deja
    			echo gettext("Cette cl&#233 existe d&#233j&#224");
		}
		break;


	
	//preparation de l'ajout d'une cle unique
	default:

    	echo gettext("Compl&#232;tez attentivement les champs suivants");
    	echo "<FORM METHOD=POST ACTION=\"ajout_cle.php\"><table border=\"1\"><tr><td>".gettext("Cat&#233;gorie")."</td><td>".gettext("Nouvelle:");
	echo "<input name=\"newcategorie\" type=\"text\" size=\"50\" > ".gettext("ou")." <select name=\"categorie\" size=\"1\" >";
    	//affichage des categories
    	$query1="Select DISTINCT categorie from corresp group by categorie;";
    	$resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
    	while ($row1=mysqli_fetch_row($resultat1)) {if ($row1[0]){ echo"<option value=\"$row1[0]\"  >$row1[0]</option>";}}
    	echo "</select></td></tr>";

	//affichage des autres infos
    	echo "<td>".gettext("Intitul&#233; de la cl&#233;")."</td>";
	echo "<td><INPUT TYPE=\"text\" NAME=\"Intitule\" size=\"100\"></td></tr>";
	echo "<tr><td>".gettext("Valeur par d&#233;faut")."</td><td> <INPUT TYPE=\"text\" NAME=\"Valeur\" size=\"100\"></td></tr>";
     	echo "<tr><td>".gettext("Antidote");
	echo "</td><td>Valide si cl&#233 de restriction: SUPPR pour supprimer la cl&#233<br/><input type=\"text\" name=\"antidote\" value=\"SUPPR\" size=\"20\" /></td>";
    	echo "<tr><td>".gettext("Genre de la cl&#233")."</td>";
	echo "<td><SELECT NAME=\"genre\"><OPTION value=\"REG_DWORD\">REG_DWORD<OPTION value=\"REG_BINARY\">REG_BINARY<OPTION value=\"REG_SZ\">REG_SZ <OPTION value=\"REG_EXPAND_SZ\">REG_EXPAND_SZ </SELECT></td></tr>";
    	echo "<tr><td>".gettext("OS concern&#233")."</td>";
	echo "<td> <SELECT NAME=\"OS[]\" multiple><OPTION value=\"TOUS\">".gettext("Tous OS")."<OPTION value=\"Win9x\">Win9x<OPTION value=\"2000\">2000<OPTION value=\"XP\">XP<OPTION value=\"Vista\">Vista<OPTION value=\"Seven\">Seven</SELECT></td></tr>";
	echo "<tr><td>".gettext("Chemin")."</td><td> <INPUT TYPE=\"text\" NAME=\"chemin\" size=\"120\"></td>";
    	echo "</tr><td>".gettext("Commentaires eventuels")."</td><td><INPUT TYPE=\"text\" NAME=\"comment\" size=\"100\" ></td></tr>";
    	echo "<tr><td>".gettext("Type de la cl&#233")."</td><td><SELECT NAME=\"type\">";
	echo "<OPTION value=\"config\">".gettext("Cl&#233 de configuration")."<OPTION value=\"restrict\">".gettext("Cl&#233 de restriction")."</SELECT></td></tr>";
    	echo "</table>";
    	echo "<INPUT TYPE=\"hidden\" name=\"ajout\" value=\"1\">";
    	echo "<INPUT TYPE=\"submit\" value=\"".gettext("OK, je suis s&#251;r de moi !")."\"><br><br></FORM><br>";
	echo gettext("Attention : Une cl&#233 de restriction sera automatiquement ajout&#233e au groupe de cl&#233 no restrict")."<br>";
}

((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
retour();

include("pdp.inc.php");
?>
