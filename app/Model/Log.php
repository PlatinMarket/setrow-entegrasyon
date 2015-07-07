<?php

App::uses('AppModel', 'Model');

class Log extends AppModel
{

	/**
	 * Returns the character position for a specific occurrence in a string
	 * @param  [type] $search Character to search for
	 * @param  [type] $string String to search
	 * @param  [type] $offset Offset of occurrence you want to find
	 */

	function strposOffset($search, $string, $offset){

		$arr = explode($search, $string);

		if($offset == 0 || $offset > max(array_keys($arr))){
			return false;
		}else{
			return strlen(implode($search, array_slice($arr, 0, $offset)));
		}

	}

	/**
	 * Parses a log file and returns in an array
	 */

	public function parse($filename, $limit = 250){

		$filename = LOGS.$filename;

		$file = fopen($filename, 'r');

		if($file){

			$file = fread($file, filesize($filename));

			/**
			 * We explode here on \n20 to ensure if its a multi-line block we keep the message together
			 * We reverse the array to show recent first
			 * Then we slice it with a limit
			 */

			$array = array_slice(array_reverse(explode("\n20", $file)), 0, $limit);

			$i = 0;

			foreach($array as $arr){

				if(empty($arr)){
					continue;
				}

				/**
				 * Hacky stuff, used for checking multi-line logging
				 */

				if(substr($arr, 0, 2) != "20"){
					$date = substr("20".$arr, 0, 10);
					$new_array[$date][$i]['timestamp'] = substr("20".$arr, 11, 9);
				}else{
					$date = substr($arr, 0, 10);
					$new_array[$date][$i]['timestamp'] = substr($arr, 11, 9);
				}

				/**
				 * This grabs the full message, strposOffset returns the correct position to substr from.
				 */

				$message = substr($arr, $this->strposOffset(" ", $arr, 3));

				/**
				 * Added nl2br to allow for breaks in messages
				 */

				$new_array[$date][$i]['message'] = nl2br($message);

				$i++;

			}
		}

		return $new_array;

	}

}
