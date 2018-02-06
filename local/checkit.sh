#/bin/sh

# draft script to compare PHP versions before an update

TIKIPATH="./"
SCRIPTPATH="${TIKIPATH}/local"

SVR="software-versions-required.txt"

#SCRIPTPATH="./"

# before doing a Tiki update check the required software versions
cd ${SCRIPTPATH}
svn up ${SVR}

USER="next"		#
PASSWORD="next"		#
CREDENTIALS="${USER}:${PASSWORD}"
PHPINFOURL="https://nextbranding.tiki.org/local/phpinfo.php"

TMPPATH="/tmp"
HTPASSWDFILE=${TMPPATH}/.htpasswd
echo sed -e "s/TEMPLATEPATH/\\${TMPPATH}/g" < _htaccess > .htaccess
echo
cat .htaccess
echo
sed -e "s/TEMPLATEPATH/\\${TMPPATH}/g" < _htaccess > .htaccess
htpasswd -n -b ${USER} ${PASSWORD} | tee ${HTPASSWDFILE}

# public access to phpinfo is dangerous, thus we added htaccess
mv phpinfo.php.bin phpinfo.php
X=`curl -u ${CREDENTIALS} ${PHPINFOURL} | grep PHP | grep -i version | grep -i php | grep -o [0-9]\\\.[0-9]\\\.[0-9] | tail -n 1 | grep -o [0-9]\\\.[0-9]`
mv phpinfo.php phpinfo.php.bin
# delete password file when not needed
rm ${HTPASSWDFILE}

Y=`grep PHP ${SVR} | cut -d, -f2`
#echo $X > foobar.tmp

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
else
   echo "we are too old, we should not run svn update"
fi
