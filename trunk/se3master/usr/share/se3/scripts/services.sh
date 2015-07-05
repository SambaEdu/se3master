#!/bin/bash

# Script permettant de relancer les services via l'interfaces


## $Id$ ##
#

[ $# -ne 2 ] && exit 1
/etc/init.d/$1 $2
