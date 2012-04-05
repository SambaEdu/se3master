#!/bin/bash
#shares_Win95: homes
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#shares_Seven: homes
#action: stop
#level: 99

# Remove firefox lock file
rm -f /home/"$1"/profil/appdata/Mozilla/Firefox/Profiles/default/parent.lock
