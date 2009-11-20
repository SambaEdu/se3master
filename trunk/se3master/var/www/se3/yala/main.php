<?php
require "config.inc.yala.php";
require "general.inc.php";
require "ldapfunc.inc.php";

require "functions.inc.php";
require "ihm.inc.php";
$javascript = "";

$login=isauth();
# {{{ login() returns binddn and bindpw if true, otherwise
# shows the login form
function login() {
	# If $ldap_server && $ldap_port are set
	if (array_key_exists("ldap_server", $_SESSION) && array_key_exists("ldap_port", $_SESSION) && $_SESSION["ldap_server"] && $_SESSION["ldap_port"]) {
		# First connect..
		$ldap_func = new LDAPFunc($_SESSION["ldap_server"], $_SESSION["ldap_port"], $_SESSION["ldap_tls"]) or exitOnError(ERROR_LDAP_CANT_CONNECT, $_SESSION["ldap_server"].":".$_SESSION["ldap_port"]);

		# Let's try to login, if successful skip the next stuff
		$bind = $ldap_func->bind($_SESSION["ldap_binddn"], $_SESSION["ldap_bindpw"]);
		if ($bind) {
			return $ldap_func;
		}
			echo "Bind problem: ".ldap_error($ldap_func->ldap_conn)."<BR>";
	}

	# Get settings either from session or from defaults
	if (isset($_SESSION["ldap_server"]))
		$ldap_server	= $_SESSION["ldap_server"];
	else
		if (defined("LDAP_SERVER"))
			$ldap_server	= LDAP_SERVER;
		else
			$ldap_server 	= NULL;

	if (isset($_SESSION["ldap_port"]))
		$ldap_port	= $_SESSION["ldap_port"];
	else
		if (defined("LDAP_PORT"))
			$ldap_port	= LDAP_PORT;
		else
			$ldap_port	= NULL;

	if (isset($_SESSION["ldap_binddn"]))
		$ldap_binddn	= $_SESSION["ldap_binddn"];
	else
		if (defined("LDAP_BINDDN"))
			$ldap_binddn	= LDAP_BINDDN;
		else
			$ldap_binddn	= NULL;

	if (isset($_SESSION["ldap_basedn"]))
		$ldap_basedn	= $_SESSION["ldap_basedn"];
	else
		if (defined("LDAP_BASEDN"))
			$ldap_basedn	= LDAP_BASEDN;
		else
			$ldap_basedn	= NULL;

	if (isset($_SESSION["ldap_tls"]))
		$ldap_tls	= $_SESSION["ldap_tls"];
	else
		if (defined("LDAP_TLS"))
			$ldap_tls	= LDAP_TLS;
		else
			$ldap_tls	= NULL;

	require INCLUDE_PATH."/login_form.inc";
} # }}}

# {{{ logout() - deletes the cookies (Session info..)
function logout() {
	global $javascript;
	if (session_destroy())
		echo "Logged out, click <A HREF=\"".MAINFILE."\" TARGET=\"right\">here</A> to login again...<BR>";
	else
		echo "Error logging out!<BR>";
} # }}}

# {{{ getCachedSchema() - wraps $ldap_func->getSchema() function to add
# caching. We use the neat serialize() function php has..
#
# Returns the values into $name2oid & $objectclasses
function getCachedSchema($ldap_func, &$name2oid, &$objectclasses) {

	# Set the filenames of cache files
	$name2oidCacheFile = NAME2OID_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$objectclassesCacheFile = OBJECTCLASSES_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$cacheIsFine = TRUE;

	/* Can we get it from cache?
	1. If cache files exist *AND*
	2. If CACHE_EXPIRES seconds haven't passed yet since the file's mtime
	*/

	if (!file_exists($name2oidCacheFile) ||
	    !file_exists($objectclassesCacheFile))
		$cacheIsFine = FALSE; # Cache files don't exist, cache is NOT fine
	else {
		if ((filemtime($name2oidCacheFile)+CACHE_EXPIRES<time()) ||
		(filemtime($objectclassesCacheFile)+CACHE_EXPIRES<time())) {
			# Cache expired, then cache is NOT fine
			$cacheIsFine = FALSE;
		}
	}


	if ($cacheIsFine) {
		if ($f = @fopen($name2oidCacheFile, "r")) {
			$str = fread($f, filesize($name2oidCacheFile)) or exitOnError(ERROR_CACHE_CANT_READ, $name2oidCacheFile);
			$name2oid = unserialize($str);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_READ, $name2oidCacheFile);

		if ($f = @fopen($objectclassesCacheFile, "r")) {
			$str = fread($f, filesize($objectclassesCacheFile)) or exitOnError(ERROR_CACHE_CANT_READ, $objectclassesCacheFile);
			$objectclasses = unserialize($str);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_READ, $objectclassesCacheFile);
	}
	else { # Cache is no good
		/* Read the schema from LDAP */
		$ldap_func->getSchemaHash($name2oid, $objectclasses);

		/* Save it in cache */
		umask(077); # We don't want the schema world readable..
		if ($f = @fopen($name2oidCacheFile, "w")) {
			fwrite($f, serialize($name2oid)) or exitOnError(ERROR_CACHE_CANT_WRITE, $name2oidCacheFile);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_WRITE, $name2oidCacheFile);

		if ($f = @fopen($objectclassesCacheFile, "w")) {
			fwrite($f, serialize($objectclasses)) or exitOnError(ERROR_CACHE_CANT_WRITE, $objectclassesCacheFile);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_WRITE, $objectclassesCacheFile);
	}
}
# }}}

