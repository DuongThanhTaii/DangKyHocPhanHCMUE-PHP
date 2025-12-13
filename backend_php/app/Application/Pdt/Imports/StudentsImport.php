<?php

namespace App\Application\Pdt\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class StudentsImport implements WithHeadingRow
{
    use Importable;

    // We are just defining this class to use with Excel::toArray() or similar
    // to leverage the heading row parsing.
}
