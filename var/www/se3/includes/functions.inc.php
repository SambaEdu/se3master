<?php

   /**
   * Librairie de fonctions utilisees dans l'interface d'administration

   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @Auteurs Equipe Tice academie de Caen
   * @Auteurs oluve olivier.le_monnier@crdp.ac-caen.fr

   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: functions.inc.php
   * @Repertoire: includes/
   */





//=================================================

/**
* Affichage du menu lateral (OBSOLETE)

* @Parametres $login
* @Return
*/

function menuprint($login) {
    global $liens,$menu;
    for ($idmenu=0; $idmenu<count($liens); $idmenu++)
    {
        echo "<div id=\"menu$idmenu\" style=\"position:absolute; left:10px; top:12px; width:200px; z-index:" . $idmenu ." ";
        if ($idmenu!=$menu) {
            echo "; visibility: hidden";
        }
        echo "\">\n";

        echo "
        <table width=\"200\" border=\"0\" cellspacing=\"3\" cellpadding=\"6\">\n";
        $ldapright["se3_is_admin"]=ldap_get_right("se3_is_admin",$login);
    $getintlevel = getintlevel();
        for ($menunbr=1; $menunbr<count($liens); $menunbr++)
        {
        // Test des droits pour affichage
            #if ($menunbr==1) $menutarget="_top";
            #else $menutarget="main";
            $menutarget="main";
            $afftest=$ldapright["se3_is_admin"]=="Y";
            $rightname=$liens[$menunbr][1];
            $level=$liens[$menunbr][2];
            if (($rightname=="") or ($afftest)) $afftest=1==1;
            else {
                if ($ldapright[$rightname]=="") $ldapright[$rightname]=ldap_get_right($rightname,$login);
                $afftest=($ldapright[$rightname]=="Y");
            }
            if ($level > $getintlevel) $afftest=0;
            if ($afftest)
            if (($idmenu==$menunbr)&&($idmenu!=0)) {
                echo "
                <tr>
                    <td class=\"menuheader\">
                        <p style='margin:2px; padding-top:2px; padding-bottom:2px'><a href=\"javascript:;\" onClick=\"P7_autoLayers('menu0');return false\"><img src=\"elements/images/arrow-up.png\" width=\"20\" height=\"12\" border=\"0\" alt=\"Up\"></a>
                        <a href=\"javascript:;\" onClick=\"P7_autoLayers('menu" . $menunbr .  "');return false\">" . $liens[$menunbr][0] . "</a></p>
                    </td>
                    </tr>
                    <tr>
                    <td class=\"menucell\">";
                for ($i=3; $i<count($liens[$menunbr]); $i+=4) {
                    // Test des droits pour affichage
                    $afftest=$ldapright["se3_is_admin"]=="Y";
                    $rightname=$liens[$menunbr][$i+2];
                    $level=$liens[$menunbr][$i+3];
                    if (($rightname=="") or ($afftest)) $afftest=1==1;
                    else {
                        if ($ldapright[$rightname]=="") $ldapright[$rightname]=ldap_get_right($rightname,$login);
                        $afftest=($ldapright[$rightname]=="Y");
                    }
                    if ($level > $getintlevel ) $afftest=0;
                    if ($afftest) {
                    	echo "<img src=\"elements/images/typebullet.png\" width=\"30\" height=\"11\" alt=\"\">";
		    	// Traite yala pour ne pas avoir deux target
		    	if (preg_match("/yala/i",$liens[$menunbr][$i+1])) {
                        	echo "<a href=\"" . $liens[$menunbr][$i+1] . "\">" . $liens[$menunbr][$i]  . "</a><br>\n";
                   	} else {
                        	echo "<a href=\"" . $liens[$menunbr][$i+1] . "\" TARGET='$menutarget'>" . $liens[$menunbr][$i]  . "</a><br>\n";
		   	}
		   }
                } // for i : bouche d'affichage des entrees de sous-menu
                echo "
                    </td></tr>\n";
            } else
            {
                echo "
                <tr>
                    <td class=\"menuheader\">
                    <p style='margin:2px; padding-top:2px; padding-bottom:2px'><a href=\"javascript:;\" onClick=\"P7_autoLayers('menu" . $menunbr .  "');return false\">
                    <img src=\"elements/images/arrow-down.png\" width=\"20\" height=\"12\" border=\"0\" alt=\"Down\">". $liens[$menunbr][0] ."</a></p>
                    </td></tr>\n";
            }
        } //for menunbr : boucle d'affichage des entrees de menu principales

        echo "
        </table>
</div>\n";
    } // for idmenu : boucle d'affichage des differents calques
} // function menuprint




