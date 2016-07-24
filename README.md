# CFONB to CSV Converter

This will convert a CFONB bank statement to a CSV file that you may
import Zoho Books or another accounting software.

CFONB stands for "Comite Francais d'Organisaton et de Normalisation Bancaires",
national body responsible for the French codification in banking activities).

## Usage

```
./cfonb-csv.sh [option...] input_file [output_file]
```

If `output_file` is not specified, a name is generated programmatically
and the file is written into the same directory as `input_file`.

Help options:

    { -t }
        test file but does not convert it
    { -h }
        print help

## Get in touch

Suggestions and bug reports are greatly appreciated: <https://github.com/lourou/cfonb-csv-converter>