<?php


   /**
   * Entete de toutes les pages. 
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   * Fonctions Interface Homme/Machine
   
   * @Auteurs Equipe Tice academie de Caen
   * @Auteurs Philippe chadefaux 
   
   * @Note Ce fichier doit etre appele par un include dans toutes les pages.
   * @Note Il ouvre la connexion a la base, lit les variables dans la table params, demarre la session pour l'aide.

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: entete.inc.php
   * @Repertoire: includes/ 
   */  
  


// Page par default de l'aide
@session_start();
$_SESSION["pageaide"]="Table_des_mati&#232;res";

require("config.inc.php");
require_once ("functions.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

require_once ("traitement_data.inc.php");

$login=isauth();


// Prise en compte de la page demandee initialement - leb 25/6/2005
if ($login == "") {
	//	header("Location:$urlauth");
	$request = $PHP_SELF;
	if ( $_SERVER['QUERY_STRING'] != "") {$request .= "?".$_SERVER['QUERY_STRING'];}

	echo "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n";
	echo "top.location.href = '$urlauth?request=" . rawurlencode($request) . "';\n";
	echo "//-->\n</script>\n";

	// Pour prevenir une poursuite dans le cas ou javascript serait desactive sur le client
	die();

} else {
	// Fin Prise en compte de la page demandee initialement - leb 25/6/2005
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>

<STYLE type="text/css">
body{
    background: url(/elements/images/fond_SE3.png) ghostwhite bottom right no-repeat fixed;
}
</STYLE>

<title><?php echo gettext("Interface d'administration de SambaEdu"); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="/elements/style_sheets/sambaedu.css" type="text/css">

  <script language = 'javascript' type = 'text/javascript'>

	function getconfirm() {
      		if(confirm("Confirmez-vous la suppression ?")) {
        		return true;
      		} else {
        		return false;
      		}
    	}
	function getformatconfirm() {
      		if(confirm("Confirmez-vous le formatage ? (cela peut etre long)")) {
        		return true;
      		} else {
        		return false;
      		}
    	}
	function getlongconfirm() {
      		if(confirm("Cette operation peut etre longue...")) {
        		return true;
      		} else {
        		return false;
      		}
    	}

	function popuprecherche(page,nom,option){
		       window.open(page,nom,option);
        	}
		
	function Reporter(l) {
		var nouveau=l.options[l.options.selectedIndex].value;
		window.opener.document.forms["visu"].elements["nouveau"].value=nouveau;
	}	
		
  </script>
  <!-- Ajout MrT pour fonctions Ajax -->

<script type="text/javascript" src="/elements/js/prototype.js" ></script>

<!-- Fin MrT -->
</head>
<body>

<?php
print "<h3 align=\"right\">Bonjour $login (niveau ";
$intlevel=getintlevel();
switch ($intlevel) {
	case 1:
		echo gettext("D&#233;butant");
		break;
	case 2:
		echo gettext("Interm&#233;diaire");
		break;
	case 3:
		echo gettext("Confirm&#233;");
		break;
	case 4:
		echo gettext("Exp&#233;rimental");
		break;
}
echo ")</h3>";
//echo "request = ".$request."<br>\n";
} // Prise en compte de la page demandee initialement - leb 25/6/2005

?>
