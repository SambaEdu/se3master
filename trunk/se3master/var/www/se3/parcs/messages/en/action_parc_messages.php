<?
	/**

   * Action sur un parc (arret - start)
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs  Stephane Boireau - MrT Novembre 2008

   * @Licence Distribue selon les termes de la licence GPL

   * @note
   * Ajaxification des pings - script parc_ajax_lib.php sur une proposition de Stéphane Boireau
   * Gestion des infobulles nouvelle mouture Tip et UnTip
   * Modification des fonctions ts et vnc qui se trouvent desormais dans /var/www/se3/includes/fonc_parc.inc.php
   * Externalisation des messages dans messages/en/action_parc_messages.php dans un hash global
   * Messages en anglais
   */

   /**

   * @Repertoire: parcs/messages/en/
   * file: action_parc_messages.php

  */

	$action_parc=array();

	$action_parc['pageTitre'] = "Actions on computer equipment";

	$action_parc['btnEteindreTitre'] = "Switch off all the computers";
	$action_parc['msgConfirmEteindre'] = "Are you sure to switch off this computer equipment ?";
	$action_parc['msgConfirmEteindreMachine'] = "Are you sure to switch off this computer ";
	$action_parc['btnAllumerTitre'] = "Switch on all computers";
	$action_parc['btnProgrammerTitre'] = "Schedule";
	$action_parc['btnRafraichirTitre'] = "Refresh this page";

	$action_parc['arrayStationTitre'] = "COMPUTERS";
	$action_parc['arrayIp'] = "IP ADRESS";
	$action_parc['arrayEtatTitre'] = "STATUS";
	$action_parc['arrayConnexionTitre'] = "CONNECTIONS";
	$action_parc['arrayControleTitre'] = "CONTROL";

        
	//==============================
	$action_parc['btnRebooterTitre'] = "Reboot all computers";
	$action_parc['msgConfirmRebooter'] = "Are yous sure to reboot all computers of the parc $parc ?";
	$action_parc['msgConfirmRebooterMachine'] = "Are yous sure to reboot this computer ?";
	//==============================
        
	
	$action_parc['msgAttendre'] = "Wait please";

	$action_parc['msgSelect'] = "Select";
	$action_parc['msgSelectParc'] = "Select a computer equipment";
	$action_parc['msgNoParc'] = "There is no computer equipment";
	$action_parc['msgDelegationAccept'] = "Your delegation is accepted.";
	$action_parc['msgNoDelegation'] = "You have no delegation on this computer equipment";

	$action_parc['msgWaitRefresh'] = "Wait a moment please, so refresh the page the time for the computer to start or stop";
	$action_parc['msgNoActions'] = "No scheduled actions today for this computer equipment";
	$action_parc['msgShutdownAction'] = "Switch off all computers is scheduled at";
	$action_parc['msgPoweronAction'] = "Switch on all computers is scheduled at";

	$action_parc['msgUserLogged'] = " is actually logged on this computer";
	$action_parc['msgUserIsLogged']=" is actually logged";
	$action_parc['msgNoSignal'] = "No signal send<li>You have to do it by hand</i>).";
	$action_parc['msgSendReboot'] = "Reboot signal send";
	$action_parc['msgSendWakeup'] = "Warm up signal send";

	$action_parc['msgStationIsOn'] = "The computer is actuallty power on, click here to switch off";
	$action_parc['msgStationIsOff'] = "The computer is actuallty power off, click here to switch on";
	$action_parc['msgPingKo'] = "Ping failed";
	$action_parc['msgPortsClosed'] = "Ports are not opened";
	$action_parc['msgTsWarning'] = "Warning, you must have a xp/2000 terminal server client installed to initiaite a remote session on this computer<font color=#FF0000>The users will be logged out during this time !!</font>";
	$action_parc['msgVncWarning'] = "Warning, you must have a vnc client installed to initiaite a remote session on this computer";


	//echo(print_r($action_parc));

?>


