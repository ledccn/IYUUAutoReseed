@echo off
chcp 65001
git fetch --all
git reset --hard origin/master
git pull
%cd%\php\php.exe %cd%\iyuu.php
pause