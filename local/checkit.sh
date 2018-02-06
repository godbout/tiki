#/bin/sh

SVR="software-versions-required.txt"

mv phpinfo.php.bin phpinfo.php
X=`curl -u next:next https://nextbranding.tiki.org/local/phpinfo.php | grep PHP | grep -i version | grep -i php | grep -o [0-9]\\\.[0-9]\\\.[0-9] | tail -n 1 | grep -o [0-9]\\\.[0-9]`
mv phpinfo.php phpinfo.php.bin
#echo $X > foobar.tmp
XX=`echo $X | sed -e 's/\.//g'`
Y=`grep PHP ${SVR} | cut -d, -f2`
YY=`echo $Y | sed -e 's/\.//g'`
echo X:$X // Y:$Y
echo XX:$XX // YY:$YY
if [ ${XX} -ge ${YY} ] ; then
   echo "we are good"
else
   echo "we are too old"
fi
