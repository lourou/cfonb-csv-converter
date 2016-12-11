<?php

/*
 * CFONB Bank Statement Parser
 *
 * (c) Louis Rouffineau <accounts - at - louis.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CfonbParser;

use Carbon\Carbon;

class CfonbParser
{

    /**
     * Read input file
     *
     * @return array
     */
    public function parse($file) {
        if (file_exists($file) && is_readable($file)) {
            $content      = file($file);
            $clean_data   = $this->getCleanData($content);
            return $clean_data;
        }
    }

    /**
     * Format data
     *
     * @return array
     */
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
                    $transaction['encoded_amount'] = $line[4];
                    break;

                // Transaction item
                case '04':
                    $transaction['name']           = $this->retrieveTransac($line, 'name');
                    $transaction['date']           = $this->retrieveTransac($line, 'date');
                    $transaction['encoded_amount'] = $this->retrieveTransac($line, 'amount');
                    $transaction['amount']         = $this->convertAmount(
                        $this->retrieveTransac($line, 'amount'),
                        $transaction['register_code']
                    );

                    if($this->isTransactionDeposit($line)){
                        $transaction['deposit']     = $transaction['amount'];
                        $transaction['withdrawal']  = 0;
                    }else{
                        $transaction['deposit']     = 0;
                        $transaction['withdrawal']  = $transaction['amount'];
                    }

                    // print_r($line);
                    // print_r($transaction); 

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

        // @todo check bank statement integrity
        // initial balance + transactions = final balance
        // exception if needed

        return $transactions;
    }

    /**
     * Remove empty lines in array
     *
     * @return string
     */
    public function removeEmptyArrayItems($line) {
        foreach ($line as $value) {
            if($value && !strstr($value, PHP_EOL)){
                $clean_line[] = $value;
            }
        }
        return $clean_line;
    }

    /**
     * Parse transaction name, date and amount
     *
     * @return string
     */
    public function retrieveTransac($line, $type){
        foreach ($line as $value) {
            // Dismiss array items ending with a numerical value
            if(!is_numeric(substr($value, -1, 1))){
                $transac_array[] = $value;
            }
        }
        switch ($type) {
            case 'name':
                // Remove last array item, concatenate values and strip date
                $name = array_slice($transac_array, 0, -1);
                $name = implode(' ', $name);
                return substr($name, 6);
                break;

            case 'amount':
                return end($transac_array);
                break;

            case 'date':
                return $this->convertDate(substr($transac_array[0], 0, 6));
                break;
        }
    }

    /**
     * Determine if transaction is a deposit
     *
     * @return BOOL
     */
    public function isTransactionDeposit($line){
        // Deposits keywords
        $deposits = array('REMISE CHEQUE', 'VIREMENT SEPA RECU');
        $transac  = implode(' ', $line);
        foreach ($deposits as $deposit) {
            if (strpos($transac, $deposit) !== FALSE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert date from DDMMYY to YYYY-MM-DD
     *
     * @return formatted date string
     */
    public function convertDate($date) {
        $year  = substr($date, 4, 2);
        $dt    = Carbon::createFromFormat('y', $year);
        $year  = $dt->format('Y');
        $month = substr($date, 2, 2);
        $day   = substr($date, 0, 2);
        return Carbon::createFromDate($year, $month, $day)->toDateString();
    }

    /**
     * Convert amount from encoded CFONB value to int
     *
     * @return int
     */
    public function convertAmount($amount, $register_code) {
        // Remove all leading zeros
        $amount = ltrim($amount, '0');

        // Amount key (last char)
        $key = substr($amount, -1);

        // Amount trunk without key
        $trunk = substr($amount, 0, -1);

        switch ($register_code) {
            // Initial or final balance
            case '01':
            case '07':

                break;

            // Transaction item
            case '04':
                $ctable = array('{', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
                $converted_amount = $trunk . array_search($key, $ctable);
                break;
        }

        /* Corresponding table
            { 0
            J 1
            K 2
                        C 3 /?
            L 3 /?
            M 4 /?
            N 5 /?
            O 6
            P 7
                        H
                        G
            Q 8 /?
            R 9 /?

        */
        
        return $converted_amount;
    }

}
