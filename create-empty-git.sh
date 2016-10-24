#!/bin/bash
FICHIER_RESULT="dossiers-manquants"
	cat $FICHIER_RESULT | while read dossier
				do
					echo "TRaitement du dossier $dossier"
					mkdir -p "$dossier"
					touch "$dossier/.empty"
				done
exit 0