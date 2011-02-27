<?php


   /**
   * Fonctions pour la partie imprimante
  
   * @Version $Id printers.inc.php 2592 2007-11-21 16:26:45Z keyser $
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Patrice Andre <h.barca@free.fr> Carip-Academie de Lyon
   
   * @Note  

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: printers.inc.php
   * @Repertoire: includes/ 
   */  
  




require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


$ou_printers="Printers";
$printersRdn="ou=Printers";
$dn["printers"]= "$printersRdn,$ldap_base_dn";




/**

* renvoie le parc de la machine dont on a fourni l'adresse IP ou une chaine vide si la machine n'appartient a aucun parc
	
* @Parametres ip de la machine
* @Return nom duparc ou chaine vide si aucun
*/

function search_parc($ip){

  global $ldap_server, $ldap_port, $dn;
  global $error;
  $error="";
  $filter="cn=*";
  $trouver=FALSE;

  // LDAP attributs
 $members_attr = array ("member");

  $machine=search_computers("ipHostNumber=".$ip);

  if ($machine != "") {
   $ds = @ldap_connect ( $ldap_server, $ldap_port );
   if ( $ds ) {
    $r = @ldap_bind ( $ds ); // Bind anonyme
    if ($r) {
        $result=@ldap_search ($ds, $dn["parcs"], $filter,$members_attr);
//	echo "\$result : ". $result;
        if ($result) {
          $info = @ldap_get_entries( $ds, $result );
//	   echo "<br>";
//	   echo $info["count"]." entrees trouvees"; echo "<br>";
// $info["count"] renvoie le nombre de parc trouve
          if ($info["count"]>0){
  unset($tabparc);
  $tabparc=array();
//$fich=fopen("/tmp/liste_parcs_$ip.txt","w+");
          for ($i=0; $i < $info["count"]; $i++) {
             for ($loop=0; $loop < $info[$i]["member"]["count"]; $loop++) {
//               echo $info[$i]["member"][$loop];echo  "<br>"; echo $info[$i]["dn"];
//		 echo "<br>";
//		 echo $info[$i]["cn"][$loop];
//		 echo "<br>";
		if (preg_match ('/$machine[0]["cn"]/',$info[$i]["member"][$loop])) {
//fwrite($fich,$info[$i]["member"][$loop]."\n");
			$parc = explode(",",$info[$i]["dn"]);
			$parc = explode("=",$parc[0]);
//fwrite($fich,"\$parc[1]=".$parc[1]."\n");
			$tabparc[]=$parc[1];
//		echo "<br>";
//		echo "La machine est dans le parc :  ".$parc[1];
			$trouver=TRUE;
			break;
		}
	      }
	  }
//fclose($fich);
         }
          @ldap_free_result ( $result );
        }

    } else {
      $error = gettext("Echec du bind anonyme");
    }
    @ldap_close ( $ds );
  } else {
    $error = gettext("Erreur de connection au serveur LDAP");
  }
  }

//if (!$trouver) {$parc[1]=""; }
//return $parc[1];

//if (!$trouver) {return NULL;}else{return $parc;}
if (!$trouver) {return NULL;}else{return $tabparc;}

}


/**

* Retourne un ou a partir d'un dn

* @Parametres dn
* @Return ou retourne
*/

// Retourne un ou a partir d'un dn
function extract_ou ($dn) {
  $champ = preg_split ("/,/",$dn);
  $champ_ou = preg_split ("/=/",$champ[1],2);
  $champ_cn = preg_split ("/=/",$champ[0],2);
  if ($champ_ou[1]=="Printers") return $champ_cn[1];
}


/**

* Recherche une imprimante a partir du filtre souhaite

* @Parametres filtre
* @Return imprimantes
*/

function search_printers ($filter)
{
  return search_imprimantes($filter,"printers");
}



/**

* Recherche les imprimantes donnees par le filtre
* @Parametres filtre et branche de recherche
* @Return 
*/

function search_imprimantes ($filter,$branch) {
  global $ldap_server, $ldap_port, $dn;
  global $error;

  // LDAP attributs
  if ("$branch"=="printers")
    $ldap_printer_attr = array (
    "nprintHardwareQueueName",
    "printer-name",
    "printer-uri",   // uri de l'imprimante
    "printer-location",                        //Emplacement  de l'imprimante
    "printer-info",        // Description de l'imprimante
    "printer-more-info" // Mode d'impression
    );
  else
    $ldap_printer_attr = array (
    "printer-name"
    );

  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = @ldap_bind ( $ds ); // Bind anonyme
    if ($r) {
      $result = @ldap_list ( $ds, $dn[$branch], $filter, $ldap_printer_attr );
	if ($result) {
        $info = @ldap_get_entries ( $ds, $result );

// print_r(array_values($info));
        if ( $info["count"]) {
          for ($loop=0; $loop < $info["count"]; $loop++) {
            $printers[$loop]["printer-name"] = $info[$loop]["printer-name"][0];
            if ("$branch"=="printers") {
                $printers[$loop]["printer-uri"] = $info[$loop]["printer-uri"][0];
                $printers[$loop]["printer-location"] = $info[$loop]["printer-location"][0];
                $printers[$loop]["printer-info"] = utf8_decode($info[$loop]["printer-info"][0]);
                $printers[$loop]["printer-more-info"] = $info[$loop]["printer-more-info"][0];
		$printers[$loop]["nprinthardwarequeuename"] = $info[$loop]["nprinthardwarequeuename"][0];
            }
          }
        }
        @ldap_free_result ( $result );
      }
    }
    @ldap_close($ds);
  }
  return $printers;
}



/**

* Test l'adresse IP pour verifier si elle est correccte (incomplet)
* @Parametres adresse ip
* @Return true ou false
*/

function verif_ip ($ip) {         // MARCHE PAS
  $motif="^([0-9]{1,3}.){3}[0-9]$";
  if (preg_match("/$motif/",$ip)) return false;
  else return false;
}



/**

* Recherche les imprimantes membres d'un parc
* @Parametres 
* @Return
*/

function printers_members ($gof,$branch,$extract) {   // Recherche les imprimantes membres d'un parc
  global $ldap_server, $ldap_port, $dn;
  global $error;
  $error="";

  $i=0;
  // LDAP attributs
  $members_attr = array (
      "member"   // Membres du groupe Profs
  );
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = @ldap_bind ( $ds ); // Bind anonyme
    if ($r) {
        $result=@ldap_read ($ds, "cn=$gof,".$dn[$branch], "cn=*", $members_attr);
        if ($result) {
          $info = @ldap_get_entries( $ds, $result );
          if ($info["count"]==1) {
            $init=0;
             for ($loop=0; $loop < $info[0]["member"]["count"]; $loop++) {
                if ($extract==1)
		  {
		    if ( ($printer_ou=extract_ou($info[0]["member"][$loop]) ) != "" )
		      {
			$ret[$i]=$printer_ou;
			$i++;
		      }
		  }
		else $ret[$loop]=$info[0]["member"][$loop];
	     }
	  }

          @ldap_free_result ( $result );
        }

    } else {
      $error = gettext("Echec du bind anonyme");
    }
    @ldap_close ( $ds );
  } else {
    $error = gettext("Erreur de connection au serveur LDAP");
  }
  return $ret;
}
?>
