<?php

require ("config.inc.php");
##########################################################################
# This file contains some definable stuff, for configuration.
# Other definitions that has nothing to do with configuration can be found
# in general.inc.php
#

#
# PATHS AND FILENAMES
#

# Main path in the unix filesystem
define("UNIX_PATH",	"$path_to_wwwse3/yala");
define("INCLUDE_PATH",	UNIX_PATH."/include");
define("IMAGES_PATH",	UNIX_PATH."/images");

# Path to place cache files in
define("CACHE_PATH",	"/tmp");

# URL PATHS
define("IMAGES_URLPATH",	"images");

# PHP filenames. usually shouldn't be changed
define("MAINFILE", "main.php");
define("TREEFILE", "tree.php");

# Icon to display when no matching icon was found (inside IMAGES_PATH)
define("DEFAULT_ICON", "default.png");



#
# CACHE STUFF
#

define("NAME2OID_CACHEFILE", CACHE_PATH."/yala_name2oid.cache");
define("OBJECTCLASSES_CACHEFILE", CACHE_PATH."/yala_objectclasses.cache");
# Number of *seconds* after which the cache expires
define("CACHE_EXPIRES", 60*5);



#
# DESIGN
#

# Enable Javascript, default on
# Disabling this will make YALA less nice.
define("ENABLE_JAVASCRIPT", TRUE);

# Size of text input
define("INPUT_TEXT_SIZE", 40);


#
# MISC
#

# Debugging
define("DEBUG", TRUE);


# Try to include additional configuration files (they're not required though)

@include "conf.d/ldap-servers.inc.php";

@include "conf.d/entry_type-config.inc.php";

@include "conf.d/attr_desc-config.inc.php";

?>