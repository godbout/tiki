#!/bin/bash
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
# 
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
#
# Clean mysql indexes because they tend to pile up and only one is actually being used
# TODO: non-interactive silent mode for crontab
# TODO: allow deletion of current index

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

INDEXES=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SHOW TABLES WHERE Tables_in_${DB_DETECTED} LIKE 'index_%';")
NB_INDEXES=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SHOW TABLES WHERE Tables_in_${DB_DETECTED} LIKE 'index_%';"| wc -l)
SEARCHENGINE=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT value FROM tiki_preferences WHERE name = 'unified_engine';" )
echo "Search engine: $SEARCHENGINE"
if [[ "$SEARCHENGINE" = "mysql" ]]
then
	CURRENTINDEX=$(mysql --skip-column-names --silent -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "SELECT value FROM tiki_preferences WHERE name = 'unified_mysql_index_current';" )
	echo "Current index: $CURRENTINDEX"
fi
echo "---"
echo "$INDEXES"
if [[ "$NB_INDEXES" = "0" ]]
then
	echo "No index found. Nothing to do."
	exit 0
fi
echo "${NB_INDEXES} indexes found. Delete them? (y/N)"
read delete
if [ "$delete" = "y" ]
then
	for i in $INDEXES
	do
		if [ "$i" != "$CURRENTINDEX" ]
		then
			mysql -u "${USER_DETECTED}" --password="${PASS_DETECTED}" "${DB_DETECTED}" -e "DROP TABLE IF EXISTS \`$i\`;"
			echo "deleting $i"
		else
			echo "keeping $i"
		fi
	done
	# Check that full-text search is configured before suggesting php console.php index:rebuild
	if [ "$SEARCHENGINE" = "mysql" ]
	then
		echo "Now may be a good time for: php console.php index:rebuild"
	fi
fi

