<?php

   /**
   
   * Page pour l'authentification
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs  oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: auth.php

  */	

  // Initialisation:
  $error=0;


  require ("config.inc.php");
  require ("jlcipher.inc.php");
  require ("functions.inc.php");

  require ("test_dates.inc.php");

  require_once ("lang.inc.php");
  bindtextdomain('se3-core',"/var/www/se3/locale");
  textdomain ('se3-core');
  
  // HTMLpurifier
  include("../se3/includes/library/HTMLPurifier.auto.php");
  $config = HTMLPurifier_Config::createDefault();
  $purifier = new HTMLPurifier($config);
  
  $al=isset($_POST['al']) ? $purifier->purify($_POST['al']) : (isset($_GET['al']) ? $purifier->purify($_GET['al']) : "");
  $login=isset($_POST['login']) ? $purifier->purify($_POST['login']) : (isset($_GET['login']) ? $purifier->purify($_GET['login']) : "");
  $request=isset($_GET['request']) ? $purifier->purify($_GET['request']) : "";
  
  if ((!isset($al)||($al!=0)) && ((isset($login) && $login != "" && isset($dummy) && $dummy != "") || ($autologon==1))) {
	$test_login=isset($login) ? $login : "";
	$test_string_auth=isset($_POST['string_auth']) ? $purifier->purify($_POST['string_auth']) : "";
	$test_al=isset($al) ? $al : "";
	if(open_session($test_login, $test_string_auth, $test_al) == 1 ) {
		if (isset($request) && ($request != '')) {
			header("Location:".rawurldecode($request));
		} else {
			// L'autologon se fait la...
			header("Location:index.php");
		}
	} else {
		if (!isset($request) || ($request != '')) {
			if (!isset($login)||($login=="")) {
				header("Location:auth.php?al=0&error=2&request=".rawurlencode($request));
			} else {
				header("Location:auth.php?al=0&error=1&request=".rawurlencode($request));
			}
		} else {
			if (!isset($login)||($login=="")) {
				header("Location:auth.php?al=0&error=2");
			} else {
				header("Location:auth.php?al=0&error=1");
			}
		}
	}
} else {
	header_crypto_html("Authentification SE3","");
	$texte .= gettext("<P>Afin de pouvoir rentrer dans l'interface <EM>SambaEdu</EM>, vous devez indiquer votre identifiant et votre mot de passe sur le r&#233;seau.\n");
	$texte .= "<form name = 'auth' action='auth.php?al=1&request=".rawurlencode($request)."' method='post' onSubmit = 'encrypt(document.auth)'>\n";
	$texte .= "<table><tr><td>\n";
	$texte .= gettext("Identifiant")." :</td><td><INPUT TYPE='text' NAME='login' SIZE='20' MAXLENGTH='30'><BR>\n";
	$texte .= "</td></tr><tr><td>\n";
	$texte .= gettext("Mot de passe")." :</td><td><INPUT TYPE='password' NAME='dummy' SIZE='20' MAXLENGTH='20'><BR>\n";
	$texte .= "</td></tr><tr align=\"right\"><td></td><td>\n";
	$texte .= "<input type='hidden' name='string_auth' value=''>";
	$texte .= "<input type='hidden' name='time' value=''>";
	$texte .= "<input type='hidden' name='client_ip' value='".remote_ip()."'>";
	$texte .= "<input type='hidden' name='timestamp' value='".time()."'>";
	$texte .= "<INPUT TYPE='submit' VALUE='".gettext("Valider")."'><BR>\n";
	$texte .= "</td></tr></table>\n";
	$texte .= "</form>\n";

	mktable ("<STRONG>".gettext("Authentification...")."</STRONG>",$texte);
	crypto_nav("");
	if ($error==1) {
		echo "<div class='alert_msg'>".gettext("Erreur d'authentification !")."</div>";
	}

	// Test de l'ecart entre la date du serveur et la date du client
	// S'il y a plus de 200 secondes d'ecart, on affiche une alert() javascript:
	test_et_alerte_dates();

	include ("includes/pdp.inc.php");
}

?>
