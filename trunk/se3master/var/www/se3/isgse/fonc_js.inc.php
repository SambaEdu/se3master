<?php
/* $Id$ */

/* Philippe Chadefaux */

sajax_init();
$sajax_remote_uri = 'affiche_pc.php';
$sajax_request_type = 'POST';
sajax_export('return_parc');
sajax_handle_client_request();
?>


<script language="javascript">
	<?php sajax_show_javascript(); ?>
	
	function finish_return_parc(response) {
		var tableau = eval(response);
		var pc = tableau[0];

		var img = tableau[4];
        	if(img!="img_only") {
			document.getElementById(pc).innerHTML = tableau[1];
		}
		
		var imgdep = "img_"+tableau[0];
		document.getElementById(imgdep).src = tableau[2];
			 
	}

	function return_list(parc,type,etat) {
      		x_return_parc(parc,type,etat,finish_return_parc);
	}

	function confirm_del(parc,type,etat) {
		var text_1 = etat;
		if(text_1 == "del_sauvegarde") {
			if (confirm("Vous allez supprimer toutes les sauvegardes de cette machine. Voulez vous vraiment continuer ?")) {
				x_return_parc(parc,type,etat,finish_return_parc);
			}
		} else {	
			if (confirm("Voulez vous vraiment continuer")) {
				x_return_parc(parc,type,etat,finish_return_parc);
			}
		}	
	}	
	
	function pass_no_parc(type,etat) {
		x_return_no_parc(type,etat);
	}
	
      svbg=""
      function chng(obj,i) {
		if(i==0) obj.setAttribute("BGCOLOR", "#A8A8A8", false)
      		if(i==1)
          		if(obj==svbg) obj.setAttribute("BGCOLOR","#CDCDCD", false)
      	  		else obj.setAttribute("BGCOLOR", "#CDCDCD", false)
      		if(i==2) {
          		if(svbg!="") svbg.setAttribute("BGCOLOR","#CDCDCD", false)
          		svbg=obj
          		obj.setAttribute("BGCOLOR", "lime", false)
      		}
     }


</script>
					       
