<?php

#declare(strict_types=1);

namespace SimpleSAML\Module\geant;

use Twig\Environment;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\TemplateControllerInterface;
use SimpleSAML\Logger;
use SimpleSAML\Module;

class ThemeTncController implements TemplateControllerInterface
{
    public function setUpTwig(Environment &$twig): void
    {
    }

    public function display(array &$data): void
    {
        $moduleConfig = Configuration::getConfig('module_geant.php');
        $tncs = $moduleConfig->getArray('tncs');

        $tnc_pics_dir = Module::getModuleDir('geant') . '/public/assets/gfx';
        $tnc_pics = array_values(preg_grep("/^tnc\d{4}_\d+\.(jpg|png)$/", scandir($tnc_pics_dir)));
        $tnc_pic = $tnc_pics[rand(0,count($tnc_pics)-1)];

        $tnc_year = substr($tnc_pic, 3, 4);
        # From 2015 onwards the year is depicted as "15" instead of 2015...
        $tnc_name = "TNC" . ($tnc_year < 2015 ? $tnc_year : substr($tnc_year, -2));
        $tnc_url = $tncs[$tnc_year]["url"];

        $tnc_location = $tncs[$tnc_year]["location"];

        $data['tnc_location'] = $tnc_location;
        $data['tnc_pic'] = $tnc_pic;
        $data['tnc_name'] = $tnc_name;
        $data['tnc_url'] = $tnc_url;
    }

}
