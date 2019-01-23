/*                                                                                  *
 *         WARNING  WARNING  WARNING  WARNING  WARNING  WARNING  WARNING            *
 *                                                                                  *
 * This will wipe out all your local H5P content, libraries, caches (and prefs?)    *
 * You should also delete all files in storage/public or storage/public/h5p:        *
 *       libraries, temp, content, export and cachedassets                          *
 *                                                                                  *
 *    No undo, use at your own risk! Uncomment to enable                            */

/*
TRUNCATE TABLE `tiki_h5p_contents`;
TRUNCATE TABLE `tiki_h5p_contents_libraries`;
TRUNCATE TABLE `tiki_h5p_libraries`;
TRUNCATE TABLE `tiki_h5p_libraries_cachedassets`;
TRUNCATE TABLE `tiki_h5p_libraries_hub_cache`;
TRUNCATE TABLE `tiki_h5p_libraries_libraries`;
TRUNCATE TABLE `tiki_h5p_libraries_languages`;
TRUNCATE TABLE `tiki_h5p_results`;
TRUNCATE TABLE `tiki_h5p_tmpfiles`;
*/

/* DELETE FROM `tiki_preferences` WHERE `name` LIKE 'h5p%'; */

