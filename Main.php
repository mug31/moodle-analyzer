<?php
require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

// Panggil kelas visitor yang sudah kita buat tadi
require 'MoodleUmlVisitor.php';

// 1. Inisiasi Mesin Parser
$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();

// 2. Siapkan kode dummy PHP untuk bahan uji coba awal
$dummyCode = <<<'CODE'
<?php
interface Identifiable {}
class BaseController {}
class DatabaseConnection {}
class Logger {}
class ConfigManager {}

class MoodleCourseController extends BaseController implements Identifiable {
    private DatabaseConnection $db;
    public int $courseId;
    private Logger $logger;
    private ConfigManager $config;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->config = new ConfigManager();
    }

    private function validateCourse() {}
}
CODE;

try {
    // 3. Ubah string kode dummy di atas menjadi struktur pohon AST
    $ast = $parser->parse($dummyCode);

    // 4. Inisiasi Traverser (penjelajah) dan masukkan Visitor kita ke dalamnya
    $traverser = new NodeTraverser();
    $visitor = new MoodleUmlVisitor();
    $traverser->addVisitor($visitor);

    // 5. Eksekusi proses penjelajahan AST
    $traverser->traverse($ast);

    // 6. Cetak hasil tangkapan visitor ke layar
    echo "=== Hasil Ekstraksi UML Data ===\n";
    print_r($visitor->getUmlData());

} catch (Error $error) {
    echo "Waduh, ada error saat parsing: {$error->getMessage()}\n";
}