<?php
   
   /**
  
   * Test les restictions sur les clients windows en fonction du nom et de la machine
   * @Version $Id: testreg.php 2949 2008-05-04 18:45:49Z plouf $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

    /**
    * @Repertoire: registre
    * file: testreg.php
    */


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "include.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

foreach($_GET as $key => $valeur)
        $$key = $valeur;


connexion();
if (test_bdd_registre()==false) {   
	exit; 
} else {
	if (test_zorn_tools()==false) {  
		exit; 
	} 
}

if (is_admin("computers_is_admin",$login)=="Y") {
    	
  	//aide 
  	$_SESSION["pageaide"]="Gestion_des_clients_windows";
	
	echo "<H1>".gettext("Simulation de cl&#233s de registre appliqu&#233es")."</H1>\n";
    	
	if (! isset($tstlogin)) {
        	echo "<FORM>\n";
        	echo "<TABLE BORDER=0>\n";
        	echo "<TR><TD>".gettext("Nom d'utilisateur")."</TD><TD><INPUT TYPE=text NAME=tstlogin></TD></TR>\n";
        	echo "<TR><TD>".gettext("Nom de l'ordinateur")."</TD><TD><INPUT TYPE=text NAME=tstnetbios></TD></TR>\n";
        	echo "</TABLE><INPUT TYPE=submit VALUE=\"".gettext("Lancer le test")."\"></FORM>\n";
    	} else {
        	// Affichage des groupes d'appartenance d'un utilisateur
          	$templates=array();
          	array_push($templates, trim($tstlogin));
          	array_push($templates, trim($tstnetbios));
          	list($user, $groups)=people_get_variables($tstlogin, true);
        	echo "<H3>".$user["fullname"]."</H3>\n";
          	if ($user["description"]) echo "<p>".$user["description"]."</p>";
        	if ( count($groups) ) {
            		echo "<U>".gettext("Membre des groupes")."</U> :<BR><UL>\n";
            		for ($loop=0; $loop < count ($groups) ; $loop++) {
            			if ($groups[$loop]["cn"]) {
            				$test=$groups[$loop]["cn"];
            				$query="select groupe from restrictions where groupe='$test' ;";
            				$resultat=mysql_query($query);
            				if (mysql_num_rows($resultat)) {
                				echo $groups[$loop]["cn"]."<BR>\n";
                				array_push($templates, $test);
            				} else {
             					echo $groups[$loop]["cn"]." ( ".gettext("pas de template d&#233fini pour ce groupe").") <BR>\n";
            				}
            			}
            		}
            		echo "</UL>\n";
        	}
        	// Affichage des parcs d'appartenance de la machine
        	$parcs=search_parcs ($tstnetbios);
        	if ( count($parcs) ) {
            		echo "<U>".gettext("La machine est dans les Parcs")."</U> :<BR><UL>\n";
            		for ($loop=0; $loop < count ($groups) ; $loop++) {
            			if ($parcs[$loop]["cn"]) {
            				$test=$parcs[$loop]["cn"];
            				$query="select groupe from restrictions where groupe='$test' ;";
            				$resultat=mysql_query($query);
            				if (mysql_num_rows($resultat)) {

                				echo $parcs[$loop]["cn"]."<BR>\n";
                				array_push($templates, $test);
            				} else {
             					echo $parcs[$loop]["cn"]." (".gettext("pas de template d&#233fini pour ce parc").") <BR>\n";
            				}
            			}
            		}
            		echo "</UL>\n";
        	}
    	}
}

if ($test) {
	echo"<h3>".gettext("R&#233sultat du test")."</h3>";
   	affichelistecat("testreg.php?tstlogin=$tstlogin&tstnetbios=$tstnetbios",$testniveau,$cat);

     	if (($cat) and !($cat=="tout")) {
    		$ajout=" and corresp.categorie = '$cat'";
    		if ($sscat) {$ajoutsscat=" AND corresp.sscat='$sscat';";
    		echo "<h3>".gettext("Sous-cat&#233gorie :")." $sscat</h3>";
     	} else {
		$ajoutsscat=""; 
	}
    	
	if (($testniveau==2) and !($sscat)) { 
		$ajoutpasaffiche=" and corresp.sscat= '' "; }

    	} else {
    		echo gettext("Choisissez une cat&#233gorie ci-dessus");
    	}
    	if ($cat=="tout") {
    		$ajout="";
    		if ($sscat) {$ajoutsscat="";}
    		$ajoutpasaffiche="";
    	}
        $query="Select restrictions.cleID,corresp.Intitule,corresp.type,restrictions.valeur from restrictions,corresp where restrictions.groupe='base' and corresp.CleID=restrictions.cleID ".$ajout.$ajoutsscat.$ajoutpasaffiche;
        $resultat = mysql_query($query);
	
        if (mysql_num_rows($resultat)) {
        	echo "<table border=\"1\"><tr><td><img src=\"/elements/images/system-help.png\" alt=\"".gettext("Aide")."\" title=\"".gettext("Aide")."\" width=\"16\" height=\"18\" border=\"0\" /></td><td>".gettext("Intitul&#233")."</td><td>".gettext("Valeur")."</td><td>".gettext("Template")."</td></tr>";
	
		while ($row=mysql_fetch_array($resultat)) {
               		$color="";
               		if ($row[2]=="restrict") { $color="#a5d6ff"; }
               		$testvaleur=$row[3];
               		$testemplate="base";
               		for ($i=0;$i<count($templates);$i++) {
              			$query2="SELECT valeur from restrictions WHERE cleID='$row[0]' AND groupe='$templates[$i]';";
               			$resultat2=mysql_query($query2);
                		if (mysql_num_rows($resultat2)) {
               				$row2=mysql_fetch_array($resultat2);
               				if ($row[2]=="restrict") { $color="#e0dfde"; }
               				$testvaleur=$row2[0];
               				$testemplates[$j]=$templates[$i];
               				$j++;
               			} else { 
					$testemplate[$i]="";  $j=0;
				}
                	}
                	echo "<tr><td><a href=\"aide_cle.php?cle=$row[0]\" target=\"_blank\" ><img src=\"/elements/images/system-help.png\" alt=\"".gettext("aide")."\" title=\"$row[1]\" width=\"16\" height=\"18\" border=\"0\" /></a>";
                        echo "</td><td>$row[1]</td><td bgcolor=\"$color\">$testvaleur</td><td>";
                	echo"<form action=\"affiche_restrictions.php\" method=\"get\"><select name=\"salles\">";
                        for ($i=0;$i<count($testemplates);$i++) {
               			if ($testemplates[$i]) {
                			echo"<option value=\"$testemplates[$i]\" >$testemplates[$i]</option> ";
              			}
              		}
              		echo"<option value=\"base\" >".gettext("base")."</option>";
                        echo"</select><input type=\"submit\" name=\"ok\" value=\"GO\" /></form></td></tr>";
                }
               	echo "</table>";
	} else {
        	echo gettext("Aucune entr&#233e trouv&#233e, pour utiliser cette fonctionnalit&#233 vous devez inscrire au moins le template 'base' dans le menu 'Attribution des cl&#233s'");
	}
}
	
include ("pdp.inc.php");

?>
