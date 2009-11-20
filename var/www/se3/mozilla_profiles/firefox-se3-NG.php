<?php
/* $Id$ */


/* =============================================
Projet SE3
Configuration des profils firefox installés avec wpkg
jean.lebail@etab.ac-caen.fr
Distribué selon les termes de la licence GPL
============================================= */

require("entete.inc.php");

//Vérification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'autenthification is_admin
require("ihm.inc.php");

function writeProxy ($proxy, $file, $ident)
{
	fwrite($file, $ident."pref('browser.safebrowsing.enabled', false);\n");
	fwrite($file, $ident."pref('browser.safebrowsing.malware.enabled', false);\n");
	fwrite($file, $ident."pref('browser.cache.disk.enable', false);\n");
	if ($proxy == "")
		fwrite($file, $ident."pref('network.proxy.type', 0);\n");
	else if (strstr($proxy, "http://")) {
		fwrite($file, $ident."pref('network.proxy.type', 2);\n");
		fwrite($file, $ident."pref('network.proxy.autoconfig_url', '$proxy');\n");
	fwrite($file, $ident."pref('network.proxy.type', 2);\n");
	}
	else {
		$proxyValues=explode(":", $proxy);
		if ($proxyValues[0] == "" or $proxyValues[1] == "")
			fwrite($file, "pref('network.proxy.type', 0);\n");
		fwrite($file, $ident."pref('network.proxy.type', 1);\n");
		fwrite($file, $ident."pref('network.proxy.http', '$proxyValues[0]');\n");
		fwrite($file, $ident."pref('network.proxy.http_port', $proxyValues[1]);\n");
		fwrite($file, $ident."pref('network.proxy.ftp', '$proxyValues[0]');\n");
		fwrite($file, $ident."pref('network.proxy.ftp_port', $proxyValues[1]);\n");
		fwrite($file, $ident."pref('network.proxy.gopher', '$proxyValues[0]');\n");
		fwrite($file, $ident."pref('network.proxy.gopher_port', $proxyValues[1]);\n");
		fwrite($file, $ident."pref('network.proxy.ssl', '$proxyValues[0]');\n");
		fwrite($file, $ident."pref('network.proxy.ssl_port', $proxyValues[1]);\n");
		fwrite($file, $ident."pref('network.proxy.no_proxies_on', 'localhost,127.0.0.1,".$_SERVER['HTTP_HOST']."');\n");
	}
}


