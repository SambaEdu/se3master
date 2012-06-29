#!/bin/bash

# Taille de la sauvegarde
# Olivier Lecluse 23/03/2001

# Entete cgi

echo MIME-Version: 1.0
echo Content-type: text/html
echo

while true
do
        read WDEV
        read WZIP
        read WSAUV
        read WSUPP
        break
done < /serveur/conf/sauvepar


cat <<EOF
<HTML>
<HEAD>
  <TITLE>Calcul de la taille de la sauvegarde</TITLE>
</HEAD>

<BODY TEXT="#000000" LINK="#0000ff" VLINK="#000080"  ALINK="#ff0000" >
<DIV ALIGN="CENTER">
  <H1>Taille de la sauvegarde</H1>
</DIV>
<BLOCKQUOTE><PRE>
EOF

du -smhc $WSAUV $WSUPP

cat <<EOF
</PRE></BLOCKQUOTE>
<HR>
<TABLE BORDER="0" WIDTH="100%">
  <TR>
    <TD><A HREF="/sauve.html"><IMG SRC="/icons/back.gif" ALIGN="TOP" BORDER="0"> Retour au menu de sauvegarde</A></TD>
    <TD ALIGN="RIGHT"><IMG SRC="/icons/apache_pb.gif"></TD>
  </TR>
</TABLE>
</BODY>
</HTML>
EOF
