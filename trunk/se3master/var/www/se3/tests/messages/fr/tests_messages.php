<?php
 /**
   
   * Page qui teste les differents services
   * @Version $Id$ 
   * 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note 
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
   * Modification du systeme d'infos bulles.(Nouvelle version de wz-tooltip) Ancienne version incompatible avec ajax
   * Externalisation des messages contenus dans les infos-bulles
   * Fonctions Tip('msg') et UnTip();
   * Nouvelle organisation de l'arborescence.
   * Passage des messages en php
   * Ce script contient les messages et les liens de la page /var/www/se3/tests.php
   */

   /**

   * @Repertoire: /tests/messages/fr/
   * file: tests_messages.php
   */

// Ce script contient les messages en francais des infos bulles. 

  
//maj serveur
	$tests_msg = array();
	$tests_msg['msg_maj_nocx'] ='Impossible de v&#233;rifier les mises &#224; jour';
	$tests_msg['msg_maj_ok'] ='Etat : serveur &#224; jour';
	$tests_msg['msg_maj_ko'] = 'Le serveur n\'est pas &#224; jour ! <br />Cliquer ici pour mettre &#224; jour';
	$tests_msg['link_maj_ko'] ='../majphp/majtest.php';
	$tests_msg['msg_maj_info']='V&#233;rifie si votre serveur est &#224; jour.<br>Si ce n\'est pas le cas, vous pouvez le mettre &#224; jour &#224; partir <a href='.$tests_msg['link_maj_ko'].'>d\'ici</a>';

// Controle l'installation des cles
	$tests_msg['link_keys_ko'] = '../registre/gestion_interface.php';
       $tests_msg['msg_keys_ok'] = 'Cliquer ici pour importer les cl&#233;s<br /><a href=\"'+$tests_msg['link_keys_ko']+'\"></a>';
	$tests_msg['msg_keys_nocx'] = 'Impossible de mettre &#224; jour les cl&#233;s, sans connexion &#224; internet';
	$tests_msg['msg_keys_info'] = 'Si vous n\'avez pas install&#233; les cl&#233;s des registres,<br>vous devez aller dans <a href=\"'.$tests_msg['link_keys_ko'].'\">Gestion des clients Windows</a> et cliquer sur effectuer la mise &#224; jour de la base des cl&#233;s';

// Controle l'installation des vbs
	
	$tests_msg['link_vbs_ko'] = '../test.php?action=installse3-domain';
	$tests_msg['msg_vbs_ko'] = 'Cliquer ici pour installer le paquet se3-domain';
	$tests_msg['msg_vbs_nocx'] = 'Impossible de mettre &#224; jour se3-domain, sans connexion &#224; internet';
	$tests_msg['msg_vbs_info'] = 'Les scripts d\'int&#233;gration permettent de configurer vos clients Windows afin qu\'ils joignent facilement le domaine. <br><br>Vous devez installer Se3-domain afin de disposer de ces scripts <br><br>Une fois les scripts install&#233;s, pour ajouter une machine XP, connectez vous en administrateur local sur la machine, puis recherchez le serveur SambaEdu. Puis allez dans /Progs/install/domscripts/ et lancez le script rejoinSE3.exe.<br><br>L\'installation de se3-domain se fait avec cette <a href=\"'.$tests_msg['link_vbs_ko'].'\">page</a>';

// Controle installation dispos clonage 
	
	$tests_msg['link_clonage_ko'] = '../tftp/config_tftp.php';
	$tests_msg['msg_clonage_ko'] = 'Cliquer ici pour mettre &#224; jour les dispositifs du paquet se3-clonage';
	$tests_msg['msg_clonage_nocx'] = 'Impossible de mettre &#224; jour les dispositifs sans connexion &#224; internet';
	$tests_msg['msg_clonage_info'] = 'Les dispositfs du paquet se3-clonage sont ind&#233;pendants et sont mis &#224; jour depuis la page de configuration. <br><br>A lancer depuis cette <a href=\"'.$tests_msg['link_clonage_ko'].'\">page</a>';

              
        
//########################### CONNEXIONS ################################################/

// Verification des connexions
	
	$tests_msg['msg_gateway_info'] = 'Test si la passerelle est joignable.<br> Si la r&#233;ponse est n&#233;gative, cela peut vouloir dire que votre routeur n\'est pas pingable, ou que celui-ci est mal configur&#233;.<br>La passerelle est le routeur ou machine qui est le passage obligatoire pour aller sur internet. Si celui-ci est en erreur, mais que vous pouvez vous connecter &#224; internet ne pas tenir compte de ce test.';

