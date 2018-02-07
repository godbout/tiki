#/bin/sh

# draft script to compare PHP versions before an update

# TODO Todo todo: merge this script with doc/devtools/svnup.sh
#                 and look at doc/devtools/svnup.php

# adjust the Tikipath to your needs
TIKIPATH="./"

# keep this hardcoded
SCRIPTPATH="${TIKIPATH}/local"

# keep this hardcoded
SVR="software-versions-required.txt"

# uncomment for local test, quick and dirty
#SCRIPTPATH="./"

# before doing a Tiki update check the required software versions
cd ${SCRIPTPATH}
svn up ${SVR}

# choose a username and a password
USER="next"		#
PASSWORD="next"		#
CREDENTIALS="${USER}:${PASSWORD}"

# adjust this to your Tiki installation
#PHPINFOURL="https://nextbranding.tiki.org/local/phpinfo.php"
PHPINFOURL="https://nextbranding.tiki.org/local/phpversion.php"

# choose a temporary path to put the passwordfile in, it will be deleted after usage
TMPPATH="/tmp"

LOCKFILE="${TMPPATH}/locktikiupdate"

HTPASSWDFILE=i"${TMPPATH}/.htpasswd"
# some debug output
#echo sed -e "s/TEMPLATEPATH/\\${TMPPATH}/g" < _htaccess > .htaccess
#echo
#cat .htaccess # should be after modifying .htaccess
#echo
#
sed -e "s/TEMPLATEPATH/\\${TMPPATH}/g" < _htaccess > .htaccess
htpasswd -n -b ${USER} ${PASSWORD} | tee ${HTPASSWDFILE}

# public access to phpinfo is dangerous, thus we added htaccess
# old: use phpinfo()
#mv phpinfo.php.bin phpinfo.php
#X=`curl -u ${CREDENTIALS} ${PHPINFOURL} | grep PHP | grep -i version | grep -i php | grep -o [0-9]\\\.[0-9]\\\.[0-9] | tail -n 1 | grep -o [0-9]\\\.[0-9]`
#mv phpinfo.php phpinfo.php.bin
# new: use phpversion()
mv phpversion.php.bin phpversion.php
X=`curl -u ${CREDENTIALS} ${PHPINFOURL} | grep -o [0-9]\\\.[0-9]\\\.[0-9] | grep -o [0-9]\\\.[0-9]`
mv phpversion.php phpversion.php.bin

# delete password file when not needed
rm ${HTPASSWDFILE}

#echo $X > foobar.tmp

Y=`grep PHP ${SVR} | cut -d, -f2`

# stripe decimal point, probably it won't work properly with versions like 5.10
## compared to 7.2 it will be 510>72 supposed to be sufficient, which is wrong
XX=`echo ${X} | sed -e 's/\.//g'`
YY=`echo ${Y} | sed -e 's/\.//g'`

# just some debug info
echo X:${X} // Y:${Y}
echo XX:${XX} // YY:${YY}

# compare installed and required version
if [ ${XX} -ge ${YY} ] ; then
   echo "we are good, we can run svn update"
   if [ -e ${LOCKFILE} ] ; then
      echo "updated is locked"
      echo "you might want to remove ${LOCKFILE} and run this script again"
      exit 1
   else
      touch ${LOCKFILE}
      echo "run svn up now"
      rm ${LOCKFILE}
   fi
else
   echo "we are too old, we should not run svn update"
fi
