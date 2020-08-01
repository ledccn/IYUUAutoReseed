@echo off
chcp 65001
git fetch --all
git reset --hard origin/master
php %~dp0iyuu.php
pause