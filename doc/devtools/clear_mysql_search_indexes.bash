#!/bin/bash
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
# 
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
#
# Clean mysql indexes because they tend to pile up and only one is actually being used
# TODO: exclude indexes index_pref_*
# TODO: do not delete current index as seen in "unified_mysql_index_current"(tiki_preferences)
# TODO: check that full-text search is configured before suggesting php console.php index:rebuild (`tiki_preferences` VALUES ('unified_engine','mysql');)

usage()
{
	echo "Usage: $0 <path/to/local.php>"
}

echo "== $1"
if [[ -f "$1" ]]
then
  DB_DETECTED=$(grep "^\$dbs_tiki" "$1" | cut -d"'" -f2| tail -n 1);
  USER_DETECTED=$(grep "^\$user_tiki" "$1" | cut -d"'" -f2| tail -n 1);
  PASS_DETECTED=$(grep "^\$pass_tiki" "$1" | cut -d"'" -f2| tail -n 1);
else
  echo "ERROR: local.php file not found"
	usage
  exit 1
fi

INDEXES=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SHOW TABLES WHERE Tables_in_${DB_DETECTED} LIKE 'index_%';")
NB_INDEXES=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SHOW TABLES WHERE Tables_in_${DB_DETECTED} LIKE 'index_%';"| wc -l)
echo "---"
echo "$INDEXES"
echo "${NB_INDEXES} indexes found. Delete them? (y/N)"
read delete
if [ "$delete" = "y" ]
then
	for i in $INDEXES
	do
		echo "deleting $i"
		mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "DROP TABLE IF EXISTS `$i`;"
	done
	echo "Now may be the perfect time for: php console.php index:rebuild"
fi