// Ping internet

	$tests_msg['msg_net_info'] = 'Test si une machine sur internet est joignable.<br><br> Si la r&#233;ponse est n&#233;gative, vous devez v&#233;rifier votre connexion internet.<br><br> - Si la connexion &#224; votre routeur &#233;tait en erreur, vous devez commencer par corriger la route par defaut puis retester <br><br> - Si vous avez un Slis devant ne pas oublier de laisser internet accessible depuis cette machine<br><br> - Ne pas oublier de d&#233;clarer le proxy si vous en avez un, pour acc&#232;der &#224; internet.';

// Verifie DNS
	$tests_msg['msg_dns_nocx']='Test de la r&#233;solution DNS impossible, sans connexion &#224; internet';

	$tests_msg['msg_dns_info']='V&#233;rifie si la r&#233;solution DNS est correcte<br>Si vous avez une erreur, vous devez v&#233;rifier que le fichier /etc/resolv.conf est bien configur&#233;.';
	$tests_msg['msg_dns2_info']='Le nom DNS que vous avez donn&#233; &#224; votre serveur Se3 ne peut &#234;tre trouv&#233;. Sans un nom correct, vous ne pourrez pas faire la mise &#224; jour des cl&#233;s des registres. Vous pouvez soit ajouter dans le DNS de votre Slis ou LCS le serveur Se3, soit mettre l\'adresse IP &#224; la place, par exemple http://172.16.0.2:909. Pour cela  <a href=\'../conf_params.php?cat=1\'>modifier le champ urlse3</a>';

// Contact serveur de mise a jour ftp

	$tests_msg['msg_ftp_nocx'] ='Impossible de tester la connexion au FTP des mises &#224; jour, sans connexion &#224; internet';
	$tests_msg['msg_ftp_info']='Test une connexion au serveur ftp de mises &#224; jour.<br><br>Si la r&#233;ponse est n&#233;gative, et que les pr&#233;c&#233;dentes r&#233;ponses<br /> &#233;taient positives, v&#233;rifier d\'abord que le serveur ftp r&#233;pond bien<br /> &#224; partir d\'un simple navigateur.<br><br>Il se peut que celui-ci soit ne soit pas joignable (panne...!).';

// Verifie l'acces au serveur web pour la maj des cles

	$tests_msg['msg_web_nocx'] ='Impossible de tester la connexion au web, sans connexion &#224; internet';
	$tests_msg['msg_web_info']='Teste si une machine sur internet est joignable sur le port 80 (Web).<br><br>Si la r&#233;ponse est n&#233;gative, vous devez v&#233;rifier votre connexion internet.<br><br>Si vous avez un Slis ou un autre proxy devant ne pas oublier de laisser <br /> internet accessible depuis cette machine et si vous n\'avez pas activ&#233; le<br /> proxy transparent, v&#233;rifier que dans /etc/profile le proxy est bien renseign&#233;.';

// Verification de la connexion au serveur de temps
//'Impossible de tester l\'acc&#232;s au serveur de temps, sans connexion &#224; internet'
	
	$tests_msg['msg_ntp_ko'] = 'Le serveur de temps est injoignable.';
	$tests_msg['msg_ntp_nocx'] ='Impossible de tester le serveur de temps, sans connexion &#224; internet';
	$tests_msg['msg_ntp_info']='Si le serveur de temps que vous avez indiqu&#233;  n\'est pas joingnable et si votre connexion internet semble correcte,<br><b> v&#233;rifier :</b><br><br> - Si vous avez un Slis de bien avoir comme serveur de temps le Slis lui m&#234;me (par exmple 172.16.0.1).<br> - Que votre proxy (routeur...etc) laisse passer en sorti, les connexions vers le port 123 UDP.<br><br>La modification s\'effectue <a href=../conf_params.php?cat=1>ici</a>';


	$tests_msg['msg_time_info']='V&#233;rifie si votre serveur est &#224; l\'heure par rapport au serveur de temps.<br>Cette diff&#233;rence doit rester inf&#233;rieure &#224; 60 sec';
	$tests_msg['msg_time_ko']='Cliquer ici pour mettre &#224; l\'heure votre serveur';
	$tests_msg['link_time_ko'] ='../test.php?action=settime';

//######################## CONTROLE LES SERVICES ##################################//
//'Cliquer ici pour mettre &#224; l\'heure votre serveur'
//'V&#233;rifie si votre serveur est &#224; l\'heure par rapport au serveur")." $ntpserv.<br>".gettext("La diff&#233;rence est actuellement de $voir sec. Cette diff&#233;rence doit rester inf&#233;rieure &#224; 60 sec'
//'Cliquer ici pour configurer l\'exp&#233;dition de mail'
//<a href=\"../conf_smtp.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a>
	
	$tests_msg['msg_mail_info']='V&#233;rifie si votre serveur est configur&#233; pour vous exp&#233;dier des mails en cas de probl&#232;me.<BR>Si ce n\'est pas le cas vous devez <a href=../conf_smtp.php>renseigner les informations permettant d\'envoyer des mails</a>';

