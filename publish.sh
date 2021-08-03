#!/bin/bash
#########################################################################
# Author: yaofei
# File Name: publish.sh
# Description: 上线脚本
#########################################################################

# rsync -avzP --delete dist/datahub_php/ root@170.106.6.136:/var/www/datahub_php

git add .
git commit --amend -m 'test'
git push origin master -f
ssh root@170.106.6.136 "cd /var/www/datahub_php; git fetch --all; git reset --hard origin/master;"