//=================================================

/**
* Affichage d'un cadre de texte

* @Parametres
* @Return
*/

/*
function mktable ($title, $content)
{
    global $HEADERCOLOR, $BACKCOLOR;

    echo "<table border='0' cellpadding='0' cellspacing='0' bgcolor='#000000' align= 'center' width='70%'>\n";
    echo "<TR><TD>\n";
    echo "<table width='100%' border='0' cellspacing='1' cellpadding='1'>\n";
//    echo "<TR><TD  bgcolor=$HEADERCOLOR>\n";
    echo "<TR><TD CLASS=\"menuheader\">\n";
    echo "<DIV CLASS='titre'>$title</DIV>\n";
//    echo "</td></tr><tr><td colspan='1' bgcolor='$BACKCOLOR'>\n";
    echo "</td></tr><tr><td CLASS=\"menucell\">\n";
    echo "<table border='0' cellspacing='0' cellpadding='2' width='100%'>\n";
    echo "<TR><TD>\n";
    echo "$content\n";
    echo "<BR></TD></TR>\n";
    echo "</table>\n";
    echo "</TD></TR>\n";
    echo "</table>\n";
    echo "</TD></TR>\n";
    echo "</table>\n";
}*/

function mktable ($title, $content)
{
    echo "<H3>$title</H3>";
    echo $content;
}


//=================================================

/**
* Affichage du menu d'aide (OBSOLETE)

* @Parametres
* @Return
*/

function mkhelp($titre,$content)
{
  $fp=fopen("/tmp/se3help.txt","w");
  if($fp) {
    fputs($fp,"<H1>$titre</H1>\n");
    fputs($fp,$content);
    fclose($fp);
  }
}


//=================================================

/**
* Retourne le nom de id

* @Parametres
* @Return
*/

function dispfield($id, $table, $field)
{
    if ($id):
    /* Renvoie le nom de id */
        $result=mysql_query("SELECT $field FROM $table WHERE id=$id");
        if ($result && mysql_num_rows($result)):
            $nom=mysql_result($result,0,0);
            mysql_free_result($result);
        else:
            $nom="";
        endif;
        return $nom;
    endif;
}


//=================================================

/**
*

* @Parametres
* @Return
*/

function listoptions($table,$sel)
{
    $res = "";
    $result=mysql_query("SELECT id, nom FROM $table");
    if ($result && mysql_num_rows($result)):
        while ($r=mysql_fetch_row($result))
            {
                $res .= "<OPTION VALUE=\"$r[0]\"";
                if ($r[0]==$sel)
                    $res .= " SELECTED";
                $res .= ">$r[1]</OPTION>\n";
            }
        mysql_free_result($result);
    endif;
    return $res;
}


//=================================================

/**
*

* @Parametres
* @Return
*/

function dispstats($idpers)
{
    global $authlink, $DBAUTH;

    if ($idpers):
        /* Renvoie le nombre de connexions */
	mysql_select_db('$DBAUTH',$authlink);
        $result=mysql_query("SELECT stat FROM personne WHERE id=$idpers", $authlink);
    if ($result && mysql_num_rows($result)):
        $stat=mysql_result($result,0,0);
    mysql_free_result($result);
    else:
    $stat="0";
    endif;
    return $stat;
    endif;
}


//=================================================

/**
*

* @Parametres
* @Return
*/

function displogin ($idpers)
{
    global $authlink, $DBAUTH;

    if ($idpers):
        /* Renvoie le timestamp du dernier login */
	mysql_select_db($DBAUTH , $authlink);
        $result=mysql_query("SELECT date_format(last_log,'%e %m %Y à %T' ) FROM personne WHERE id=$idpers", $authlink);
    if ($result && mysql_num_rows($result)):
        $der_log=mysql_result($result,0,0);
    mysql_free_result($result);
    else:
    $der_log="";
    endif;
    return $der_log;
    endif;
}

#
# Fonctions relatives a la session
#

//=================================================

/**
* Test si on est authentifie

* @Parametres
* @Return Si non, renvoie "" -  Si oui, renvoie l'uid de la personne

*/

