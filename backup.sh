#!/bin/sh
# 传入的种子备份参数
if [ $1 ]; then
	AppName=$1
else
	echo 'AppName not null'
	exit 1
fi

if [ $2 ]; then
	torrentDir=$2
else
	echo 'torrentDir not null'
	exit 2
fi

# 脚本当前目录
pwddir=$(cd $(dirname $0); pwd)
# 当前日期
DATE=$(date +%Y%m%d)
# 备份在当前目录
backupdir=$pwddir"/"$AppName$DATE
echo "种子备份目录："$backupdir
mkdir $backupdir -p
# 种子目录
torrentDir=$torrentDir"/*"
# 备份
cp -rf $torrentDir $backupdir
# 成功提示
echo "ok";