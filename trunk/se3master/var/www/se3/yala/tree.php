<?php

require "config.inc.yala.php";
require "ldapfunc.inc.php";
require "general.inc.php";
/* jlcf */
require "functions.inc.php";
require "ihm.inc.php";
$login=isauth();
if (($login == "") || (is_admin("se3_is_admin",$login)!="Y")) exit;
/* jlcf */

/* {{{ createIconsHash() creates an associative array which maps entry
	types to their matching icon filenames,
	and another assoc. array containing the html string for image size
	(i.e. WIDTH="11" HEIGHT="22")
*/
function createIconsHash($entry_types, &$icons, &$icons_size) {

	foreach (array_keys($entry_types) as $key) {
		$filename	= IMAGES_PATH."/".$key.".png";
		$open_filename	= IMAGES_PATH."/open_".$key.".png";
		if (file_exists($filename)) {
			$icons[$key]		= $key.".png";
			$size = getimagesize($filename);
			if ($size)
				$icons_size[$key] = $size[3];

			if (file_exists($open_filename)) {
				$icons["open_".$key] = "open_".$key.".png";
				$size = getimagesize($open_filename);
				if ($size)
					$icons_size["open_".$key] = $size[3];
			}
		}
	}

} /* }}} */

function showContents($ldap_func, $base_dn) {
	global $expand, $collapse, $yala_bind;

	# Associative arrays mapping group names to icon files / sizes in html
	global $icons, $icons_size;
	global $icons_size;

	$sr = @ldap_list($ldap_func->ldap_conn, $base_dn, "(objectClass=*)", array("dn", "objectclass"));
	if (!$sr) {
		exitOnError(ERROR_CANT_READ_TREE, $base_dn);
	}

	$info = ldap_get_entries($ldap_func->ldap_conn, $sr);
	if (!$info["count"]) return;

	# First let's sort the list of DNs
	for ($i = 0; $i < $info["count"]; $i++) {
		$DNs[$i] = $info[$i]["dn"];

		for ($j = 0; $j < $info[$i]["objectclass"]["count"]; $j++)
			$objectclasses[$DNs[$i]][$j] = $info[$i]["objectclass"][$j];
	}
	sort($DNs, SORT_STRING);

	echo "<TABLE CLASS=\"dense\">";

	# Get size of icon1 (plus/minus icon)
	# We assume they're all in the same size to make code nicer.
	$size = getimagesize(IMAGES_PATH."/minus.png");
	if ($size)
		$icon1_size = $size[3];
	else
		$icon1_size = "";

	for ($i = 0; $i < count($DNs); $i++) {
		$dn = $DNs[$i];

		# Find out what the objectclass group is
		$group = getEntryType($objectclasses[$dn]);

		if (ereg("^([^\,]+)", $dn, $regs))
			$rdn = $regs[1];
		else
			die("Invalid dn $dn!");

		# If this is the currently-changed-status (active) entry
		if ($dn == $collapse || $dn == $expand) {
			$anchor = "<A NAME=\"activeentry\"></A>";
		}
		else {
			$anchor = "";
		}

		# Set the group's icon
		if (array_key_exists($group, $icons)) {
			$icon2		= $icons[$group];
			$icon2_size	= $icons_size[$group];
		}
		else {
			$icon2		= DEFAULT_ICON;
			$icon2_size	= "";
		}

		if (array_key_exists("expanded", $_SESSION) && array_key_exists($dn, $_SESSION["expanded"]) && $_SESSION["expanded"][$dn] == TRUE) {
			$argument = "collapse=".urlencode($dn);
			if ($i == count($DNs))
				$last = "last";
			else
				$last = "";
			$icon1 = $last."minus.png";

			# If the 'open' group icon exists, use it instead of
			# what is already set
			if (array_key_exists("open_".$group, $icons)) {
				$icon2		= $icons["open_".$group];
				$icon2_size	= $icons_size["open_".$group];
			}

			if ( $yala_bind == 1 ) $new_link  = "<FONT SIZE=\"-2\">[&nbsp;<A HREF=\"".MAINFILE."?do=choose_entrytype&parent=".urlencode($dn)."\" TARGET=\"right\"><ACRONYM TITLE=\"Create a new entry under this entry\">new</ACRONYM></A>&nbsp;]</FONT>";
		}
		else {
			$argument = "expand=".urlencode($dn);
			if ($i == count($DNs) - 1)
				$last = "last";
			else
				$last = "";
			$icon1 = $last."plus.png";
			$new_link = "";
		}


		if (ENABLE_JAVASCRIPT) {
			$cooljs_href = "onMouseOver=\"javascript:window.status='".$dn."'; return true;\" onMouseOut=\"javascript:window.status=''; return true;\"";
		}
		echo "<TR VALIGN=\"middle\"><TD CLASS=\"dense\">".$anchor."<A HREF=\"".TREEFILE."?".$argument."#activeentry\"><IMG SRC=\"".IMAGES_URLPATH."/".$icon1."\" ".$icon1_size." BORDER=\"0\"></A></TD><TD CLASS=\"dense\"><TABLE CLASS=\"dense\" CELLSPACING=0 CELLPADDING=0><TR><TD CLASS=\"dense\"><IMG SRC=\"".IMAGES_URLPATH."/".$icon2."\" ".$icon2_size." BORDER=\"0\"></TD><TD CLASS=\"dense\" NOWRAP><FONT SIZE=\"-1\"><A HREF=\"".MAINFILE."?do=view_entry&entry=".urlencode($dn)."\" TARGET=\"right\" ".$cooljs_href.">".$rdn."</A></FONT>&nbsp;".$new_link."</TD></TR></TABLE></TD></TR>\n";

		if (array_key_exists("expanded", $_SESSION) && array_key_exists($dn, $_SESSION["expanded"]) && $_SESSION["expanded"][$dn] == TRUE) {
			echo "<TR><TD ALIGN=\"center\"></TD><TD>";
			showContents($ldap_func, $dn);
			echo "</TD></TR>";
		}
	}
	echo "</TABLE>";
}

