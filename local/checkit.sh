#/bin/sh
mv phpinfo.php.bin phpinfo.php
X=`curl -u next:next https://nextbranding.tiki.org/local/phpinfo.php | grep PHP | grep -i version | grep -i php | grep -o [0-9]\\\.[0-9]\\\.[0-9] | tail -n 1 | grep -o [0-9]\\\.[0-9]` ; echo $X > foobar.tmp
mv phpinfo.php phpinfo.php.bin