# {{{ viewEntry() displays the given entry's attributes and values,
# according to the schema of its objectclasses
# If no entry is given we display an empty entry of the specified object claases
#
# For each attribute we'll show $empties values in addition
function viewEntry($ldap_func, $entry, $empties = 0, $dn = "", $objectclasses = array()) {
	global $attr_desc,$yala_bind; # FIXME Global is ugly

	$info = array();

	if (!$ldap_func || !$entry && !count($objectclasses)) exitOnError(ERROR_FEW_ARGUMENTS);

	# Will contain a list of attributes later
	$may = $must = array();

	# Get the schema contents
	getCachedSchema($ldap_func, $name2oid, $schema_objectclasses);



	# In the next lines we'll fill this array with the list of objectclasses
	$objectclasses_list = array();

	if ($entry) {
		$entry = formatInputStr($entry);
		# If we are viewing an existing entry, read list of the entry's objectclasses

		$sr = ldap_read($ldap_func->ldap_conn, $entry, "(objectclass=*)") or exitOnError(ERROR_LDAP_CANT_SEARCH);
		$info = ldap_get_entries($ldap_func->ldap_conn, $sr) or exitOnError(ERROR_LDAP_CANT_SEARCH);

		# Fill objectclasses_list with the objectclasses of that attribute
		for ($i = 0; $i < $info[0]["objectclass"]["count"]; $i++) {
			array_push($objectclasses_list, strtolower($info[0]["objectclass"][$i]));
		}
	}
	else {
		# If it's an empty entry, objectclasses are given arguments
		$objectclasses_list = $objectclasses;

		# Add the values of the objectclass attribute in a patchy-way
		$info[0]["objectclass"] = array();
		foreach ($objectclasses_list as $objectclass) {
			array_push($info[0]["objectclass"], $objectclass);
		}
		$info[0]["objectclass"]["count"] = count($objectclasses_list);
	}


	# Now when we have $objectclass_list, get the attrs of each objectclass
	# And merge them (an entry might have several objectclasses)
	foreach ($objectclasses_list as $objectclass) {
#		echo "Reading attributes of objectclass ".$objectclass."<BR>\n";
#		$objectclass = strtolower($info[0]["objectclass"][$i]);

		# Recursively get the may/must attributes of $objectclass and it's
		# superiors.
		$maymust = $ldap_func->getMayMust($objectclass, $schema_objectclasses, $name2oid);

		$must = array_merge($must, $maymust["must"]);
		$may = array_merge($may, $maymust["may"]);
	}

#echo $info[0]["objectclass"][0];

	# Remove dups + sort
	$must = array_unique($must);
	$may  = array_unique($may);
	asort($must);
	asort($may);

	# ENTRY TITLE COMES HERE
	if ($entry) echo "<CENTER><H4><B>".$entry."</B></H4></CENTER><BR>\n";

	# Allow adding/removing empty fields
	# TODO We don't allow this on 'new' entry mode because I'm lazy.
	if ($entry) echo "One <A HREF=\"".MAINFILE."?do=view_entry&entry=".urlencode($entry)."&empties=".($empties+1)."\">more</A> / <A HREF=\"".MAINFILE."?do=view_entry&entry=".urlencode($entry)."&empties=".($empties-1)."\">less</A> empty value field (for each attribute)<BR>";



	echo "<FORM NAME=\"form\" METHOD=\"post\" ACTION=\"".MAINFILE."\"";
	if (ENABLE_JAVASCRIPT) echo " onSubmit=\"javascript:return confirm('Are you sure you want to commit a \''+this.chosen_action.value+'\' operation on this entry?');\"";
	echo ">\n";
	if ($entry) echo "<INPUT TYPE=\"hidden\" NAME=\"entry\" VALUE=\"".$entry."\">\n";
	echo "<INPUT TYPE=\"hidden\" NAME=\"chosen_action\" VALUE=\"none\">\n";
	echo "<TABLE>\n";

	# Show the dn before anything else
	if (is_array($info) && array_key_exists(0, $info) && array_key_exists("dn", $info[0]))
		$dn = $info[0]["dn"];

	if (!isset($dn)) $dn = "";

	echo "<TR CLASS=\"bgcolor1\"><TD CLASS=\"dnattr\"><ACRONYM TITLE=\"Distinguished Name\">dn</ACRONYM>:&nbsp;<FONT SIZE=\"-2\">[&nbsp;<A HREF=\"".MAINFILE."?do=modrdn_form&entry=".urlencode($dn)."\">modify dn</A>&nbsp;]&nbsp;</FONT></TD><TD><INPUT TYPE=\"text\" NAME=\"dn\" VALUE=\"".formatOutputStr($dn)."\" SIZE=\"".INPUT_TEXT_SIZE."\"></TD></TR>\n";

	$status = MUST;
	$maymust = &$must;
	while ( 1 ) {
		foreach ($maymust as $attr) {

			# See if there's a description to this specific attribute
			$acronym_begin = "";
			$acronym_end = "";

			if (isset($attr_desc) && is_array($attr_desc)) {
				if (array_key_exists($attr, $attr_desc)) {
					$acronym_begin = "<ACRONYM TITLE=\"".$attr_desc[$attr]."\">";
					$acronym_end = "</ACRONYM>";
				}
			}

			# Mark the attribute if it's a MUST
			if ($status == MUST) {
				$mark_begin = "<B>";
				$mark_end   = "</B>";
			}
			else {
				$mark_begin = "";
				$mark_end   = "";
			}

			if (array_key_exists(strtolower($attr), $info[0]))
				$val  = $info[0][strtolower($attr)];
			else
				$val = NULL;


			# Show all the existing values (if none, at least
			# one empty!) + $empty_values empties in addition
			for ($j = 0; ($j < ( max($val["count"], 1) + $empties)); $j++) {
				# Very stupid color changing
				if (isset($bgcolor) && $bgcolor == "bgcolor2") $bgcolor = "bgcolor1";
				else
					$bgcolor = "bgcolor2";

				if ($j + 1 > $val["count"]) $value = "";
				else
					$value = formatOutputStr($val[$j]);
				echo "<TR CLASS=\"".$bgcolor."\"><TD CLASS=\"attr\">".$mark_begin.$acronym_begin.$attr.$acronym_end.$mark_end.":</TD><TD CLASS=\"value\"><INPUT TYPE=\"text\" NAME=\"".$attr."[]\" VALUE=\"".$value."\" SIZE=\"".INPUT_TEXT_SIZE."\"></TD></TR>\n";

			}

		}

		if ($status == MAY) break;
		if ($status == MUST) { $status = MAY; $maymust = &$may; }

	}
	echo "</TABLE><BR>\n<CENTER><TABLE BORDER=\"0\" CELLSPACING=\"2\" CELLPADDING=\"2\" WIDTH=\"75%\"><TR ALIGN=\"center\">";
	if ( $entry && $yala_bind==1 ) {
		echo "<TD><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"Modify\"";
		if (ENABLE_JAVASCRIPT) echo " onClick=\"javascript:this.form.chosen_action.value='Modify';\"";
		echo "></TD>";
	}
	if ( $yala_bind==1 ) {
		echo "<TD><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"New\"";
		if (ENABLE_JAVASCRIPT) echo " onClick=\"javascript:this.form.chosen_action.value='New';\"";
		echo "></TD>";
	}
	if ( $entry && $yala_bind==1 ) {
		echo "<TD><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"Delete\"";
		if (ENABLE_JAVASCRIPT) echo " onClick=\"javascript:this.form.chosen_action.value='Delete';\"";
		echo "></TD>";
	}
	echo "</TR></TABLE></CENTER>\n";
	echo "</FORM>\n";


}
# }}}

