   /**
   
   * Page qui teste les differents services
   * @Version $Id: tests.js 3002 2008-05-30 12:58:43Z keyser $
   *  
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note 
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
   * Modification du systeme d'infos bulles.(Nouvelle version de wz-tooltip Fonctions Tip('msg') TagToTip() UnTip() ) Ancienne version incompatible avec ajax
   * Externalisation des messages contenus dans les infos-bulles. 
   * Nouvelle organisation de l'arborescence.
 
   
   */

   /**

   * @Repertoire: /tests/js/
   * file: tests.js
   */

	var can_connect_internet=false;
	var ntpserver;
	
	function trim (myString) 	{
		return myString.replace(/^\s+/g,'').replace(/\s+$/g,'')
	} 
	
	function init() {
		//alert('Ajax works fine');
		//lancer a partir d'ici les divers process de test
		ntpserver=$('ntp_server').innerHTML;
		
		init_default_msg();
		
		//test ligne internet
		var url2 = './tests/test_internet.php';
		var params2 = '';
		var method2 = 'post';
		
		var url = './tests/test_gateway.php';
		var method = 'post';
		var params='';
		$('check_gateway').src = '../elements/images/spinner.gif';
		var ajax2 = new Ajax.Request(url,{ method: method, parameters: params, onSuccess: function(requester){
			if( requester.responseText == '1') {
				       $('check_gateway').src = '../elements/images/recovery.png';
					$('check_dns_se3').src = '../elements/images/spinner.gif';
					//DNS_SE3
					var url4 = './tests/test_dns_se3.php';
					var params4 = '';
					var method4 = 'post';
					var ajax32 = new Ajax.Request(url4,{ method: method4, parameters: params4, onSuccess: function(requester){
					var reponse4 = requester.responseText;	
					if(reponse4 == '1')
						$('check_dns_se3').src = '../elements/images/recovery.png';
					else
						$('check_dns_se3').src = '../elements/images/critical.png';
					}});


				}
			else
				$('check_gateway').src = '../elements/images/critical.png';
		}});	
					
		//test cles
		$('check_keys').src = '../elements/images/spinner.gif';
		var url9 = './tests/test_keys.php';
		var params9 = '';
		var method9 = 'post';
		var ajax39 = new Ajax.Request(url9,{ method: method9, parameters: params9, onSuccess: function(requester){
			var reponse9 = requester.responseText;	
			if(reponse9 == '1') {
				$('check_keys').src = '../elements/images/recovery.png';
				$('check_keys').onmouseover= function(){ return false; };

			} else {
				$('check_keys').src = '../elements/images/critical.png';
				$('check_keys').onmouseover= function() {
				UnTip();
				Tip(msg_keys_ko,WIDTH,250,SHADOW,true,DURATION,duration);
				this.onmouseout=function() { UnTip(); }
				}
				$('link_keys').href = link_keys_ko;
			}
			
		
		}});

		//test VBS
		$('check_vbs').src = '../elements/images/spinner.gif';
		var url10 = './tests/test_vbs.php';
		var params10 = '';
		var method10 = 'post';
		var ajax310 = new Ajax.Request(url10,{ method: method10, parameters: params10, onSuccess: function(requester){
		var reponse10 = requester.responseText;	
		if(reponse10 == '1') {
			$('check_vbs').src = '../elements/images/recovery.png';
			$('check_vbs').onmouseover= function(){ return false; };

		}
		else {
			$('check_vbs').src = '../elements/images/critical.png';
			$('check_vbs').onmouseover= function() {
				UnTip();
				Tip(msg_vbs_ko,WIDTH,250,SHADOW,true,DURATION,duration);
				this.onmouseout=function() { UnTip(); }
				}

			$('link_vbs').href = link_vbs_ko;

			}
		}});
//test clonage
		$('check_clonage').src = '../elements/images/spinner.gif';
		var url13 = './tests/test_clonage.php';
		var params13 = '';
		var method13 = 'post';
		var ajax313 = new Ajax.Request(url13,{ method: method13, parameters: params13, onSuccess: function(requester){
		var reponse13 = requester.responseText;	
                
              //  if (reponse13 != '-1') {
		
		//	Element.show('ligne_clonage');
                    
                
		if(reponse13 == '1') {
			$('check_clonage').src = '../elements/images/recovery.png';
			$('check_clonage').onmouseover= function(){ return false; };

		}
		else {
			$('check_clonage').src = '../elements/images/critical.png';
			$('check_clonage').onmouseover= function() {
				UnTip();
				Tip(msg_clonage_ko,WIDTH,250,SHADOW,true,DURATION,duration);
				this.onmouseout=function() { UnTip(); }
				}

			$('link_clonage').href = link_clonage_ko;

			}
                        
                //} else {
	//		Element.hide('ligne_clonage');
		//}
		}});
            
		//test client
		$('check_client').src = '../elements/images/spinner.gif';
		var url101 = './tests/test_client.php';
		var params101 = '';
		var method101 = 'post';
		var ajax3101 = new Ajax.Request(url101,{ method: method101, parameters: params101, onSuccess: function(requester){
		var reponse101 = requester.responseText;	
		if(reponse101 == '1') {
			$('check_client').src = '../elements/images/recovery.png';
			

			}
		else {
			$('check_client').src = '../elements/images/critical.png';
			$('check_client').onmouseover= function() {
							UnTip();
							Tip(msg_client_ko,STICKY,true,WIDTH,250,SHADOW,true,DURATION,duration);
							this.onmouseout=function() { UnTip(); }
						}
						$('link_client').href = link_client_ko;

		}
		}});


		//test services
		$('check_mail').src = '../elements/images/spinner.gif';
		$('check_smb').src = '../elements/images/spinner.gif';
		$('check_sid').src = '../elements/images/spinner.gif';
		$('check_mysql').src = '../elements/images/spinner.gif';
		
		
		$('check_ondul').src = '../elements/images/spinner.gif';


		var url11 = './tests/test_services.php';
		var params11 = '';
		var method11 = 'post';
		var ajax311 = new Ajax.Request(url11,{ method: method11, parameters: params11, onSuccess: function(requester){
		var reponse11 = eval(requester.responseText);	
		eval(requester.responseText);
		if(arr_services[0] == '1')
			$('check_mail').src = '../elements/images/recovery.png';
		else
			$('check_mail').src = '../elements/images/critical.png';
		if(arr_services[1] == '1')
			$('check_smb').src = '../elements/images/recovery.png';
		else {
			$('check_smb').src = '../elements/images/critical.png';
			$('check_smb').onmouseover= function(){ 
										UnTip();
										Tip(msg_samba_ko);
										this.onmouseout=function() { UnTip(); }
			}
			$('link_samba').href = link_samba_ko;

		}
		if(arr_services[2] == '1')
			$('check_sid').src = '../elements/images/recovery.png';
		else {
			$('check_sid').src = '../elements/images/critical.png';
			$('check_sid').onmouseover= function(){ 
										UnTip();
										Tip(msg_sid_ko);
										this.onmouseout=function() { UnTip(); }
			}

		}
		if(arr_services[3] == '1')
			$('check_mysql').src = '../elements/images/recovery.png';
		else
			$('check_mysql').src = '../elements/images/critical.png';
		
		if (arr_services[4] != '-1') {
		
			Element.show('ligne_dhcp');
			if(arr_services[4] == '1') {
				$('check_dhcp').src = '../elements/images/recovery.png';
				$('check_dhcp').onmouseover= function(){ 
										UnTip();
										Tip(msg_dhcp_ok);
										this.onmouseout=function() { UnTip(); }
				}

			}
			else {
				$('check_dhcp').src = '../elements/images/critical.png';
				$('check_dhcp').onmouseover= function(){ 
										UnTip();
										Tip(msg_dhcp_ko);
										this.onmouseout=function() { UnTip(); }
				}

			}
			
		} else {
			Element.hide('ligne_dhcp');
		}
		
		if(arr_services[5] == '1') {
			$('check_ondul').src = '../elements/images/recovery.png';
			$('check_ondul').onmouseover= function(){ 
										UnTip();
										Tip(msg_ondul_ok);
										this.onmouseout=function() { UnTip(); }
			}
			$('link_ondul').href = link_ondul_ok;
			$('help_ondul_se3').onmouseover= function(){ 
										UnTip();
										Tip(msg_ondul_ok);
										this.onmouseout=function() { UnTip(); }
			}

		}
		else
		{
			$('check_ondul').src = '../elements/images/warning.png';
			$('check_ondul').onmouseover= function(){ 
										UnTip();
										Tip(msg_ondul_ko);
										this.onmouseout=function() { UnTip(); }
			}
			$('link_ondul').href = link_ondul_ko;
			$('help_ondul_se3').onmouseover= function(){ 
										UnTip();
										Tip(msg_ondul_ko_info);
										this.onmouseout=function() { UnTip(); }
			}


		}

		

		}});

		//test services
		$('check_disk1').src = '../elements/images/spinner.gif';
		$('check_disk2').src = '../elements/images/spinner.gif';
		$('check_disk3').src = '../elements/images/spinner.gif';
		$('check_disk4').src = '../elements/images/spinner.gif';
		


		var url12 = './tests/test_disks.php';
		var params12 = '';
		var method12 = 'post';
		var ajax312 = new Ajax.Request(url12,{ method: method12, parameters: params12, onSuccess: function(requester){
		var reponse12 = eval(requester.responseText);	
		
		$('space_disk1').innerHTML = '<I>- Espace occup&#233;: ( '+arr_space_disks1[0]+' % )</I>';
		$('space_disk2').innerHTML = '<I>- Espace occup&#233;: ( '+arr_space_disks2[0]+' % )</I>';
		$('space_disk3').innerHTML = '<I>- Espace occup&#233;: ( '+arr_space_disks3[0]+' % )</I>';
		$('space_disk4').innerHTML = '<I>- Espace occup&#233;: ( '+arr_space_disks4[0]+' % )</I>';
	

		if(arr_space_disks1[0] < 96)
			$('check_disk1').src = '../elements/images/recovery.png';
		else
			$('check_disk1').src = '../elements/images/critical.png';
		if(arr_space_disks2[0] < 96)
			$('check_disk2').src = '../elements/images/recovery.png';
		else
			$('check_disk2').src = '../elements/images/critical.png';
		if(arr_space_disks3[0] < 96)
			$('check_disk3').src = '../elements/images/recovery.png';
		else
			$('check_disk3').src = '../elements/images/critical.png';
		if(arr_space_disks4[0] < 96)
			$('check_disk4').src = '../elements/images/recovery.png';
		else
			$('check_disk4').src = '../elements/images/critical.png';
		
		

		$('help_disk1').onmouseover= function() {
			Tip('Partition root /<br>Espace total: <b>'+arr_space_disks1[1]+' Go</b><br>Espace occup&#233;: <b>'
			+arr_space_disks1[2]+' Go</b><br>Espace disponible: <b>'+arr_space_disks1[3]+' Go</b>');
			this.onmouseout = function() { UnTip(); }
		}
		
		$('help_disk2').onmouseover= function() {
			Tip('Partition /var/se3<br>Espace total: <b>'+arr_space_disks2[1]+' Go</b><br>Espace occup&#233;: <b>'
			+arr_space_disks2[2]+' Go</b><br>Espace disponible: <b>'+arr_space_disks2[3]+' Go</b>');
			this.onmouseout = function() { UnTip(); }
		}
		$('help_disk3').onmouseover= function() {
			Tip('Partition /home<br>Espace total: <b>'+arr_space_disks3[1]+' Go</b><br>Espace occup&#233;: <b>'
			+arr_space_disks3[2]+' Go</b><br>Espace disponible: <b>'+arr_space_disks3[3]+' Go</b>');
			this.onmouseout = function() { UnTip(); }
		}
		$('help_disk4').onmouseover= function() {
			Tip('Partition /var<br>Espace total: <b>'+arr_space_disks4[1]+' Go</b><br>Espace occup&#233;: <b>'
			+arr_space_disks4[2]+' Go</b><br>Espace disponible: <b>'+arr_space_disks4[3]+' Go</b>');
			this.onmouseout = function() { UnTip(); }
		}
		
		
		}});


		$('check_internet').src = '../elements/images/spinner.gif';
		var ajax3 = new Ajax.Request(url2,{ method: method2, parameters: params2, onSuccess: function(requester){
			can_connect_internet = (requester.responseText == '0%');	
			if(can_connect_internet) {
				
				var ajax311 = new Ajax.Request('tests/popup_alert.php',{ onSuccess: function(requester){
					eval(requester.responseText);	
				}});

				$('check_internet').src = '../elements/images/recovery.png';
				//DNS
				var url3 = './tests/test_dns.php';
				var params3 = '';
				var method3 = 'post';
				
				$('check_dns').src = '../elements/images/spinner.gif';
				var ajax31 = new Ajax.Request(url3,{ method: method3, parameters: params3, onSuccess: function(requester){
					var reponse3 = requester.responseText;	
					if(reponse3 == '1') {
						$('check_dns').src = '../elements/images/recovery.png';
						$('check_dns').onmouseover= function(){ return false; };

						}
					else
						$('check_dns').src = '../elements/images/critical.png';
				}});

				
				//MAJ
				var url8 = './tests/test_maj.php';
				var params8 = '';
				var method8 = 'post';
				
				$('check_maj').src = '../elements/images/spinner.gif';
				var ajax38 = new Ajax.Request(url8,{ method: method8, parameters: params8, onSuccess: function(requester){
					var reponse8 = parseInt(requester.responseText,10);	
					
					if (-1 == reponse8) {
						$('check_maj').src = '../elements/images/info.png';
						$('link_maj').href='#';
						$('check_maj').onmouseover= function() {
							UnTip();
							Tip(msg_maj_nocx,WIDTH,250,SHADOW,true,DURATION,duration);
							this.onmouseout=function() { UnTip(); }
						}	
					}
                                       	
					if (1 == reponse8) {
						$('check_maj').src = '../elements/images/recovery.png';
						$('check_maj').onmouseover= function() {
							UnTip();
							Tip(msg_maj_ok,WIDTH,250,SHADOW,true,DURATION,duration);
							this.onmouseout=function() { UnTip(); }
						}	
					}
					
					if (0 == reponse8)  {
						$('check_maj').src = '../elements/images/critical.png';
						$('check_maj').onmouseover= function() {
							UnTip();
							Tip(msg_maj_ko,STICKY,true,WIDTH,250,SHADOW,true,DURATION,duration);
							this.onmouseout=function() { UnTip(); }
						}
						$('link_maj').href = link_maj_ko;

					}
				}});

				//SECU
				var url81 = './tests/test_secu.php';
				var params81 = '';
				var method81 = 'post';
				
				$('check_secu').src = '../elements/images/spinner.gif';
				var ajax381 = new Ajax.Request(url81,{ method: method81, parameters: params81, onSuccess: function(requester){
					var reponse81 = requester.responseText;	
					if(reponse81 == '1') {
						$('check_secu').src = '../elements/images/recovery.png';
						$('check_secu').onmouseover= function(){ return false; };

						}
					else {
						$('check_secu').src = '../elements/images/warning.png';
						$('check_secu').onmouseover= function() {
							UnTip();
							Tip(msg_secu_ko,STICKY,true,WIDTH,250,SHADOW,true,DURATION,duration);
							this.onmouseout=function() { UnTip(); }
						}
						$('link_secu').href = link_secu_ko;

					}
				}});


					//WEB
					$('check_web').src = '../elements/images/spinner.gif';
					var url6 = './tests/test_web.php';
					var params6 = '';
					var method6 = 'post';
					var ajax34 = new Ajax.Request(url6,{ method: method6, parameters: params6, onSuccess: function(requester){
					var reponse6 = requester.responseText;	
					if(reponse6 == '1') {
						$('check_web').src = '../elements/images/recovery.png';
						$('check_web').onmouseover= function(){ return false; };

					}
					else
						$('check_web').src = '../elements/images/critical.png';
					}});

					//NTP
					$('check_ntp').src = '../elements/images/spinner.gif';
					var url7 = './tests/test_ntp.php';
					var params7 = '';
					var method7 = 'post';
					var ajax35 = new Ajax.Request(url7,{ method: method7, parameters: params7, onSuccess: function(requester){
						var reponse7 = requester.responseText;
						
						if(reponse7 == '1') {
							$('check_ntp').src = '../elements/images/recovery.png';
							$('check_ntp').onmouseover= function(){ 
								UnTip();
								this.onmouseout=function() { UnTip(); }

							}

							Element.show('ligne_date');
							$('check_time').src = '../elements/images/spinner.gif';

							var ajax35 = new Ajax.Request('tests/test_time.php',{ onSuccess: function(requester){
								var reponse735 = requester.responseText;
								
								if(reponse735 == '1') 
									$('check_time').src = '../elements/images/recovery.png';
								else {
								
									$('check_time').src = '../elements/images/critical.png';
									$('check_time').onmouseover= function(){ 
										UnTip();
										Tip(msg_time_ko);
										this.onmouseout=function() { UnTip(); }
									}

									$('link_time').href = link_time_ko;
								}

							}});

						} else {
							$('check_ntp').src = '../elements/images/critical.png';
							Element.hide('ligne_date');
							$('check_ntp').onmouseover= function(){ 

								if (can_connect_internet)
									Tip(msg_ntp_ko,WIDTH,250,SHADOW,true,DURATION,duration);
								else	
									Tip(msg_ntp_nocx,WIDTH,250,SHADOW,true,DURATION,duration);
							}
						}
					}});

				
				

				
				}
			else
				$('check_internet').src = '../elements/images/critical.png';
					
		}});

		
		
		
	}

	Event.observe(window,'load',init,false);

