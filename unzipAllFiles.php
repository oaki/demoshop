<?
$zip = new ZipArchive;
if ($zip->open('chcemeshop_sk.zip') === TRUE) {
    $zip->extractTo(dirname(__FILE__));
    $zip->close();
    echo 'ok';
} else {
    echo 'failed';
}