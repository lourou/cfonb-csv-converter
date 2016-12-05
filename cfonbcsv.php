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

            //return print_r($clean_data, 1);
            return $clean_data;
        }
    }

    public function getCleanData($content) {
        $transactions = array();
        foreach ($content as $raw_line) {
            // Split the line of text every space
            $line = explode(' ', $raw_line);

            // Remove array items containing spaces
            $line = $this->removeEmptyArrayItems($line);

            // Parsing register_code
            $transaction['register_code'] = substr($line[0], 0, 2);

            // Determine what kind of transaction each line is
            switch ($transaction['register_code']) {

                // Initial balance
                case '01':
                    $transaction = array();
                    $transaction['date'] = $line[3];
                    $transaction['encoded_amount'] = $line[3];

                    print_r($line);

                    break;

                // Transaction item
                case '04':

                    break;

                // Transaction details
                case '05':

                    break;

                // Final balance
                case '07':

                    break;


            }

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

/* Corresponding table
    { 0
      1
    K 2
    C 3 /?
    L 3 /?
    M 4 /?
    N 5 /?
      6
      7
    H
    G
    Q 8 /?
    R 9 /?

*/


//$file   = 'files/RlvCompte_010816.txt';
$file   = 'files/releve.ccf';
$parser = new CfonbDataParser;
print $parser->parse($file);


