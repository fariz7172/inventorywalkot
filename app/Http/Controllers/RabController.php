<?php

namespace App\Http\Controllers;

use App\Models\Rab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ZipArchive;

class RabController extends Controller
{
    public function downloadSpt(Rab $rab)
    {
        $templatePath = public_path('assets/Surat_Perintah_Tugas_walikota.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template dokumen Surat_Perintah_Tugas_walikota.docx tidak ditemukan.');
        }

        // Siapkan direktori temporary
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . '/SPT_' . $rab->id . '_' . uniqid() . '.docx';
        if (!copy($templatePath, $tempFile)) {
            abort(500, 'Gagal menyiapkan file dokumen untuk diproses.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== true) {
            abort(500, 'Gagal membuka berkas dokumen template.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!$xml) {
            $zip->close();
            @unlink($tempFile);
            abort(500, 'Gagal membaca isi konten dokumen template.');
        }

        // Format data
        $nomorSpt = $rab->nomor_spt ?: ($rab->id . '/KG.II.OO');
        $lokasi = htmlspecialchars($rab->lokasi, ENT_QUOTES, 'UTF-8');

        // Nama Pembuat / Penerima Perintah & NIP
        $penerimaName = '-';
        $penerimaNip = '-';
        if (!empty($rab->user_id) && $rab->user) {
            $penerimaName = $rab->user->name;
            $penerimaNip = $rab->user->nip ?: '-';
        } elseif ($rab->kecamatan_id) {
            $userKec = User::where('kecamatan_id', $rab->kecamatan_id)->first();
            if ($userKec) {
                $penerimaName = $userKec->name;
                $penerimaNip = $userKec->nip ?: '-';
            }
        } elseif (auth()->check()) {
            $penerimaName = auth()->user()->name;
            $penerimaNip = auth()->user()->nip ?: '-';
        }
        $penerimaName = htmlspecialchars($penerimaName, ENT_QUOTES, 'UTF-8');
        $penerimaNip = htmlspecialchars($penerimaNip, ENT_QUOTES, 'UTF-8');

        // Jabatan sesuai Kecamatan / Dinas
        $kecamatanName = $rab->kecamatan ? strtoupper($rab->kecamatan->nama_kecamatan) : '';
        $jabatan = $kecamatanName 
            ? "KEPALA SATUAN PELAKSANA KECAMATAN " . $kecamatanName 
            : "KEPALA SEKSI PEMELIHARAAN DRAINASE";
        $jabatan = htmlspecialchars($jabatan, ENT_QUOTES, 'UTF-8');

        // Tanggal Mulai (Tanggal dibuat RAB)
        Carbon::setLocale('id');
        $tglMulai = $rab->created_at 
            ? Carbon::parse($rab->created_at)->locale('id')->isoFormat('D MMMM Y') 
            : Carbon::now()->locale('id')->isoFormat('D MMMM Y');

        // Tanggal Selesai (Tanggal created_at terakhir dari material_rab)
        $latestMaterial = \Illuminate\Support\Facades\DB::table('material_rab')
            ->where('rab_id', $rab->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $tglSelesai = ($latestMaterial && $latestMaterial->created_at) 
            ? Carbon::parse($latestMaterial->created_at)->locale('id')->isoFormat('D MMMM Y') 
            : $tglMulai;

        $tahunAnggaran = $rab->created_at ? Carbon::parse($rab->created_at)->format('Y') : date('Y');

        // Judul Swakelola dengan baris bertingkat (Nama pekerjaan di bawah Tipe I, dan Tahun Anggaran di bawah Nama Pekerjaan)
        $titleBlock = "PELAKSANAAN SWAKELOLA TIPE I</w:t><w:br/><w:t>" . $lokasi . "</w:t><w:br/><w:t>TAHUN ANGGARAN " . $tahunAnggaran;
        $xml = str_replace(
            'PELAKSANAAN SWAKELOLA TIPE I [NAMA PEKERJAAN] TAHUN ANGGARAN 2026',
            $titleBlock,
            $xml
        );

        // Penggantian Placeholder dalam Template XML
        $xml = str_replace('[NOMOR SPT]', $nomorSpt, $xml);
        $xml = str_replace('[NAMA PEKERJAAN]', $lokasi, $xml);
        $xml = str_replace('[NAMA PEKERJAAN/KEGIATAN]', $lokasi, $xml);
        $xml = str_replace('[NAMA PENERIMA PERINTAH]', $penerimaName, $xml);
        $xml = str_replace('[NIP/NRK]', $penerimaNip, $xml);

        // Jabatan across paragraph 14 & 15
        $xml = str_replace(': [KEPALA SATUAN PELAKSANA KECAMATAN ... / KEPALA ', ': ' . $jabatan, $xml);
        $xml = str_replace('SEKSI PEMELIHARAAN DRAINASE]', '', $xml);

        // Nilai DPA (Penggantian bertahap sesuai urutan pada template)
        $dpaReplacements = [
            ': 1.03.06 PROGRAM PENGELOLAAN DAN PENGEMBANGAN SISTEM DRAINASE',
            ': 1.03.06.1.01 Pengelolaan dan Pengembangan Sistem Drainase yang Terhubung Langsung dengan Sungai Lintas Daerah Kabupaten/Kota dan Kawasan Strategis Provinsi',
            ': 1.03.06.1.01.0010 Operasi dan Pemeliharaan Sistem Drainase Perkotaan',
            ': 1.03.06.1.01.0010.001 Operasi dan Pemeliharaan Sistem Drainase',
            ': 5.1.02.03.004.00024 Belanja Pemeliharaan Bangunan Air-Bangunan Air Irigasi-Bangunan Waduk Irigasi'
        ];

        foreach ($dpaReplacements as $replacement) {
            $pos = strpos($xml, ': [SESUAI DPA]');
            if ($pos !== false) {
                $xml = substr_replace($xml, $replacement, $pos, strlen(': [SESUAI DPA]'));
            }
        }

        // Jenis Pekerjaan
        $xml = str_replace(' / [SESUAI PAKET]', '', $xml);

        // Lokasi
        $xml = str_replace('[LOKASI]', $lokasi, $xml);

        // Tanggal Pelaksanaan
        $xml = str_replace('[TANGGAL MULAI]', $tglMulai, $xml);
        $xml = str_replace('[TANGGAL SELESAI]', $tglSelesai, $xml);

        // Tanda Tangan Penerima & Pemberi Perintah
        $xml = str_replace('[NAMA]', $penerimaName, $xml);

        // Ganti NIP penerima perintah (pada blok tanda tangan pertama)
        $posPenerimaNip = strpos($xml, 'NIP [NIP]');
        if ($posPenerimaNip !== false) {
            $xml = substr_replace($xml, 'NIP ' . $penerimaNip, $posPenerimaNip, strlen('NIP [NIP]'));
        }

        // Ganti NIP pemberi perintah yang tersisa
        $xml = str_replace('[NIP]', '-', $xml);
        $xml = str_replace('[TANGGAL]', $tglMulai, $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $safeFilename = 'Surat_Perintah_Tugas_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorSpt) . '.docx';

        return response()->download($tempFile, $safeFilename)->deleteFileAfterSend(true);
    }

    public function downloadBast(Rab $rab)
    {
        $templatePath = public_path('assets/Berita_Acara_Serah_Terima.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template dokumen Berita_Acara_Serah_Terima.docx tidak ditemukan.');
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . '/BAST_' . $rab->id . '_' . uniqid() . '.docx';
        if (!copy($templatePath, $tempFile)) {
            abort(500, 'Gagal menyiapkan file BAST.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== true) {
            abort(500, 'Gagal membuka berkas template BAST.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!$xml) {
            $zip->close();
            @unlink($tempFile);
            abort(500, 'Gagal membaca isi konten template BAST.');
        }

        // 1. Data Preparation
        $sptNo = $rab->nomor_spt ?: ($rab->id . '/KG.II.OO');
        $parts = explode('/', $sptNo);
        $nomorUrut = trim($parts[0]);

        $penerimaName = '-';
        $penerimaNip = '-';
        if (!empty($rab->user_id) && $rab->user) {
            $penerimaName = $rab->user->name;
            $penerimaNip = $rab->user->nip ?: '-';
        } elseif ($rab->kecamatan_id) {
            $userKec = User::where('kecamatan_id', $rab->kecamatan_id)->first();
            if ($userKec) {
                $penerimaName = $userKec->name;
                $penerimaNip = $userKec->nip ?: '-';
            }
        } elseif (auth()->check()) {
            $penerimaName = auth()->user()->name;
            $penerimaNip = auth()->user()->nip ?: '-';
        }
        $penerimaName = htmlspecialchars($penerimaName, ENT_QUOTES, 'UTF-8');
        $penerimaNip = htmlspecialchars($penerimaNip, ENT_QUOTES, 'UTF-8');
        $lokasi = htmlspecialchars($rab->lokasi, ENT_QUOTES, 'UTF-8');

        Carbon::setLocale('id');
        $hariIni = Carbon::now()->locale('id')->isoFormat('dddd');
        $tglIni = Carbon::now()->locale('id')->isoFormat('D');
        $blnIni = Carbon::now()->locale('id')->isoFormat('MMMM');

        $tglRab = $rab->created_at 
            ? Carbon::parse($rab->created_at)->locale('id')->isoFormat('D MMMM Y') 
            : Carbon::now()->locale('id')->isoFormat('D MMMM Y');

        $tglMulai = $tglRab;
        $latestMaterial = \Illuminate\Support\Facades\DB::table('material_rab')
            ->where('rab_id', $rab->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $tglSelesai = ($latestMaterial && $latestMaterial->created_at) 
            ? Carbon::parse($latestMaterial->created_at)->locale('id')->isoFormat('D MMMM Y') 
            : $tglMulai;

        // 2. Replacements
        // Nomor BAST
        $xml = preg_replace('/NOMOR:\s+\/PR\.01\.02/', 'NOMOR: ' . $nomorUrut . '/PR.01.02', $xml);

        // Hari, Tanggal, Bulan saat download
        $xml = str_replace('[HARI]', $hariIni, $xml);
        $xml = str_replace('tanggal [TANGGAL] bulan [BULAN]', "tanggal {$tglIni} bulan {$blnIni}", $xml);

        // Nama Ketua Tim Pelaksana
        $xml = str_replace('[NAMA KETUA TIM PELAKSANA]', $penerimaName, $xml);

        // Pihak 1 NIP/NRK di daftar nomor 1
        $xml = str_replace(': [NIP]', ': ' . $penerimaNip, $xml);

        // Surat Perintah Tugas Nomor [NOMOR SPT] tanggal [TANGGAL];
        $xml = str_replace('[NOMOR SPT]', $sptNo, $xml);
        $xml = str_replace('tanggal [TANGGAL];', "tanggal {$tglRab};", $xml);

        // Lokasi & Jangka Waktu
        $xml = str_replace('[LOKASI]', $lokasi, $xml);
        $xml = str_replace('[TANGGAL MULAI]', $tglMulai, $xml);
        $xml = str_replace('[TANGGAL SELESAI]', $tglSelesai, $xml);

        // Signature Pihak Kesatu: [NAMA]
        $xml = preg_replace('/\[<\/w:t>.*?NAMA\]/s', $penerimaName, $xml);
        $xml = str_replace('[NAMA]', $penerimaName, $xml);

        // NIP Pihak Kesatu signature
        $posPenerimaNip = strpos($xml, 'NIP [NIP]');
        if ($posPenerimaNip !== false) {
            $xml = substr_replace($xml, 'NIP ' . $penerimaNip, $posPenerimaNip, strlen('NIP [NIP]'));
        }

        // Sisa NIP (Pihak Kedua Heria Suwandi)
        $xml = str_replace('[NIP]', '-', $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $safeFilename = 'Berita_Acara_Serah_Terima_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorUrut) . '.docx';

        return response()->download($tempFile, $safeFilename)->deleteFileAfterSend(true);
    }
}
