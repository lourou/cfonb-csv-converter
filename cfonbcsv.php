<?php

require 'vendor/autoload.php';
require 'cfonbparser/cfonbparser.php';

use Carbon\Carbon;
use CfonbParser\CfonbParser;

//$file   = 'files/RlvCompte_010816.txt';
//$file   = 'files/releve.ccf';

$parser = new CfonbParser;
$parser->parse($file);
