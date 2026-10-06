<?php

namespace Tests\Feature;

use App\Services\Pdf\CompatibleFpdi;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CompatibleFpdiTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'resi-pdf-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->directory);
        $this->app->useStoragePath($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_compressed_pdf_is_cached_and_can_be_printed_without_changing_original(): void
    {
        $source = $this->directory.'/compressed.pdf';
        $process = new Process(['node', '-e',
            'const fs=require("fs"); const {PDFDocument}=require("pdf-lib"); (async()=>{const d=await PDFDocument.create(); d.addPage([200,300]).drawText("RESI JY1234567890"); d.addPage([300,400]); fs.writeFileSync(process.argv[1],await d.save({useObjectStreams:true}));})().catch(e=>{console.error(e);process.exit(1)});',
            $source,
        ], base_path());
        $process->mustRun();
        $originalHash = hash_file('sha256', $source);

        try {
            (new Fpdi)->setSourceFile($source);
            $this->fail('Fixture must trigger the compressed cross-reference error.');
        } catch (CrossReferenceException $error) {
            $this->assertSame(CrossReferenceException::COMPRESSED_XREF, $error->getCode());
        }

        $pdf = new CompatibleFpdi;
        $this->assertSame(2, $pdf->setSourceFile($source));
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);
        $this->assertEqualsWithDelta(200 / 72 * 25.4, $size['width'], 0.001);
        $this->assertEqualsWithDelta(300 / 72 * 25.4, $size['height'], 0.001);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        $output = $pdf->Output('S');
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertSame($originalHash, hash_file('sha256', $source));

        // A fresh print request must use the cache even with Node unavailable.
        config(['pdf.node_binary' => $this->directory.'/missing-node']);
        $this->assertSame(2, (new CompatibleFpdi)->setSourceFile($source));
        $this->assertCount(1, glob($this->directory.'/app/private/resi_pdf_cache/*.pdf'));
    }

    public function test_standard_pdf_does_not_need_node_or_cache(): void
    {
        config(['pdf.node_binary' => $this->directory.'/missing-node']);
        $source = $this->directory.'/standard.pdf';
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->Output('F', $source);

        $this->assertSame(1, (new CompatibleFpdi)->setSourceFile($source));
        $this->assertDirectoryDoesNotExist($this->directory.'/app/private/resi_pdf_cache');
    }
}