# {{{ modifyEntry() gets the modifications and decides what to do...
function modifyEntry($ldap_func, $post_vars) {
	$add_hash = array();
	$del_hash = array();
	$replace_hash = array();


	if (!$ldap_func || !is_array($post_vars)) exitOnError(ERROR_FEW_ARGUMENTS);
	$dn = formatInputStr($post_vars["entry"]);

	$sr = ldap_read($ldap_func->ldap_conn, $dn, "(objectClass=*)") or exitOnError(ERROR_LDAP_CANT_SEARCH);
	$entry = ldap_first_entry($ldap_func->ldap_conn, $sr) or exitOnError(ERROR_LDAP_CANT_SEARCH);
	$attributes = ldap_get_attributes($ldap_func->ldap_conn, $entry);


	#
	# Create add_hash
	#

	# Pass on each posted attribute
	while ( list($attr, $posted_values) = each($post_vars) ) {
		# We care about this var only if it's an array
		if (!is_array($posted_values)) continue;

		# If $attr wasn't found in ldap (means that it has no
		# value in ldap yet) - add all values to the 'add_hash'
		if (!isset($attributes[$attr])) {
			foreach ($posted_values as $posted_value) {
				# Skip empty values
				if ($posted_value == "") continue;
				if (!array_key_exists($attr, $add_hash))
					$add_hash[$attr] = array();
				array_push($add_hash[$attr], $posted_value);
			}
			continue;
		}

		$ldap_values = ldap_get_values($ldap_func->ldap_conn, $entry, $attr);

		foreach ($posted_values as $posted_value) {
			# Skip empty values
			if ($posted_value == "") continue;
			if (!in_array($posted_value, $ldap_values)) {
				if (!array_key_exists($attr, $add_hash))
					$add_hash[$attr] = array();
				array_push($add_hash[$attr], $posted_value);
			}
		}
	}


	#
	# Create del_hash
	#

	# Now pass on each attribute from ldap and see if it has a real
	# value in $posted_vars
	for ($i = 0; $i < $attributes["count"]; $i++) {
		$attr = $attributes[$i];
		$ldap_values = ldap_get_values($ldap_func->ldap_conn, $entry, $attr);

		for ($j = 0; $j < $ldap_values["count"]; $j++) {
			if (!in_array($ldap_values[$j], $post_vars[$attr]))  {
				if (!array_key_exists($attr, $del_hash))
					$del_hash[$attr] = array();
				array_push($del_hash[$attr], $ldap_values[$j]);
			}
		}
	}

	#
	# Create replace_hash
	#

	# Now we have two hashes, add_hash and del_hash. If we both del a
	# value of attribute 'x' and add another value - we'd rather REPLACE
	# this value instead of deleting and adding (as long as attribute
	# 'x' has only a single value!).. (MUST values cannot be deleted,
	# that is)
	if (is_array($del_hash))
	while ( list($attr, $values) = each($del_hash) ) {

		# If it has more than one value in the directory, we cannot
		# replace it.
		if ($attributes[$attr]["count"] > 1) continue;

		# does the same attribute from del_hash exist in add_hash too?
		if (array_key_exists($attr, $add_hash) && count($add_hash[$attr])) {
			$add = array_shift($add_hash[$attr]);
			$del = array_shift($del_hash[$attr]);

			if (!array_key_exists($attr, $replace_hash))
				$replace_hash[$attr] = array();
			array_push($replace_hash[$attr], $add);
		}
	}

	$operations = array("del", "add", "replace");

	# Now commit the changes, first del, then add, then replace
	foreach ($operations as $op) {
		$varname = $op."_hash"; #either del/add/replace_hash
		if (!count(${$varname})) continue; # If empty, skip
		echo "<B>".$op.":</B><BR>";

		reset(${$varname});
		while (list($attr, $values) = each(${$varname})) {
			# FIXME make it commit each operation only once
			foreach($values as $value) {
				$entry = array();

				echo $attr.": ".formatOutputStr($value)." - ";
				$entry[$attr] = $value;

				switch($op) {
					case "del": $result = @ldap_mod_del($ldap_func->ldap_conn, $dn, $entry); break;
					case "add": $result = @ldap_mod_add($ldap_func->ldap_conn, $dn, $entry); break;
					case "replace": $result = @ldap_mod_replace($ldap_func->ldap_conn, $dn, $entry); break;
				}
				if ($result) echo "OK";
				else
					exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
				echo "<BR>";
			}
		}

	}

} # }}}

