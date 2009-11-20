#======================================
# ~/.bashrc: executed by bash(1) for non-login shells.

#export PS1='\h:\w\$ '
BLEU="\033[1;34m"
GRIS="\033[0;37m"
JAUNE="\033[1;33m"
VERT="\033[1;32m"
BLANC="\033[1;37m"
ROUGE="\033[1;31m"
CYAN="\033[0;36m"

if [ -z "$SSH_TTY" ]; then
	export PS1=${JAUNE}'\d \t'${GRIS}' '${VERT}'\u@\h'${BLANC}':'${BLEU}'\w'${BLANC}'\n\$ '
else
	DOMDNS=$(grep "^search " /etc/resolv.conf | cut -d" " -f2)
fi

if [ "$TERM" = "screen" ]; then
	export PS1=${JAUNE}'\d \t'${GRIS}' '${CYAN}'\u@\h.'${DOMDNS}${BLANC}':'${BLEU}'\w'${BLANC}'\n\$ '
else
	export PS1=${JAUNE}'\d \t'${GRIS}' '${ROUGE}'\u@\h.'${DOMDNS}${BLANC}':'${BLEU}'\w'${BLANC}'\n\$ '
fi

umask 022

# You may uncomment the following lines if you want `ls' to be colorized:
export LS_OPTIONS='--color=auto'
eval "`dircolors`"
alias ls='ls $LS_OPTIONS'
alias ll='ls $LS_OPTIONS -l'
alias l='ls $LS_OPTIONS'

alias h="history"
alias grep="grep --color"


alias df='df -h'
alias free='free -m'
alias vi='vim'
#
# Some more alias to avoid making mistakes:
alias rm='rm -i'
alias cp='cp -i'
alias mv='mv -i'
#======================================
