
#!/bin/bash

## $Id$ ##


# lire le contenu d'un repertoire
echo "non" > /tmp/testfichier.tmp
if [ -f "$1" ]
then
echo "oui" > /tmp/testfichier.tmp
fi


