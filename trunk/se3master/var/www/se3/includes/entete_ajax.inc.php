<?

 
@session_start();
$_SESSION["pageaide"]="Table_des_mati&#232;res";

require("config.inc.php");
require_once ("functions.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


$login=isauth();


// Prise en compte de la page demandee initialement - leb 25/6/2005
if (($login == "") || (ldap_get_right("se3_is_admin",$login)!="Y") )  {
	//	header("Location:$urlauth");
	$request = $PHP_SELF;
	if ( $_SERVER['QUERY_STRING'] != "") $request .= "?".$_SERVER['QUERY_STRING'];
	echo "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n";
	echo "top.location.href = '$urlauth?request=" . rawurlencode($request) . "';\n";
	echo "//-->\n</script>\n";
} 
?>