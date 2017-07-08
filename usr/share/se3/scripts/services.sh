#!/bin/bash

# Script permettant de relancer les services via l'interfaces


## $Id$ ##
#

[ $# -ne 2 ] && exit 1
service $1 $2
