#!/bin/bash

# $Id$ #

#####Supprime une entrée dans clients.ini, lancé par parcs/delete.php#####

echo "Suppression de $1"

perl -pi -e "s,^$1=.*\n,," /var/se3/Progs/install/installdll/clients.ini
exit 0
