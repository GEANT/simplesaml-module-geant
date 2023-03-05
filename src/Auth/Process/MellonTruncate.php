<?php

/**
 * last resort for fall-back.
 *
 * @author Dyonisius Visser <visser@terena.org>
 * @package simpleSAMLphp
 * @version $Id: SmartAttrs.php 26 2011-05-24 20:26:32Z visser $
 */
class sspmod_terena_Auth_Process_MellonTruncate extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * Attributes which should be added/appended.
	 *
	 * Assiciative array of arrays.
	 */
	private $attributes = array();

	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		// Truncate attribute names
		foreach ($request['Attributes'] as $name => $value) {
			if(strlen($name) > 127) {
				SimpleSAML_Logger::debug('Name too long: '.$name);
				$newname = substr($name, 0, 127);
				$request['Attributes'][$newname] = $value;
				unset($request['Attributes'][$name]);
			}
			
		}

		// Truncate attribute values
		foreach ($request['Attributes'] as $name => $value) {
			$request['Attributes'][$name] = array_map(function($v) {return substr($v, 0, 383); }, $value);
		}
	}
}

?>
