<?php


   /**
   * Fonctions utiles
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   * Fonctions Interface Homme/Machine
   
   * @Auteurs Equipe Tice academie de Caen
   * @Auteurs Philippe chadefaux 
   * @Auteurs Jean Gourdin
   
   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: fonc_outils.inc.php
   * @Repertoire: includes/ 
   */  
  
  
/**

* Fonctions qui retourne vrai si l'inventaire (ocs) est actif
	
* @Parametres
* @Return 1 ou 0
   
*/

function inventaire_actif() {
	include("config.inc.php");
	return $inventaire;
}	



   /**

   * Fonctions qui Ping une machine Return 1 si Ok 0 pas de ping
	
   * @Parametres Adresse IP de la machine a pinguer

   * @Return  1 si le ping repond - 0 si pas de reponse
   
   */
/*
function fping($ip) { // Ping une machine Return 1 si Ok 0 pas de ping
	return exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'");
}
*/


   /**

   * Fonctions qui retourne l'adresse IP d'une machine en fonction de son nom ou 0 si pas d'IP
	
   * @Parametres  Nom de la machine 

   * @Return  L'adresse IP
	
  */
  
function avoir_ip($mpenc) { // Retourne l'adresse IP d'une machine en fonction de son nom ou 0 si pas d'IP
                 
	$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
        if ($mp_curr[0]["ipHostNumber"]) {
                $iphost=$mp_curr[0]["ipHostNumber"];
		return $iphost;
	} else {
		return 0;
	}	
}	



   /**

   * Fonctions qui retourne le nom d'une machine en fonction de son  adresse IP
	
   * @Parametres Adresse IP  de la machine 

   * @Return Le nom de la machine
	
  */
  
function avoir_nom($ipHost) { // Retourne le nom d'une machine a partir de l'adresse IP ou 0 si pas
                 
	$mp_curr=search_machines("(&(ipHostNumber=$ipHost)(objectClass=ipHost))","computers");
        if ($mp_curr[0]["cn"]) {
                $mpenc=$mp_curr[0]["cn"];
		return $mpenc;
	} else {
		return 0;
	}	
}	



   /**

   * Fonctions qui retourne si une machine a une demande de maintenance et le type 
	
   * @Parametres  Nom de la machine 

   * @Return  Le type de la demande de maintenance  de la machine
	
  */
  
function testMaintenance($mpenc) { // Retourne si une machine a une demande de maintenance et le type
	$dbnameinvent="ocsweb";
        include("dbconfig.inc.php");
	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
        $query="select * from repairs where (STATUT='2' or STATUT='0') and NAME='$mpenc'";
        $result = mysql_query($query,$authlink_invent);
        $ligne=mysql_num_rows($result);
	if ($ligne > 0) {
                while ($row = mysql_fetch_array($result)) {
                        return $row["PRIORITE"];
		}
	}
}	



   /**

   * Fonctions qui retourne le login de la connexion en cours ou 0 
	
   * @Parametres  Nom de la machine 

   * @Return Le login de la connexion en cours sur la machine ou 0 si pas de connexion
	
  */
  
function connexion_smb($mpenc) { //Retourne le login de la connexion en cours ou 0
	$ip=avoir_ip($mpenc);
	$connect_smb = exec("smbstatus | grep '($ip)' | cut -d' ' -f5");
	if ($connect_smb!="") { return $connect_smb; } 
}



   /**

   Fonctions qui retourne la date du dernier inventaire 
	
   * @Parametres Nom de la machine 

   * @Return Date du dernier inventaire 
	
  */
  
