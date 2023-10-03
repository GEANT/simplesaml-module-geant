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

        foreach ($state['Attributes'] as $name => $values) {

            if (strlen($name) > 127) {
                Logger::debug("Attribute name '$name' too long, truncating it to 127 chars");
                $newname = substr($name, 0, 127);
                $state['Attributes'][$newname] = $values;
                unset($state['Attributes'][$name]);
            }

            $too_long_values = array_filter($values, function($x) { return strlen($x) > 383; });

            foreach ($too_long_values as $tl) {
                Logger::debug("Attribute value '$tl' too long, truncating it to 383");
            }
            if(count($too_long_values) > 0) {
                $state['Attributes'][$name] = array_map(function($x) {return substr($x, 0, 383); }, $values);
            }
        }
    }
}
