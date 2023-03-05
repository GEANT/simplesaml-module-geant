<?php

declare(strict_types=1);

namespace SimpleSAML\Module\geant\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;

/**
 * Remove prefix from an attribute value
 *
 * @package SimpleSAMLphp
 */

class RemovePrefix extends Auth\ProcessingFilter {

    public function process(array &$state): void {

        Assert::keyExists($state, 'Attributes');

        $mapped_attributes = [];


//        Logger::debug('FOOBAR ' . var_export($state['Attributes'], True));


        foreach ($state['Attributes'] as $name => $vales) {
            if (preg_match("/^urn:mace:(dir|terena\.org):attribute-def:(.*)/", $name, $matches)) {
                $state['Attributes'][$matches[2]] = $state['Attributes'][$name];
                unset($state['Attributes'][$name]);
            }
        }
    }
}