function der_inventaire($nom_machine) { // retourne la date du dernier inventaire a partir de hardware
        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select OSNAME,WORKGROUP,PROCESSORS,MEMORY,IPADDR,LASTDATE from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour = $res["OSNAME"]." WG : ".$res["WORKGROUP"]." P : ".$res["PROCESSORS"]." Mem : ".$res["MEMORY"]." DI : ";
	        		if ($res["LASTDATE"]) {
		        		$retour .= date('d M Y',strtotime($res["LOGDATE"]));
				}
			}
		} else {
			$retour=0;
		}
		
		return $retour;
	} else { // Pas d'inventaire a ce nom
		return 0;
	}	
}



   /**

   * Fonctions qui retourne l'ID de $nom_machine ou 0 a partir de la table hardware 
	
   * @Parametres Nom de la machine 

   * @Return ID de la machine pour ocs
	
  */



/*  
function avoir_systemid($nom_machine) { // retourne l'ID de $nom_machine ou 0 a partir de la table hardware

        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select ID from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour=$res["ID"];
			}
		} else {
			$retour=0;
		}
		
		return $retour;
	} else { // Pas d'inventaire a ce nom
		return 0;
	}	
}
*/


   /**

   * Fonctions qui retourne l'os de la machine 
	
   * @Parametres Nom de la machine 

   * @Return  Le type de la machine
	
  */
  
function type_os($nom_machine) { // retourne l'os de la machine
        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select OSNAME from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour = $res["OSNAME"];
				if (preg_match('/XP/i',$retour)) { // Pour le moment on a que 2 types d'icones 98 ou XP
					$retour="XP";
					return $retour;
				} elseif (preg_match('/2000/i',$retour)) {
					$retour="XP";
					return $retour;
				} elseif (preg_match('/2003/i',$retour)) {
					$retour="XP";
					return $retour;
				} elseif (preg_match('/ME/i',$retour)) {
					$retour="98";
					return $retour;
				} elseif (preg_match('/98/i',$retour)) {
				        $retour="98";
					return $retour;
				} elseif (preg_match('/95/i',$retour)) {
				         $retour="98";
					 return $retour;
				} elseif (preg_match('/Linux/i',$retour)) {
				         $retour="Linux";
					 return $retour;
				} else return 0;
				
			}
		} else {
			return 0;
		}
		
	} else { // Pas d'inventaire a ce nom
		return 0;
	}	
}



   /**

   * Fonctions qui supprime une machine d'un parc
	
   * @Parametres  Nom du parc - Nom de la machine 

   * @Return:
	
  */
  
function move_computer_parc($parc,$computer) { // Supprime une machine d'un parc
	 // Suppression des machines dans le parc
	include ("config.inc.php"); 
	$cDn = "cn=".$computer.",".$computersRdn.",".$ldap_base_dn;
	$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
	exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
        exec ("/usr/share/se3/sbin/printers_group.pl");

}
			  
   /**
   * Fonction supprime un parc si celui-ci est vide
	
   * @Parametres  Nom du parc
   * @Return
	
  */
  
function move_parc($parc) { 
	include ("config.inc.php"); 
	$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
        exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");
	exec ("/usr/share/se3/sbin/printers_group.pl");
}	


   /**

   * Fonctions qui teste si cups tourne
	
   * @Parametres

   * @Return 1 si cups tourne - 0 si tourne pas
  */
  
function test_cups() { //test si cups tourne
	$status_cups=exec("/usr/bin/lpstat -r");
        if ($status_cups=="scheduler is running") {
		return 1;
        //	$icone_cups="enabled.png";
        } else {	
		return 0;
	//	$icone_cups="disabled.png";
	}								   
}


   /**

   * Fonctions qui demarre cups
	
   * @Parametres 
   
   * @Return 
   */
  

function start_cups() { //demarre ou stop cups
	if (test_cups()==0) {
		exec ("sudo /etc/init.d/cupsys start");
	} else {
		exec ("sudo /etc/init.d/cupsys stop");
	}
}

   /**

   * Fonctions qui supprime une imprimante d'un parc (sans la supprimer de l'annuaire)
	
   * @Parametres  parc : Nom du parc - printer : Nom de l'imprimante

   * @Return
   
  */
  
