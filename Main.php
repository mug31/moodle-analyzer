<?php
require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

require 'MoodleUmlVisitor.php';
require 'PlantUmlBuilder.php';

// 1. Tentukan daftar direktori modul Moodle
$targetDirectories = [
    'assign' => 'D:\Downloads\moodle-5.1.3(1)\moodle\public\mod\assign\classes',
    'course' => 'D:\Downloads\moodle-5.1.3(1)\moodle\public\course\classes',
    'user'   => 'D:\moodle-5.1.3(1)\moodle\public\user\classes'
];

$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();

foreach ($targetDirectories as $moduleName => $targetDirectory) {
    // Validasi keberadaan direktori
    if (!is_dir($targetDirectory)) {
        echo "Peringatan: Direktori target {$moduleName} tidak ditemukan di path: {$targetDirectory}\n";
        continue;
    }

    // RESET Visitor dan Traverser untuk setiap modul baru agar data tidak bercampur
    $traverser = new NodeTraverser();
    $visitor = new MoodleUmlVisitor();
    $traverser->addVisitor($visitor);

    echo "==================================================\n";
    echo "MEMULAI PEMINDAIAN MODUL: " . strtoupper($moduleName) . "\n";
    echo "Path: {$targetDirectory}\n";
    echo "--------------------------------------------------\n";

    $directory = new RecursiveDirectoryIterator($targetDirectory);
    $iterator = new RecursiveIteratorIterator($directory);
    $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    $fileCount = 0;
    $errorCount = 0;

    foreach ($regex as $file) {
        $filePath = $file[0];
        $code = file_get_contents($filePath);
        try {
            $ast = $parser->parse($code);
            $traverser->traverse($ast);
            $fileCount++;
            echo "Berhasil mem-parse: " . basename($filePath) . "\n";
        } catch (Error $error) {
            echo "GAGAL mem-parse {$filePath}: {$error->getMessage()}\n";
            $errorCount++;
        }
    }

    // Pengumpulan data metrik per modul
    $umlData = $visitor->getUmlData();
    $totalClasses = 0;
    $totalInterfaces = 0;
    $totalAttributes = 0;
    $totalMethods = 0;
    $totalRelations = 0;
    $relationDetails = [
        'inheritance' => 0, 'realization' => 0, 'association' => 0, 
        'aggregation' => 0, 'composition' => 0
    ];

    foreach ($umlData as $name => $data) {
        if ($data['type'] === 'class') $totalClasses++;
        if ($data['type'] === 'interface') $totalInterfaces++;

        $totalAttributes += count($data['properties']);
        $totalMethods += count($data['methods']);

        if (!empty($data['relations']['inheritance'])) {
            $totalRelations++;
            $relationDetails['inheritance']++;
        }

        foreach (['realization', 'association', 'aggregation', 'composition'] as $relType) {
            $count = count($data['relations'][$relType]);
            $totalRelations += $count;
            $relationDetails[$relType] += $count;
        }
    }

    echo "\n--- HASIL EKSTRAKSI KUANTITATIF (" . strtoupper($moduleName) . ") ---\n";
    echo "Total File PHP   : {$fileCount}\n";
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
    echo "--------------------------------------------------\n";

    if (!empty($umlData)) {
        $builder = new PlantUmlBuilder($umlData);
        $plantUmlOutput = $builder->build();
        
        // Output file unik per modul
        $outputFileName = 'hasil_rekonstruksi_moodle_' . $moduleName . '.puml';
        file_put_contents(__DIR__ . '/' . $outputFileName, $plantUmlOutput);
        echo "SUKSES: File PlantUML disimpan sebagai {$outputFileName}\n\n";
    }
}