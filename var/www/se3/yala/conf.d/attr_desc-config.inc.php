<?php

#
# Attribute Descriptions
# 
# $attr_desc is an associative array (hash) containing a list of values 
# (key) and their description
# (value). It'll be available to the user in some ways, i.e. <ACRONYM> 
# HTML Tag.
#
# Note: In future release the description will be taken directly from the 
# schema (DESC line in the attributetype block)
#

# General
$attr_desc["cn"] = "Nom complet";
$attr_desc["sn"] = "Nom";

# User objects
$attr_desc["uidNumber"]		= "User ID: Unique ID of a user, i.e. 532";
$attr_desc["gidNumber"]		= "Group ID: The Unique ID of a group, i.e. 532";
$attr_desc["gecos"]		= "User's full name (aka GECOS field), i.e. John Smith";

?>