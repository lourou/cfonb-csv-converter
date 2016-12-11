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
use Exception;

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
        }else{
            throw new Exception("Cannot open input file.", 1);
        }
    }

    /**
     * Convert to CSV
     *
     * @return string
     */
    public function convertToCSV($data) {
        $csv_data[] = array('Date', 'Withdrawals', 'Deposits', 'Payee', 'Description', 'Reference Number');
        foreach ($data as $item) {
            if($item['register_code'] == '04'){
                $csv_data[] = array(
                    'Date'             => $item['date'],
                    'Withdrawals'      => number_format($item['withdrawal']/100, 2, '.', ''),
                    'Deposits'         => number_format($item['deposit']/100, 2, '.', ''),
                    'Payee'            => '',
                    'Description'      => $item['name'],
                    'Reference Number' => substr($item['description'], 0, 100),
                );
            }
        }
        return $csv_data;
    }

    /**
     * Format data
     *
     * @return array
     */
    public function getCleanData($content) {
        // Init
        $transactions = array();
        $content[] = '';
        
        // Loop
        foreach ($content as $raw_line) {
            // Split the line of text every space
            // Remove array items containing spaces
            $line = explode(' ', $raw_line);
            $line = $this->removeEmptyArrayItems($line);

            // Determine what kind of transaction each line is
            $register_code = substr($line[0], 0, 2);

            // Add transaction to array if the line is != transaction details
            if(isset($transaction) && !empty($transaction) && $register_code != '05'){
                $transactions[] = $transaction;
                unset($transaction);
            }

            switch ($register_code) {

                // Initial and final balance
                case '01':
                case '07':
                    $transaction = array();
                    $transaction['register_code']  = $register_code;
                    $transaction['name']           = 'balance';
                    $transaction['date']           = $this->convertDate($line[3], 'date'); 
                    $transaction['encoded_amount'] = $line[4];
                    $transaction['amount']         = $this->convertAmount($line[4], $register_code);
                    $transaction['deposit']        = 0;
                    $transaction['withdrawal']     = 0;
                    $transaction['description']    = '';
                    break;

                // New transaction item
                case '04':
                    $transaction = array();
                    $transaction['register_code']  = $register_code;
                    $transaction['name']           = $this->retrieveTransac($line, 'name');
                    $transaction['date']           = $this->retrieveTransac($line, 'date');
                    $transaction['encoded_amount'] = $this->retrieveTransac($line, 'amount');
                    $transaction['amount']         = $this->convertAmount($this->retrieveTransac($line, 'amount'), $register_code);

                    if($this->isTransactionDeposit($line)){
                        $transaction['deposit']     = $transaction['amount'];
                        $transaction['withdrawal']  = 0;
                    }else{
                        $transaction['deposit']     = 0;
                        $transaction['withdrawal']  = $transaction['amount'];
                        $transaction['amount']      = -1 * abs($transaction['amount']);
                    }
                    break;

                // Transaction details
                case '05':
                    // Remove first 3 items, concatenate values
                    $details_array = array_slice($line, 3);
                    $details       = implode(' ', $details_array);
                    $transaction['description'] .= $details.' ';
                    break;
            }
        }

        // Integrity check
        if($this->checkIntegrity($transactions)){
            return $transactions;
        }else{
            throw new Exception("Integrity Test Failed.", 1);
        }
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

        // Conversion table
        $ctable = array(
            '{' => 0, '}' => 0,
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9,
            'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'O' => 6, 'P' => 7, 'Q' => 8, 'R' => 9,
        );

        // Concatenate trunk + last digit
        $converted_amount = $trunk . $ctable[$key];
        
        return $converted_amount;
    }

    /**
     * Check transactions integrity
     *
     * @return string
     */
    public function checkIntegrity($transactions) {
        foreach ($transactions as $transaction) {
            // As soon as we reach a 07 register_code, check integrity of previous items
            if($transaction['register_code'] == '07'){
                if($transaction['amount'] == $sum){
                    return true;
                }else{
                    return false;
                }
            }
            if(isset($transaction['amount'])){
                $sum += $transaction['amount'];
            }else{
                return false;
            }
        }
    }

}
