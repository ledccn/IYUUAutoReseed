@echo off
chcp 65001
git fetch --all
git reset --hard origin/master
git pull
%cd%\php-7.4.2-nts-Win32-vc15-x86\php %cd%\iyuu.php
pause