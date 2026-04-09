<?php
require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

require 'MoodleUmlVisitor.php';
require 'PlantUmlBuilder.php';

// 1. Tentukan target path direktori yang ingin dianalisis
// Sesuaikan path ini dengan lokasi folder Moodle di komputermu
$targetDirectory = 'D:\Downloads\moodle-5.1.3(1)\moodle\public\mod\assign\classes';

// Validasi keberadaan direktori
if (!is_dir($targetDirectory)) {
    echo "Peringatan: Direktori target tidak ditemukan di path:\n{$targetDirectory}\n";
    echo "Silakan buat foldernya atau sesuaikan path-nya.\n";
    exit(1);
}

// 2. Inisiasi mesin Parser dan Traverser
$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();

$traverser = new NodeTraverser();
$visitor = new MoodleUmlVisitor();
$traverser->addVisitor($visitor);

// 3. Siapkan alat penjelajah direktori rekursif
$directory = new RecursiveDirectoryIterator($targetDirectory);
$iterator = new RecursiveIteratorIterator($directory);
// Filter hanya untuk file berakhiran .php
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

echo "Memulai proses pemindaian direktori: {$targetDirectory}\n";
echo "--------------------------------------------------\n";

$fileCount = 0;
$errorCount = 0;

// 4. Mulai proses iterasi pembacaan setiap file PHP
foreach ($regex as $file) {
    $filePath = $file[0];
    $code = file_get_contents($filePath);

    try {
        // Ubah kode menjadi AST dan lewati ke Visitor
        $ast = $parser->parse($code);
        $traverser->traverse($ast);
        $fileCount++;
        
        // Opsional: tampilkan log file yang berhasil diproses agar proses tidak terlihat diam
        echo "Berhasil mem-parse: " . basename($filePath) . "\n";
    } catch (Error $error) {
        // Tangkap error per file agar tidak menghentikan keseluruhan proses
        echo "GAGAL mem-parse {$filePath}: {$error->getMessage()}\n";
        $errorCount++;
    }
}

echo "--------------------------------------------------\n";
echo "Pemindaian selesai.\n";
echo "Total file PHP berhasil diproses: {$fileCount}\n";
echo "Total file gagal diproses: {$errorCount}\n\n";

// 5. Eksekusi pengumpulan data dan pembuatan PlantUML
$umlData = $visitor->getUmlData();

// Analisis Kuantitatif untuk Data Paper
$totalClasses = 0;
$totalInterfaces = 0;
$totalAttributes = 0;
$totalMethods = 0;
$totalRelations = 0;
$relationDetails = [
    'inheritance' => 0, 
    'realization' => 0, 
    'association' => 0, 
    'aggregation' => 0, 
    'composition' => 0
];

foreach ($umlData as $name => $data) {
    if ($data['type'] === 'class') $totalClasses++;
    if ($data['type'] === 'interface') $totalInterfaces++;

    $totalAttributes += count($data['properties']);
    $totalMethods += count($data['methods']);

    // Hitung Inheritance
    if (!empty($data['relations']['inheritance'])) {
        $totalRelations++;
        $relationDetails['inheritance']++;
    }

    // Hitung relasi lainnya
    foreach (['realization', 'association', 'aggregation', 'composition'] as $relType) {
        $count = count($data['relations'][$relType]);
        $totalRelations += $count;
        $relationDetails[$relType] += $count;
    }
}

echo "\n=== HASIL EKSTRAKSI KUANTITATIF ===\n";
echo "Total Kelas      : {$totalClasses}\n";
echo "Total Interface  : {$totalInterfaces}\n";
echo "Total Atribut    : {$totalAttributes}\n";
echo "Total Metode     : {$totalMethods}\n";
echo "Total Relasi     : {$totalRelations}\n";
echo "Detail Relasi:\n";
echo " - Inheritance   : {$relationDetails['inheritance']}\n";
echo " - Realization   : {$relationDetails['realization']}\n";
echo " - Association   : {$relationDetails['association']}\n";
echo " - Aggregation   : {$relationDetails['aggregation']}\n";
echo " - Composition   : {$relationDetails['composition']}\n";
echo "===================================\n\n";

if (empty($umlData)) {
    echo "Tidak ada struktur Class atau Interface yang ditemukan untuk di-generate.\n";
} else {
    echo "Mulai menyusun format teks PlantUML...\n";
    $builder = new PlantUmlBuilder($umlData);
    $plantUmlOutput = $builder->build();

    // 6. Simpan hasil akhir ke dalam file .puml
    $outputFileName = 'hasil_rekonstruksi_moodle.puml';
    $outputFilePath = __DIR__ . '/' . $outputFileName;
    
    file_put_contents($outputFilePath, $plantUmlOutput);

    echo "=== PROSES SUKSES ===\n";
    echo "File PlantUML berhasil dibuat dan disimpan di:\n{$outputFilePath}\n";
}