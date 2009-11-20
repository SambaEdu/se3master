<?php


   /**
   
   * Librairie de fonctions utilisees dans l'interface d'administration parcs
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs sandrine dangreville matice creteil aout 2005

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: fonc_parc.php

  */	



require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


//*****************connexion bdd*******************
  $authlink = @mysql_connect($dbhost,$dbuser,$dbpass);
        @mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");
     


/**

* Fonctions d'affichage de menu action dans parcs
* @Parametres
* @Return
*/


function affiche_action($parc)
{
echo "<script type=\"text/javascript\">
<!--
window.onload=montre;
function montre(id) {
var d = document.getElementById(id);
  for (var i = 1; i<=10; i++) {
    if (document.getElementById('smenu'+i)) {document.getElementById('smenu'+i).style.display='none';}
  }
if (d) {d.style.display='block';}
}
//--></script><div id=\"menu\">
  "; if ($parc) {
echo"<dl><dt onmouseover=\"javascript:montre('smenu0');\" onmouseout=\"javascript:montre('');\"><a href=\"action_parc.php\" title=\"Parc choisi\">$parc&nbsp;&nbsp;<img src=\"../elements/images/command.png\" alt=\"".gettext("Changer de parc")."\" title=\"".gettext("Changer de parc")."\" width=\"20\" height=\"20\" border=\"0\" /></a></dt></dl>\n";
    }
 else{

echo"  <dl><dt onmouseover=\"javascript:montre('smenu0');\" onmouseout=\"javascript:montre('');\"><a href=\"action_parc.php\" title=\"".gettext("Choisir un parc")."\">".gettext("Choisir un parc")."</a></dt>\n";
echo "<dd id=\"smenu0\" onmouseover=\"javascript:montre('smenu0');\" onmouseout=\"javascript:montre('');\">
      <ul class=ssmenu onmouseout=\"javascript:montre('');\">\n";
          $list_parcs=search_machines("objectclass=groupOfNames","parcs");
        if ( count($list_parcs)>0) {
            for ($loop=0; $loop < count($list_parcs); $loop++) {
           if ($acces_restreint)  {  if ((!this_parc_delegate($login,$list_parcs[$loop]["cn"],"manage")) and (!this_parc_delegate($login,$list_parcs[$loop]["cn"],"view"))) { continue; } }
 if ($list_parcs[$loop]["cn"]<>$parc) { echo"<li class=ssmenu onmouseout=\"javascript:montre('');\"><a href=\"action_parc.php?parc=".$list_parcs[$loop]["cn"]."\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".$list_parcs[$loop]["cn"]."</a></li>\n";}
        }   }
echo"</ul>
    </dd>
  </dl>\n";



  }

