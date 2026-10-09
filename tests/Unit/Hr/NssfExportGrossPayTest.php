<?php

namespace Tests\Unit\Hr;

use App\Services\Hr\PayrollExports\NssfExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ZipArchive;

class NssfExportGrossPayTest extends TestCase
{
    public function test_gross_pay_is_stored_as_a_whole_number_the_nssf_converter_accepts(): void
    {
        $shillings = $this->wholeShillings('33000.00');

        $this->assertSame(33000, $shillings);

        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->fromArray([
            ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'],
            ['RKS/STAFF200', 'Njogu', 'Brian Murage', 34165387, 'A010123476H', '2027410852', $shillings, null],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nssf-gross-pay-test.xlsx';
        (new Xlsx($sheet))->save($path);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<v>33000</v>', $xml);
        $this->assertStringNotContainsString('33000.0', $xml);
    }

    public function test_fractional_gross_pay_rounds_to_the_nearest_shilling(): void
    {
        $this->assertSame(22341, $this->wholeShillings('22340.50'));
        $this->assertSame(18000, $this->wholeShillings(18000.0));
        $this->assertSame(15000, $this->wholeShillings('15000.49'));
    }

    private function wholeShillings(mixed $amount): int
    {
        $method = new ReflectionMethod(NssfExport::class, 'wholeShillings');

        return $method->invoke(new NssfExport(), $amount);
    }
}
