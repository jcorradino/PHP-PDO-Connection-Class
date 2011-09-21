<?php
define('DB_CONNECTION_STRING',"mysql:host=localhost;dbname=test");
define('DB_UN',"root");
define('DB_PW',"root");
/*
	 * db.class.php - database interface class using PDO
	 *
	 * Handles database connectivity and data handling.
	 *
	 * Work by Jason Corradino, licensed under a Creative Commons Attribution 3.0 License.
	 * License: http://creativecommons.org/licenses/by/3.0/us/
	 *
	 * Based on my work from ImYourDeveloper.com and Groupon.com.
	 *
	 * See something you don't like?  Bugs?  Questions, comments, or concerns?  Let me know: http://imyourdeveloper.com/contactme/
	 *
*/
	class db {
		var $pdo;
		
		function __construct() {
			$this->connect(); // initialize database connection
		}
		
		private function connect () { // establishes connection with database
			try {
				$this->pdo = new PDO ( DB_CONNECTION_STRING, DB_UN, DB_PW ); // replace with your flavor of database and credentials/socket info
			} catch ( PDOException $e ) {
				trigger_error( "There has been an error establishing a database connection.", E_USER_ERROR );
				exit( 1 );
			}
		}
		
		function close () { // destroys connection with database
			$this->pdo = null;
		}
		
		/*
			Accepts queries like this:
			SELECT * FROM `table` WHERE `field1` = :field1 AND `field2` = :field2;
			
			With the following $args array:
			Array (
				':field1' => 'value',
				':field2' => 'value'
			);
			
			!!! Any statement containing "insert", "where", or "replace" will automatically fail if the args array is empty. !!!
		*/
		function run ( $sql, $args = "" ) { // runs through a query, returning true or false depending on whether or not a change was made.
			if ( $sql != "" ) {
				if ( ( ( strstr( strtolower( $sql ), "insert" ) != "" || strstr( strtolower( $sql ), "where" ) != "" || strstr( strtolower( $sql ), "replace" ) != "" ) && $args != "" ) ) {
					if ( is_array( $args ) ) {
						// $args is present and the query is trying to insert, replace, or search using "where."
						array_walk( $args, array( &$this, 'sanitize' ) );
						try {
							$prepare = $this->pdo->prepare ( $sql, array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY ) );
							$prepare->execute( $args );
							return $prepare->fetchAll();
						} catch ( PDOException $e ) {
							$error = "Error: sql run failed - $e";
							trigger_error( $error, E_USER_WARNING );
							throw new Exception($error);
							return false;
						}
					} else {
						$error = "Notice: $args must be an array, sql run failed.";
						trigger_error( $error, E_USER_NOTICE );
						throw new Exception($error);
						return false;
					}
				} else if ( strstr( strtolower( $sql ), "insert" ) == "" && strstr( strtolower( $sql ), "where" ) == "" && strstr( strtolower( $sql ), "replace" ) == "" ) {
					// query is pulling data without any dynamic data
					try {
						$prepare = $this->pdo->prepare($sql);
						$prepare->execute();
						return $prepare->fetchAll();
					} catch ( PDOException $e ) {
						$error = "Error: sql run failed - $e";
						trigger_error( $error, E_USER_WARNING );
						throw new Exception($error);
						return false;
					}
				} else {
					$error = "Notice: sql run failed.";
					trigger_error( $error, E_USER_NOTICE );
					throw new Exception($error);
					return false;
				}
			} else {
				$error = "Notice: no query defined.";
				trigger_error( $error, E_USER_NOTICE );
				throw new Exception($error);
				return false;
			}
		}
		
		function query ( $sql, $args = "" ) { // runs through the "run" function, but with default exception handling
			try {
				return $this->run($sql, $args);
			} catch (Exception $e) {
				echo "<p><strong>More info:</strong><blockquote>".$e."</blockquote></p>";
			}
		}
		
		private function sanitize ( &$string ) { // clean args lead to a happy database
			$this->pdo->quote( $string );
		}
	}
?>