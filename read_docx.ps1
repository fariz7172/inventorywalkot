Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead("d:\program file\Project Kantor\Inventory\public\assets\SPB.docx")
$entry = $zip.GetEntry("word/document.xml")
$reader = New-Object System.IO.StreamReader($entry.Open())
$xmlContent = $reader.ReadToEnd()
$reader.Close()
$zip.Dispose()
$text = $xmlContent -replace '<[^>]+>', ' '
Set-Content -Path "d:\program file\Project Kantor\Inventory\spb_text.txt" -Value $text
