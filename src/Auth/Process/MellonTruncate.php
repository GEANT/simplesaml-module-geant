<?php

declare(strict_types=1);

namespace SimpleSAML\Module\geant\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;

/**
 * Truncate attribute names and values for use with mod_mellon
 *
 * @package SimpleSAMLphp
 */

class MellonTruncate extends Auth\ProcessingFilter {

    public function process(array &$state): void {

        Assert::keyExists($state, 'Attributes');

        $mapped_attributes = [];  // FIXME do we need this?

        $value_max_length = 383;
        $name_max_length = 127;

        foreach ($state['Attributes'] as $name => $values) {

            if (strlen($name) > $name_max_length) {
                Logger::debug("Attribute name '$name' too long, truncating it to $name_max_length chars");
                $newname = substr($name, $name_max_length);
                $request['Attributes'][$newname] = $values;
                unset($request['Attributes'][$name]);
            }

            $too_long_values = array_filter($values, function($x) { return strlen($x) > $value_max_length; });
            foreach ($too_long_values as $tl) {
                Logger::debug("Attribute value '$tl' too long, truncating it to $value_max_length");
            }
            if(count($too_long_values) > 0) {
                $request['Attributes'][$name] = array_map(function($x) {return substr($x, 0, $value_max_length); }, $values);
            }
        }
    }
}
