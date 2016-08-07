<?php

/**
* CFONB to CSV
*/
class CfonbDataParser
{

    public function parse($file) {
        if (file_exists($file) && is_readable($file)) {
            $content      = file($file);
            $clean_data   = $this->getCleanData($content);
            
            return print_r($clean_data, 1);
        }
    }

    public function getCleanData($content) {
        $transactions = array();
        foreach ($content as $raw_line) {
            // Split the line of text every space
            $line = explode(' ', $raw_line);

            // Remove array items containing spaces
            $line = $this->removeEmptyArrayItems($line);

            // Walk each line and construct proper data
            print strpos($line[0], '2');

            // Read 2 first two chars, determine register_code
            $transaction['register_code'] = substr($line[0], 0, 2);

            // Add transaction to transactions array
            $transactions[] = $transaction;
        }

        return $transactions;
    }

    protected function removeEmptyArrayItems($line) {
        foreach ($line as $value) {
            if($value){
                $clean_line[] = $value;
            }
        }
        return $clean_line;
    }

}


$file   = 'files/RlvCompte_010816.txt';
$parser = new CfonbDataParser;
print $parser->parse($file);