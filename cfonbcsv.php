<?php

require 'vendor/autoload.php';
require 'cfonbparser/cfonbparser.php';

use Carbon\Carbon;
use CfonbParser\CfonbParser;

// $file   = 'files/RlvCompte_010816.txt';

try {
    $parser = new CfonbParser;
    $data   = $parser->parse($file);
} catch (Exception $e) {
    echo 'Error: ',  $e->getMessage(), "\n";
}

if($data){
    // Do something with the data
}