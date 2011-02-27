<?php


   /**
   
   * Permet de supprimer un partage
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Equipe TICE Crdp de Caen
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: partages/
   * file: delete_share.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-partages',"/var/www/se3/locale");
textdomain ('se3-partages');

foreach ($_POST as $cle=>$val) {
    $$cle = $val;
    }

if (is_admin("se3_is_admin",$login)=="Y") {


	//aide
	$_SESSION["pageaide"]="Ressources_et_partages";
	
	echo "<h1>".gettext("Suppression de partage")."</h1>";

	// Definition des messages d'alerte
        $alerte_1="<div class='error_msg'>\n".gettext("Votre demande de suppression d'un partage n'a pas &#233;t&#233; prise en compte car une t&#226;che d'administration est en cours sur le serveur <b>\n");
        $alerte_2=gettext("</b>, veuillez r&#233;it&#233;rer votre demande plus tard. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
        $alerte_3="<div class='error_msg'>".gettext("Votre demande  suppression d'un partage d'unpartage a &#233;chou&#233;e. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
        $alerte_4="<div class='error_msg'>".gettext("Il n'y a pas de partage &#224; supprimer sur le serveur")." <b>$cn_srv</b> !</div>\n";
        // Definition des messages d'info
        $info_1 = gettext("Cette t&#226;che est ordonnanc&#233e, vous recevrez un mail de confirmation de suppression dans quelques instants...");

        if ( mono_srv() ) {
                // configuration mono serveur  : determination des parametres du serveur
                $serveur=search_machines ("(l=maitre)", "computers");
                $cn_srv= $serveur[0]["cn"];
                $stat_srv = $serveur[0]["l"];
                $ipHostNumber =  $serveur[0]["ipHostNumber"];
        } else {
                // configuration multi-serveurs : presentation d'un form de selection du serveur
                if ( !$selected_srv && !$End_ph1) {
                        echo "<H3>".gettext("S&#233lection du serveur ou vous souhaitez supprimer un partage :")." </H3>\n";
                        $servers=search_computers ("(|(l=esclave)(l=maitre))");
                        echo "<form action=\"delete_share.php\" method=\"post\">\n";
                        for ($loop=0; $loop < count($servers); $loop++) {
                                echo $servers[$loop]["description"]." ".$servers[$loop]["cn"]."&nbsp;<input type=\"radio\" name=\"cn_srv\" value =\"".$servers[$loop]["cn"]."\"";
                                if ($loop==0) echo "checked";
                                echo "><BR>\n";
                        }
                        $form="<input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
                        $form ="<input type=\"hidden\" name=\"selected_srv\" value=\"true\">\n";
                        $form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
                        $form.="</form>\n";
                        echo $form;
                } else {
                        // Determination des parametres du serveur cible dans le cas d'une conf multi-serveurs
                        $serveur=search_machines ("(cn=$cn_srv)", "computers");
                        $stat_srv = $serveur[0]["l"];
                        $ipHostNumber =  $serveur[0]["ipHostNumber"];
                }
        }
        // Fin selection et recherche des caracteristiques du serveur
        // Phase 1 : Selection du partage a supprimer
        if ( !$End_ph1 &&  $stat_srv ) {
                echo "<h3>".gettext("Suppression d'un partage sur")." $cn_srv : </h3>\n";
                echo "<h6>".gettext("[Phase1] S&#233lection du partage &#224 supprimer :")."</h6>\n";
                // Recherche de la liste des partages supprimables
                if ( $stat_srv == "maitre" ) {
                        // Cas d'un serveur maitre
                        exec ("/bin/grep \"#</\" /etc/samba/smb_etab.conf", $AllOutPut, $ReturnValueShareName);
                } else {
                // Cas d'un serveur esclave
                        exec ("ssh -l remote_adm $ipHostNumber '/bin/grep \"#</\" /etc/samba/smb.conf'", $AllOutPut, $ReturnValueShareName);
                }
                // Fin recherche de la liste des partages supprimables
                if ( $ReturnValueShareName != 0 ) {
                        // Il n'y a pas de partages supprimables
                        echo $alerte_4;
                } else {
                        // Nettoyage des balises <> dans $AllOutPut
                        for ($loop=0; $loop<count ($AllOutPut); $loop++) {
                                $ShareName[$loop] = substr($AllOutPut[$loop],3,strlen($AllOutPut[$loop])-4);
                                #echo "DEBUG >> ".htmlentities($ShareName[$loop])."<br>";
                        }
                        // Presentation du form de selection du partage a supprimer
                        $form = "<form action=\"delete_share.php\" method=\"post\">\n";
                        $form .= "<p>".gettext("S&#233lectionnez le partage &#224 supprimer :")."</p>\n";
                        // Affichage liste des partageses a supprimer
                        $form .= "<select size=\"".$size."\" name=\"del_sharename\">\n";
                        for ($loop=0; $loop<count($ShareName);$loop++) {
                                $form .= "<option value=".$ShareName[$loop].">".$ShareName[$loop]."\n";
                        }
                        $form .= "</select><br>\n";
                        $form .= "<input type=\"hidden\" name=\"cn_srv\" value=\"$cn_srv\">\n";
                        $form .= "<input type=\"hidden\" name=\"stat_srv\" value=\"$stat_srv\">\n";
                        $form .= "<input type=\"hidden\" name=\"ipHostNumber\" value=\"$ipHostNumber\">\n";
                        $form .= "<input type='hidden' value='true' name='End_ph1'>\n";
                        $form .= "<p></p><input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
                        $form .= "<input type=\"submit\" value=\"Valider\"  onclick= \"return getconfirm();\"></p></p>\n";
                        $form .= "</form>\n";
                        echo $form;
                        // Fin presentation du form de selection du partage a supprimer
                }
        } elseif (  $End_ph1 &&  $stat_srv ) {
                // Phase 2  : Preparation du script admind.sh
                echo "<H3>".gettext("Suppresion du partage")." $del_sharename ".gettext("sur")." $cn_srv : </h3>\n";
                echo "<H6>".gettext("[Phase 2] :")."</h6>\n";
                // Creation du script bash pour admind
                $commandes = "#!/bin/bash\n";
                $commandes .= "SMBCONF=/etc/samba/smb_etab.conf\n";
                $commandes .= "SHARENAME=$del_sharename\n";
                $commandes .= "mv \$SMBCONF \$SMBCONF.share_orig\n";
                $commandes .= "test=true\n";
                $commandes .= "share=false\n";
		#===========================================================
		# AJOUT: 19/02/2006
                #$commandes .= "cat \$SMBCONF.share_orig | grep -B1000 \"include = /etc/samba/printers_se3/%m.inc\" > /etc/samba/smb.conf\n";
                #$commandes .= "cat \$SMBCONF.share_orig | grep -A1000 \"include = /etc/samba/printers_se3/%m.inc\" | grep -v \"include = /etc/samba/printers_se3/%m.inc\" > /etc/samba/fin_du_smb.conf\n";
		#===========================================================
                #$commandes .= "cat \$SMBCONF.share_orig | while (\$test)\n";
                $commandes .= "cat \$SMBCONF.share_orig | while (\$test)\n";
                $commandes .= "do\n";
                $commandes .= "       read ligne || test=false\n";
                $commandes .= " if [ \$test = false ]; then\n";
                $commandes .= "         exit 0;\n";
                $commandes .= " fi\n";
                $commandes .= "        if [ \"\$ligne\" = \"#<\$SHARENAME>\" ]; then\n";
                $commandes .= "                share=true\n";
                $commandes .= "        fi\n";
                $commandes .= "        if [ \$share = false ]; then\n";
                $commandes .= "         notab=false\n";
                $commandes .= "         echo \$ligne | grep \"\[\" > /dev/null && notab=true\n";
                $commandes .= "         echo \$ligne | grep \"#\" > /dev/null && notab=true\n";
                $commandes .= "                if [ \$notab = true ]; then\n";
                $commandes .= "                 echo \"\$ligne\" >>\$SMBCONF\n";
                $commandes .= "         else\n";
                $commandes .= "                 echo \" \$ligne\" >>\$SMBCONF\n";
                $commandes .= "         fi\n";
                $commandes .= "        fi\n";
                $commandes .= "        if [ \"\$ligne\" = \"#</\$SHARENAME>\" ]; then\n";
                $commandes .= "               share=false\n";
                $commandes .= "       fi\n";
                $commandes .= "done\n";
                // mel CR de creation ressources Classes
                $Subject=gettext("[SE3 T&#226;che d'administration] Suppresion d'un partage\n");
                list($user,$groups)=people_get_variables("admin", true);
                $mel_adm=$user["email"];
                $commandes.="\n#".gettext("Mel CR Suppresion d'un partage")."\n";
                $commandes.="cat > /tmp/admind.tmp <<-EOF\n";
                $commandes.= gettext("La suppression du partage")." $del_sharename\n";
                $commandes.= gettext("sur le serveur")." $cn_srv ".gettext("a &#233;t&#233; effectu&#233;e avec succ&#232;s.\n");
                $commandes.= "\n";
                $commandes.= "EOF\n";
                $commandes.= "mail -s \"$Subject\" $mel_adm < /tmp/admind.tmp\n";
                // Fin Preparation du script admind.sh

                // Creation du script tmp_$stat_srv.sh sur le serveur maitre
                $fp=@fopen("/var/remote_adm/tmp_".$stat_srv.".sh","w");
                if($fp) {
                        fputs($fp,$commandes."\n");
                        fclose($fp);
                        chmod ("/var/remote_adm/tmp_$stat_srv.sh", 0600);
                        // Si creation sur le maitre
                        if ( $stat_srv == "maitre" ) {
                                // Si pas de presence de admin.sh
                                if ( !is_file("/var/remote_adm/admin.sh") ) {
                                        // Renommage et chmod +x du script sur le maitre
                                        rename ("/var/remote_adm/tmp_".$stat_srv.".sh", "/var/remote_adm/admin.sh");
                                        chmod ("/var/remote_adm/admin.sh", 0750);
                                        if (file_exists("/var/remote_adm/admin.sh"))
                                                echo $info_1;
                                        else echo $alerte_3;
                                }  else {
                                        // Message d'alerte  : Presence d'un admin.sh !!
                                        echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
                                }
                        // Si creation sur un esclave
                        } elseif ( $stat_srv == "esclave" ) {
                                // Copie du maitre vers l'esclave
                                //  Recherche de la presence d'un admin.sh sur le serveur esclave
                                exec ("ssh -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
                                # echo "DEBUG >> ssh -v -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'<br>";
                                // Si pas de presence de admin.sh sur l'esclave
                                if (! $AllOutput[0]) {
                                        // Copie du script sur l'esclave avec scp
                                        exec ("/usr/bin/scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh", $AllOutput, $ReturnValue);
                                        # echo "DEBUG >> /usr/bin/scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh<br>";
                                        // chmod +x , renommage du script bash
                                        exec ("ssh -l remote_adm  $ipHostNumber 'chmod +x /var/remote_adm/tmp_$stat_srv.sh;mv  /var/remote_adm/tmp_$stat_srv.sh /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
                                        if ($ReturnValue==0) {
                                                // Effacement de tmp_esclave.sh cree sur le maitre
                                                unlink ("/var/remote_adm/tmp_esclave.sh");
                                                echo $info_1;
                                        } else echo $alerte_3;
                                } else {
                                        // Message d'alerte : Presence d'un admin_esclave.sh !!
                                        echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
                                }
                        } // Fin elseif ( $stat_srv == "esclave" )
                }  else { // Fin if ($fp)
                        echo "<div  class='error_msg'>".gettext("ERREUR : Impossible de cr&#233er le fichier d'ordonnancement de cr&#233ation de ressources classes !"). "</div>\n";
                }
        } // Fin elseif (  $End_ph1 &&  $stat_srv ) {
} // Fin if is_admin
  include ("pdp.inc.php");
