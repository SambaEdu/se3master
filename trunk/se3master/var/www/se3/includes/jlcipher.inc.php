<?php


   /**
   * Librairie de fonctions pour cryptage
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe Tice academie de Caen
   * @Auteurs « jLCF >:> » jean-luc.chretien@tice.ac-caen.fr

   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: jlcipher.inc.php
   * @Repertoire: includes/ 
   */  
  


// Constantes j-LCipher
$MaxLifeTime = "5"; /* seconde */

# Messages d'erreur  j-LCipher  loges dans /var/log/se3/auth.log
$MsgError[1]="Possible spoof IP source address";
$MsgError[2]="MaxTimeLife expire";
$MsgError[3]=$MsgError[1]." and ".$MsgError[2];
$MsgError[4]="Authentification error";

$logpath ="/var/log/se3/";



//=================================================

/**
* Affiche de la partie cryptage

* @Parametres 
* @Return 
*/

function header_crypto_html( $titre,$path)
{
global $login;
        ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
        <HEAD>
	<META http-equiv="pragma" content="no-cache" />
                <TITLE><?php echo $titre ?></TITLE>
				<link type="text/css" rel="stylesheet" href="/elements/style_sheets/sambaedu.css" />
                <style type="text/css">
                        body{
                                background: url(/elements/images/fond_SE3.png) ghostwhite bottom right no-repeat fixed;
                        }
                </style>
                <script language = 'javascript' type = 'text/javascript' src="<?php echo $path?>crypto.js"></script>
                <script language = 'javascript' type = 'text/javascript' src="<?php echo $path?>public_key.js"></script>
                <script language = 'javascript' type = 'text/javascript'>
                        <!--
                        function auth_popup() {
                                window.focus();
                                auth_popupWin = window.open("<?php echo $path?>auth_se3.html","auth_se3","width=600,height=400,resizable=no,scrollbars=no,toolbar=no,menubar=no,status=no");
                                auth_popupWin.focus();
                        }
                <?php if ( preg_match("/Authentification/i", $titre)) { ?>
                        function encrypt(f) {
                                var endTime=new Date();
                                f.time.value =  (endTime.getTime()-startTime.getTime())/1000.0;
                                encode = f.dummy.value+"|"+f.client_ip.value+"|"+f.timestamp.value+"|"+f.time.value;
                                f.string_auth.value=rsaEncode(public_key_e,public_key_pq,encode);
                                f.dummy.value="******";
                                f.timestamp.value="";
                                f.time.value="";
                        }

                        function timerStart() {
                                startTime=new Date();
                        }

                        timerStart();
                <?php } else { ?>

                        function encrypt(f) {
                                if (f.dummy.value!="") {
                                        f.string_auth.value=rsaEncode(public_key_e,public_key_pq,f.dummy.value);
                                        f.dummy.value="******";
                                }
                                if (f.dummy1.value!="") {
                                        f.string_auth1.value=rsaEncode(public_key_e,public_key_pq,f.dummy1.value);
                                        f.dummy1.value="******";
                                }
                        }

                <?php } ?>

                        // -->
                </script>
        </HEAD>
        <BODY>
<?php
print "<H3 ALIGN=RIGHT>Bonjour $login</H3>";
}



//=================================================

/**
* Affiche le lien actif ou inactif pour le cryptage

* @Parametres
* @Return
*/

function crypto_nav($path)
{
         global   $HTTP_USER_AGENT;
        // Affichage logo crypto
        if (preg_match("/Mozilla/4.7/i", $HTTP_USER_AGENT)) {
                echo " <a HREF='".$path."html' onClick='auth_popup(); return false' TARGET='_blank'><img src='".$path."elements/images/no_crypto.png' alt='".gettext("Attention, avec ce navigateur votre mot de passe va circuler en clair sur le r&#233;seau !")."' width=48 height=48 border=0></a>";
        } else {
                echo " <a HREF='".$path."auth_se3.html' onClick='auth_popup(); return false' TARGET='_blank'><img src='".$path."elements/images/crypto.png' alt='".gettext("Cryptage du mot de passe actif !")."' width=48 height=48 border=0></a>";
        }
}



//=================================================

/**
* Retourne l'IP distante

* @Parametres
* @Return
*/

function remote_ip()
{
      if(getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
      } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
      } else {
        $ip = getenv("REMOTE_ADDR");
      }
      return $ip;
}



//=================================================

/**
* Decode le mot de passe

* @Parametres
* @Return
*/

function decode_pass($string_auth) {
        global  $MaxLifeTime,$path_to_wwwse3;
        // Decodage de la chaine d'authentification cote serveur avec une cle privee
        exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth'",$AllOutPut,$ReturnValue);
        // Extraction des parametres
        $tmp = preg_split ("/[\|\]/",$AllOutPut[0],4);
        $passwd = $tmp[0];
        $ip_src = $tmp[1];
        $timestamp = $tmp[2];
        $timewait = $tmp[3];
        $timetotal= $timewait+$timestamp+$MaxLifeTime;
        #echo  " MaxLifeTime: $MaxLifeTime ipsrc:$ip_src  timestamp:$timestamp time :".time()."timetotal:$timetotal pass:$passwd";
        // Interpretation des resultats
                if ( $ip_src != remote_ip() && time() <  $timetotal ) {
                        $error = 1;
                } elseif   ( time() >  $timetotal && $ip_src == remote_ip() ) {
                        $error = 2;
                }  elseif ( $ip_src != remote_ip()   &&   time() >  $timetotal ) {
                        $error = 3;
                }
        return array ($passwd, $error,$ip_src,$timetotal);
}



//=================================================

/**
* Detecte la cle

* @Parametres
* @Return
*/

function detect_key_orig()
{
        $myFile = file( "public_key.js");
        if ( preg_match("/19281203,140977887,4051811,156855586,32904/i",$myFile[1])) return true;  else return false;
}

?>
