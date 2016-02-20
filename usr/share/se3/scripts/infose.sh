#!/bin/bash

## $Id$ ##



##### Retourne en HTML diverses informations sur SambaEdu #####
#
# Olivier LECLUSE 13 09 2002
# Modif jean NAVARRO 25/02/2005

grpdn="$2,$3"
usrdn="$1,$3"
smbpath=$4

echo "<H1>Informations générales SambaEdu</H1>"
echo "<H2>Informations sur les comptes</H2>"
nbg=`ldapsearch -x -b "$grpdn" "cn=*" "cn" |grep dn: |wc -l`
echo "<UL><LI>Nombre de groupes : $nbg</LI>"

nbCl=`ls /var/se3/Classes | wc -l`
echo "<LI>Nombre de Classes : $nbCl</LI>"

nbg=`cat $smbpath |grep "\[" |wc -l`
let nbg=nbg-2
echo "<LI>Nombre de partages : $nbg</LI></UL>"


echo ""
nbc=`ldapsearch -x -b "$usrdn" "uid=*" "uid" |grep dn: |wc -l`
nbcu=`ls /home/|wc -l`
#moins admin, netlogon, templates
let nbcu=nbcu-3
echo "<LI>Nombre de comptes inscrits : $nbc   ==> utilisés : $nbcu</LI>"


nbProfs=`ldapsearch -xLLL cn=Profs |grep memberUid | wc -l`

let "i= 0"
let "nbpa= 0"
uid=(`ldapsearch -xLLL cn=Profs | grep memberUid |cut -d" " -f2`)
while [ "$i" -lt "${#uid[@]}" ]
do   
	if [ -d /home/${uid[$i]} ]; then 
	((nbpa += 1))
	fi
	((i += 1))
done
let "pcprof = $nbpa * 100 / $nbProfs"
echo "<LI>Nombre de profs inscrits : $nbProfs   ==> actifs : $nbpa  ($pcprof%)</LI>"

nbEleves=`ldapsearch -xLLL cn=eleves | grep memberUid | wc -l`

let "i= 0"
let "nbela= 0"
uid=(`ldapsearch -xLLL cn=eleves | grep memberUid |cut -d" " -f2`)
while [ "$i" -lt "${#uid[@]}" ]
do   
	if [ -d /home/${uid[$i]} ]; then 
	((nbela += 1))
	fi
	((i += 1))
done
let "pcelev = $nbela * 100 / $nbEleves"
echo "<LI>Nombre d'élèves inscrits : $nbEleves ==>  actifs : $nbela ($pcelev%)</LI>"


cat <<EOF
<H2>Utilisation de la mémoire</H2>
<BLOCKQUOTE><PRE>
EOF

free -m

cat <<EOF
</PRE></BLOCKQUOTE>
<H2>Informations sur le Noyau</H2>
<BLOCKQUOTE><PRE>
EOF

uname -a

cat <<EOF
</PRE></BLOCKQUOTE>
<H2>Processus en cours</H2>
<BLOCKQUOTE><PRE>
EOF

top -b -n1

echo "</PRE></BLOCKQUOTE>"
