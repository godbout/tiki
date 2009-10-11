<?php
require_once 'lib/core/lib/TikiDb.php';

class TikiDb_Bridge extends TikiDb
{
	function startTimer() // {{{
	{
		self::get()->startTimer();
	} // }}}

	function stopTimer($starttime) // {{{
	{
		self::get()->stopTimer($starttime);
	} // }}}

	function qstr( $str ) // {{{
	{
		return self::get()->qstr( $str );
	} // }}}

	function query( $query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = true ) // {{{
	{
		return self::get()->query( $query, $values, $numrows, $offset, $reporterrors );
	} // }}}

	function queryError( $query, &$error, $values = null, $numrows = -1, $offset = -1 ) // {{{
	{
		return self::get()->queryError( $query, $error, $values, $numrows, $offset );
	} // }}}

	function getOne( $query, $values = null, $reporterrors = true, $offset = 0 ) // {{{
	{
		return self::get()->getOne( $query, $values, $reporterrors, $offset );
	} // }}}

	function setErrorHandler( TikiDb_ErrorHandler $handler ) // {{{
	{
		self::get()->setErrorHandler( $handler );
	} // }}}

	function setTablePrefix( $prefix ) // {{{
	{
		self::get()->setTablePrefix( $prefix );
	} // }}}

	function setUsersTablePrefix( $prefix ) // {{{
	{
		self::get()->setUsersTablePrefix( $prefix );
	} // }}}

	function getServerType() // {{{
	{
		return self::get()->getServerType();
	} // }}}

	function setServerType( $type ) // {{{
	{
		self::get()->setServerType( $type );
	} // }}}

	function getErrorMessage() // {{{
	{
		return self::get()->getErrorMessage();
	} // }}}

	protected function setErrorMessage( $message ) // {{{
	{
		self::get()->setErrorMessage( $message );
	} // }}}

	protected function handleQueryError( $query, $values, $result ) // {{{
	{
		self::get()->handleQueryError( $query, $values, $result );
	} // }}}

	protected function convertQuery( &$query ) // {{{
	{
		self::get()->convertQuery( $query );
	} // }}}

	protected function convertQueryTablePrefixes( &$query ) // {{{
	{
		self::get()->convertQueryTablePrefixes( $query );
	} // }}}

	function convertSortMode( $sort_mode ) // {{{
	{
		return self::get()->convertSortMode( $sort_mode );
	} // }}}

	function convertBinary() // {{{
	{
		return self::get()->convertBinary();
	} // }}}
	
	function cast( $var,$type ) // {{{
	{
		return self::get()->cast( $var, $type );
	} // }}}

	function getQuery() // {{{
	{
		return self::get()->getQuery();
	} // }}}

	function setQuery( $sql ) // {{{
	{
		return self::get()->setQuery( $sql );
	} // }}}

	function ifNull( $field, $ifNull ) // {{{
	{
		return self::get()->ifNull( $field, $ifNull );
	} // }}}

	function in( $field, $values, &$bindvars ) // {{{
	{
		return self::get()->in( $field, $values, $bindvars );
	} // }}}

	function concat() // {{{
	{
		$arr = func_get_args();
		return call_user_func_array( array( self::get(), 'concat' ), $arr );
	} // }}}
}

?>
