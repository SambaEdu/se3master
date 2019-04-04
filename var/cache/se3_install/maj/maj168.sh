#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj168"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)
PASS_SQL="$(grep -vE '^[[:space:]]*#' /root/.my.cnf | grep password /root/.my.cnf | cut -d "=" -f2)"
exec 2>&1
echo "Mise a jour 168 :
- Modification des sources pour utilisation de deb.sambaedu.org à la place de wawadeb
- Modification des scripts php ou bash pour utilisation de deb.sambaedu.org à la place de wawadeb
- Modification de la bdd pour utilisation de deb.sambaedu.org à la place de wawadeb
"

echo "Mise a jour 168 :
- Modification des sources pour utilisation de deb.sambaedu.org à la place de wawadeb
- Modification des scripts php ou bash pour utilisation de deb.sambaedu.org à la place de wawadeb
- Modification de la bdd pour utilisation de deb.sambaedu.org à la place de wawadeb

">> $HISTORIQUE_MAJ
 mysql -u root -p$PASS_SQL -D se3db -e "update params set value = 'http://deb.sambaedu.org/se3/client_linux_xenial' where Name = 'SrcPxeClientLin' ;"
 mysql -u root -p$PASS_SQL -D se3db -e "update params set value = '' where Name = 'urlmaj' ;"
 mysql -u root -p$PASS_SQL -D se3db -e "update params set value = '' where Name = 'ftpmaj' ;"
 

# Nouvelles sources SE3 
cat >/etc/apt/sources.list.d/se3.list <<END
#sources pour se3
deb http://deb.sambaedu.org/debian wheezy se3

# testing désactivé par défaut
#deb http://deb.sambaedu.org/debian wheezy se3testing
END

# Nouvelles sources wheezy
cat >/etc/apt/sources.list <<END
# Sources standard:
deb http://archive.debian.org/debian/ wheezy main non-free contrib

# Security Updates:
deb http://security.debian.org/ wheezy/updates main contrib non-free
END

exit 0
