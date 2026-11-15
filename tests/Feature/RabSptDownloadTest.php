<?php

namespace Tests\Feature;

use App\Models\Rab;
use App\Models\User;
use App\Http\Controllers\RabController;
use Tests\TestCase;
use ZipArchive;

class RabSptDownloadTest extends TestCase
{
    public function test_rab_index_page_is_accessible(): void
    {
        $admin = User::first();
        $response = $this->actingAs($admin)->get('/dashboard/rab');
        $response->assertStatus(200);
        $response->assertSee('Data RAB');
    }

    public function test_download_spt_generates_valid_docx(): void
    {
        $admin = User::first();
        $this->actingAs($admin);

        $rab = Rab::with(['kecamatan', 'user'])->first();
        $this->assertNotNull($rab, 'RAB data must exist in database.');

        $controller = new RabController();
        $response = $controller->downloadSpt($rab);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('docx', $response->getFile()->getExtension());

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);
        $this->assertGreaterThan(10000, filesize($filePath));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath) === true, 'Downloaded file must be a valid docx zip archive.');

        $xml = $zip->getFromName('word/document.xml');
        $this->assertNotEmpty($xml, 'document.xml must exist in docx archive.');
        $zip->close();

        // Verifikasi konten penting di dalam SPT sesuai format Pak Deni
        $textContent = strip_tags($xml);
        $this->assertStringContainsString('SURAT PERINTAH TUGAS', $textContent);
        $this->assertStringContainsString('TENTANG', $textContent);
        $this->assertStringContainsString('PELAKSANAAN', $textContent);
        $this->assertStringContainsString('Berdasarkan:', $textContent);
        $this->assertStringContainsString('Peraturan Presiden Nomor 16 Tahun 2018', $textContent);
        $this->assertStringContainsString('Peraturan Presiden Nomor 46 Tahun 2025', $textContent);
        $this->assertStringContainsString('MEMERINTAHKAN:', $textContent);
        $this->assertStringContainsString('Kepada :', $textContent);
        $this->assertStringContainsString('Untuk melaksanakan pekerjaan', $textContent);
        $this->assertStringContainsString('Dokumen Teknis dan Administratif', $textContent);
        $this->assertStringContainsString('Ketentuan Pelaksanaan', $textContent);
        $this->assertStringContainsString('Pelaporan dan Pengawasan', $textContent);
        $this->assertStringContainsString('Jangka Waktu', $textContent);
        $this->assertStringContainsString('Heria Suwandi', $textContent);
        $this->assertStringContainsString('197101272006041009', $textContent);

        // Tidak boleh ada placeholder mentah tersisa
        $this->assertStringNotContainsString('[NOMOR SPT]', $xml);
        $this->assertStringNotContainsString('[NAMA PEKERJAAN JUDUL]', $xml);
        $this->assertStringNotContainsString('[NAMA PEKERJAAN PENGANTAR]', $xml);
        $this->assertStringNotContainsString('[TAHUN ANGGARAN]', $xml);
        $this->assertStringNotContainsString('[NAMA PENERIMA]', $xml);
        $this->assertStringNotContainsString('[NIP PENERIMA]', $xml);
        $this->assertStringNotContainsString('[JABATAN PENERIMA]', $xml);
        $this->assertStringNotContainsString('[JENIS PEKERJAAN]', $xml);
        $this->assertStringNotContainsString('[LOKASI]', $xml);
        $this->assertStringNotContainsString('[TIM PELAKSANA KECAMATAN]', $xml);
        $this->assertStringNotContainsString('[TANGGAL MULAI]', $xml);
        $this->assertStringNotContainsString('[TANGGAL SELESAI]', $xml);
    }

    public function test_download_spt_http_route(): void
    {
        $admin = User::first();
        $rab = Rab::first();
        $response = $this->actingAs($admin)->get(route('rab.download-spt', $rab->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
