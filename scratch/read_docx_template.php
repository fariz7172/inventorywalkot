<?php
$file = $argv[1] ?? 'public/assets/BERITA ACARA SERAH TERIMA BARANG DISTRIBUSIPENGELUARAN.docx';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$zip = new ZipArchive();
if ($zip->open($file) === true) {
    if (($index = $zip->locateName('word/document.xml')) !== false) {
        $data = $zip->getFromIndex($index);
        $xml = new DOMDocument();
        $xml->loadXML($data);
        $content = $xml->saveXML();
        
        // Simple extraction of text nodes
        $text = strip_tags($content);
        echo "DOCUMENT CONTENT:\n";
        echo "=================\n";
        echo $text;
        echo "\n=================\n";
    }
    $zip->close();
} else {
    echo "Failed to open zip\n";
}
