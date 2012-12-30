#!/bin/bash

#
## $Id$ ##
#
##### Retourne la commande df au format HTML #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Retourne la commande df au format HTML"
	echo "Usage : aucune option"
	exit
fi	

# Etat ement d'un utilisateur
# Olivier LECLUSE 03 10 1999
cat <<EOF
<DIV ALIGN="CENTER">
<H1>Espace libre sur le disque</H1>
</DIV> <BR>
<TABLE WIDTH="80%" ALIGN="CENTER" BORDER="1">
EOF

titre="1"
df -l -P -x tmpfs | while true
do
	read ligne
	if [ "$ligne" = "" ]; then
		break
	fi
	set -- $ligne
	if [ "$titre" = "1" ]; then
		echo "<TR><TD ALIGN='CENTER'><STRONG>Partition</STRONG></TD>"
		echo "<TD ALIGN='CENTER'><STRONG>Point de montage</STRONG></TD>"
		echo "<TD ALIGN='CENTER'><STRONG>Espace total(Mo)</STRONG></TD>"
		echo "<TD ALIGN='CENTER'><STRONG>Espace occup&#233; (Mo)</STRONG></TD>"
		echo "<TD ALIGN='CENTER'><STRONG>Espace libre (Mo)</STRONG></TD>"
		echo "<TD ALIGN='CENTER'><STRONG>Poucentage occup&#233;</STRONG></TD></TR>"
		titre=""
	else
		occ=`echo $5|cut -d% -f1`
		color="#33FF33"
		if [ $occ -ge 75 ]; then color="#Fcb000"; fi
		if [ $occ -ge 90 ]; then color="#FF3333"; fi
		echo "<TR><TD>"
		echo $1; echo "</TD><TD ALIGN='CENTER' BGCOLOR=$color>"
		echo $6; echo "</TD><TD ALIGN='CENTER' BGCOLOR=$color>"
		let total=$2/1024
		echo $total; echo "</TD><TD ALIGN='CENTER' BGCOLOR=$color>"
		let total=$3/1024
		echo $total; echo "</TD><TD ALIGN='CENTER' BGCOLOR=$color>"
		let total=$4/1024
		echo $total; echo "</TD><TD ALIGN='CENTER' BGCOLOR=$color>"
		echo "$occ %"; echo "</TD></TR>"
	fi
done
cat <<EOF
</TABLE>
EOF
