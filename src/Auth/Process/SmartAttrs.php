<?php

declare(strict_types=1);

namespace SimpleSAML\Module\geant\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;


/**
 * Based on what an IdP sends, generate a set of attributes that
 * will always be available: smart_id, fname, lname, organisation,
 * email, and country.
 * smart_id is a globally unique id, which is needed when doing
 * business with IdPs from multiple worlds (NRENs, OpenID, Twitter,
 * Facebook, etc), because in that case there is no common user id.
 *
 * Attributes are picked in decreasing order of OK-ness, with a
 * last resort for fall-back.
 *
 * @author Dyonisius Visser <visser@terena.org>
 * @package simpleSAMLphp
 * @version $Id: SmartAttrs.php 26 2011-05-24 20:26:32Z visser $
 */
class SmartAttrs extends Auth\ProcessingFilter {

    /**
     * Attributes which should be added/appended.
     *
     * Assiciative array of arrays.
     */
    private $attributes = array();



    // givenName = first name
    private function getGivenName($attributes) {
        /* Look for the obvious */
        if (isset($attributes['givenName'])) {
            return $attributes['givenName'];
        }

        /* First word of cn */
        if (isset($attributes['cn'][0])) {
            $decomposed = explode(' ', $attributes['cn'][0]);
            if (count($decomposed) >= 2) {
                return array($decomposed[0]);
            }
        }

        /* First word of displayName */
        if (isset($attributes['displayName'][0])) {
            $decomposed = explode(' ', $attributes['displayName'][0]);
            if(count($decomposed) >= 2) {
                return array($decomposed[0]);
            }
        }

        // fname.lname@mail.com
        if (isset($attributes['mail'][0])) {
            if(preg_match('/^(.*)\.(.*)@/', $attributes['mail'][0], $matches)) {
                return array(ucwords(strtolower($matches[1])));
            }
        }

        /* Last resort */
        return array('first_name');
    }


    // cn = last name
    private function getCn($attributes) {
        /* Look for the obvious */
        if (isset($attributes['sn'])) {
            return $attributes['sn'];
        }

        /* Last word of cn */
        if (isset($attributes['cn'][0])) {
            $decomposed = explode(' ', $attributes['cn'][0]);
            if(count($decomposed) >= 2) {
                array_shift($decomposed);
                return array(implode(' ', $decomposed));
            }
        }

        /* Last word of displayName */
        if (isset($attributes['displayName'][0])) {
            $decomposed = explode(' ', $attributes['displayName'][0]);
            if(count($decomposed) >= 2) {
                array_shift($decomposed);
                return array(implode(' ', $decomposed));
            }
        }

        // fname.lname@mail.com
        if (isset($attributes['mail'][0])) {
            if(preg_match('/^(.*)\.(.*)@/', $attributes['mail'][0], $matches)) {
                return array(ucwords(strtolower($matches[2])));
            }
        }

        // name@domain.com?
        if (isset($attributes['mail'][0])) {
            if(preg_match('/^(.*)@/', $attributes['mail'][0], $matches)) {
                return array(ucfirst($matches[1]));
            }
        }

        /* Last resort */
        return array('last_name');
    }


    // displayName = 'full name'
    private function getDisplayName($attributes) {
        /* Look for the obvious */
        if (isset($attributes['displayName'])) {
            return $attributes['displayName'];
        }

        /* Glue givenName and cn together */
        return array($this->getGivenName($attributes)[0] . ' ' . $this->getCn($attributes)[0]);
    }



    private function getIdpName($idpmeta) {
        if (!empty($idpmeta['name']['en'])) {
            return array($idpmeta['name']['en']);
        } else {
            return array($idpmeta['entityid']);
        }
    }