$testniveau=getintlevel();
if (($parc) and ($testniveau>3)) {
echo" <dl>
    <dt onmouseover=\"javascript:montre('smenu1');\">".gettext("Installations clientes")."</dt>

    <dd id=\"smenu1\" onmouseover=\"javascript:montre('smenu1');\" onmouseout=\"javascript:montre('');\">
      <ul class=ssmenu>
        <li class=ssmenu><a href=\"install_locale.php?parc=$parc&action=verif_1\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".gettext("I Verification des postes")."</a></li>
        <li class=ssmenu><a href=\"install_locale.php?parc=$parc&action=msi\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".gettext("II Pr&eacute;parer l'installation")."</a></li>
        <li class=ssmenu><a href=\"install_locale.php?parc=$parc&action=msi_3\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".gettext("III Executer l'installation")."</a></li>
        </ul>
    </dd>
  </dl>
";   }
//else { echo"<dl><dt onmouseover=\"javascript:montre('smenu1');\">Installations clientes</dt></dl>";}

//  else {
//  echo "<dl><dt onmouseover=\"javascript:montre('smenu2');\">G&eacute;rer les applications</dt></dl>";} // */
if ($parc) {
echo "  <dl>
    <dt onmouseover=\"javascript:montre('smenu3');\">Etat du parc</dt>
      <dd id=\"smenu3\" onmouseover=\"javascript:montre('smenu3');\" onmouseout=\"javascript:montre('');\">
      <ul class=ssmenu>

        <li class=ssmenu><a href=\"wolstop_station.php?parc=$parc&action=timing\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".gettext("Planification")."</a></li>
        <li class=ssmenu><a href=\"action_parc.php?parc=$parc&action=detail\"><img src=\"../elements/images/typebullet.png\" width=\"30\" height=\"11\" border=\"0\">".gettext("Etat - Controle")."</a></li>
      </ul>

      </dd>
  </dl>\n"; }
//  else  { echo "<dl><dt onmouseover=\"javascript:montre('smenu3');\">Etat du parc</dt></dl>";}
echo"  </div>
";
}


/**

* Fonctions d'affichage des appli
* @Parametres
* @Return
*/


function affiche_appli()
{
	echo "<table align=center><tr>
	<td class=menuheader width=\"130\" height=\"30\" align=\"center\"><a href=\"appli_client.php?action=new_private_appli\">".gettext("Ajouter")."</a></td>
	<td class=menuheader width=\"130\" height=\"30\" align=\"center\"><a href=\"appli_client.php?action=list_cat\">".gettext("Par cat&#233gorie")."</a></td>
	<td class=menuheader width=\"130\" height=\"30\" align=\"center\"><a href=\"appli_client.php?action=list_alpha\">".gettext("Par ordre alphab&#233tique")."</a></td>
	<td class=menuheader width=\"130\" height=\"30\" align=\"center\"><a href=\"appli_client.php?action=waiting\">".gettext("En attente")."</a></td>
	<!--<td class=menuheader width=\"130\" height=\"30\" align=\"center\"><a href=\"appli_client.php?action=validate_central\">".gettext("Mise a jour Serveur Central")."</a></td>-->
	</table>\n";

}


/**

* retourne l'ID de $nom_machine ou 0 a partir de la table hardware
* @Parametres
* @Return
*/

function avoir_systemid($nom_machine) { // retourne l'ID de $nom_machine ou 0 a partir de la table hardware
        session_start();
	$_SESSION["loggeduser"]="$login";
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


/**

* test si une machine cliente repond au ping
* @Parametres $ip adresse a pinguer
* @Return
*/


function fping($ip)
{
	return exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'");
}



/**

* test la connexion ssh sur une machine cliente
* @Parametres $ip adresse a tester
* @Return
*/


function ssh($ip)
{
	$fp=@fsockopen($ip,22,&$errno,&$errstr,5);
	if (!$fp) {
	   return 0;
	} else {  return 1; }
}



/**

* test si une machine cliente repond sur le port 3389
* @Parametres $ip adresse a tester
* @Return construit un fichier ts dans /tmp pour cette machine si c'est le cas
*/


function ts($ip)
{
	$fp=@fsockopen($ip,3389,&$errno,&$errstr,5);
	if (!$fp) {
		return 0;
	} else {
$ligne="screen mode id:i:2
desktopwidth:i:1400
desktopheight:i:1050
session bpp:i:16
winposstr:s:0,1,23,0,1379,619
full address:s:$ip
compression:i:1
keyboardhook:i:2
audiomode:i:0
redirectdrives:i:0
redirectprinters:i:1
redirectcomports:i:0
redirectsmartcards:i:1
displayconnectionbar:i:1
autoreconnection enabled:i:1
username:s:
domain:s:
alternate shell:s:
shell working directory:s:
disable wallpaper:i:1
disable full window drag:i:1
disable menu anims:i:1
disable themes:i:0
disable cursor setting:i:0
bitmapcachepersistenable:i:1";
$content_dir = '/tmp/';
$fichier_rdp = $content_dir . "$ip.rdp";
if (file_exists($fichier_rdp)) unlink($fichier_rdp);

$get= fopen ($fichier_rdp, "w+");
fputs($get,$ligne);
fclose($get);
$ts=aide(gettext("Attention, vous devez avoir un client xp/2000 ou un client terminal server pour acc&#233der &#224 la session distante du poste. <font color=#FF0000>Les utilisateurs seront temporairement d&#233connect&#233s pendant votre intervention !!</font>"),"<a href=tsvnc.php?machine=$ip&action=ts&file=$fichier_rdp><img src=\"../elements/images/monitorTS.gif\" alt=\"\" title=\"terminal server\" width=\"30\" height=\"30\" border=\"0\" /></a>");
return $ts;
}
}



/**

* test si une machine cliente repond sur le port 5900 vnc
* @Parametres $ip adresse a tester
* @Return construit un fichier vnc dans /tmp pour cette machine si c'est le cas
*/


function vnc($ip)
{
$fp=@fsockopen($ip,5900,&$errno,&$errstr,5);
if (!$fp) {
 //  echo "$errstr ($errno)<br />\n";
   return 0;
}
else
{
$ligne="[connection]
host=$ip
port=5900
proxyhost=
proxyport=5900
password=
[options]
use_encoding_0=1
use_encoding_1=1
use_encoding_2=1
use_encoding_3=0
use_encoding_4=1
use_encoding_5=1
use_encoding_6=1
use_encoding_7=1
use_encoding_8=1
use_encoding_9=1
use_encoding_10=0
use_encoding_11=0
use_encoding_12=0
use_encoding_13=0
use_encoding_14=0
use_encoding_15=0
use_encoding_16=1
preferred_encoding=5
restricted=0
viewonly=0
nostatus=0
nohotkeys=0
showtoolbar=1
AutoScaling=0
fullscreen=0
autoDetect=1
8bit=0
shared=1
swapmouse=0
belldeiconify=0
emulate3=1
emulate3timeout=100
emulate3fuzz=4
disableclipboard=0
localcursor=1
Scaling=0
scale_num=100
scale_den=100
cursorshape=1
noremotecursor=0
compresslevel=6
quality=6
ServerScale=1
EnableCache=0
QuickOption=1
UseDSMPlugin=0
UseProxy=0
DSMPlugin=";
$content_dir = '/tmp/';
$fichier_vnc = $content_dir . "$ip.vnc";
if (file_exists($fichier_vnc)) unlink($fichier_vnc);

$get= fopen ($fichier_vnc, "w+");
fputs($get,$ligne);
fclose($get);
$vnc=aide(gettext("Attention, vous devez avoir un client vnc pour acc&#233der au serveur vnc du poste choisi."),"<a href=tsvnc.php?machine=$ip&action=vnc&file=$fichier_vnc><img src=\"../elements/images/monitorVNC.gif\" alt=\"\" title=\"vnc\" width=\"30\" height=\"30\" border=\"0\" /></a>\n");
return $vnc;
}
}



/**

*  pour l'affichage des heures dans le menu planification des allumage/extinction postes
* @Parametres 
* @Return retourne un <option></option>
*/


function heure_deroulante($parcf,$jourf,$type_actionf)
{
	global $authlink;
	if ($parcf) {
		$resultf=mysql_query("select heure from actionse3 where action='$type_actionf' and parc='$parcf' and jour='$jourf';", $authlink) or die("Impossible d'effectuer la requete");

		if ($resultf) {
			$rowf=mysql_fetch_row($resultf);
			$heuref=$rowf[0];
		}           
	}

	$timef=explode(":",$heuref);
	//$heure_act=date("H");
	//$minute_act=date("m");
	for ($if=0;$if<24;$if++) {
		$selectedf="";
		for ($jf=0;$jf<4;$jf++) {
			$mf=$jf*15;
			$selectedf="";
			if  (($if==$timef[0]) and ($mf==$timef[1])) { $selectedf="SELECTED"; } //elseif ($heure_act==$i-1) { $selected="SELECTED"; }
			if ($mf<10) { echo "<option $selectedf >$if:0$mf</option>\n"; } else { echo "<option $selectedf>$if:$mf</option>\n"; }
			$selectedf="";
		}
	}
}


// test si une action d'allumage extinction est prevu sur un parc donne 
// un jour donne ( retourne checked pour un select)
function jour_check($parcf,$jourf,$type_actionf)
{
	global $authlink;
	if ($parcf) {
		$resultf=mysql_query("select heure from actionse3 where parc='$parcf' and jour='$jourf' and action='$type_actionf';", $authlink) or die("Impossible d'effectuer la requete");
		if ($resultf) {
			if (mysql_num_rows($resultf)>0) { return "checked"; }
		}
	}
}




/**

* affiche le tableau detaillant l'allumage des machines et la prise de controle
* @Parametres
* @Return 
*/


function detail_parc($parc)
{
	include "printers.inc.php";

	global $smbversion;
	echo "\n<br>\n<CENTER>\n";
	echo "<TABLE border=1 width=\"60%\">\n<tr class=menuheader style=\"height: 30\">\n";
	echo "<td align=\"center\"></td>\n";
	echo "<td align=\"center\">STATIONS CONCERNEES</td>\n";
	echo "<td align=\"center\">ETAT</td>\n";
	echo "<td align=\"center\">CONNEXIONS</td>\n";
	echo "<td align=\"center\">CONTROLE</td></tr>\n";

        $mp_all=gof_members($parc,"parcs",1);

	// Filtrage selon critere
        if ("$filtrecomp"=="") $mp=$mp_all;
        else {
                $lmloop=0;
                $mpcount=count($mp_all);
                for ($loop=0; $loop < count($mp_all); $loop++) {
                	$mach=$mp_all[$loop];
                   	if (preg_match(/$filtrecomp/,$mach)) $mp[$lmloop++]=$mach;
                }
        }

        if ( count($mp)>0) {
                 sort($mp);
                 for ($loop=0; $loop < count($mp); $loop++) {
                        $mpenc=urlencode($mp[$loop]);
                 	$mp_en_cours=urldecode($mpenc);
   			$mp_curr=search_machines("(&(cn=$mp_en_cours)(objectClass=ipHost))","computers");

       			// Test si on a une imprimante ou une machine
			$resultat=search_imprimantes("printer-name=$mp_en_cours","printers");
			$suisje_printer="0";
			for ($loopp=0; $loopp < count($resultat); $loopp++) {
				if ($mp_en_cours==$resultat[$loopp]['printer-name']) {
					$suisje_printer="1";
					continue;
				}
			}
			
			// On teste si la machine a des connexions actives
			// en fonction de la version de samba
			// On ne rentre dedans que si on est pas une imprimante

			if ($suisje_printer!="1") {
				if ("$smbversion"=="samba3") $smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $mp_en_cours ' |cut -d' ' -f2 |head -n1");
        			else $smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4}' |grep ' $mp_en_cours ' |cut -d' ' -f3 |head -n1");
        			if ($smbsess=="") {
					$etat_session="<img type=\"image\" src=\"../elements/images/disabled.png\">\n";
				} else {
        				if ("$smbversion"=="samba3") $login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$5\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
        				else $login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$4\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
        				$etat_session="<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$login est actuellement connect&#233; sur ce poste')\"><img type=\"image\" src=\"../elements/images/enabled.png\"></u>\n";
        			}
       	
				if ($mp_curr[0]["ipHostNumber"]) {
                  			$iphost=$mp_curr[0]["ipHostNumber"];
                  			$ping=fping($iphost);

					// On teste si la machine est en marche ou pas
                  			if ($ping) {
                  				$etat=aide(gettext("La machine est actuellement allum&#233e, cliquez pour l eteindre"),"<a href=\"action_machine.php?machine=$mp_en_cours&action=shutdown&parc=$parc&retour=action_parc\"  onclick=\"if (window.confirm('Etes-vous sur de vouloir &#233;teindre la machine $mp_en_cours ?'))
                                	   	{return true;}
                				else {return false;}\"/><img type=\"image\" border=\"0\" src=\"../elements/images/enabled.png\"></a>\n");
                			} else { 
						$etat=aide(gettext("La machine est actuellement &#233;teinte, cliquez pour l allumer"),"<a href=\"action_machine.php?machine=$mp_en_cours&action=wol&parc=$parc&retour=action_parc\"><img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\"></a>\n"); 
					}
				
				// Inventaire
				$sessid=session_id();
				$systemid=avoir_systemid($mpenc);

		  		// Affichage du tableau
				echo "<tr>";
				// Affichage de l'icone informatique
				echo "<td align=\"center\">\n";
				echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" onclick=\"popuprecherche('../ocsreports/machine.php?sessid=$sessid&amp;systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=500');\"  title=\"Station\" alt=\"Station\"></TD>";
				echo "<td align=center ><a href=show_histo.php?selectionne=2&amp;mpenc=$mp_en_cours>$mp_en_cours</A></td>\n<td align=center >$etat</td>\n<td align=center>$etat_session </td>\n";
				echo "<td align=\"center\">";
				if ($ping) {
                  			$ts=ts($iphost);
                  			$vnc=vnc($iphost);
                  			if ($ts) { echo $ts; }
                  			if ($vnc) { echo $vnc; }
                   			if ((!$ts) and (!$vnc)) { echo "<img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\">\n"; }
                 
                  		}  else { echo "<img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\">\n"; }
                  		echo "</td></tr>\n";
                  	}

		   }
        	}
	}
	echo "</table>\n";   
	echo "</center>\n";

}



/**

* Affiche un tableau des imprimantes dans un parc
* @Parametres $parc le nom du parc a  tester
* @Return
*/

function detail_parc_printer($parc)
{
	// include "printers.inc.php";

        $mp_all=gof_members($parc,"parcs",1);

	// Filtrage selon critere
        if ("$filtrecomp"=="") $mp=$mp_all;
        else {
                $lmloop=0;
                $mpcount=count($mp_all);
                for ($loop=0; $loop < count($mp_all); $loop++) {
                	$mach=$mp_all[$loop];
                   	if (preg_match(/$filtrecomp/,$mach)) $mp[$lmloop++]=$mach;
                }
        }

        if ( count($mp)>0) {
                 sort($mp);

		$tableau_printer = "\n<br>\n<CENTER>\n";
		$tableau_printer .=  "<TABLE border=1 width=\"60%\">\n<tr class=menuheader style=\"height: 30\">\n";
		$tableau_printer .=  "<td align=\"center\"></td>\n";
		$tableau_printer .=  "<td align=\"center\">Imprimantes</td>\n";
		$tableau_printer .=  "<td align=\"center\">Etat</td>\n";
       
		$tableau_printer .=  "<td align=\"center\">Travaux d'impression</td>\n";
       		$tableau_printer .=  "</tr>\n";

		$suisje_printer="0";
		for ($loop=0; $loop < count($mp); $loop++) {
                        $mpenc=urlencode($mp[$loop]);
                 	$mp_en_cours=urldecode($mpenc);
   			$mp_curr=search_machines("(&(cn=$mp_en_cours)(objectClass=ipHost))","computers");

       			// Test si on a une imprimante ou une machine
			$resultat=search_imprimantes("printer-name=$mp_en_cours","printers");
			for ($loopp=0; $loopp < count($resultat); $loopp++) {
				if ($mp_en_cours==$resultat[$loopp]['printer-name']) {
					$suisje_printer="1";
					$tableau_printer .= "<tr>";				
					$tableau_printer .= "<td align=\"center\"><img style=\"border: 0px solid ;\" src=\"../elements/images/printer.png\" title=\"Imprimante\" alt=\"Imprimante\"></TD>";
					
					$tableau_printer .= "<TD align=\"center\"><A href='../printers/view_printers.php?one_printer=$mp_en_cours'>$mp_en_cours</A></TD>\n";
					
					$printer = $mp_en_cours;
					$sys= exec("/usr/bin/lpstat -p $printer | grep enabled");
					$sys2= exec("/usr/bin/lpstat -a $printer | grep not");

					if (($sys != "") and ($sys2 == "")) {
						$tableau_printer .= "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$printer est actuellement active.')\"><img type=\"image\" border=\"0\" src=\"../elements/images/enabled.png\"></u>\n"; 

                                	}  else { 
						if ($sys2 == "") {
							$tableau_printer .= "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$printer est actuellement inactive. Les documents sont stock&#233;s dans la file d impression.')\"><img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\"></u>\n"; 
						} else {
							$tableau_printer .= "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$printer rejette actuellement tout travail d impression.')\"><img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\"></u>\n"; 
						}
					}
					$tableau_printer .= "</TD>\n";
				
					// Travaux d'impression
					$sys= exec("/usr/bin/lpstat -o $printer");
					if ($sys != "") {
						$tableau_printer .= "<TD align=\"center\"><img type=\"image\" border=\"0\" src=\"../elements/images/enabled.png\">\n";
                                        }  else {
                                                $tableau_printer .= "<TD align=\"center\"><img type=\"image\" border=\"0\" src=\"../elements/images/disabled.png\">\n";
                                        }
					
					$tableau_printer .= "</tr>\n";
					continue;
				}
			}
		}
		$tableau_printer .=  "</table></center>";
		if ($suisje_printer=="1") {
			echo $tableau_printer;
			echo "<br>";
		}	
	}
}	




/**

* Cette machine a-t-elle ete validee pour la liaison ssh?
* @Parametres $mpenc le nom de la machine a tester
* @Return
*/


function deja_valid($mpenc)
{
	$fichier_valid="/var/se3/unattended/install/computers/$mpenc.tmp";
                 
	if(!(file_exists($fichier_valid) and (filesize($fichier_valid) > 2) )) {
        	return aide(gettext("Cette machine n a jamais &eacute;t&eacute; valid&eacute;e compl&egrave;tement"),"<img src=\"../elements/images/serviceevent.gif\" alt=\"".gettext("Machine jamais valid&eacute;e")."\"  width=\"20\" height=\"20\" border=\"0\" />"); }
                 else {
              		return aide(gettext("Pour supprimer ce poste de la liste des clients valid&eacute;s, n&eacute;c&eacute;ssaire si <ul><li>un poste est r&eacute;install&eacute; </li><li>apr&egrave;s un clonage</li><li>apr&egrave;s une r&eacute;instalation du client ssh</li></ul>"),"<a href=\"install_locale.php?action=trash&mpenc=$mpenc\"><img src=\"../elements/images/edittrash.png\" alt=\"Supprimer un client\" title=\"Supprimer $mpenc\" width=\"16\" height=\"16\" border=\"0\" /></a>");   }
}


/**

* Supprime l'anti slash
* @Parametres $string la chaine a traiter
* @Return
*/

function enleveantislash($string)
{
            $temp=rawurlencode($string);
            $temp1=preg_replace("/%5C%27/","%27",$temp);
            $temp2=preg_replace("/%5C%22/","%22",$temp1);
            $final=rawurldecode($temp2);
return $final;
}



/**

* Supprime la double barre
* @Parametres $string la chaine a traiter
* @Return
*/

function enlevedoublebarre($string)
{
            $temp=rawurlencode($string);
            $temp1=preg_replace("/%5C%5C/","%5C",$temp);
            $final=rawurldecode($temp1);
return $final;
}


/**

* Test reponse d'une url 
* @Parametres $string la chaine a traiter
* @Return
*/

function filemtime_remote($uri)
{
   $uri = parse_url($uri);
   $handle = @fsockopen($uri['host'],80);
   if(!$handle)
       return 0;

   fputs($handle,"GET $uri[path] HTTP/1.1\r\nHost: $uri[host]\r\n\r\n");
   $result = 0;
   while(!feof($handle))
   {
       $line = fgets($handle,1024);
       if(!trim($line))
           break;

       $col = strpos($line,':');
       if($col !== false)
       {
           $header = trim(substr($line,0,$col));
           $value = trim(substr($line,$col+1));
           if(strtolower($header) == 'last-modified')
           {
               $result = strtotime($value);
               break;
           }
       }
   }
   fclose($handle);
   return $result;
}


/**

* Selectionne des scripts 
* @Parametres 
* @Return
*/


function list_alpha()
{
global $authlink;

$query="Select nom,script,valide from appli_se3 order by nom asc;";
$resultat = mysql_query($query) or die ( "Probleme d'acc&#232;s &#224; la base" );
$query_nom="Select nom from appli_se3 order by nom asc;";
$resultat_nom = mysql_query($query_nom) or die ( "Probleme d'acc&#232;s &#224; la base" );

//echo "<div align=center><a href=appli_client.php?action=list_cat><img src=\"../elements/images/left.gif\" alt=\"\" title=\"left\" width=\"15\" height=\"15\" border=\"0\" />&nbsp;Retour vers liste par cat&#233;gories</a></div>";
echo "<table align=center width=\"60%\">\n<tr><td class=menuheader height=\"30\" align=\"center\">".gettext("NOM")."</td>\n<td class=menuheader height=\"30\" align=\"center\">".gettext("SCRIPT")."</td>\n<td class=menuheader height=\"30\" align=\"center\" width=\"25\">".gettext("ETAT")."</td>\n<td class=menuheader height=\"30\" width=\"25\" align=\"center\">\n<img src=\"../elements/images/edit.png\" alt=\"\" title=\"edit\" width=\"16\" height=\"16\" border=\"0\" /></td>\n</tr>\n</table>\n";
echo "<table align=center width=\"60%\">\n<tr>\n";
while ($row_nom=mysql_fetch_row($resultat_nom))
{
$chaine = strtoupper(substr("$row_nom[0]", 0,1));
if ($test_alpha<>$chaine)
{
echo "<td align=center><a href=#$chaine>$chaine</a></td>\n";
$test_alpha=$chaine;
}
}
echo "</table>\n<table align=center width=\"60%\">\n";
while ($row=mysql_fetch_row($resultat))
{
$chaine = strtoupper(substr("$row[0]", 0,1));
if ($test_alpha<>$chaine)
{
echo "<tr><td colspan=4 class=menuheader ><a name=\"$chaine\">$chaine</a></td></tr>\n";
$test_alpha=$chaine;
}
if  ((!$row[2]) or (!file_exists('/var/se3/unattended/install/scripts/'.$row[1])))
{
$alert=aide(gettext("Une application n est pas valid&#233e si lors de la cr&#233ation ou de la modification de de cette application, les executables demand&#233s dans le script ne sont pas trouv&#233s &#224 l endroit indiqu&#233. A vous de les placer au bon endroit (X:\\unattended\\install\\packages) puis Editer l application, puis cliquer sur Enregistrer mes modifications, si les executables sont trouv&#233s, votre application sera automatiquement valid&#233e."),"<img src=\"../elements/images/critical.png\" alt=\"Application non valid&eacute;e\" width=\"20\" height=\"20\" border=\"0\" />");
}
else
{
$alert=aide(gettext("Cette application a &#233t&#233 valid&#233e, cela signifie que les executables ont &#233t&#233 trouv&#233s. Cela ne signifie pas que le script associ&#233 &#224 l application est correct. Seul les scripts import&#233s du serveur central ont &#233t&#233 test&#233s par l equipe de sambaedu."),"<img src=\"../elements/images/enabled.png\" alt=\"Application valid&eacute;e\"  width=\"20\" height=\"20\" border=\"0\" />");
}
echo "<tr><td align=\"center\" >".$row[0]."</td><td align=\"center\">".$row[1]."</td>\n<td align=\"center\">$alert</td>\n<td align=\"center\"><a href=appli_client.php?action=edit_appli&appli=".urlencode($row[0])."><img src=\"../elements/images/edit.png\" alt=\"\" title=\"Editer l appllication\" width=\"16\" height=\"16\" border=\"0\" /></A></td>\n</tr>\n";
}
echo "</table>\n";
}


/**

* Test la presence d'un fichier de log pour une machine 
* @Parametres $machine la machine a tester
* @Return
*/

function test_log($machine)
{
	$fichier_log="/var/se3/unattended/install/computers/$machine.log";
                 
	if(!(file_exists($fichier_log) and (filesize($fichier_valid) > 2) )) {
   		//    $fp = @fopen($fichier_log, "r");
    		$tab = file($fichier_log);
    
    		$inverse_tab=array_reverse($tab);
    		if (preg_match("/A)bort/i",$inverse_tab[0])) { 
    			return true;
		} else {
    			return false;
    		}
	}
}


/**

* vire une machine d'un parc, Supprime le parc si la machine est la derniere dedans

* @Parametres  Nom du parc et de la machine
* @Return  
*/

function supprime_machine_parc($mpenc,$parc) {
	include "config.inc.php";
	// On compte si la demande ne porte pas sur toutes les machines
	$mp_all=gof_members($parc,"parcs",1);
	$mpcount=count($mp_all);
	// Si la demande porte sur la derniere machine du parc
	// On vire le parc
	if ($mpcount == "1") {
		$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn; 
		exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");
	}
	if ($mpcount > "1") {
		$resultat=search_imprimantes("printer-name=$mpenc","printers");
		$suisje_printer="non";
		for ($loopp=0; $loopp < count($resultat); $loopp++) {
			if ($mpenc==$resultat[$loopp]['printer-name']) {
				$suisje_printer="yes";	
				continue;
			}	
		}
		$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
		if ($suisje_printer=="yes") {
			// je suis une imprimante
			$cDn = "cn=".$mpenc.",".$printersRdn.",".$ldap_base_dn;
		} else {
			// je suis un ordinateur
			$cDn = "cn=".$mpenc.",".$computersRdn.",".$ldap_base_dn;
		}
		// on supprime
		exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
	}
}


/**

* Test si un parc exist

* @Parametres  Nom du parc 
* @Return yes ou no
*/

function parc_exist($parc) {
	include "config.inc.php";
	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
	if ( count($list_parcs)>0) {
		for ($loop=0; $loop < count($list_parcs); $loop++) {
			if ($parc==$list_parcs[$loop]["cn"]) {
				return yes;
			}
		}
	}
	return no;
}	


/**

*  Fonction permettant de nettoyer la table delegation en fonction des parcs existants

* @Parametres 
* @Return
*/


function nettoie_delegation() {
	include "config.inc.php";
	$query="select parc from delegation GROUP BY parc";
		$result= mysql_query($query);
	if ($result) {
		$ligne= mysql_num_rows($result);
		if ($ligne>0) {
			while ($row = mysql_fetch_row($result)) {
				$exist_parc=parc_exist($row[0]);
				if ($exist_parc=="no") {
					$query_del="delete from delegation where parc='$row[0]'";
					mysql_query($query_del);
					echo "<BR> Suppression de la d&#233;l&#233;gation pour le parc $row[0]";
					echo "<BR>";
				} else { continue; }
			}
		}
	}	
}


/**

* Mises a jour des fichiers xml de wpkg 

* @Parametres
* @Return
*/


function update_wpkg() {
	global $computersRdn, $parcsRdn, $ldap_base_dn;
	// Met a jour les fichiers :
	//   /var/se3/unattended/install/wpkg/hosts.xml
	//   /var/se3/unattended/install/wpkg/profiles.xml
	$wpkgUpdateHostsProfiles="/usr/share/se3/scripts/update_hosts_profiles_xml.sh";
	if (file_exists($wpkgUpdateHostsProfiles)) exec ("$wpkgUpdateHostsProfiles '$computersRdn' '$parcsRdn' '$ldap_base_dn'");
	
	// Met a jour le fichier /var/se3/unattended/install/wpkg/droits.xml
	$wpkgUpdateDroit="/usr/share/se3/scripts/update_droits_xml.sh";
	if (file_exists($wpkgUpdateDroit)) exec ("$wpkgUpdateDroit");
}

?>
