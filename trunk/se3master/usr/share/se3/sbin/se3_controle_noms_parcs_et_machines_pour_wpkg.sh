#!/bin/bash

# $Id$
# Script de recherche des correspondances nom_machine=nom_parc
# Auteur: Stephane Boireau

. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null

echo "**************************************************"
echo "* Controle des noms de machines et noms de parcs *"
echo "**************************************************"

tmp=/root/tmp/controle_noms_parcs_et_machines_pour_wpkg_$(date +%Y%m%d%H%M%S)
mkdir -p $tmp

ldapsearch -xLLL -b ou=Parcs,$BASEDN cn | grep "^cn: " | sed -e "s|^cn: ||" |while read parc
do
	t=$(ldapsearch -xLLL -b ou=Computers,$BASEDN cn=$parc | grep "^cn: ")
	if [ -n "$t" ]; then
		echo "Anomalie: Une machine porte le meme nom qu'un parc: $parc"
		echo "Cela peut perturber WPKG."
		echo "$parc" >> $tmp/liste_pb_parc_machine.txt
	fi
done

if [ -e $tmp/liste_pb_parc_machine.txt ]; then
	echo "Anomalie: Une ou des machines portent le meme nom qu'un ou des parcs.
Cela peut perturber WPKG et causer des lenteurs de connexion.

Voici le mail qui a traite de ce probleme:
==========================================
Symptome : lenteur � la connexion, impossibilit� d'atteindre l'interface 
de wpkg

cause : profiles.xml fait 240 Mo

diagnostique : une station porte le m�me nom qu'un parc ! ( quel ... � 
fait �a ? ;-) �)
( Bien vu Jean Le Bail et merci )

solution :
1) Renommer le ... de poste qui a le mauvais nom

2) renommer profiles.xml et hosts.xml
cd /var/se3/unattended/install/wpkg
mv profiles.xml profiles.xml.bak
mv hosts.xml hosts.xml.bak

3) reg�n�rer ces fichiers � partir des donn�es de l'annuaire :
Le plus facile est d'utiliser l'interface web du SE3
en retirant puis rajouter un poste d'un parc.
( ajouter le nouveau poste qui a �t� renomm� )

4) R�tablir avec l'interface web les associations
entre les applis et les postes et parcs.
( le fichiers profiles.xml initial sera vierge )

Voici la liste des machines en conflit:
" > $tmp/mail.txt
	cat $tmp/liste_pb_parc_machine.txt >> $tmp/mail.txt
	cat $tmp/mail.txt | mail -s "[SE3] Pb noms de machines/parcs" root

	echo "RECAPITULATIF:"
	cat $tmp/mail.txt 
else
	echo "Aucune machine ne porte le meme nom qu'un parc."
	echo "Pas de probleme de ce cote."
fi

rm -fr $tmp
echo "Termine."
