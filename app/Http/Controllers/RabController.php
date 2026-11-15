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
            $templatePath = public_path('assets/SPT_pak_deni_template.docx');
        }
        if (!file_exists($templatePath)) {
            abort(404, 'Template dokumen SPT tidak ditemukan.');
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

        // Cari user pembuat RAB atau user sesuai kecamatan (utamakan admin kecamatan jika relevan, role pemel, atau kasatpel kecamatan RAB)
        $targetUser = null;
        if (auth()->check() && (auth()->user()->kecamatan_id || auth()->user()->hasRole('kecamatan_admin') || auth()->user()->hasRole('pemel'))) {
            $targetUser = auth()->user();
        } elseif (preg_match('/\b(PML|PEMEL|PEMELIHARAAN)\b/i', $rab->lokasi)) {
            $targetUser = User::role('pemel')->first() ?: auth()->user();
        } elseif (!empty($rab->kecamatan_id)) {
            $targetUser = User::where('kecamatan_id', $rab->kecamatan_id)->first();
        } elseif (!empty($rab->user_id) && $rab->user) {
            $targetUser = $rab->user;
        } elseif (auth()->check()) {
            $targetUser = auth()->user();
        }

        $penerimaName = $targetUser ? ($targetUser->name ?: 'Rezky A P') : 'Rezky A P';
        $penerimaNip = ($targetUser && $targetUser->nip) ? $targetUser->nip : '-';
        $jabatan = ($targetUser && !empty($targetUser->jabatan)) ? $targetUser->jabatan : 'Ketua Satuan Pelaksana Kecamatan Penjaringan';

        $penerimaName = htmlspecialchars($penerimaName, ENT_QUOTES, 'UTF-8');
        $penerimaNip = htmlspecialchars($penerimaNip, ENT_QUOTES, 'UTF-8');
        $jabatan = htmlspecialchars($jabatan, ENT_QUOTES, 'UTF-8');

        // Tanggal Mulai & Selesai
        Carbon::setLocale('id');
        $mulaiCarbon = $rab->created_at ? Carbon::parse($rab->created_at) : Carbon::now();
        $tglMulai = $mulaiCarbon->locale('id')->isoFormat('D MMMM Y');

        $latestMaterial = \Illuminate\Support\Facades\DB::table('material_rab')
            ->where('rab_id', $rab->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestMaterial && $latestMaterial->created_at && Carbon::parse($latestMaterial->created_at)->gt((clone $mulaiCarbon)->addDays(5))) {
            $tglSelesai = Carbon::parse($latestMaterial->created_at)->locale('id')->isoFormat('D MMMM Y');
        } else {
            $tglSelesai = (clone $mulaiCarbon)->addMonth()->locale('id')->isoFormat('D MMMM Y');
        }

        if (request()->filled('tgl_mulai')) {
            $tglMulai = Carbon::parse(request('tgl_mulai'))->locale('id')->isoFormat('D MMMM Y');
        }
        if (request()->filled('tgl_selesai')) {
            $tglSelesai = Carbon::parse(request('tgl_selesai'))->locale('id')->isoFormat('D MMMM Y');
        }

        $tahunAnggaran = $rab->created_at ? Carbon::parse($rab->created_at)->format('Y') : date('Y');

        // Kecamatan / Tim Pelaksana
        $timPelaksana = 'Kecamatan Penjaringan';
        if ($rab->kecamatan && !empty($rab->kecamatan->name)) {
            $timPelaksana = 'Kecamatan ' . $rab->kecamatan->name;
        } elseif ($targetUser && !empty($targetUser->jabatan) && preg_match('/Kecamatan\s+([A-Za-z\s]+)/i', $targetUser->jabatan, $m)) {
            $timPelaksana = 'Kecamatan ' . trim($m[1]);
        } elseif ($targetUser && !empty($targetUser->kecamatan?->name)) {
            $timPelaksana = 'Kecamatan ' . $targetUser->kecamatan->name;
        } elseif (preg_match('/\b(Penjaringan|Cilincing|Koja|Pademangan|Tanjung Priok|Kelapa Gading)\b/i', $rab->lokasi, $m)) {
            $timPelaksana = 'Kecamatan ' . trim($m[1]);
        } elseif ($targetUser && preg_match('/(Pemeliharaan|Pemel)/i', $targetUser->jabatan ?? '')) {
            $timPelaksana = 'Seksi Pemeliharaan Drainase';
        }
        $timPelaksana = preg_replace('/^Kecamatan\s+Kecamatan\s+/i', 'Kecamatan ', $timPelaksana);

        // Judul & Pengantar Pekerjaan
        if (preg_match('/^(PERBAIKAN|PENGURASAN|PEMELIHARAAN|PEMBANGUNAN)/i', $lokasi)) {
            $namaPekerjaanJudul = mb_strtoupper($lokasi);
            $namaPekerjaanPengantar = $lokasi;
        } else {
            $namaPekerjaanJudul = 'PERBAIKAN SALURAN CROSSING ' . mb_strtoupper($lokasi);
            $namaPekerjaanPengantar = 'perbaikan saluran crossing ' . $lokasi;
        }

        $jenisPekerjaan = preg_match('/pengurasan/i', $lokasi) ? 'Pengurasan Saluran' : 'Perbaikan Saluran Crossing';

        // Replacements
        $replacements = [
            '[NOMOR SPT]' => $nomorSpt,
            '[NAMA PEKERJAAN JUDUL]' => $namaPekerjaanJudul,
            '[TAHUN ANGGARAN]' => $tahunAnggaran,
            '[NAMA PENERIMA]' => $penerimaName,
            '[NIP PENERIMA]' => $penerimaNip,
            '[JABATAN PENERIMA]' => $jabatan,
            '[NAMA PEKERJAAN PENGANTAR]' => $namaPekerjaanPengantar,
            '[JENIS PEKERJAAN]' => $jenisPekerjaan,
            '[LOKASI]' => $lokasi,
            '[TIM PELAKSANA KECAMATAN]' => $timPelaksana,
            '[TANGGAL MULAI]' => $tglMulai,
            '[TANGGAL SELESAI]' => $tglSelesai,
            // Fallback replacements
            '[NAMA PEKERJAAN]' => $lokasi,
            '[NAMA PEKERJAAN/KEGIATAN]' => $lokasi,
            '[NAMA]' => $penerimaName,
            '[NIP]' => $penerimaNip,
            '[TANGGAL]' => $tglMulai,
        ];

        foreach ($replacements as $search => $replace) {
            $xml = str_replace($search, $replace, $xml);
        }

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
        $jabatan = 'Ketua Tim Pelaksana Swakelola Tipe I';

        // Cari user Pihak Kesatu: prioritaskan admin kecamatan jika relevan, role pemel jika login sebagai pemel, atau kasatpel kecamatan RAB
        $targetUser = null;
        if (auth()->check() && (auth()->user()->kecamatan_id || auth()->user()->hasRole('kecamatan_admin') || auth()->user()->hasRole('pemel'))) {
            $targetUser = auth()->user();
        } elseif (preg_match('/\b(PML|PEMEL|PEMELIHARAAN)\b/i', $rab->lokasi)) {
            $targetUser = User::role('pemel')->first() ?: auth()->user();
        } elseif (!empty($rab->kecamatan_id)) {
            $targetUser = User::where('kecamatan_id', $rab->kecamatan_id)->first();
        } elseif (!empty($rab->user_id) && $rab->user) {
            $targetUser = $rab->user;
        } elseif (auth()->check()) {
            $targetUser = auth()->user();
        }

        if ($targetUser) {
            $penerimaName = $targetUser->name ?: 'Rezky A P';
            $penerimaNip = $targetUser->nip ?: '-';
            if (!empty($targetUser->jabatan)) {
                $jabatan = $targetUser->jabatan;
            }
        }

        $penerimaName = htmlspecialchars($penerimaName, ENT_QUOTES, 'UTF-8');
        $penerimaNip = htmlspecialchars($penerimaNip, ENT_QUOTES, 'UTF-8');
        $jabatan = htmlspecialchars($jabatan, ENT_QUOTES, 'UTF-8');
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

        // Pihak 1 Jabatan di daftar nomor 1
        if (!empty($targetUser?->jabatan)) {
            $xml = str_replace(
                '<w:t>Ketua</w:t></w:r><w:proofErr w:type="spellEnd"/><w:r><w:t xml:space="preserve"> Tim Pelaksana Swakelola Tipe I</w:t>',
                '<w:t>' . $jabatan . '</w:t>',
                $xml
            );
            $xml = str_replace(
                '<w:t>Ketua Tim Pelaksana Swakelola Tipe I</w:t>',
                '<w:t>' . $jabatan . '</w:t>',
                $xml
            );
        }

        // Pihak 2 NIP (Heria Suwandi) di daftar nomor 2
        $nipHeriaSuwandi = '197101272006041009';
        $posPihak2Nip = strpos($xml, ': [NIP]');
        if ($posPihak2Nip !== false) {
            $xml = substr_replace($xml, ': ' . $nipHeriaSuwandi, $posPihak2Nip, strlen(': [NIP]'));
        }

        // Keputusan Nomor [NOMOR SK] & Surat Perintah Tugas Nomor [NOMOR SPT]
        $xml = str_replace('[NOMOR SK] Tahun 2026', '6 Tahun 2026 Tanggal 5 Januari 2026', $xml);
        $xml = str_replace('1445 Tahun 2026 ', '6 Tahun 2026 Tanggal 5 Januari 2026 ', $xml);
        $xml = str_replace('1445 Tahun 2026', '6 Tahun 2026 Tanggal 5 Januari 2026', $xml);
        $xml = str_replace('[NOMOR SK]', '6', $xml);
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
