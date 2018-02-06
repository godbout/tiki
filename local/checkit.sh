#/bin/sh

# draft script to compare PHP versions before an update

TIKIPATH="./"
SCRIPTPATH="${TIKIPATH}/local"

SVR="software-versions-required.txt"

# before doing a Tiki update check the required software versions
cd ${SCRIPTPATH}
svn up ${SVR}

USER="next"		#
PASSWORD="next"		#
CREDENTIALS="${USER}:${PASSWORD}"
PHPINFOURL="https://nextbranding.tiki.org/local/phpinfo.php"

htpasswd -n -b ${USER} ${PASSWORD} | tee .htpasswd

# public access to phpinfo is dangerous
mv phpinfo.php.bin phpinfo.php
X=`curl -u ${CREDENTIALS} ${PHPINFOURL} | grep PHP | grep -i version | grep -i php | grep -o [0-9]\\\.[0-9]\\\.[0-9] | tail -n 1 | grep -o [0-9]\\\.[0-9]`
mv phpinfo.php phpinfo.php.bin

Y=`grep PHP ${SVR} | cut -d, -f2`
#echo $X > foobar.tmp

# stripe decimal point, probably it won't work properly with versions like 5.10
XX=`echo ${X} | sed -e 's/\.//g'`
YY=`echo ${Y} | sed -e 's/\.//g'`

# just some debug info
echo X:${X} // Y:${Y}
echo XX:${XX} // YY:${YY}

# compare installed and required version
if [ ${XX} -ge ${YY} ] ; then
   echo "we are good, we can run a svn update"
else
   echo "we are too old, we should not run svn update"
fi
