#!/bin/bash

PHPVERSION=`php -i | head | grep 'Version' | cut -d ' ' -f 4-10`
NOWDATE=`date +%s`
GITREPO=`git config remote.origin.url`
REPO=${GITREPO/#git:/https:}

echo $GITREPO
echo $REPO

echo "Starting memtest";
php memtest.php > results/$PHPVERSION.$NOWDATE.txt

git remote set-url --push origin $REPO
git remote set-branches --add origin master 
git fetch -q
git config user.name '$GIT_NAME'
git config user.email '$GIT_EMAIL'
git config credential.helper "store --file=.git/credentials"
echo "https://$GH_TOKEN:@github.com" >> .git/credentials
git add results/*
git commit -m "Add Results From Travis"
git push origin master 
