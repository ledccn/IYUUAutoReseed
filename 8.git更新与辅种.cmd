@echo off
chcp 65001
git fetch --all
git reset --hard origin/master
#git pull
php %cd%\iyuu.php
pause