# {{{ deleteEntry() deletes the given entry from the directory
function deleteEntry($ldap_func, $dn) {

	echo "<B>Deleting</B>... ";

	$dn = formatInputStr($dn);

	$result = @ldap_delete($ldap_func->ldap_conn, $dn);

	if ($result) echo "OK";
	else
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
	echo "<BR>";
}
# }}}

# {{{ newEntry() creates a new LDAP entry according to the given parameters
function newEntry($ldap_func, $post_vars) {

	$dn = formatInputStr($post_vars["dn"]);
	echo "dn: ".$dn."<BR>";

	# Let's construct $entry, which will contain the future attrs/vals
	$entry = array();
	while (list($attr, $values) = each($post_vars)) {

		# Skip if the value is not an array- it means that it's not
		# a variable ment to be an attribute
		if (!is_array($values)) continue;

		foreach ($values as $value) {
			# Skip if value is empty
			if (!$value) continue;

			if (!array_key_exists($attr, $entry))
				$entry[$attr] = array();

			array_push($entry[$attr], $value);
			echo $attr.": ".$value."<BR>";
		}
	}

	echo "<BR><B>Adding...</B> ";
	$result = @ldap_add($ldap_func->ldap_conn, $dn, $entry);
	if ($result) echo "OK";
	else
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));

} # }}}

