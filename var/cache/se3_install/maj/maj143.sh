#!/bin/bash

## $Id: maj143.sh 7589 2013-01-06 01:11:10Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj143"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
# [ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 143 :
- Ajout droit fond ecran si module actif 
- Ajout acl annuaire" >> $HISTORIQUE_MAJ
 
if [ "$menu_fond_ecran" == "1" ]; then
  echo "dn: cn=fond_can_change,${rightsRdn},${ldap_base_dn}
objectClass: groupOfNames
cn: fond_can_change
member: uid=admin,${peopleRdn},${ldap_base_dn}
" | ldapadd -x -D ${adminRdn},${ldap_base_dn} -w ${adminPw}

  mkdir -p /var/www/se3/Admin/fonds_ecran/courant
  chown www-se3 /var/www/se3/Admin/fonds_ecran/courant

  mkdir -p /var/lib/se3/fonds_ecran
  chown www-se3 /var/lib/se3/fonds_ecran
fi

 
/usr/share/se3/scripts/mkSlapdConf.sh


echo "Mise a jour 143 :
- Ajout droit fond ecran si module actif"


exit 0		
