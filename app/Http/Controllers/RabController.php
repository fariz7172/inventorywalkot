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
        
        // Cari user pembuat RAB atau user sesuai kecamatan
        $targetUser = null;
        if (!empty($rab->user_id) && $rab->user) {
            $targetUser = $rab->user;
        } elseif (!empty($rab->kecamatan_id)) {
            $targetUser = User::where('kecamatan_id', $rab->kecamatan_id)->first();
        } elseif (auth()->check()) {
            $targetUser = auth()->user();
        }

        $penerimaName = $targetUser ? $targetUser->name : '-';
        $penerimaNip = ($targetUser && $targetUser->nip) ? $targetUser->nip : '-';
        $jabatan = ($targetUser && !empty($targetUser->jabatan)) ? $targetUser->jabatan : '-';

        $penerimaName = htmlspecialchars($penerimaName, ENT_QUOTES, 'UTF-8');
        $penerimaNip = htmlspecialchars($penerimaNip, ENT_QUOTES, 'UTF-8');
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

        // Penggantian Placeholder Header dan DPA dalam Template XML
        $xml = str_replace('[NOMOR SPT]', $nomorSpt, $xml);
        $xml = str_replace('[NAMA PEKERJAAN]', $lokasi, $xml);
        $xml = str_replace('[NAMA PEKERJAAN/KEGIATAN]', $lokasi, $xml);
        $xml = str_replace('[NOMOR DPA]', '04/039/DPA/2026', $xml);
        $xml = str_replace('[TANGGAL DPA]', '30 Desember 2026', $xml);
        $xml = str_replace('[NOMOR SK SWAKELOLA]', '1445', $xml);
        $xml = str_replace('[NOMOR SK SWAKELOLA] Tahun', '1445 Tahun', $xml);

        // Cari blok 5 paragraf (mulai dari 'Kepada' sampai 'Tahun Anggaran') untuk digantikan dengan tabel borderless yang sangat rapi
        $posMemerintahkan = strpos($xml, 'MEMERINTAHKAN:</w:t></w:r></w:p>');
        if ($posMemerintahkan !== false) {
            $startP = strpos($xml, '<w:p ', $posMemerintahkan);
            $posTahun = strpos($xml, 'Tahun', $posMemerintahkan);
            $endP = false;
            if ($posTahun !== false) {
                $closeP = strpos($xml, '</w:p>', $posTahun);
                if ($closeP !== false) {
                    $endP = $closeP + strlen('</w:p>');
                }
            }

            if ($startP !== false && $endP !== false && $endP > $startP) {
                $rowsKepada = [
                    ['Nama', $penerimaName],
                    ['NIP/NRK', $penerimaNip],
                    ['Jabatan', $jabatan],
                    ['Kedudukan', 'Ketua/Koordinator Tim Pelaksana Swakelola Tipe I'],
                ];

                $namaProgram = '1.03.06 PROGRAM PENGELOLAAN DAN PENGEMBANGAN SISTEM DRAINASE';
                $namaKegiatan = '1.03.06.1.01 Pengelolaan dan Pengembangan Sistem Drainase yang Terhubung Langsung dengan Sungai Lintas Daerah Kabupaten/Kota dan Kawasan Strategis Provinsi';
                $namaSubkegiatan = '1.03.06.1.01.0010 Operasi dan Pemeliharaan Sistem Drainase Perkotaan';
                $nomorKodeKegiatan = '1.03.06.1.01.0010.001 Operasi dan Pemeliharaan Sistem Drainase';
                $kodeRekening = '5.1.02.03.004.00024 Belanja Pemeliharaan Bangunan Air-Bangunan Air Irigasi-Bangunan Waduk Irigasi';

                $rowsData = [
                    ['Nama Program', $namaProgram],
                    ['Nama Kegiatan', $namaKegiatan],
                    ['Nama Subkegiatan', $namaSubkegiatan],
                    ['Nomor/Kode Kegiatan', $nomorKodeKegiatan],
                    ['Kode Rekening', $kodeRekening],
                    ['Jenis Pekerjaan', 'Perbaikan dan Pengurasan Saluran'],
                    ['Lokasi', $lokasi],
                    ['Tahun Anggaran', $tahunAnggaran],
                ];

                $kepadaHeading = '<w:p><w:pPr><w:spacing w:before="120" w:after="40" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:t>Kepada :</w:t></w:r></w:p>';
                $dataHeading = '<w:p><w:pPr><w:spacing w:before="140" w:after="40" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:t>Untuk melaksanakan pekerjaan Swakelola Tipe I dengan data sebagai berikut:</w:t></w:r></w:p>';

                $cleanTableSection = $kepadaHeading . $this->renderBorderlessTable($rowsKepada) . $dataHeading . $this->renderBorderlessTable($rowsData);

                $xml = substr_replace($xml, $cleanTableSection, $startP, $endP - $startP);
            }
        }

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

        // NIP Pemberi Perintah (Heria Suwandi)
        $nipHeriaSuwandi = '197101272006041009';
        $posHeriaNip = strpos($xml, 'NIP [NIP]');
        if ($posHeriaNip !== false) {
            $xml = substr_replace($xml, 'NIP ' . $nipHeriaSuwandi, $posHeriaNip, strlen('NIP [NIP]'));
        }

        // Ganti sisa placeholder jika ada
        $xml = str_replace('[NIP]', $nipHeriaSuwandi, $xml);
        $xml = str_replace('[TANGGAL]', $tglMulai, $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $safeFilename = 'Surat_Perintah_Tugas_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorSpt) . '.docx';

        return response()->download($tempFile, $safeFilename)->deleteFileAfterSend(true);
    }

    public function downloadBast(Rab $rab, ?string $customTglMulai = null, ?string $customTglSelesai = null)
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

        if (!empty($customTglMulai)) {
            $tglMulai = Carbon::parse($customTglMulai)->locale('id')->isoFormat('D MMMM Y');
        } else {
            $tglMulai = $tglRab;
        }

        if (!empty($customTglSelesai)) {
            $tglSelesai = Carbon::parse($customTglSelesai)->locale('id')->isoFormat('D MMMM Y');
        } else {
            $latestMaterial = \Illuminate\Support\Facades\DB::table('material_rab')
                ->where('rab_id', $rab->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $tglSelesai = ($latestMaterial && $latestMaterial->created_at) 
                ? Carbon::parse($latestMaterial->created_at)->locale('id')->isoFormat('D MMMM Y') 
                : $tglMulai;
        }

        // 2. Replacements
        // Nomor BAST
        $xml = preg_replace('/NOMOR:\s+\/PR\.01\.02/', 'NOMOR: ' . $nomorUrut . '/PR.01.02', $xml);

        // Hari, Tanggal, Bulan saat download
        $xml = str_replace('[HARI]', $hariIni, $xml);
        $xml = str_replace('tanggal [TANGGAL] bulan [BULAN]', "tanggal {$tglIni} bulan {$blnIni}", $xml);

        // Nama Ketua Tim Pelaksana
        $xml = str_replace('[NAMA KETUA TIM PELAKSANA]', $penerimaName, $xml);

        // Pihak 1 NIP/NRK di daftar nomor 1
        $posPihak1Nip = strpos($xml, ': [NIP]');
        if ($posPihak1Nip !== false) {
            $xml = substr_replace($xml, ': ' . $penerimaNip, $posPihak1Nip, strlen(': [NIP]'));
        }

        // Pihak 2 NIP (Heria Suwandi) di daftar nomor 2
        $nipHeriaSuwandi = '197101272006041009';
        $posPihak2Nip = strpos($xml, ': [NIP]');
        if ($posPihak2Nip !== false) {
            $xml = substr_replace($xml, ': ' . $nipHeriaSuwandi, $posPihak2Nip, strlen(': [NIP]'));
        }

        // Keputusan Nomor [NOMOR SK] & Surat Perintah Tugas Nomor [NOMOR SPT]
        $xml = str_replace('[NOMOR SK]', '1445', $xml);
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

        // NIP Pihak Kedua (Heria Suwandi)
        $nipHeriaSuwandi = '197101272006041009';
        $posHeriaNip = strpos($xml, 'NIP [NIP]');
        if ($posHeriaNip !== false) {
            $xml = substr_replace($xml, 'NIP ' . $nipHeriaSuwandi, $posHeriaNip, strlen('NIP [NIP]'));
        }

        // Sisa NIP (Pihak Kedua Heria Suwandi di daftar identitas atas & lainnya)
        $xml = str_replace('[NIP]', $nipHeriaSuwandi, $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $safeFilename = 'Berita_Acara_Serah_Terima_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorUrut) . '.docx';

        return response()->download($tempFile, $safeFilename)->deleteFileAfterSend(true);
    }

    protected function renderBorderlessTable(array $rows, int $w1 = 2500, int $w2 = 200, int $w3 = 5940): string
    {
        $total = $w1 + $w2 + $w3;
        $out = '<w:tbl>'
            . '<w:tblPr>'
            . '<w:tblW w:w="' . $total . '" w:type="dxa"/>'
            . '<w:tblLayout w:type="fixed"/>'
            . '<w:tblBorders>'
            . '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '</w:tblBorders>'
            . '<w:tblCellMar>'
            . '<w:top w:w="30" w:type="dxa"/>'
            . '<w:bottom w:w="30" w:type="dxa"/>'
            . '<w:left w:w="20" w:type="dxa"/>'
            . '<w:right w:w="20" w:type="dxa"/>'
            . '</w:tblCellMar>'
            . '</w:tblPr>'
            . '<w:tblGrid>'
            . '<w:gridCol w:w="' . $w1 . '"/>'
            . '<w:gridCol w:w="' . $w2 . '"/>'
            . '<w:gridCol w:w="' . $w3 . '"/>'
            . '</w:tblGrid>';

        foreach ($rows as $r) {
            $l = $r[0];
            $v = $r[1];
            $out .= '<w:tr>'
                . '<w:trPr><w:cantSplit/></w:trPr>'
                . '<w:tc>'
                . '<w:tcPr><w:tcW w:w="' . $w1 . '" w:type="dxa"/><w:vAlign w:val="top"/></w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:before="20" w:after="20" w:line="240" w:lineRule="auto"/></w:pPr>'
                . '<w:r><w:t>' . $l . '</w:t></w:r>'
                . '</w:p>'
                . '</w:tc>'
                . '<w:tc>'
                . '<w:tcPr><w:tcW w:w="' . $w2 . '" w:type="dxa"/><w:vAlign w:val="top"/></w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:before="20" w:after="20" w:line="240" w:lineRule="auto"/></w:pPr>'
                . '<w:r><w:t>:</w:t></w:r>'
                . '</w:p>'
                . '</w:tc>'
                . '<w:tc>'
                . '<w:tcPr><w:tcW w:w="' . $w3 . '" w:type="dxa"/><w:vAlign w:val="top"/></w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:before="20" w:after="20" w:line="240" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr>'
                . '<w:r><w:t>' . $v . '</w:t></w:r>'
                . '</w:p>'
                . '</w:tc>'
                . '</w:tr>';
        }
        $out .= '</w:tbl>';
        return $out;
    }
}
