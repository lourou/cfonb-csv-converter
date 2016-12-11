<?php

require 'vendor/autoload.php';
require 'cfonbparser/cfonbparser.php';

use Carbon\Carbon;
use CfonbParser\CfonbParser;

switch ($argv[1]) {
    case '':
    case '-h':
    case '--help':
        print "Usage: php cfonbcsv.php [option...] input_file [output_file]\n\n";
        print "Options:\n\n";
        print "    { -t }\n";
        print "        test file but does not convert it\n";
        print "    { -h }\n";
        print "        print help\n\n";
        print "Suggestions and bug reports are greatly appreciated: https://github.com/lourou/cfonb-csv-converter\n";
        break;

    case '-t':
        try {
            $input  = $argv[2];
            $parser = new CfonbParser;
            $data   = $parser->parse($input);
        } catch (Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";
        }
        if($data){
            print "Integrity check successful!\n";
        }else{
            print "Test Failed.\n";
        }
        break;

    default:
        try {
            $input  = $argv[1];
            if(empty($argv[2])){
                throw new Exception("Specify an output file.", 1);
            }
            $output = $argv[2];
            $parser = new CfonbParser;
            $data   = $parser->parse($input);
        } catch (Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";
        }

        if($data){
            // Generate CSV
            try {
                $csv_data = $parser->convertToCSV($data);
                $fp       = @fopen($output, 'w');
                if($fp){
                    foreach ($csv_data as $fields) {
                        fputcsv($fp, $fields);
                    }
                    fclose($fp);
                    print "CSV file successfully created at: $output\n";
                }else{
                    throw new Exception("Cannot Save CSV file.", 1);
                }
            } catch (Exception $e) {
                echo 'Error: ',  $e->getMessage(), "\n";
            }
        }else{
            print "Failed to generate CSV.\n";
        }
        break;
}
