#!/bin/sh
cd /root/IYUUAutoReseed
git fetch --all
git reset --hard origin/master
git pull