# {{{ search_form() displays a search form
function search_form($ldap_basedn) {
	include INCLUDE_PATH."/search_form.inc";
} # }}}

# {{{ search() search and return the results as an array
function search($ldap_func, $post_vars) {
	$binddn = $post_vars["basedn"];
	$filter = $post_vars["filter"];
	$scope  = $post_vars["scope"];

	if (!$binddn || !$filter || !$scope)
		exitOnError(ERROR_FEW_ARGUMENTS);

	$info = $ldap_func->search($binddn, $filter, $scope);

	echo "<H2><CENTER>".$info["count"]." result(s)</CENTER></H2><BR>\n";
	if (!$info["count"]) return;

	for ($i = 0; $i < $info["count"]; $i++) {
		$dn = $info[$i]["dn"];
		echo "<A HREF=\"".MAINFILE."?do=view_entry&entry=".urlencode($dn)."\">".$dn."</A><BR>\n";
	}
} # }}}

# {{{ choose_entrytype() displays a form for choosing a new entry type
function choose_entrytype($entry_types, $ldap_func, $parent) {

	$ldap_func->getSchemaHash($name2oid, $schema_objectclasses);

	require INCLUDE_PATH."/choose_entrytype_form.inc";
} # }}}

# {{{ new_form() displays a form for adding a new entry
function new_form($ldap_func, $entry_types, $post_vars) {
	$entry_type = $post_vars["entry_type"] or exitOnError(ERROR_FEW_ARGUMENTS);

	$objectclasses_list = array();

	# If we have the parent DN, put ",<parent DN>" as the dn
	if ($post_vars["dn"]) $dn = ",".$post_vars["dn"];
	else
		$dn = "";

	# If custom, list of objectclasses is given as an argument
	if ($entry_type == "custom") {
		if (!count($post_vars["custom_objectclasses"])) exitOnError(ERROR_FEW_ARGUMENTS);
		$objectclasses_list = $post_vars["custom_objectclasses"];
	}
	else # If a specific entry type was chosen, list is in $entry_types
		$objectclasses_list = $entry_types[$entry_type];

	viewEntry($ldap_func, "", 0, $dn, $objectclasses_list);


} # }}}

# {{{ modrdn_form() displays a form for modifying the dn
function modrdn_form($ldap_func, $entry) {
	$entry = formatInputStr($entry);
	if (eregi("^([^,]+),(.*)$", $entry, $regs)) {
		$rdn		= $regs[1];
		$superior	= $regs[2];
	}
	include INCLUDE_PATH."/modrdn_form.inc";

} # }}}

# {{{ modrdn() - Modify the RDN and/or the Parent ( = rename )
function modrdn($ldap_func, $post_vars) {
	$entry		= formatInputStr($post_vars["entry"]);
	$newrdn		= formatInputStr($post_vars["newrdn"]);
	$deleteoldrdn	= formatInputStr($post_vars["deleteoldrdn"]);
	$newsuperior	= formatInputStr($post_vars["newsuperior"]);

echo $newrdn;
	echo "dn: ".formatOutputStr($entry)."<BR>\n";
	echo "newrdn: ".formatOutputStr($newrdn)."<BR>\n";
	echo "deleteoldrdn: ".formatOutputStr($deleteoldrdn)."<BR>\n";
	if ($newsuperior) echo "newsuperior: ".formatOutputStr($newsuperior)."<BR>\n";

	$result = ldap_rename($ldap_func->ldap_conn, $entry, $newrdn, $newsuperior, $deleteoldrdn);
	if ($result) echo "OK";
	else
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
	echo "<BR>\n";
} # }}}

