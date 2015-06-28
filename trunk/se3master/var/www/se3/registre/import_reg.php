<?php


   /**
   
   * Gestion des cles pour clients Windows (permet d'ajouter une cle dans la base)
   * @Version $Id$ 
   
  */	

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "include.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

echo "<h1>Importation des cl&#233;s</h1>";

// connexion();

if (ldap_get_right("computers_is_admin", $login) != "Y")
    die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");

// Aide
$_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

$act = $_POST['action'];
switch ($act) {
    default:
        break;

    case "file":
        if (isset($_POST['upload'])) { // si formulaire soumis
            if (file_exists("/tmp/import.reg"))
                unlink("/tmp/import.reg");
            $content_dir = '/tmp/'; // dossier ou sera deplace le fichier
            $tmp_file = $_FILES['fichier']['tmp_name'];

            if (!is_uploaded_file($tmp_file)) {
                exit(gettext("Le fichier est introuvable"));
            }
// on copie le fichier dans le dossier de destination
            $name_file = $_FILES['fichier']['name'];

            if (!move_uploaded_file($tmp_file, $content_dir . $name_file)) {
                exit(gettext("Impossible de copier le fichier dans") . " $content_dir");
            }
            $fichier_reg = $content_dir . $name_file;
            echo gettext("Le fichier") . " $name_file " . gettext("a bien &#233t&#233 upload&#233");
			print_nice(read_reg_file($content_dir . $name_file));

        }

        break;

    case "valid":
		echo "a faire";
        break;
}

function print_nice($elem,$max_level=10,$print_nice_stack=array()){
    if(is_array($elem) || is_object($elem)){
        if(in_array(&$elem,$print_nice_stack,true)){
            echo "<font color=red>RECURSION</font>";
            return;
        }
        $print_nice_stack[]=&$elem;
        if($max_level<1){
            echo "<font color=red>nivel maximo alcanzado</font>";
            return;
        }
        $max_level--;
        echo "<table border=1 cellspacing=0 cellpadding=3 width=100%>";
        if(is_array($elem)){
            echo '<tr><td colspan=2 style="background-color:#333333;"><strong><font color=white>CLE</font></strong></td></tr>';
        }else{
            echo '<tr><td colspan=2 style="background-color:#333333;"><strong>';
            echo '<font color=white>OBJECT Type: '.get_class($elem).'</font></strong></td></tr>';
        }
        $color=0;
        foreach($elem as $k => $v){
            if($max_level%2){
                $rgb=($color++%2)?"#888888":"#BBBBBB";
            }else{
                $rgb=($color++%2)?"#8888BB":"#BBBBFF";
            }
            echo '<tr><td valign="top" style="width:40px;background-color:'.$rgb.';">';
            echo '<strong>'.$k."</strong></td><td>";
            print_nice($v,$max_level,$print_nice_stack);
            echo "</td></tr>";
        }
        echo "</table>";
        return;
    }
    if($elem === null){
        echo "<font color=green>NULL</font>";
    }elseif($elem === 0){
        echo "0";
    }elseif($elem === true){
        echo "<font color=green>TRUE</font>";
    }elseif($elem === false){
        echo "<font color=green>FALSE</font>";
    }elseif($elem === ""){
        echo "<font color=green>EMPTY STRING</font>";
    }else{
        echo str_replace("\n","<br>\n",$elem);
    }
}


function read_reg_file($regfile)
{

$handle = fopen ($regfile,"r");
//echo "handle: " . $file . "<br>";
$row = 1;
unset($n);
$os = "TOUS";
$description = "";
$categorie = "appli";
$souscategorie = "";

while ((($data = fgets($handle, 1024)) !== FALSE) ) {

    $num = count($data);
    //echo "$num fields in line $row: $data <br>\n";

$reg_section = preg_replace("/\r/i", "", $data);   

if (preg_match("/^;categorie=(.+)$/", $reg_section, $res)) { 
	$categorie = $res[1];
} else if (preg_match("/^;souscategorie=(.+)$/", $reg_section, $res)) { 
	$souscategorie = $res[1];
} else if (preg_match("/^;description=(.+)$/", $reg_section, $res)) { 
	$description = $res[1];
} else if (preg_match("/^;os=(.+)$/", $reg_section, $res)) { 
	$os = $res[1];
} else if (preg_match("/^\[([^;\r\n]+)\]$/", $reg_section, $res)) { 
	$path = $res[1];
	unset($binary);
} else if (preg_match("/^(.+)=(?:(dword|he[^:]+|dword):|)(.+)$/", $reg_section, $res)) {  
	if (preg_match("/^(.+)\\\\$/", $res[3], $valeur)) {
		$binary = $valeur[1];
	} else {
		$binary = preg_replace("/^\"(.+)\"$/", "\\1", $res[3]);
	}	
	$n++;
	$cle[$n]['path']=$path;
	$cle[$n]['key'] = preg_replace("/^\"(.+)\"$/", "\\1", $res[1]);
	$cle[$n]['valeur'] = $binary;
	$cle[$n]['categorie'] = $categorie;
	$cle[$n]['souscategorie'] = $souscategorie;
	$cle[$n]['os'] = $os;
	$cle[$n]['description'] = $description;
	if ($res[2] == "dword") {
		$cle[$n]['type'] = "REG_DWORD";
	} else if ($res[2] == "hex") {
		$cle[$n]['type'] = "REG_BINARY";
	} else if ($res[2] == "hex(2)") {
		$cle[$n]['type'] = "REG_EXPAND_SZ";
		$cle[$n]['valeur'] = hexToStr($binary);
	} else if ($res[2] == "hex(7)") {
		$cle[$n]['type'] = "REG_MULTI_SZ";
		$cle[$n]['valeur'] = hexToStr($binary);
	} else {	
		$cle[$n]['type'] = "REG_SZ";
	}   
} else if (preg_match("/^\s+([A-Fa-f0-9,]+)(\\\\|)$/", $reg_section, $res)) {
	if ($res[2]) {
		$binary .= $res[1];
	} else if ($cle[$n]['type'] == "REG_BINARY") {	
		$cle[$n]['valeur'] = $binary.$res[1];
	} else {
		$cle[$n]['valeur'] = hexToStr($binary.$res[1]);
	}	
}
$row++;
} //end while 
fclose($handle);
return($cle);
}

function hexToStr($hexlist)
{
    $string='';
    foreach (preg_split("/,/", $hexlist) as $key=>$value)
    {
//  retour a la ligne
	if ($value == "00") { $value = "0a"; }        
	$string .= chr(hexdec($value));
    }
    return $string;
}


retour();

include("pdp.inc.php");
?>