//AUTHENTIFICATION
if (! $login ) {
    echo "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n";
    $request = '/mozilla_profiles/firefox-se3-NG.php';
    echo "top.location.href = '/auth.php?request=" . rawurlencode($request) . "';\n";
    echo "//-->\n</script>\n";
} else {
	if (is_admin("computer_is_admin",$login)!="Y")
		die ("Vous n'avez pas les droits suffisants pour acc&#233der &#224 cette fonction</BODY></HTML>");

	$choix=$_GET['choix'];
	$config=$_GET['config'];
	$PathFichierWWW = "/var/se3/unattended/install/packages/firefox/firefox-profile.js";

	// Lecture du fichier $PathFichierWWW
	$nCond=0;
	$url = array();
	$typeUrl = array();
	$valUrl = array();

	echo "<h1>Configuration de Firefox</h1>";

	$defaultUrl = 'http://www.google.fr/webhp';
	if (file_exists($PathFichierWWW)) {
		$fp = fopen($PathFichierWWW, "r");
		while (!feof($fp)) {
			$ligne = fgets($fp, 1024);
			$match=0;
			if ( preg_macth("/\tpref\('browser.startup.homepage', '(.*)'\);/", $ligne, $val) ) {
				$defaultUrl = $val[1];
			} else if ( preg_match("/\tpref\('network.proxy.type', (.*)\);/", $ligne, $val) ) {
				$proxyType = $val[1];
			} else if ( preg_match("/\tpref\('network.proxy.http', '(.*)'\);/", $ligne, $val) ) {
				$proxy[0] = $val[1];
			} else if ( preg_match("/\tpref\('network.proxy.http_port', (.*)\);/", $ligne, $val) ) {
				$proxy[1] = $val[1];
			} else if ( preg_match("/\tpref\('network.proxy.autoconfig_url', '(.*)'\);/", $ligne, $val) ) {
				$proxyUrl = $val[1];
			} else if ( preg_match("/\tif \( username == '(.*)' \) {/", $ligne, $val) ) {
				$typeUrl[$nCond] = 'username';
				$valUrl[$nCond] = $val[1];
				$match=1;
				$ligne = fgets($fp, 1024);
				while ($ligne != "\t}\n") {
					if ( preg_match("/\tpref\('browser.startup.homepage', '(.*)'\);/", $ligne, $val) ) {
						$url[$nCond] = $val[1];
					}
					if ( preg_match("/\t\tpref\('network.proxy.type', (.*)\);/", $ligne, $val) ) {
						$condProxyType[$nCond] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http', '(.*)'\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][0] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http_port', (.*)\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][1] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.autoconfig_url', '(.*)'\);/", $ligne, $val) ) {
						$condProxy[$nCond] = $val[1];
					}
					$ligne = fgets($fp, 1024);
				}
				$nCond++;
			} else if ( preg_match("/\tif \( computername == '(.*)' \) {/", $ligne, $val) ) {
				$typeUrl[$nCond] = 'username';
				$valUrl[$nCond] = $val[1];
				$match=1;
				$ligne = fgets($fp, 1024);
				while ($ligne != "\t}\n") {
					if ( preg_match("/\tpref\('browser.startup.homepage', '(.*)'\);/", $ligne, $val) ) {
						$url[$nCond] = $val[1];
					}
					if ( preg_match("/\t\tpref\('network.proxy.type', (.*)\);/", $ligne, $val) ) {
						$condProxyType[$nCond] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http', '(.*)'\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][0] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http_port', (.*)\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][1] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.autoconfig_url', '(.*)'\);/", $ligne, $val) ) {
						$condProxy[$nCond] = $val[1];
					}
					$ligne = fgets($fp, 1024);
				}
				$nCond++;
			} else if ( preg_match("/\tif \( userGroups.indexOf\(',' \+ '(.*)' \+ ','\) >= 0 \) {/", $ligne, $val) ) {
				$typeUrl[$nCond] = 'username';
				$valUrl[$nCond] = $val[1];
				$match=1;
				$ligne = fgets($fp, 1024);
				while ($ligne != "\t}\n") {
					if ( preg_match("/\tpref\('browser.startup.homepage', '(.*)'\);/", $ligne, $val) ) {
						$url[$nCond] = $val[1];
					}
					if ( preg_match("/\t\tpref\('network.proxy.type', (.*)\);/", $ligne, $val) ) {
						$condProxyType[$nCond] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http', '(.*)'\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][0] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http_port', (.*)\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][1] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.autoconfig_url', '(.*)'\);/", $ligne, $val) ) {
						$condProxy[$nCond] = $val[1];
					}
					$ligne = fgets($fp, 1024);
				}
				$nCond++;
			} else if ( preg_match("/\tif \( parcs.indexOf\(',' \+ '(.*)' \+ ','\) >= 0 \) {/", $ligne, $val) ) {
				$typeUrl[$nCond] = 'username';
				$valUrl[$nCond] = $val[1];
				$match=1;
				$ligne = fgets($fp, 1024);
				while ($ligne != "\t}\n") {
					if ( preg_match("/\tpref\('browser.startup.homepage', '(.*)'\);/", $ligne, $val) ) {
						$url[$nCond] = $val[1];
					}
					if ( preg_match("/\t\tpref\('network.proxy.type', (.*)\);/", $ligne, $val) ) {
						$condProxyType[$nCond] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http', '(.*)'\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][0] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.http_port', (.*)\);/", $ligne, $val) ) {
						$condProxyVals[$nCond][1] = $val[1];
					} else if ( preg_match("/\tpref\('network.proxy.autoconfig_url', '(.*)'\);/", $ligne, $val) ) {
						$condProxy[$nCond] = $val[1];
					}
					$ligne = fgets($fp, 1024);
				}
				$nCond++;
			}
			if ($proxyType == "0")
				$proxyUrl = "";
			else if ($proxyType == "1")
				$proxyUrl = $proxy[0].":".$proxy[1];

			if ($match > 0 ) {
				$i=$nCond-1;
				echo "<!-- j=$i, typeUrl=".$typeUrl[$i].", valUrl=".$valUrl[$i].", url=" . $url[$i] ." -->\n";
			}
		}
		fclose ($fp);
	}
	if ($condProxyType[$i] == "0")
		$condProxy[$i] = "";
	else if ($condProxyType[$i] == "1")
		$condProxy[$i] = $condProxyVals[$i][0].":".$condProxyVals[$i][1];

	$match = 0;
	if ( $_GET['choix'] != '' ) {
		$choix = $_GET['choix'];
		if ( $choix == 'supprimer' ) {
			for ($i=$_GET['item']; $i < ($nCond-1) ; $i++) {
				$url[$i] = $url[$i+1];
				$typeUrl[$i] = $typeUrl[$i+1];
				$valUrl[$i] = $valUrl[$i+1];
				$condProxy[$i] = $condProxy[$i+1];
			}
			$nCond--;
			$match=1;
		} else if ($_POST['url'] == '') {
			echo "L'url de la page de démarrage n'est pas définie !<br>\n";
		} else {
			if ( $choix == 'defaut' ) {
				$defaultUrl = $_POST['url'];
				$proxyUrl = $_POST['proxy'];
				$match=1;
			}
			if ( $choix=='username' || $choix=='userGroups' || $choix=='computername' || $choix=='parcs') {
				if ($_POST[$choix] == '') {
					switch ($choix) {
						case 'username'     : echo "Le nom de l'utilisateur n'est pas défini !<br>\n";break;
						case 'userGroups'   : echo "Le nom du groupe n'est pas défini !<br>\n";break;
						case 'computername' : echo "Le nom du poste n'est pas défini !<br>\n";break;
						case 'parcs'        : echo "Le nom du parc n'est pas défini !<br>\n";break;
					}
				} else {
					$url[$nCond] = $_POST['url'];
					$condProxy[$nCond] = $_POST['condProxy'];
					$typeUrl[$nCond] = $choix;
					$valUrl[$nCond] = $_POST[$choix];
					$match=1;
					$nCond++;
				}
			}
		}
	}
	if ($match == 1) {
		// Mise à jour du fichier
		if (!$fp = fopen($PathFichierWWW, "w")) {
			echo "Erreur d'ouverture en écriture du fichier '$PathFichierWWW' !<br>\n";
		} else {
			fwrite($fp, "// Ce fichier est défini par l'interface SE3\n");
			fwrite($fp, "\n");
			fwrite($fp, "try {\n");
			fwrite($fp, "\tpref('browser.startup.homepage', '$defaultUrl');\n");
			writeProxy ($proxyUrl, $fp, "\t");
			for ($i=0; $i < $nCond ; $i++) {
				if ($typeUrl[$i] == 'username') {
					fwrite($fp, "\tif ( username == '".$valUrl[$i]."' ) {\n");
					writeProxy ($condProxy[$i], $fp, "\t\t");
					fwrite($fp, "\t\tpref('browser.startup.homepage', '".$url[$i]."');\n\t}\n");
				} else if ($typeUrl[$i] == 'computername') {
					fwrite($fp, "\tif ( computername == '".$valUrl[$i]."' ) {\n");
					writeProxy ($condProxy[$i], $fp, "\t\t");
					fwrite($fp, "\t\tpref('browser.startup.homepage', '".$url[$i]."');\n\t}\n");
				} else if ($typeUrl[$i] == 'userGroups') {
					fwrite($fp, "\tif ( userGroups.indexOf(',' + '".$valUrl[$i]."' + ',') >= 0 ) {\n");
					writeProxy ($condProxy[$i], $fp, "\t\t");
					fwrite($fp, "\t\tpref('browser.startup.homepage', '".$url[$i]."');\n\t}\n");
				} else if ($typeUrl[$i] == 'parcs') {
					fwrite($fp, "\tif ( parcs.indexOf(',' + '".$valUrl[$i]."' + ',') >= 0 ) {\n");
					writeProxy ($condProxy[$i], $fp, "\t\t");
					fwrite($fp, "\t\tpref('browser.startup.homepage', '".$url[$i]."');\n\t}\n");
				}
			}
			fwrite($fp, "} catch(e) {\n");
			fwrite($fp, "\tdisplayError('firefox-se3.js', e);\n");
			fwrite($fp, "}\n");
			fclose ($fp);
		}
	}

	$list_groups=search_groups("(cn=*)");
	$j =0; $k =0;
	$m = 0;
	for ($loop=0; $loop < count ($list_groups) ; $loop++) {
	  // Classe
	  if ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) ||
	       preg_match ("/Equipe_/", $list_groups[$loop]["cn"]) ||
	       (!preg_match ("/^overfill/", $list_groups[$loop]["cn"]) &&
	    !preg_match ("/^lcs-users/", $list_groups[$loop]["cn"]) &&
	    !preg_match ("/^admins/", $list_groups[$loop]["cn"]) &&
	    !preg_match ("/Cours_/", $list_groups[$loop]["cn"]) &&
	    !preg_match ("/Matiere_/", $list_groups[$loop]["cn"]) &&
	    !preg_match ("/^machines/", $list_groups[$loop]["cn"])) ) {
	    $groupe[$m]["cn"] = $list_groups[$loop]["cn"];
	    $groupe[$m]["description"] = $list_groups[$loop]["description"];
	    $m++;
	  }
	}

	$form ="<form name='defaut' action='firefox-se3-NG.php?choix=defaut' method='post'>\n";
	$form .="<h3>Page de démarrage par défaut</H3>\n";
	$form .="<input type='text' name='url' value='$defaultUrl' size=80><input type='submit' value='Définir'><br>\n";
	$form .="<h3>Proxy par defaut (http:// pour autoconf ou ip:port)</H3>\n";
	$form .="<input type='text' name='proxy' value='$proxyUrl' size=80><input type='submit' value='Définir'><br>\n";
	$form .="</form>\n";
	$form .="<h3>Page de démarrage selon l'utilisateur ou le poste</H3>\n";
	$form .= "<table>\n";
	for ($i=0; $i < $nCond ; $i++) {
		$form .= "<tr><td align='right'>";
		if ($typeUrl[$i] == 'username') {
			$form .= "Si l'utilisateur est: ";
		} else if ($typeUrl[$i] == 'computername') {
			$form .= "Si le poste est: ";
		} else if ($typeUrl[$i] == 'userGroups') {
			$form .= "Si l'utilisateur est membre du groupe: ";
		} else if ($typeUrl[$i] == 'parcs') {
			$form .= "Si le poste est dans le parc ";
		}
		$form .= "</td><td><b>".$valUrl[$i]."</b></td><td> Page de démarrage : <b>".$url[$i]."</b>, Proxy: <b>".$condProxy[$i]."</b></td>\n";
		$form .= "</td><td><input type='button' value='Supprimer' onclick=\"document.location='firefox-se3-NG.php?choix=supprimer&item=$i';\"></td>\n";
		$form .= "</tr>\n";
	}
	$form .= "</table><br>\n";
	$form .="<h6>La dernière des conditions ci-dessus qui s'applique, lors du démarrage d'un navigateur firefox, définit la page de démarrage utilisée.</h6>\n";

	$lastUrl = $defaultUrl;
	if ($_POST['url'] != '') $lastUrl = $_POST['url'];
	$form .="<h3>Paramètre conditionnels:</H3>\n";
	$form .="<h3>Ajouter une page de démarrage conditionnelle</H3>\n";

	$form .= "<form name='formulaire' action='firefox-se3-NG.php?config=add' method='post'>\n";
	$form .= "<input type='text' title='Url de la page de démarrage' value='$lastUrl' name='url' size='80'>\n";
	$form .="<h3>Ajouter un proxy conditionel (http:// pour autoconf ou ip:port) </H3>\n";
	$form .= "<input type='text' title='Proxy' value='$proxyUrl' name='condProxy' size='80'>\n";
	$form .= "<table>\n";
	$form .= "<tr><td align='right'> Si l'utilisateur est : </td><td><input title=\"Nom de login de l'utilisateur\" type='text' name='username' size=20></td>";
	$form .= "<td><input type='button' value='Ajouter' onclick=\"document.formulaire.action='firefox-se3-NG.php?choix=username';document.formulaire.submit();\"></td></tr>\n";

	$form .= "<tr><td align='right'> Si l'utilisateur est membre du groupe: </td>\n";
	$form .= "<td><select name='userGroups' >\n";
	for ($loop=0; $loop < count ($groupe) ; $loop++) {
		$form .= "<option value='".$groupe[$loop]["cn"]."'>".$groupe[$loop]["cn"]."</option>\n";
	}
	$form .= "</select></td>\n";
	$form .= "<td><input type='button' value='Ajouter' onclick=\"document.formulaire.action='firefox-se3-NG.php?choix=userGroups';document.formulaire.submit();\"></td></tr>\n";

	$form .= "<tr><td align='right'> Si le poste est : </td><td><input title=\"Nom du poste\" type='text' name='computername' size=20></td>\n";
	$form .= "<td><input type='button' value='Ajouter' onclick=\"document.formulaire.action='firefox-se3-NG.php?choix=computername';document.formulaire.submit();\"></td></tr>\n";

	$groupDn = $dn["groups"];
	$dn["groups"] = str_replace("Groups", "Parcs", $dn["groups"]);
	$parc = search_groups ("(cn=*)");
	$dn["groups"] = $groupDn;
	$nParc = count( $parc);
	$form .= "<tr><td align='right'> Si le poste est dans le parc : </td>\n";
	$form .= "<td><select name='parcs' >\n";
	for ($i=0; $i<$nParc; $i++) {
		$form .= "<option value='".$parc[$i]["cn"]."'>".$parc[$i]["cn"]."</option>\n";
	}
	$form .= "</select></td>\n";
	$form .= "<td><input type='button' value='Ajouter' onclick=\"document.formulaire.action='firefox-se3-NG.php?choix=parcs';document.formulaire.submit();\"></td></tr>\n";


	$form .= "</table>\n";
	$form .= "</form>\n";

	echo $form;
	echo "<br>";
}
echo "</body></html>";

include("pdp.inc.php");
?>