session_start();
/* jlcf */
 if  (($login!="") && (is_admin("se3_is_admin",$login)=="Y")){

	$_SESSION["ldap_server"] = $ldap_server;
	$_SESSION["ldap_port"] = $ldap_port;
	$_SESSION["ldap_basedn"] = $ldap_base_dn;
	$_SESSION["ldap_binddn"] = LDAP_BINDDN;
	if ($yala_bind==1)
		$_SESSION["ldap_bindpw"] = $adminPw;
	else $_SESSION["ldap_bindpw"] = "";
	//if ($HTTP_POST_VARS["ldap_tls"])
	//	$_SESSION["ldap_tls"]	= TRUE;

	$_SESSION["ldap_tls"]	= FALSE;
}
/* jlcf */

# No ldap_server variable prolly means we're not connected yet
if (!array_key_exists("ldap_server", $_SESSION)) die("Not connected!");

$ldap_func = new LDAPFunc($_SESSION["ldap_server"], $_SESSION["ldap_port"], $_SESSION["ldap_tls"]) or die("Cannot Connect!");
$ldap_func->bind($_SESSION["ldap_binddn"], $_SESSION["ldap_bindpw"]) or die("Can't bind");

if (array_key_exists("expand", $HTTP_GET_VARS))
	$expand		= $HTTP_GET_VARS["expand"];
else
	$expand		= NULL;

if (array_key_exists("collapse", $HTTP_GET_VARS))
	$collapse	= $HTTP_GET_VARS["collapse"];
else
	$collapse	= NULL;

if ($expand) $_SESSION["expanded"][$expand] = TRUE;
if ($collapse) unset($_SESSION["expanded"][$collapse]);

require INCLUDE_PATH."/header.inc";

require INCLUDE_PATH."/toolbar.inc";

$icons		= array();
$icons_size	= array();
if (isset($entry_types) && is_array($entry_types))
	createIconsHash($entry_types, $icons, $icons_size);


showContents($ldap_func, $_SESSION["ldap_basedn"]);

require INCLUDE_PATH."/footer.inc";
?>
