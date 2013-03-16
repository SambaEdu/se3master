
if not exist I:\ net use I: \\se3\Docs
if not exist H:\ net use H: \\se3\Classes
if not exist L:\ net use L: \\se3\Progs
echo %USERNAME
if %USERNAME% == admin goto admin
goto imp

:admin

if not exist X:\ net use X: \\se3\admhomes
if not exist Y:\ net use Y: \\se3\admse3

pause
:imp
start /B cscript //B %SYSTEMROOT%\Printers.vbs
