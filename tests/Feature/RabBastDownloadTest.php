<?php

namespace Tests\Feature;

use App\Models\Rab;
use App\Models\User;
use App\Http\Controllers\RabController;
use Tests\TestCase;
use ZipArchive;

class RabBastDownloadTest extends TestCase
{
    public function test_laporan_rab_page_is_accessible(): void
    {
        $admin = User::first();
        $response = $this->actingAs($admin)->get('/dashboard/laporan/rab');
        $response->assertStatus(200);
        $response->assertSee('Laporan RAB');
    }

    public function test_download_bast_generates_valid_docx(): void
    {
        $admin = User::first();
        $this->actingAs($admin);

        $rab = Rab::with(['kecamatan', 'user'])->first();
        $this->assertNotNull($rab, 'RAB data must exist in database.');

        $controller = new RabController();
        $response = $controller->downloadBast($rab, '2026-07-03', '2026-08-03');

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

        $textContent = strip_tags($xml);

        // Header & Judul
        $this->assertStringContainsString('SUKU DINAS SUMBER DAYA AIR', $textContent);
        $this->assertStringContainsString('PEMERINTAH KOTA ADMINISTRASI JAKARTA UTARA', $textContent);
        $this->assertStringContainsString('BERITA ACARA SERAH TERIMA HASIL PEKERJAAN', $textContent);
        $this->assertStringContainsString('NOMOR:', $textContent);
        $this->assertStringContainsString('/PR.01.02', $textContent);

        // Pembuka & Pihak-pihak
        $this->assertStringContainsString('Pada hari ini', $textContent);
        $this->assertStringContainsString('tahun Dua Ribu Dua Puluh Enam', $textContent);
        $this->assertStringContainsString('disebut PIHAK KESATU', $textContent);
        $this->assertStringContainsString('Heria Suwandi', $textContent);
        $this->assertStringContainsString('197101272006041009', $textContent);
        $this->assertStringContainsString('Pejabat Pembuat Komitmen Pengelolaan dan Pengembangan Sistem Drainase', $textContent);
        $this->assertStringContainsString('disebut PIHAK KEDUA', $textContent);

        // Dasar Hukum
        $this->assertStringContainsString('Berdasarkan:', $textContent);
        $this->assertStringContainsString('Keputusan Kepala Suku Dinas Sumber Daya Air Kota Administrasi Jakarta Utara Nomor 6 Tahun 2026 Tanggal 5 Januari 2026 tentang Penetapan Penyelenggara Swakelola Tipe I;', $textContent);
        $this->assertStringContainsString('Surat Perintah Tugas Nomor', $textContent);

        // Rincian Pekerjaan
        $this->assertStringContainsString('PIHAK KESATU menyerahkan kepada PIHAK KEDUA dan PIHAK KEDUA menerima hasil pekerjaan', $textContent);
        $this->assertStringContainsString('dengan rincian:', $textContent);
        $this->assertStringContainsString('Nama Pekerjaan:', $textContent);
        $this->assertStringContainsString('Program/Kegiatan:', $textContent);
        $this->assertStringContainsString('Pengelolaan dan Pengembangan Sistem Drainase yang Terhubung Langsung dengan Sungai Lintas Daerah Kabupaten/Kota dan Kawasan Strategis Provinsi', $textContent);
        $this->assertStringContainsString('Lokasi:', $textContent);
        $this->assertStringContainsString('Jangka Waktu:', $textContent);
        $this->assertStringContainsString('3 Juli s.d 3 Agustus 2026', $textContent);
        $this->assertStringContainsString('Realisasi Fisik:', $textContent);
        $this->assertStringContainsString('100%', $textContent);

        // Penutup
        $this->assertStringContainsString('Hasil pekerjaan telah diperiksa oleh Tim Pengawas dan dinyatakan SESUAI sebagaimana Berita Acara Hasil Pemeriksaan tersebut di atas.', $textContent);
        $this->assertStringContainsString('Laporan pelaksanaan beserta dokumen pendukung menjadi lampiran dan bagian yang tidak terpisahkan dari Berita Acara ini.', $textContent);
        $this->assertStringContainsString('Sejak ditandatanganinya Berita Acara ini, tanggung jawab atas hasil pekerjaan yang diserahterimakan beralih kepada PIHAK KEDUA', $textContent);
        $this->assertStringContainsString('Demikian Berita Acara Serah Terima Hasil Pekerjaan ini dibuat dalam rangkap secukupnya untuk dipergunakan sebagaimana mestinya.', $textContent);

        // Tanda Tangan
        $this->assertStringContainsString('PIHAK KESATU', $textContent);
        $this->assertStringContainsString('PIHAK KEDUA', $textContent);
        $this->assertStringContainsString('Pejabat Pembuat Komitmen', $textContent);

        // Pastikan tidak ada placeholder yang tersisa
        $this->assertStringNotContainsString('[JUDUL LOKASI]', $xml);
        $this->assertStringNotContainsString('[NOMOR BAST]', $xml);
        $this->assertStringNotContainsString('[HARI]', $xml);
        $this->assertStringNotContainsString('[TANGGAL]', $xml);
        $this->assertStringNotContainsString('[BULAN]', $xml);
        $this->assertStringNotContainsString('[TAHUN TERBILANG]', $xml);
        $this->assertStringNotContainsString('[NAMA PIHAK KESATU]', $xml);
        $this->assertStringNotContainsString('[NIP PIHAK KESATU]', $xml);
        $this->assertStringNotContainsString('[JABATAN PIHAK KESATU]', $xml);
        $this->assertStringNotContainsString('[NOMOR SPT]', $xml);
        $this->assertStringNotContainsString('[TANGGAL SPT]', $xml);
        $this->assertStringNotContainsString('[PENGANTAR LOKASI]', $xml);
        $this->assertStringNotContainsString('[NAMA PEKERJAAN]', $xml);
        $this->assertStringNotContainsString('[LOKASI]', $xml);
        $this->assertStringNotContainsString('[JANGKA WAKTU]', $xml);
        $this->assertStringNotContainsString('[NAMA]', $xml);
        $this->assertStringNotContainsString('[NIP]', $xml);
    }

    public function test_download_bast_http_route(): void
    {
        $admin = User::first();
        $rab = Rab::first();
        $response = $this->actingAs($admin)->get(route('rab.download-bast', $rab->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