    // o = organisation
    private function getO($attributes, $idpmeta) {

        /* Look for the obvious */
        if (isset($attributes['organizationName'])) {
            return $attributes['organizationName'];
        }


        if (isset($attributes['o'])) {
            return $attributes['o'];
        }

        /* Uppercase version of schacHomeOrganization (domain without TLD) */
        if(isset($attributes['schacHomeOrganization'][0])) {
            return array(strtoupper(preg_replace('/(\w+\.)?(\w+)\.\w+$/', '$2',
                $attributes['schacHomeOrganization'][0])));
        }

        // Don't take these generic ones into account.
        if(in_array($idpmeta['entityid'], array(
            // Stuff from our Bridge
            'https://login.terena.org/bridge/facebook',
            'https://login.terena.org/bridge/google',
            'https://login.terena.org/bridge/saml2/idp/metadata.php',
            'https://login.terena.org/bridge/linkedin',
            'https://login.terena.org/bridge/twitter',
            'https://login.terena.org/bridge/yahoo',
            // Real SAML IdPs
            'https://idp.unitedid.org/idp/shibboleth',

        ))) {
            return array('No organisation');
        }


        /* IdP name as displayed on login page */
        if (!empty($idpmeta['name']['en'])) {
            return array($idpmeta['name']['en']);
        }

        /* Uppercase version of domain of first e-mail address, without TLD */
        if(isset($attributes['mail'][0])) {
            return array(strtoupper(preg_replace('/.*@(\w+\.)?(\w+)\.\w+$/', '$2',
                $attributes['mail'][0])));
        }

        /* Uppercase version of eduPersonPrincipalName domain without TLD) */
        if(isset($attributes['eduPersonPrincipalName'][0])) {
            return array(strtoupper(preg_replace('/.*@(\w+\.)?(\w+)\.\w+$/', '$2',
                $attributes['eduPersonPrincipalName'][0])));
        }


        /* Last resort, static string */
        return array('My_Organisation');
    }


    private function getCountryName($attributes) {
        /* See http://www.iso.org/iso/english_country_names_and_code_elements */
        $cc = array(
            'AF','AX','AL','DZ','AS','AD','AO','AI','AQ','AG','AR',
            'AM','AW','AU','AT','AZ','BS','BH','BD','BB','BY','BE',
            'BZ','BJ','BM','BT','BO','BA','BW','BV','BR','IO','BN',
            'BG','BF','BI','KH','CM','CA','CV','KY','CF','TD','CL',
            'CN','CX','CC','CO','KM','CG','CD','CK','CR','CI','HR',
            'CU','CY','CZ','DK','DJ','DM','DO','EC','EG','SV','GQ',
            'ER','EE','ET','FK','FO','FJ','FI','FR','GF','PF','TF',
            'GA','GM','GE','DE','GH','GI','GR','GL','GD','GP','GU',
            'GT','GG','GN','GW','GY','HT','HM','VA','HN','HK','HU',
            'IS','IN','ID','IR','IQ','IE','IM','IL','IT','JM','JP',
            'JE','JO','KZ','KE','KI','KP','KR','KW','KG','LA','LV',
            'LB','LS','LR','LY','LI','LT','LU','MO','MK','MG','MW',
            'MY','MV','ML','MT','MH','MQ','MR','MU','YT','MX','FM',
            'MD','MC','MN','ME','MS','MA','MZ','MM','NA','NR','NP',
            'NL','AN','NC','NZ','NI','NE','NG','NU','NF','MP','NO',
            'OM','PK','PW','PS','PA','PG','PY','PE','PH','PN','PL',
            'PT','PR','QA','RE','RO','RU','RW','BL','SH','KN','LC',
            'MF','PM','VC','WS','SM','ST','SA','SN','RS','SC','SL',
            'SG','SK','SI','SB','SO','ZA','GS','ES','LK','SD','SR',
            'SJ','SZ','SE','CH','SY','TW','TJ','TZ','TH','TL','TG',
            'TK','TO','TT','TN','TR','TM','TC','TV','UG','UA','AE',
            'GB','US','UM','UY','UZ','VU','VE','VN','VG','VI','WF',
            'EH','YE','ZM','ZW'
        );

        /* Look for the obvious */
        if (isset($attributes['countryName'][0])) {
            if(in_array(strtoupper($attributes['countryName'][0]), $cc)) {
                return array(strtoupper($attributes['countryName'][0]));
            }
        }

        /* TLD of first e-mail */
        if (isset($attributes['mail'][0])) {
            if(preg_match('/.*\.([a-z][a-z])$/', $attributes['mail'][0], $matches)) {
                if(in_array(strtoupper($matches[1]), $cc)) {
                    return array(strtoupper($matches[1]));
                }
            }
        }

        /* TLD of EPTI */
        if (isset($attributes['eduPersonTargetedID'][0])) {
            if(preg_match('/.*\.([a-z][a-z])$/', $attributes['eduPersonTargetedID'][0], $matches)) {
                if(in_array(strtoupper($matches[1]), $cc)) {
                    return array(strtoupper($matches[1]));
                }
            }
        }

        /* TLD of EPPN */
        if (isset($attributes['eduPersonPrincipalName'][0])) {
            if(preg_match('/.*\.([a-z][a-z])$/', $attributes['eduPersonPrincipalName'][0], $matches)) {
                if(in_array(strtoupper($matches[1]), $cc)) {
                    return array(strtoupper($matches[1]));
                }
            }
        }

        /* Last 2 letters of preferredLanguage */
        if (isset($attributes['preferredLanguage'][0])) {
            if(strlen($attributes['preferredLanguage'][0]) >= 2) {
                if(in_array(strtoupper(substr($attributes['preferredLanguage'][0], -2)), $cc)) {
                    return array(strtoupper(substr($attributes['preferredLanguage'][0], -2)));
                }
            }
        }

        /* Last resort */
        return array('0');
    }


