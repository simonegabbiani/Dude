<?php

//
// THIS IS UGLY, VERY VERY OLD, DEPRECATED LIBRARY
//                              ^^^^^^^^^^

# basic database connection

class DB_ConnectionError extends Exception {}
class DB_SelectDB extends Exception {}
class DB_QueryError extends Exception {}

class DB_MYSQL {
	const RETNORMAL = 1;
	const RETFIRSTFIELD = 2;
	const RETARRAY = 3;
	const RETARRAY_ASSOC = 6;
	const RETLIST = 4;
	const RETSIMPLEARRAY = 5;
	public static function open( $HOST, $DBNAME, $USERNAME, $PASSWORD ) {
		if (($c = @mysql_connect( $HOST, $USERNAME, $PASSWORD )) === false)
			throw new DB_ConnectionError(mysql_error());
		if ((@mysql_select_db( $DBNAME , $c )) === false)
			throw new DB_SelectDB(mysql_error());
		return $c;
	}
	public static function close($DB_CONNECTION) {
		@mysql_close( $DB_CONNECTION );
	}
	/**
	 * @returns INTEGER. Returns total records founded. 
	 *          Returns 1 with RETFIRSTFIELD (if any, otherwise 0).
	 * @RESULT  Is NULL when returns 0 otherwise a resource or the
	 *			first column value on RETFIRSTFIELD or a STRING with
	 *			RETLIST (todo). With RETFIRSTFIELD, NULL could be the
	 * 			value of the column so you must see the return value
	 *			to make difference between "no results" and
	 *			"result found" situations.
	 */
	public static function query( $DB_CONNECTION, $QUERY, $resultType, &$RESULT, $listDelimiter = ';\n' )
	{
		if (($RESULT = @mysql_query( $QUERY, $DB_CONNECTION )) === false)
			throw new DB_QueryError(mysql_error());
		$COUNT = @mysql_num_rows($RESULT);
		if ($COUNT == 0) return 0;

		switch( $resultType )
		{
		case self::RETNORMAL:
			break;

		case self::RETFIRSTFIELD:
			if ($COUNT > 0) {
				if (mysql_data_seek( $RESULT, 0 ) === false)
					throw new DB_ResultSeekError(mysql_error());
				$buff = mysql_fetch_row( $RESULT );
				$RESULT = $buff[ 0 ];
				mysql_free_result( $RESULT );
			}
			break;

		case self::RETARRAY:
			$rs = array();
			for( $i=0; $i<$COUNT; $i++ ) {
				mysql_data_seek( $RESULT, $i );
				array_push($rs, mysql_fetch_row( $RESULT ));
			}
			mysql_free_result( $RESULT );
			$RESULT =& $rs;
			break;

		case self::RETARRAY_ASSOC:
			$rs = array();
			for( $i=0; $i<$COUNT; $i++ ) {
				mysql_data_seek( $RESULT, $i );
				array_push($rs, mysql_fetch_assoc( $RESULT ));
			}
			mysql_free_result( $RESULT );
			$RESULT =& $rs;
			break;

		case RETSIMPLEARRAY:
			//TO VERIFY!!! (prob. todo, fatto a metà..)
			$rs = array();
			for( $i=0; $i<$COUNT; $i++ ) {
				mysql_data_seek( $RESULT, $i );
				$row = mysql_fetch_row( $RESULT );
				if (isset($row[1]))
					$rs[ $row[0] ] = $row[1];
				else
					$rs[ $row[0] ] = $row[0];
			}
			mysql_free_result( $RESULT );
			$RESULT =& $rs;
			break;

		case self::RETLIST:
			# restituisce il primo fields di ogni recordset trovato in una sola stringa delimitati da $listDelimiter
			/* DA FARE!!!
			$buff = '';
			if ($COUNT > 0) {
				for( $i=0; $i<$COUNT; $i++ )
				{
					$rs = pg_fetch_row( $RESULT, $i );
					//echo "*** " . $rs[0] ." <br>";
					$buff .= ',' . $rs[0];
				}
				pg_free_result( $RESULT );
				$RESULT = $buff;
				$buff = substr( $buff, 1 );
			}
			return $buff;
			*/
			die( 'self::RETLIST non implementato in sql_class per MYSQL' );
			break;

		default:
		}
		
		return $COUNT;
	}
	public static function toString( $DB_CONNECTION, $v ) {
		try {
			return ($v == NULL) ? 'NULL' : ( '\'' . mysql_real_escape_string( $v, $DB_CONNECTION ) . '\'' );
		}
		catch (Exception $e) {
			return '\'\'';
		}
	}
	public static function toNumber( $DB_CONNECTION, $v ) {
		try {
			return mysql_real_escape_string( (double) $v, $DB_CONNECTION );
		}
		catch (Exception $e) {
			return 0;
		}
	}
	public static function toBoolean( $DB_CONNECTION, $v ) {
		try {
			return $v ? 'TRUE' : 'FALSE';
		}
		catch (Exception $e) {
			return 'FALSE';
		}
	}
	public static function toBooleanInverse( $DB_CONNECTION, $v ) {
		if ($v == 'TRUE')
			return true;
		if ($v == 'FALSE')
			return false;
		throw new DataValueException();
		return false;
	}
}








?>