// Test le serveur smb
//'Cliquer ici pour essayer de relancer samba'
//<a href=\"../test.php?action=startsamba\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a>
	$tests_msg['msg_samba_ko'] ='Cliquer ici pour essayer de relancer samba';
	$tests_msg['link_samba_ko']='../test.php?action=startsamba';
	$tests_msg['msg_samba_info']='Teste une connexion au domaine.<br /> Si celui-ci est en Echec, v&#233;rifiez qu\'il est bien d&#233;marr&#233;. Pour le d&#233;marrer /etc/init.d/samba start';

	$tests_msg['msg_sid_ko'] = 'Attention : des sid diff&#233;rents sont d&#233;clar&#233;s dans l\'annuaire, mysql et le secrets.tdb';
	$tests_msg['msg_sid_info']='Teste la pr&#233;sence d\'&#233;ventuels doublons de SID.<br><br>Lancez la commande <b>/usr/share/se3/scripts/correctSID.sh</b> pour identifier et r&#233;soudre le probl&#232;me de SID.';
 
// Test la base MySQL
	
	$tests_msg['msg_mysql_info']='Teste l\'int&#233;grit&#233; de votre base MySQL, par rapport &#224; ce qu\'elle devrait avoir.<br><br>Si cela est en erreur, lancer la commande <b>/usr/share/se3/sbin/testMySQL -v</b> afin de connaitre la cause du probl&#232;me.';

// Controle si le dhcp tourne si celui-ci a ete installe
	$tests_msg['msg_dhcp_ok']='Serveur DHCP actif';
	$tests_msg['msg_dhcp_ko']='Serveur DHCP inactif';
	$tests_msg['msg_dhcp_info']='Test l\'&#233;tat du serveur DHCP.<br> Pour l\'activer ou le d&#233;sactiver aller sur <a href=dhcp/config.php>la page suivante</a>.';
	
// Test la presence d'un onduleur
//'Etat de l\'onduleur'
	$tests_msg['link_ondul_ok'] = '../cgi-bin/nut/upsstats.cgi';
	$tests_msg['link_ondul_ko'] ='../ups/ups.php';
	$tests_msg['msg_ondul_ok'] = 'Etat de l\'onduleur';
	$tests_msg['msg_ondul_ko'] = 'Configurer un onduleur';
	$tests_msg['msg_ondul_ko_info']='Test la pr&#233;sence et l\'&#233;tat d\'un onduleur<BR><BR>Il n\'y a pas d\'onduleur d&#233;tect&#233; sur ce serveur.<br>Cela peut provoquer la perte des donn&#233;es. On vous conseille d\'en installer un.';
	
//################################### DISQUES #########################################################//
// Disques
// 

// Securite
	$tests_msg['link_secu_ko'] ='../test.php?action=updatesystem';
	$tests_msg['msg_secu_ko'] ='Cliquez sur ce bouton pour lancer la mise &#224; jour syst&#232;me via l\'interface. Vous pouvez aussi effectuer la mise &#224; jour en ligne de commande en lancant le script <b>se3_update_system.sh</b>';
	$tests_msg['msg_secu_nocx'] ='Impossible de tester les mises &#224; jour de s&#233;curit&#233; Debian, sans connexion &#224; internet';
	$tests_msg['msg_secu_info'] ='Teste si ce serveur est bien &#224; jour par rapport au serveur de s&#233;curit&#233; de Debian.<br><br>Pour mettre &#224; jour votre serveur, utilisez l\'interface ou lancez le script <b>se3_update_system.sh</b> dans une console<br><br>Attention, cela entraine aussi la mise &#224; jour des paquets Se3';

// Clients
	$tests_msg['msg_client_ko']='Le mot de passe samba du compte adminse3 correspond pas avec le contenu de se3db, voir l\'aide pour pour corriger le probl&#232;me';
	$tests_msg['link_client_ko'] ='../test.php?action=setadminse3smbpass';

	$tests_msg['msg_client_info']='V&#233;rifie que le mot de passe samba du compte adminse3 .<br><br>Si ce n\'est pas le cas, vous ne pourrez pas int&#233;grer de nouvelles machines.<br><br>Dans ce cas pour reforcer ce mot de passe, taper la commande : <br><br><b>smbpasswd adminse3</b><br><br>Puis taper le mot de passe qui correspond &#224; celui de la BDD.';
	
	
?>