    private function getMail($attributes) {
        /* Look for the obvious */

        if (isset($attributes['mail'])) {
            return $attributes['mail'];
        }

        // hack
        //       if (isset($attributes['eduPersonPrincipalName'][0])) {
        //          return $attributes['eduPersonPrincipalName'][0];
        //       }

    /* Last resort
      We provide a non-valid address here, so that when a users logs in
      to edit his details, he is required to put in something valid.
     */
        return array('invalid_email_needs_updating');
    }




    /**
     * Apply filter to add or replace attributes.
     *
     * Add or replace existing attributes with the configured values.
     *
     * @param array &$request  The current request
     */
    public function process(array &$state): void {

        Assert::keyExists($state, 'Attributes');

        $attributes =& $state['Attributes'];

        $entityID = $state['saml:sp:IdP'];

        $metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpmeta = $metadata->getMetaData($entityID, 'saml20-idp-remote');

        // ePTID needs special care from 1.14 on, as it can be DOMNodeList object
        // See https://simplesamlphp.org/docs/stable/simplesamlphp-upgrade-notes-1.14
        if (isset($attributes['eduPersonTargetedID'][0])) {
            if ($attributes['eduPersonTargetedID'][0] instanceof SAML2\XML\saml\NameID) {
                $request['Attributes']['eduPersonTargetedID'] = array($attributes['eduPersonTargetedID'][0]->getValue());
            }
        }

        $collected = array(
            'mail' => $this->getMail($attributes),
            'givenName' => $this->getGivenName($attributes),
            'cn' => $this->getCn($attributes),
            'displayName' => $this->getDisplayName($attributes),
            'idpname' => $this->getIdpName($idpmeta),
            'o' => $this->getO($attributes, $idpmeta),
            'countryName' => $this->getCountryName($attributes),
        );

        Logger::debug('Synthesized attributes: ' . json_encode($collected));
        foreach($collected as $c=>$v) {
            if(isset($c)) {
                $state['Attributes'][$c] = $v;
            }
        }
    }
}

?>