###############################################
# Here we BEGIN

session_start();
if (array_key_exists("submit", $HTTP_POST_VARS))
	$submit		= $HTTP_POST_VARS["submit"];
else
	$submit = NULL;

if (array_key_exists("do", $HTTP_GET_VARS))
	$do		= $HTTP_GET_VARS["do"];
else
	$do = NULL;
/*
if (isset($HTTP_POST_VARS["ldap_server"])) {

	# If we're just after the login form:
	$_SESSION["ldap_server"] = $HTTP_POST_VARS["ldap_server"];
	$_SESSION["ldap_port"] = $HTTP_POST_VARS["ldap_port"];
	$_SESSION["ldap_basedn"] = $HTTP_POST_VARS["ldap_basedn"];
	$_SESSION["ldap_binddn"] = LDAP_BINDDN;
	#$_SESSION["ldap_binddn"] = $HTTP_POST_VARS["ldap_binddn"];
	if ($yala_bind==1)
		$_SESSION["ldap_bindpw"] = $adminPw;
	else $_SESSION["ldap_bindpw"] = "";
	# $_SESSION["ldap_bindpw"] = $HTTP_POST_VARS["ldap_bindpw"];
	if (array_key_exists("ldap_tls", $HTTP_POST_VARS))
		$_SESSION["ldap_tls"]	= TRUE;
	else
		$_SESSION["ldap_tls"]	= FALSE;
}
*/


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

	if ($yala_bind==1 && $submit ==NULL ) $submit == "Login";
	elseif ($yala_bind==0 && $submit ==NULL ) $submit = "Anonymous Login";


if ($submit == "Login" || $submit == "Anonymous Login" || $submit == "Delete" || $submit == "New" || $submit == "Modify" || $submit == "Modrdn" || $do == "logout") {
	$javascript .= "top.left.location.reload();\n"; # Refresh after login
}

require INCLUDE_PATH."/header.inc";

# If anonymous login, act as there is no binddn nor bindpw
if ($submit == "Anonymous Login") {
	$ldap_binddn = LDAP_BINDDN; $ldap_bindpw = "";
}
$ldap_func = login();

# Sanity checks on parameters
if (array_key_exists("empties", $HTTP_GET_VARS)) {
	$empties = $HTTP_GET_VARS["empties"];
	if ($empties < 0) $empties = 0; elseif ($empties > 5) $empties = 5;
}
else
	$empties = NULL;


if (DEBUG) echo $empties;

if ($do) {
	switch ($do) {
		case "logout": logout(); break;
		case "search_form": search_form($_SESSION["ldap_basedn"]); break;
		case "modrdn_form": modrdn_form($ldap_func, $HTTP_GET_VARS["entry"]); break;
		case "view_entry": viewEntry($ldap_func, $HTTP_GET_VARS["entry"], $empties); break;
		case "choose_entrytype":
			if (array_key_exists("parent", $HTTP_GET_VARS))
				$parent = $HTTP_GET_VARS["parent"];
			else
				$parent = "";
			choose_entrytype($entry_types, $ldap_func, $parent);

			break;

		default: exitOnError(ERROR_BAD_OP, $do);
	}
}

if ($submit) { # If it's a form which was submitted (modify/del/add...)
	if (is_array($HTTP_POST_VARS)) {
		# First format the posted strings
		$post_vars = formatInputArray($HTTP_POST_VARS);
	}
	switch ($submit) {
		case "Modrdn": modrdn($ldap_func, $post_vars); break;
		case "Modify": modifyEntry($ldap_func, $post_vars); break;
		case "Delete": deleteEntry($ldap_func, $post_vars["entry"]); break;
		case "New": newEntry($ldap_func, $post_vars); break;
		case "Search": search($ldap_func, $post_vars); break;
		case "Create": new_form($ldap_func, $entry_types, $post_vars);
		case "Anonymous Login":
		case "Login": break;
		default: exitOnError(ERROR_BAD_OP, $submit);
	}
}


require INCLUDE_PATH."/footer.inc";