function move_printer_parc($parc,$printer) { // Sort une imprimante $printer du parc $parc
	if ($parc !="" && $printer != "") {
		exec ("/usr/share/se3/sbin/printerDelPark.pl $printer $parc",$AllOutPutValue,$ReturnValue);
	}
}				      

   /**

   * Fonctions qui supprime une imprimante definitivement
	
   * @Parametres	printer : Nom de l'imprimante

   * @Return
   
  */

function move_printer($printer) { // Supprime une imprimante definitivement
     exec ("/usr/share/se3/sbin/printerDel.pl $printer",$AllOutPutValue,$ReturnValue);
}


   /**

   * Fonctions qui stop ou start une imprimante 
	
   * @Parametres  printer : Nom de l'imprimante - status : etat de l'imprimante

   * @Return
   
  */

function stop_start_printer($printer,$status) { //Stop ou start une imprimante
	if (isset($printer)){
      		exec ("/usr/bin/$status $printer");
	}	
}						


/**
* Affiche la date dans le format j/m/a

* @Parametres la date

* @Return la date au format j/m/a
*/

function affiche_date($date) {
list($a,$m,$j)=preg_split("/-/",$date);
return "$j/$m/$a";
}

/**
* Retourne une liste sous forme d'un tableau 

* @Parametres $liste

* @Return la tableau
*/


function liste_tab($liste) {
$t= preg_split("/\|/",$liste);
for ($i=0; $i< count($t) ; $i=$i+2) {
 $cle=$t[$i];
 $val=$t[$i+1];
 $tab[$cle]=$val; 
 }
return $tab;
}

/**
* Retourne la classe de l'eleve a partir de son uid

* @Parametres $login

* @Return la classe 
*/

function classe_eleve($login) {
list($user, $groups)=people_get_variables($login, true);
$nb_groupes= count($groups);
for ($g=0; $g< $nb_groupes; $g++) {
  if  (preg_match("/^Classe/", $groups[$g]["cn"] ) ) {
   $classe =  $groups[$g]["cn"] ;
   return $classe;
   break;
  }
}
if(isset($classe)) {return $classe;}
}


/**
* a partir du tab d'uid fournit un tableau associatif uid-eleve => classe
* @Parametres $tab tableau

* @Return tabeau uid eleve => classe 
*/

function classe_eleves($tab) {
$nb = sizeof($tab);
$tab_eleves_classe= array();
for ($p=0; $p < $nb; $p++) {
  $uid=$tab[$p];
    $tab_eleves_classe[$uid] = classe_eleve($uid);
    }
return $tab_eleves_classe;
}
    
/**

* fournit la classe, le fullname et le sexe d'un eleve a partir de son uid (tableau)

* @Parametres $login de l'eleve

* @Return Retourne la classe, le fullname et le sexe

*/
function params_eleve($login) {
list($user, $groups)=people_get_variables($login, true);
$nb_groupes= count($groups);
// oblige de faire une boucle parmi tous les groupes !!
for ($g=0; $g< $nb_groupes; $g++) {
  if  (preg_match("/^Classe/", $groups[$g]["cn"] ) ) {
   $classe =  $groups[$g]["cn"] ;
   break;
  }
 }
return array('classe'=>$classe, 'sexe'=>$user["sexe"], 'nom'=>$user["fullname"]) ;
}

/**
 * Retourne un select pour choisir la date

 * @Parametres date et param
 * @Return un select au sens HTML

*/

