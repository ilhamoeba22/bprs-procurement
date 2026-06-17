#!/bin/bash
TARGET=/home/bprw7255/bprs_procurement
GIT_DIR=/home/bprw7255/repos/bprs-procurement.git

git --work-tree=$TARGET --git-dir=$GIT_DIR checkout -f main

cd $TARGET
# php artisan view:clear
# php artisan cache:clear
