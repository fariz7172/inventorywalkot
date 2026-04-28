function printStockOpname() {
    var printArea = document.getElementById('print-area-opname');
    if (!printArea) {
        alert('Data cetak tidak ditemukan. Silakan buka detail terlebih dahulu.');
        return;
    }

    var printContents = printArea.innerHTML;
    var ref = printArea.getAttribute('data-ref') || 'Draft';

    var css = [
        '@page { margin: 1.5cm; }',
        'body { font-family: "Times New Roman", serif; color: black; background: white; margin: 0; padding: 0; }',
        'table { border-collapse: collapse; width: 100%; }',
        'th, td { border: 1px solid black; padding: 6px 8px; }',
        'img { max-width: 100%; height: auto; }',
        'b, strong { font-weight: bold; }',
        'u { text-decoration: underline; }'
    ].join('\n');

    var htmlContent = '<!DOCTYPE html><html><head>'
        + '<meta charset="UTF-8">'
        + '<title>Stock Opname - ' + ref + '</title>'
        + '<style>' + css + '</style>'
        + '</head><body>' + printContents + '</body></html>';

    var blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var printWindow = window.open(url, '_blank');

    if (!printWindow) {
        alert('Popup diblokir browser. Mohon izinkan popup untuk halaman ini, lalu coba lagi.');
        URL.revokeObjectURL(url);
        return;
    }

    printWindow.addEventListener('load', function() {
        setTimeout(function() {
            printWindow.print();
            setTimeout(function() {
                printWindow.close();
                URL.revokeObjectURL(url);
            }, 500);
        }, 300);
    });
}
