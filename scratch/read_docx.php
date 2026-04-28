<?php
$file = __DIR__ . '/../public/assets/BERITA ACARA STOK OPNAME.docx';
$z = new ZipArchive();
$z->open($file);
$xml = $z->getFromName('word/document.xml');
$z->close();

$dom = new DOMDocument();
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$paragraphs = $xpath->query('//w:p');
foreach ($paragraphs as $para) {
    $texts = $xpath->query('.//w:t', $para);
    $line = '';
    foreach ($texts as $t) {
        $line .= $t->textContent;
    }
    if (trim($line)) echo $line . PHP_EOL;
}