function choix_date($date,$param) {
  $tab_mois =array(9=>gettext("Septembre"),10=>gettext("Octobre"),11=>gettext("Novembre"),12=>gettext("D&#233;cembre"),1=>gettext("Janvier"),2=>gettext("F&#233;vrier"),3=>gettext("Mars"),4=>gettext("Avril"),5=>gettext("Mai"),6=>gettext("Juin"),7=>gettext("Juillet"),8=>gettext("Ao&#251;t"));
  list($an,$mois,$jour)=preg_split("/-/",$date);   
  echo "<select name=\"jour_$param\">";
  //   <option value=\"jour\"> jour</option>";
       for ($i=1; $i<=31;$i++) {
        $ii= ($i<10 ?"0$i":$i);
        echo "<option value=\"$ii\" ".($ii==$jour?"selected":"")."> $ii</option>";
    }
  echo "</select>";
  echo "<select name=\"mois_$param\"> ";
  //   <option value=\"mois\"> mois</option>";
       for ($i=1; $i<=12;$i++) {
        $ii= ($i<10 ?"0$i":$i);
        echo "<option value=\"$ii\" ".($ii==$mois?"selected":"")."> $tab_mois[$i]</option>";
     }
   echo "</select>";       
   echo "<select name=\"an_$param\"> ";
   //    <option value=\"an\"> ann&#233;e</option>";
   echo "<option value=\"$an\" selected> $an</option>";
   $an_suivant=$an+1;
   echo "<option value=\"$an_suivant\" > $an_suivant</option>";  
   echo "</select>"; 
}

/**
 * Test si uid est prof
 * @Parametres uid
 * @Return 1 su oui 0 si non
*/

function est_prof($uid) {
$groupes=search_groups("memberUid=$uid");
$prof=0;
$n=count ($groupes);
if ($n >0)
  for ($i=0; $i < $n; $i++) 
   if ($groupes[$i]["cn"]=="Profs") {
     $prof=1; break;
   }
  return $prof;
}     
    
/**
 
* fournit la liste des classes d'un prof
* modif : inclut aussi les groupes auxquels le prof appartient
* prevoir un tableau  gid => cn
 
 * @Parametres login du prof
 * @return un tableau avec les classes
*/ 
function classes_prof($login) {
$classes = array();
list($user, $groups)=people_get_variables($login, true);
$nb_groupes= count($groups);
for ($g=0; $g< $nb_groupes; $g++) {
    if  (preg_match("/^Equipe/", $groups[$g]["cn"] ) ) 
	$classes[]= preg_replace("/^Equipe/", "Classe",  $groups[$g]["cn"]) ;
    elseif  (preg_match("/^Matiere/", $groups[$g]["cn"] ) )  continue;
    elseif  (preg_match("/^Cours/", $groups[$g]["cn"] ) )  continue;    
    elseif  (preg_match("/^Profs/", $groups[$g]["cn"] ) )  continue;    
    elseif  (preg_match("/^admins/", $groups[$g]["cn"] ) )  continue;    
    else $classes[] = $groups[$g]["cn"];
}
return $classes;
}

/**

* retourne une liste a partir d'un tableau

* @Parametres tab le tableau a transformer
* @Return une liste

*/

function tab_liste($tab) {
$liste="";
foreach ($tab as $cle => $val) {
 if ($val != "" and $val != "#")
  $liste .= $cle.'|'.$val.'|';
}
$liste=preg_replace("/|$/","",$liste);
return $liste;
}


/**
* Fonctions: Test la presence d'une entree dans la table params et en retourne la valeur

* @Parametres $dhcp_vlan_valeur : Contenu de dhcp_vlan
* @Return -  
* Ex : entree_table_param_exist("savbandactiv","0","5","sauvegarde sur bande");
*/
	
function entree_table_param_exist($nom,$valeur,$cat,$comment) {
	// include ("config.inc.php");
	// si la variable $nom n'est pas definie on cree l'entree dans la base sql
	if ($$nom == "") {
	        $resultat=mysql_query("INSERT into params set id='NULL', name='$nom', value='$valeur', srv_id='0',descr='$comment',cat='$cat'");
		return 0;
	}	
}

/**
* a partir d'un uid d'élève fournit le nom inversé de son répertoire classe 
* en cas de login prenom.nom, renvoie nom.prenom, sinon renvoie le login
* @Parametres $uid

* @Return $rep 
*/

function inverse_login($login) {
    $tab=preg_split('/\./', $login);
    if (count($tab)==2) {
        $rep=$tab[1].".".$tab[0];
    }else{    
        $rep=$login;
    }
    return $rep;
}


?>
