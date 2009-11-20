<?php


   /**
   
   * Permet configurer un onduleur esclave
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Rassemble les fonctions pour ups 
   */

   /**

   * @Repertoire: ups
   * file: ups.commun.php

  */	



/**
* Parser
* @Parametres
* @Return
*/

function debutElement0($parser, $name, $attrs) {
    global $filiation,$lselect,$pmarque;
    array_push($filiation,$name);
    if ($name=="MARQUE" and $pmarque=='') {
    if ($attrs['NOM']!='') {
      $marque=$attrs['NOM'];
//      echo "[<a href=\"$lien?pmarque=$marque\">$marque</A>]<br>\r\n";
      array_push($lselect,$marque);
      }
    }
}

/**
* Parser
* @Parametres
* @Return
*/

function characterData0($parser, $data) {
}


/**
* Parser
* @Parametres
* @Return
*/

function finElement($parser, $name) {
    global $filiation;
    array_pop($filiation);
}

/**
* Parser
* @Parametres 
* @Return 
*/

Function debutElement1($parser, $name, $attrs) {
    global $marqueOk,$version,$versionOk,$filiation,$lselect,$pmarque,$pversion;
    array_push($filiation,$name);
    if ($name=="MARQUE") {
       if ($attrs['NOM']==$pmarque){$marqueOk=true;}else{$marqueOk=false;};
    }
    if ($name=="VERSION" and $marqueOk) {
    if ($attrs['VER']!='')
      {$version=$attrs['VER'];
//       echo "[$pmarque] <a href=\"$lien?pmarque=$pmarque&pversion=$version\">$version</A>";

       $versionOk=true;
       $version=$attrs['VER'];
       if ($pmarque!='' and $pversion==''){ array_push($lselect,$version);}
       }
       else
       { $versionOk=false; }
    }
}

/**
* parser
* @Parametres 
* @Return 
*/


function characterData1($parser, $data) {
    global $marqueOk,$filiation;
    end($filiation);
    $element=current($filiation);
//    if ($element=="DRIVER" and $marqueOk)
//      {print " (driver : $data)<br>\r\n";
//      }
}


/**
* parser
* @Parametres $parser $name $attrs
* @Return 
*/

function debutElement2($parser, $name, $attrs) {
    global $pversion,$versionOk,$pmarque,$marqueOk,$filiation;
    array_push($filiation,$name);
    if ($name=="MARQUE") {
       if ($attrs['NOM']==$pmarque){$marqueOk=true;}else{$marqueOk=false;};
    }
    if ($name=="VERSION") {
    if ($attrs['VER']==$pversion and $marqueOk)
      {
       $versionOk=true;
//       echo "[$pmarque] $pversion<br>\r\n";
	}
       else
       { $versionOk=false; }
    }
}

/**
* Parse a partir de parser les data
* @Parametres $parser $data
* @Return 
*/

function characterData2($parser, $data) {
    global $versionOk,$filiation,$lselect,$pmarque,$pversion,$pdriver,$pport,$ptype;
    end($filiation);
    $element=current($filiation);
    if ($element=="DRIVER" and $versionOk){$pdriver=$data;}
    if ($element=="TYPE" and $versionOk) {$ptype=$data;} 
    if ($element=="CABLE" and $versionOk){
//      echo "<a href=\"$lien?pmarque=$pmarque&pversion=$pversion&pdriver=$pdriver&pcable=$data\">$data</A><br>\r\n";
      array_push($lselect,$data);
      }
}

/**
* Genere un etat sous forme de tableau de l'onduleur
* @Parametres $ups_location nom de l'onduleur
* @Return Un tableau HTML
*/

function affichage_ups($ups_location) { // Genere un etat sous forme de tableau de l'onduleur

//    $ups_location = "myups@127.0.0.1";
   $text .= "<br><br><CENTER>";
   if(file_exists("/etc/nut/ipmaster")) {
	$ip=exec("cat /etc/nut/ipmaster"); 
	$text .= gettext("Connected on ups of $ip");
	$text .= "<br><br>";
   }
	
   if(file_exists("/etc/nut/ipslave")) {
	$ip=exec("cat /etc/nut/ipslave"); 
	$text .= gettext("UPS master for $ip");
	$text .= "<br><br>";
   }
   $text .= "<TABLE border=\"1\" width=\"60%\" bgcolor=\"#50A0A0\">";
   $text .= "<TR style='height: 30'><TD align=center><b>Model</b></TD><TD align=center><b>Status</b></TD><TD align=center><b>Battery</b></TD><TD align=center><b>Input</b></TD><TD align=center><b>Output</b></TD><TD align=center><b>Load</b></TD></TR>";
   $text .= "<TR style='height:30'>";


   $ups_test = exec("upsc $ups_location");
   if ($ups_test=="") {
        $text .= "<TD bgcolor=\"red\" colspan=\"6\" align=\"center\">";
        $text .= "NO UPS DETECTED";
        $text .= "</TD>";
   } else {
        $text .= "<TD>";
        $text .= exec ("upsc $ups_location ups.mfr");
        $text .= " - ";
        $text .= exec ("upsc $ups_location ups.model");

        $text .= "</TD>";
        $ups_status = exec("upsc $ups_location ups.status");

        if (preg_match("/OL/i","$ups_status")) {
                $text .= "<TD bgcolor=\"#00FF00\" align=center>";
                $text .= "ONLINE";
        } elseif (preg_match("/OB/i","$ups_status")) {
                $text .= "<TD bgcolor=\"red\" align=center>";
                $text .= "ON BATTERY";
        } else {
                $text .= "<TD bgcolor=\"red\" align=center>";
        }

        $text .= "</TD>";
        $ups_battery = exec("upsc $ups_location battery.charge");
        if ($ups_battery > 30) {
                $text .= "<TD bgcolor=\"#00FF00\" align=center>";
        } else {
                $text .= "<TD bgcolor=\"red\" align=center>";
        }
        $text .= "$ups_battery";
        $text .= " %</TD><TD bgcolor=\"#00FF00\" align=center>";
        $text .= exec("upsc $ups_location input.voltage");

        $text .= " VAC</TD><TD bgcolor=\"#00FF00\" align=center>";
        $text .= exec("upsc $ups_location output.voltage");

        $text .= " VAC</TD><TD bgcolor=\"#00FF00\" align=center>";
        $text .= exec("upsc $ups_location ups.load");
        $text .= " %</TD></TR>";
   }
$text .= "</TABLE>";
return $text;
}

