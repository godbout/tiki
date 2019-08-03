#!/bin/bash
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
# 
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
#
# Looks up actionlog content and allow trimming because it could grow forever
# TODO: non-interactive silent mode for crontab
# TODO: allow more flexible choices, such as "keep last NNN entries" etc.

usage()
{
	echo "Usage: $0 [path/to/local.php]"
}

if [[ "$1" = "" ]]
then
	# If no parameter was given, try to guess
	if [[ -f db/local.php ]]
	then
		LOCALFILE="db/local.php"
		echo "Detected: $LOCALFILE"
	else
		usage
		exit 1
	fi
else
	# Use the one provided
	LOCALFILE="$1"
fi

echo "Using: $LOCALFILE"
if [[ -f "$LOCALFILE" ]]
then
  DB_DETECTED=$(grep "^\$dbs_tiki" "$LOCALFILE" | cut -d"'" -f2| tail -n 1);
  USER_DETECTED=$(grep "^\$user_tiki" "$LOCALFILE" | cut -d"'" -f2| tail -n 1);
  PASS_DETECTED=$(grep "^\$pass_tiki" "$LOCALFILE" | cut -d"'" -f2| tail -n 1);
else
  echo "ERROR: local.php file not found"
	usage
  exit 1
fi

#mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SHOW TABLES;"
COUNT=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT COUNT(*) FROM tiki_actionlog ; ")
#mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT * FROM tiki_actionlog ORDER by lastModif DESC LIMIT 4; "
COUNT_OLDER_THAN_1_YEAR=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT COUNT(*) FROM tiki_actionlog WHERE FROM_UNIXTIME(lastModif) < DATE_SUB(NOW(),INTERVAL 1 YEAR) ; ")
COUNT_OLDER_THAN_2_YEARS=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT COUNT(*) FROM tiki_actionlog WHERE FROM_UNIXTIME(lastModif) < DATE_SUB(NOW(),INTERVAL 2 YEAR) ; ")
TIME_OLDEST=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT lastModif FROM tiki_actionlog ORDER BY lastModif ASC LIMIT 1; ")
TIME_OLDEST_FRIENDLY=$(date "+%F %H:%M %Z" --date="@${TIME_OLDEST}")
TIME_LATEST=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT lastModif FROM tiki_actionlog ORDER BY lastModif DESC LIMIT 1; ")
TIME_LATEST_FRIENDLY=$(date "+%F %H:%M %Z" --date="@${TIME_LATEST}")
echo "Number of entries in table tiki_actionlog: ${COUNT}"
echo "Number of entries older than 1 year: ${COUNT_OLDER_THAN_1_YEAR}"
echo "Number of entries older than 2 years: ${COUNT_OLDER_THAN_2_YEARS}"
echo "Oldest entry is dated: ${TIME_OLDEST_FRIENDLY}"
echo "Latest entry is dated: ${TIME_LATEST_FRIENDLY}"

echo "Delete entries older than 1 year? (y/N)"
read delete
if [ "$delete" = "y" ]
then
	mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "DELETE FROM tiki_actionlog WHERE FROM_UNIXTIME(lastModif) < DATE_SUB(NOW(),INTERVAL 1 YEAR) ; "
else
	echo "Delete entries older than 2 years? (y/N)"
	read delete
	if [ "$delete" = "y" ]
	then
		mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "DELETE FROM tiki_actionlog WHERE FROM_UNIXTIME(lastModif) < DATE_SUB(NOW(),INTERVAL 2 YEAR) ; "
	fi
fi

