# CFONB to CSV Converter

This will convert a CFONB bank statement from HSBC France to a CSV file that you may
import Zoho Books or another accounting software.

CFONB stands for "Comite Francais d'Organisation et de Normalisation Bancaires",
national body responsible for the French codification in banking activities).

## Install

```
composer install
```

## Usage

```
php cfonbcsv.php [option...] input_file output_file
```

Options:

    { -t }
        test file but does not convert it
    { -h }
        print help

## Get in touch

Suggestions and bug reports are greatly appreciated: <https://github.com/lourou/cfonb-csv-converter>