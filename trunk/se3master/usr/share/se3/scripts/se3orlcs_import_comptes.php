<?php
	/*
		$Id$
	*/

	// Detection LCS ou SE3
	if ( file_exists("/var/www/se3") ) {$servertype="SE3";}
	else {$servertype="LCS";}

	// ===========================================================
	// AJOUTS: 20070914 boireaus
	//if($argc < 11 || in_array($argv[1], array('--help', '-help', '-h', '-?'))){
	//if($argc < 15 || in_array($argv[1], array('--help', '-help', '-h', '-?'))){
	if($argc < 17 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
	// ===========================================================
		$chaine="USAGE: Vous devez passer en paramétres (dans l'ordre):\n";
		$chaine.="       . Le type du fichier 'csv' ou 'xml';\n";
		$chaine.="       . le chemin du fichier élèves;\n";
		$chaine.="       . le chemin du fichier XML de STS EDT;";
		$chaine.="       . le préfixe (CLG_, LYC_, LP_, LEGT_) si vous en avez besoin;\n";
		$chaine.="       . 'y' ou 'n' selon que l'import est annuel ou non;\n";
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez seulement une simulation ou non;\n";
		$chaine.="       . le suffixe pour le fichier HTML result.SUFFIXE.html généré;\n";
		$chaine.="       . une chaine aléatoire pour le sous-dossier de stockage des CSV;\n";
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez créer les CSV ou non.\n";
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez chronométrer les opérations ou non.\n";

		// ===========================================================
		// AJOUTS: 20070914 boireaus
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez créer des Equipes vides ou non.\n";
		$chaine.="                    (avec 'n' elles sont créées et peuplées)\n";
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez créer Cours ou non.\n";
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez créer Matières ou non.\n";
		// ===========================================================
		$chaine.="       . 'y' ou 'n' selon que vous souhaitez corriger ou non les attributs\n";
		$chaine.="                    gecos, cn, sn et givenName si des différences sont trouvées.\n";
		$chaine.="       . 'y' ou 'n' selon qu'il faut utiliser ou non un fichier F_UID.txt\n";
		$chaine.="       . 'y' ou 'n' selon qu'il faut alimenter un groupe Professeurs Principaux\n";
		// ===========================================================



		echo $chaine;

		if($servertype=="LCS") {
			require ("/var/www/lcs/includes/config.inc.php");
			$adressedestination="admin@$domain";
			$sujet="ERREUR: import_comptes.php ";
			$message=$chaine;
			$entete="From: root@$domain";
			mail("$adressedestination", "$sujet", "$message", "$entete");
		}
		else {
			// Récupérer les adresses,... dans le /etc/ssmtp/ssmtp.conf
			unset($tabssmtp);
			#require ("/var/www/se3/import_sconet/crob_ldap_functions.php");
			require ("/var/www/se3/includes/crob_ldap_functions.php");
			$tabssmtp=lireSSMTP();
			// Contrôler les champs affectés...
			if(isset($tabssmtp["root"])) {
				$adressedestination=$tabssmtp["root"];
				$sujet="ERREUR: import_comptes.php ";
				$message=$chaine;
				$entete="From: ".$tabssmtp["root"];
				mail("$adressedestination", "$sujet", "$message", "$entete");
			}
		}
		exit();
	}

	if($servertype=="LCS") {
		require ("/var/www/lcs/includes/config.inc.php");
		include "/var/www/Annu/includes/ldap.inc.php";
		include "/var/www/Annu/includes/ihm.inc.php";
		require ("/var/www/Annu/includes/crob_ldap_functions.php");
	}
	else {
		include "/var/www/se3/includes/ldap.inc.php";
		include "/var/www/se3/includes/ihm.inc.php";
		require ("/var/www/se3/includes/config.inc.php");
		require ("/var/www/se3/includes/crob_ldap_functions.php");

		require_once ("/var/www/se3/includes/lang.inc.php");
		bindtextdomain('se3-annu',"/var/www/se3/locale");
		textdomain ('se3-annu');
	}

	// Récupération des variables
	$type_fichier_eleves=$argv[1];
	$eleves_file=$argv[2];
	$sts_xml_file=$argv[3];
	$prefix=$argv[4];
	$annuelle=$argv[5];
	$simulation=$argv[6];
	$timestamp=$argv[7];
	$randval=$argv[8];
	$temoin_creation_fichiers=$argv[9];
	$chrono=$argv[10];

	// ===========================================================
	// AJOUTS: 20070914 boireaus
	$creer_equipes_vides=$argv[11];
	$creer_cours=$argv[12];
	$creer_matieres=$argv[13];
	// ===========================================================
	$corriger_gecos_si_diff=$argv[14];
	// ===========================================================
	$temoin_f_uid=$argv[15];
	// ===========================================================
	$alimenter_groupe_pp=$argv[16];
	// ===========================================================

	// Pour effectuer des affichages de debug:
	$debug_import_comptes="n";

	// Chemins (essentiellement):
	if($servertype=="LCS") {
		$racine_www="/var/www";
		$www_import="/Annu/import_sconet.php";
		$chemin_http_csv="setup/csv/".$timestamp."_".$randval;
		$dossiercsv=$racine_www."/".$chemin_http_csv;
		$echo_file="$racine_www/Admin/result.$timestamp.html";
		$echo_http_file="$baseurl/Admin/result.$timestamp.html";
		$dossier_tmp_import_comptes="/var/lib/lcs/import_comptes";
		$pathscripts="/usr/share/lcs/scripts";
		$user_web = "www-data";

		// $argv[17] pour SE3
		$rafraichir_classes="n";
	}
	else {
		$racine_www="/var/www/se3";
		$www_import="/annu/import_sconet.php";
		$chemin_http_csv="setup/csv/".$timestamp."_".$randval;
		$dossiercsv=$racine_www."/".$chemin_http_csv;
		$echo_file="$racine_www/Admin/result.$timestamp.html";
		$sql="SELECT value FROM params WHERE name='se3ip';";
		$res_ip=mysql_query($sql);
		if(mysql_num_rows($res_ip)>0) {
			$lig_ip=mysql_fetch_object($res_ip);
			$baseurl="http://".$lig_ip->value.":909";
		}
		else {
			$baseurl="http://IP_SE3:909";
		}
		$echo_http_file="$baseurl/Admin/result.$timestamp.html";
		$dossier_tmp_import_comptes="/var/lib/se3/import_comptes";
		$pathscripts="/usr/share/se3/scripts";
		$user_web = "www-se3";

		$rafraichir_classes="n";
		if((isset($argv[17]))&&($argv[17]=="y")) {
			$rafraichir_classes="y";
		}
	}

	if($servertype=="LCS") {
		// Cas d'un LCS ou defaultgid et domainsid ne sont pas dans la table params
		exec ("getent group lcs-users | cut -d ':' -f 3", $retvalgid);
		$defaultgid= $retvalgid[0];
		exec ("ldapsearch -x -LLL  objectClass=sambaDomain | grep sambaSID | cut -d ' ' -f 2",$retvalsid);
		$domainsid = $retvalsid[0];
		// Si il n'y a pas de sambaSID dans l'annuaire, on fixe une valeur factice
		// Il faudra appliquer un correct SID lors de l'installation d'un se3
		if (!isset($domainsid)) {$domainsid ="S-0-0-00-0000000000-000000000-0000000000";}
	}

	// AJOUT: 20080610
	$attribut_pseudo="initials";
	$controler_pseudo="y";
	$corriger_givenname_si_diff="y";

?>
