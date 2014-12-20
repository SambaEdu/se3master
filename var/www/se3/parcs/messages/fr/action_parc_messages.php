<?

/**

   * Action sur un parc (arret - start)
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs  Stephane Boireau - MrT Novembre 2008

   * @Licence Distribue selon les termes de la licence GPL

   * @note
   * Ajaxification des pings - script parc_ajax_lib.php sur une proposition de Stephane Boireau
   * Gestion des infobulles nouvelle mouture Tip et UnTip
   * Modification des fonctions ts et vnc qui se trouvent desormais dans /var/www/se3/includes/fonc_parc.inc.php
   * Externalisation des messages dans messages/fr/action_parc_messages.php dans un hash global
   * Messages en francais
   */

   /**

   * @Repertoire: parcs/messages/fr/
   * file: action_parc_messages.php

  */


	$action_parc=array();

	$action_parc['pageTitre'] = "Actions sur les stations";
	$action_parc['arrayLogonTitre'] = "Gpo";
	$action_parc['btnEteindreTitre'] = "Eteindre tous les postes";
	$action_parc['msgConfirmEteindre'] = "Etes-vous sur de vouloir &#233;teindre le parc $parc ?";
	$action_parc['msgConfirmEteindreMachine'] = "Etes-vous sur de vouloir &#233;teindre la machine ";
	$action_parc['btnAllumerTitre'] = "Allumer tous les postes";
	$action_parc['btnProgrammerTitre'] = "Programmer";
	$action_parc['btnRafraichirTitre'] = "Rafraichir la page";
    $action_parc['btnListerTitre'] = "Lister le parc";
        
	//==============================
	$action_parc['btnRebooterTitre'] = "Rebooter tous les postes";
	$action_parc['msgConfirmRebooter'] = "Etes-vous sur de vouloir rebooter le parc $parc ?";
	$action_parc['msgConfirmRebooterMachine'] = "Etes-vous sur de vouloir rebooter la machine ";
	//==============================
        
	$action_parc['arrayStationTitre'] = "STATIONS CONCERNEES";
    $action_parc['arrayIp'] = "ADRESSE IP";
	$action_parc['arrayEtatTitre'] = "ETAT";
	$action_parc['arrayConnexionTitre'] = "CONNEXION";
	$action_parc['arrayControleTitre'] = "CONTROLE";

	
	$action_parc['msgAttendre'] = "Patientez S.V.P";

	$action_parc['msgSelect'] = "S&#233;lectionner";
	$action_parc['msgSelectParc'] = "S&#233;lectionnez un parc";
	$action_parc['msgNoParc'] = "Il n'existe pas encore de parc";
	$action_parc['msgDelegationAccept'] = "Votre d&#233;l&#233;gation a &#233;t&#233; prise en compte pour l'affichage de cette page.";
	$action_parc['msgNoDelegation'] = "Vous n'avez pas de parc d&#233;l&#233;gu&#233;";

	$action_parc['msgWaitRefresh'] = "Veuillez patienter, puis rafraichir la page, le temps que la machine d&#233;marre ou stop";
	$action_parc['msgNoActions'] = "Pas d'actions pr&eacute;vues aujourd'hui sur le parc";
	$action_parc['msgShutdownAction'] = "Extinction des stations pr&eacute;vu &agrave;";
	$action_parc['msgPoweronAction'] = "Allumage des stations pr&eacute;vu &agrave;";

	$action_parc['msgUserLogged'] = " est actuellement connect&#233; sur ce poste";
	$action_parc['msgUserIsLogged']=" est actuellement connect&eacute;.";
	$action_parc['msgNoSignal'] = "Aucun signal envoy&eacute; (<i>il faudra rebooter manuellement</i>).";
	$action_parc['msgSendReboot'] = "Signal de reboot envoy&eacute;.";
	$action_parc['msgSendWakeup'] = "Signal de r&eacute;veil envoy&eacute;.";

	$action_parc['msgStationIsOn'] = "La machine est actuellement allum&#233e, cliquez pour l\'&#233;teindre";
	$action_parc['msgStationIsOff'] = "La machine est actuellement &#233;teinte, cliquez pour l\'allumer";
	$action_parc['msgPingKo'] = "Echec du ping";
	$action_parc['msgPortsClosed'] = "Ports non ouverts";
	$action_parc['msgTsWarning'] = "Attention, vous devez avoir un client xp/2000 ou un client terminal server pour acc&#233der &#224 la session distante du poste. <font color=#FF0000>Les utilisateurs seront temporairement d&#233connect&#233s pendant votre intervention !!</font>";
	$action_parc['msgVncWarning'] = "Attention, vous devez avoir un client vnc pour acc&#233der au serveur vnc du poste choisi.";


	//echo(print_r($action_parc));

?>