function isauth()
{
    /* Teste si une authentification est faite
                - Si non, renvoie ""
                - Si oui, renvoie l'uid de la personne
    */
    global $HTTP_COOKIE_VARS, $authlink;
    if ( ! empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $result=mysql_query("SELECT login FROM sessions WHERE sess='$sess'", $authlink);
        if ($result && mysql_num_rows($result)):
               $login=mysql_result($result,0,0);
            mysql_free_result($result);
        endif;
    endif;
    return $login;
}


//=================================================

/**
* Fabrique un N° de session aleatoire

* @Parametres
* @Return N° de session
*/


function mksessid()
{
    /* Fabrique un N° de session aleatoire */
    global $Pool, $SessLen;

    $count=10;
    do
    {
        $sid="";
        $count--;
        for ($i = 0; $i < $SessLen ; $i++)
            $sid .= substr($Pool, (mt_rand()%(strlen($Pool))),1);
        $query="SELECT id FROM sessions WHERE sess='$sid'";
        $result=mysql_query($query);
        $res=mysql_num_rows($result);
    }
    while ($res>0 && $count);
    return $sid;
}


//=================================================

/**
* Verifie le login et le mot de passe sur l'annuaire ldap et ouvre une session en cas de succes

* @Parametres
* @Return
*/

function open_session($login, $passwd,$al)
{
    global $urlauth, $authlink, $secook;
    global  $dbhost, $dbuser, $dbpass, $autologon, $REMOTE_ADDR;
    global $MsgError,$logpath,$defaultintlevel,$smbversion;
    $res=0;
    $loginauto="";
    if (($al!=1)&&("$autologon"=="1")){

		$logintstsecu=exec("smbstatus -p | grep \"(".$_SERVER['REMOTE_ADDR'].")\" | grep -v root | grep -v nobody | grep -v adminse3  | grep -v unattend | wc -l");
		if ("$logintstsecu" == "1") {
			$loginauto=exec("smbstatus -p |gawk '{if ($5==\"(".$_SERVER['REMOTE_ADDR'].")\") if ( ! index(\" root nobody unattend adminse3 \", \" \" $2 \" \")) {print $2;exit}}'");
		}

        # echo $loginauto . " __ smbstatus | grep $REMOTE_ADDR | grep home\  | head -n 1 | gawk -F' ' '{print $2}'";
        //$loginauto=exec("smbstatus | grep \"".$REMOTE_ADDR ."\" | head -n 1 | gawk -F' ' '{print $2}'");
        if ("$loginauto" != "") {
            $auth_ldap=1;
            $login=$loginauto;
        }
        //echo "-->";
    }
    if ($auth_ldap!=1) {
                        // decryptage du mot de passe
                        list ($passwd, $error,$ip_src,$timetotal) = decode_pass($passwd);
                        // Si le decodage ne comporte pas d'erreur
                        if (!$error) {
                                $auth_ldap = user_valid_passwd ( $login ,  $passwd);
                                if (!$auth_ldap) $error=4;
                        }
                        if ($error) {
                                // Log en cas d'echec
                                $fp=fopen($logpath."auth.log","a");
                                if($fp) {
                                        fputs($fp,"[".$MsgError[$error]."] ".date("j/m/y:H:i")."|ip requete : ".$ip_src."|remote ip : ".remote_ip()."|Login : ".$login."|TimeStamp srv : ".time()."|TimeTotal : ".$timetotal."\n");
                                        fclose($fp);
                                }
                        }
    }
    if ($auth_ldap) :
    $sessid=mksessid();
    setcookie("SambaEdu3", "$sessid", 0,"/","",$secook);
    $encode_pass = "secret";
    $result=mysql_query("INSERT INTO sessions  VALUES ('', '$sessid', '$encode_pass', '$login',0,$defaultintlevel)", $authlink);
    $res=1;
    endif;
    return $res;
}


//=================================================

/**
* Ferme la session en cours

* @Parametres
* @Return
*/

function close_session()
{
    /* Ferme la session en cours */
    global $HTTP_COOKIE_VARS, $authlink, $secook;
    if (empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $login="";
    else:
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $result=mysql_query("DELETE FROM sessions WHERE sess='$sess'", $authlink);
        setcookie ("SambaEdu3", "", 0,"/","",$secook);
    endif;
}


//=================================================

/**
* Lis l'etat du flag help dans la table session (OBSOLETE)

* @Parametres
* @Return
*/


/*
function readhelp()
{
    // Lis l'etat du flag help dans la table session
    global $HTTP_COOKIE_VARS, $authlink;
    $ret=0;
    if (! empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $result = mysql_query("SELECT help FROM sessions WHERE sess='$sess'",$authlink);
        if ($result && mysql_num_rows($result)):
            $ret=mysql_result($result,0,0);
            mysql_free_result($result);
        endif;
    endif;
    return $ret;
}
*/


//=================================================

/**
* Change l'etat du flag help dans la table session (OBSOLETE)

* @Parametres
* @Return
*/

/*
function changehelp()
{
    // Change l'etat du flag help dans la table session
    global $HTTP_COOKIE_VARS, $authlink;

    $ret=0;
    if (! empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $query="SELECT help FROM sessions WHERE sess='$sess'";
        $result = mysql_query($query,$authlink);
        if ($result):
            $ret=mysql_result($result,0,0);
            mysql_free_result($result);
            if ($ret==0) $ret=1; else $ret=0;
            $result = mysql_query("UPDATE sessions SET help=$ret WHERE sess='$sess'",$authlink);
        endif;
    endif;
    return $ret;
}
*/


//=================================================

/**
* Retourne le niveau de l'interface

* @Parametres
* @Return
*/

function getintlevel()
{
    /* Lis le niveau d'interface dans la table session */
    global $HTTP_COOKIE_VARS, $authlink;
    $ret=0;
    if (! empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $result = mysql_query("SELECT intlevel FROM sessions WHERE sess='$sess'",$authlink);
        if ($result && mysql_num_rows($result)):
            $ret=mysql_result($result,0,0);
            mysql_free_result($result);
        endif;
    endif;
    return $ret;
}


//=================================================

/**
* Change le niveau d'interface dans la table session

* @Parametres
* @Return
*/

function setintlevel($new_level)
{
    global $HTTP_COOKIE_VARS, $authlink;

    if (! empty($HTTP_COOKIE_VARS["SambaEdu3"])):
        $sess=$HTTP_COOKIE_VARS["SambaEdu3"];
        $result = mysql_query("UPDATE sessions SET intlevel=$new_level WHERE sess='$sess'",$authlink);
    if (!$result) echo "Erreur d'ecriture dans la table sessions\n";
    endif;
}

#
# Fonctions utilitaires diverses
#

$offsets = array ( 31, 41, 59, 26, 54 );

//=================================================

/**
* Retourne $val en hexa

* @Parametres
* @Return
*/

function hexa ( $val ) {
  if ( empty ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case "0": return 0;
    case "1": return 1;
    case "2": return 2;
    case "3": return 3;
    case "4": return 4;
    case "5": return 5;
    case "6": return 6;
    case "7": return 7;
    case "8": return 8;
    case "9": return 9;
    case "A": return 10;
    case "B": return 11;
    case "C": return 12;
    case "D": return 13;
    case "E": return 14;
    case "F": return 15;
  }
  return 0;
}



//=================================================

/**
* decode

* @Parametres
* @Return

* Extract a user's name from a session id
* This is a lame attempt at security.  Otherwise, users would be
* able to edit their cookies.txt file and set the username in plain
* text.
* $instr is a hex-encoded string. "Hello" would be "678ea786a5".

*/

function decode ( $instr ) {
    global $offsets;
    $orig = "";
    for ( $i = 0; $i < strlen ( $instr ); $i += 2 ) {
        $ch1 = substr ( $instr, $i, 1 );
        $ch2 = substr ( $instr, $i + 1, 1 );
        $val = hexa ( $ch1 ) * 16 + hexa ( $ch2 );
        $j = ( $i / 2 ) % count ( $offsets );
        $newval1 = $val - $offsets[$j] + 256;
        $newval1 %= 256;
        $dec_ch = chr ( $newval1 );
        $orig .= $dec_ch;
    }
    return $orig;
}



//=================================================

/**
* Take an input string and encoded it into a slightly encoded hexval, that we can use as a session cookie.

* @Parametres
* @Return
*/

function encode ( $instr ) {
    global $offsets;
    $ret = "";
    for ( $i = 0; $i < strlen ( $instr ); $i++ ) {
        $ch1 = substr ( $instr, $i, 1 );
        $val = ord ( $ch1 );
        $j = $i % count ( $offsets );
        $newval = $val + $offsets[$j];
        $newval %= 256;
        $ret .= bin2hex ( chr ( $newval ) );
    }
    return $ret;
}


//=================================================

/**
* Affiche le formulaire des parametres correspondant a la categorie $cat

* @Parametres
* @Return
*/

function aff_param_form($cat)
{
    $texte_form="<TABLE BORDER=\"1\">";
    $result=mysql_query("SELECT * from params WHERE cat=$cat");
    if ($result) {
        while ($r=mysql_fetch_array($result)) {
            $texte_form .= "<TR><TD COLSPAN=\"2\">".$r["descr"]." (<EM><FONT color=\"red\">".$r["name"]."</FONT></EM>)</TD>";
            $texte_form .= "<TD><INPUT TYPE=\"text\" SIZE=\"25\" VALUE=\"".$r["value"]."\" NAME=\"form_".$r["name"]."\"></TD></TR>\n";
        }
    }
    $texte_form .= "</TABLE>";
    return $texte_form;
}



//
// Fonctions relatives a lannuaire LDAP
//



//=================================================

/**
* Verification du mot de passe d'un utilisateur

* @Parametres
* @Return  true si le mot de passe est valide, false dans les autres cas
*/

function user_valid_passwd ( $login, $password ) {
  global $ldap_server, $ldap_port, $dn;

  $filter = "(userpassword=*)";
  $ret = false;
  $DEBUG = false;

  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = @ldap_bind ( $ds,"uid=".$login.",".$dn["people"] , $password );
    if ( $r ) {
      $read_result=@ldap_read ($ds, "uid=".$login.",".$dn["people"], $filter);
      if ($read_result) {
        $entrees = @ldap_get_entries($ds,$read_result);
        if ($entrees[0]["userpassword"][0]) {
          $ret= true;
        } else {
          $error = gettext("Mot de passe invalide");
        }
      } else {
        $error = gettext("Login invalide");
      }
    } else {
      $error = gettext("L'Authentification a \xe9chou\xe9e");
    }
    @ldap_unbind ($ds);
    @ldap_close ($ds);
  } else {
    $error = gettext("Erreur de connection au serveur LDAP");
  }
  if ($DEBUG) echo "$error<BR>\n";
  return $ret;
}



//=================================================

/**
*  Function to search the dn of a given user

* @Parametres $login - user login
* @Parametres $dn - complete dn for the user (must be given by ref )
* @Return TRUE if the user is found, FALSE in other case
*/


function user_search_dn ( $login ,$dn ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr,$peopleRdn;
  global $adminDn,$adminPw;

  $ret = false;
      echo $peopleDn;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ( $adminDn != "") {
      $r = @ldap_bind ( $ds, $adminDn, $adminPw ); // bind as administrator

    } else {
      $r = @ldap_bind ( $ds ); // bind as anonymous
    }
    if (!$r) {
      $error = "Invalid Admin's login for LDAP Server";
    } else {
      $sr = @ldap_search ( $ds, "$peopleRdn,$ldap_base_dn", "($ldap_login_attr=$login)");
      if (!$sr) {
        $error = "Error searching LDAP server: " . ldap_error($ds);
      } else {
        $info = @ldap_get_entries ( $ds, $sr );
        if ( $info["count"] != 1 ) {
         $error = "Invalid login";
        } else {
          $ret = true;
          $dn = $info[0]["dn"];
          // echo "Found dn : $dn\n";
        }
        @ldap_free_result ( $sr );
      }
      @ldap_close ( $ds );
    }
  } else {
    $error = "Error connecting to LDAP server";
    $ret = false;
  }
  return $ret;
}


//=================================================

/**
* Recherche si $nom est present dans le droit $type

* @Parametres
* @Return
*/

function ldap_get_right_search ($type,$search_filter,$ldap)
{
    global $dn,$login;
    $ret="N";
    $typearr=explode("|","$type");
    $i=0;
    while (($ret=="N") and ($i < count($typearr))) {
      $base_search="cn=".$typearr[$i]."," . $dn["rights"];
      $search_attributes=array("cn");
      $result = @ldap_read($ldap, $base_search, $search_filter, $search_attributes);
      if ($result) {
        if (ldap_count_entries ($ldap,$result) == 1) $ret="Y";
        ldap_free_result($result);
      } else {
    // Analyse pour les membres d'un groupe
        $base_search="cn=".$typearr[$i]."," . $dn["groups"];
        $result = @ldap_read($ldap, $base_search, "memberUid=$login", $search_attributes);
        if ($result) {
          if (ldap_count_entries ($ldap,$result) == 1) $ret="Y";
          ldap_free_result($result);
        }
      }
      $i++;
  }
    // echo "recherche $type $search_filter ==> $ret<BR>";
    return $ret;
}


//=================================================

/**
* Met a jour un parametre dans la table params de la base SQL

* @Parametres
* @Return
*/

function setparam($name,$value)
{
        $query="UPDATE params SET value=\"$value\" WHERE name=\"$name\"";
        $result=mysql_query($query);
        if (!$result) print gettext("oops: la requete "). "<STRONG>$query</STRONG>" . gettext(" a provoqu&#233; une erreur");
}



//=================================================

/**
* Detrmine si $login a le droit $type

* @Parametres
* @Return
*/

function ldap_get_right($type,$login)
{
    global $ldap_server, $ldap_port, $adminDn, $adminPw, $dn;

    $nom="uid=" . $login . "," . $dn["people"];
    $ret="N";
    $ldap = ldap_connect ($ldap_server, $ldap_port);
    if ( !$ldap ) {
        echo "Error connecting to LDAP server";
    } else {
        if ( $adminDn != "") {
            $r = ldap_bind ( $ldap, $adminDn, $adminPw );     // bind as administrator
        } else {
            $r = ldap_bind ( $ldap ); // bind as anonymous
        }
        if (!$r) {
            echo "Invalid Admin's login for LDAP Server";
        } else {

            // Recherche du nom exact
            $search_filter = "(member=$nom)";
            $ret=ldap_get_right_search ($type,$search_filter,$ldap,$base_search);
            if ($ret=="N") {
            // Recherche sur les Posixgroups d'appartenance
                $result1 = @ldap_list ( $ldap, $dn["groups"], "memberUid=$login", array ("cn") );
                if ($result1) {
                $info = @ldap_get_entries ( $ldap, $result1 );
                   if ( $info["count"]) {
                    $loop=0;
                    while (($loop < $info["count"]) && ($ret=="N")){
                        $search_filter = "(member=cn=".$info[$loop]["cn"][0].",".$dn["groups"].")";
                        $ret=ldap_get_right_search ($type,$search_filter,$ldap,$base_search,$search_attributes);
                        $loop++;
                    }
                }
                @ldap_free_result ( $result1 );
                }
            }
            if ($ret=="N") {
            // Recherche sur les GroupsOfNames d'appartenance
                $result1 = @ldap_list ( $ldap, $dn["groups"], "member=uid=$login,".$dn["people"], array ("cn") );
                if ($result1) {
                $info = @ldap_get_entries ( $ldap, $result1 );
                   if ( $info["count"]) {
                    $loop=0;
                    while (($loop < $info["count"]) && ($ret=="N")){
                        $search_filter = "(member=cn=".$info[$loop]["cn"][0].",".$dn["groups"].")";
                        $ret=ldap_get_right_search ($type,$search_filter,$ldap,$base_search,$search_attributes);
                        $loop++;
                    }
                }
                @ldap_free_result ( $result1 );
                }
            }
        }
    ldap_close ($ldap);
    }
    return $ret;
}


/*
/** Fonctions liees a la creation de partages, de ressources...
/*



//=================================================

/**
* Recherche de la nature mono ou multi serveur de la plateforme SE3

* @Parametres Retourne true si "mono seveur" cad un seul serveur maitre
* @Parametres Retourne false si "multi-serveur" cad 1 serveur maitre associe a 1 ou des serveurs esclaves
* @Return
*/

function mono_srv () {
    $master=search_machines ("(l=maitre)", "computers");
        $slaves= search_machines ("(l=esclave)", "computers");
        if ( count($master) == 0 ) {
            echo gettext("<P>ERREUR : Il n'y a pas de serveur maitre d&#233;clar&#233; dans l'annuaire ! <BR>Veuillez contacter le super utilisateur du serveur SE3.</P>");
        } elseif (  count($master) == 1  && count($slaves) == 0 ) {
            // Plateforme mono-serveur
        return true;
        } elseif (  count($master) == 1  && count($slaves) > 0  ) {
        return false;
        }
}



//=================================================

/**
* Affiche les info-bulles si le champ infobul_activ est a 1

* @Parametres
* @Return
*/

function aide($texte_aide,$caption="?") { //Affiche les info-bulles si le champ infobul_activ est a 1
	global $infobul_activ;
	if (!$texte_aide) { return false ;} else {
		if ($infobul_activ=="1") {  return "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('$texte_aide')\">$caption</u>";

		} else { return $caption;}
	}
}



//=================================================

/**
* permet de savoir si ce parc est delegue  a ce login pour le niveau donne

* @Parametres
* @Return
*/

function this_parc_delegate($login,$parc,$niveau)
{
	require "config.inc.php";
	$authlink_delegate = @mysql_connect($dbhost,$dbuser,$dbpass);
	@mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");
	$query_delegate="SELECT `parc` FROM `delegation` WHERE `login`='$login' and `parc`='$parc' and `niveau`='$niveau';";
	
	$result_delegate=mysql_query($query_delegate,$authlink_delegate);
	if ($result_delegate) {
		$ligne_delegate=mysql_num_rows($result_delegate);
		if ($ligne_delegate>0) { return true; } else { return false;}
	} else { return false;}
	mysql_close($authlink_delegate);
}


//=================================================

/**
* donne la liste des parcs delegue pour le login donne

* @Parametres
* @Return
*/

function list_parc_delegate($login)
{
require "config.inc.php";

$authlink_delegate = @mysql_connect($dbhost,$dbuser,$dbpass);
@mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");

   $query="select parc from delegation where login='$login';";
   $list_delegate=array();
    $result= mysql_query($query);
    if ($result)
    { $ligne= mysql_num_rows($result);
      if ($ligne>0)
         {
            while ($row=mysql_fetch_row($result))
          {
          array_push($list_delegate,$row[0]);
         // echo $row[0];
          }
          }
     }
     sort($list_delegate);
return $list_delegate;
mysql_close($authlink_delegate);

}


//=================================================

/**
* permet de savoir si une machine est dans un des parcs delegue pour le login donne

* @Parametres
* @Return
*/

function in_parc_delegate($login,$machine)
{
	$list_parc_user=list_parc_delegate($login);
	$list_parc_machine=search_parcs($machine);
	for($i=0;$i<count($list_parc_machine);$i++) {
		if  (in_array($list_parc_machine[$i]["cn"],$list_parc_user)) { /*echo "test de ".$list_parc_machine[$i]["cn"]." ok";*/ $test++; break; } else { /*echo "test de ".$list_parc_machine[$i]["cn"]." non";*/ }
        }
	if ($test) { /*echo "cette machine fait partie des machines d&#233;l&#233;gu&#233;s";*/ return true; } else { return false; }
}



//=================================================

/**
* Retourne le niveau de delegation en fonction du login et du nom du parc

* @Parametres $login et $parc
* @Return niveau de delegation
*/

function niveau_parc_delegate($login,$parc)
{
	require "config.inc.php";

	$authlink_delegate = @mysql_connect($dbhost,$dbuser,$dbpass);
	@mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");

   	$query="select niveau  from delegation where login='$login' and parc='$parc';";
    	$result= mysql_query($query);
    	if ($result) {
		$ligne= mysql_num_rows($result);
      		if ($ligne>0) {
            		while ($row=mysql_fetch_row($result)) {
          			$niveau_delegate = $row[0];
         			// echo $row[0];
          		}
          	}
     	}

	return $niveau_delegate;
	mysql_close($authlink_delegate);

}


/**
* Fonction destinee a afficher les variables transmises d'une page à l'autre: GET, POST et SESSION

* @Parametres
* @Return
*/
function debug_var(){
	// Fonction destinee a afficher les variables transmises d'une page à l'autre: GET, POST et SESSION
	echo "<div style='border: 1px solid black; background-color: white; color: black;'>\n";

	$cpt_debug=0;

	echo "<p><strong>Variables transmises en POST, GET, SESSION,...</strong> (<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)</p>\n";

	echo "<div id='container_debug_var_$cpt_debug'>\n";
	$cpt_debug++;

	echo "<p>Variables envoyees en POST: ";
	if(count($_POST)==0) {
		echo "aucune";
	}
	else {
		echo "(<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)";
	}
	echo "</p>\n";
	echo "<blockquote>\n";
	echo "<div id='container_debug_var_$cpt_debug'>\n";
	$cpt_debug++;

	echo "<script type='text/javascript'>
	tab_etat_debug_var=new Array();

	function affiche_debug_var(id,mode) {
		if(document.getElementById(id)) {
			if(mode==1) {
				document.getElementById(id).style.display='';
			}
			else {
				document.getElementById(id).style.display='none';
			}
		}
	}
</script>\n";

	echo "<table summary=\"Tableau de debug\">\n";
	foreach($_POST as $post => $val){
		//echo "\$_POST['".$post."']=".$val."<br />\n";
		//echo "<tr><td>\$_POST['".$post."']=</td><td>".$val."</td></tr>\n";
		echo "<tr><td valign='top'>\$_POST['".$post."']=</td><td>".$val;

		if(is_array($_POST[$post])) {
			echo " (<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)";
			echo "<table id='container_debug_var_$cpt_debug' summary=\"Tableau de debug\">\n";
			foreach($_POST[$post] as $key => $value) {
				echo "<tr><td>\$_POST['$post'][$key]=</td><td>$value</td></tr>\n";
			}
			echo "</table>\n";
			//echo "<script type='text/javascript'>affiche_debug_var('debug_var_$post',tab_etat_debug_var[$cpt_debug]);</script>\n";
			$cpt_debug++;
		}

		echo "</td></tr>\n";
	}
	echo "</table>\n";
	echo "</div>\n";
	echo "</blockquote>\n";


	echo "<p>Variables envoyees en GET: ";
	if(count($_GET)==0) {
		echo "aucune";
	}
	else {
		echo "(<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)";
	}
	echo "</p>\n";
	echo "<blockquote>\n";
	echo "<div id='container_debug_var_$cpt_debug'>\n";
	$cpt_debug++;
	echo "<table summary=\"Tableau de debug sur GET\">";
	foreach($_GET as $get => $val){
		//echo "\$_GET['".$get."']=".$val."<br />\n";
		echo "<tr><td>\$_GET['".$get."']=</td><td>".$val."</td></tr>\n";
	}
	echo "</table>\n";
	echo "</div>\n";
	echo "</blockquote>\n";


	echo "<p>Variables envoyees en SESSION: ";
	if(count($_SESSION)==0) {
		echo "aucune";
	}
	else {
		echo "(<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)";
	}
	echo "</p>\n";
	echo "<blockquote>\n";
	echo "<div id='container_debug_var_$cpt_debug'>\n";
	$cpt_debug++;
	echo "<table summary=\"Tableau de debug sur SESSION\">";
	foreach($_SESSION as $variable => $val){
		//echo "\$_SESSION['".$variable."']=".$val."<br />\n";
		echo "<tr><td>\$_SESSION['".$variable."']=</td><td>".$val."</td></tr>\n";
	}
	echo "</table>\n";
	echo "</div>\n";
	echo "</blockquote>\n";


	echo "<p>Variables envoyees en SERVER: ";
	if(count($_SERVER)==0) {
		echo "aucune";
	}
	else {
		echo "(<a href='#' onclick=\"tab_etat_debug_var[$cpt_debug]=tab_etat_debug_var[$cpt_debug]*(-1);affiche_debug_var('container_debug_var_$cpt_debug',tab_etat_debug_var[$cpt_debug]);return false;\">*</a>)";
	}
	echo "</p>\n";
	echo "<blockquote>\n";
	echo "<div id='container_debug_var_$cpt_debug'>\n";
	$cpt_debug++;
	echo "<table summary=\"Tableau de debug sur SERVER\">";
	foreach($_SERVER as $variable => $valeur){
		//echo "\$_SERVER['".$variable."']=".$valeur."<br />\n";
		echo "<tr><td>\$_SERVER['".$variable."']=</td><td>".$valeur."</td></tr>\n";
	}
	echo "</table>\n";
	echo "</div>\n";
	echo "</blockquote>\n";


	echo "<script type='text/javascript'>
	// On masque le cadre de debug au chargement:
	//affiche_debug_var('container_debug_var',var_debug_var_etat);

	//for(i=0;i<tab_etat_debug_var.length;i++) {
	for(i=0;i<$cpt_debug;i++) {
		if(document.getElementById('container_debug_var_'+i)) {
			affiche_debug_var('container_debug_var_'+i,-1);
		}
		// Variable destinee à alterner affichage/masquage
		tab_etat_debug_var[i]=-1;
	}
</script>\n";

	echo "</div>\n";
	echo "</div>\n";
}

?>
