<?php
require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

require 'MoodleUmlVisitor.php';
require 'PlantUmlBuilder.php';

$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();

$dummyCode = <<<'CODE'
<?php
interface Identifiable {}
class BaseController {}
class DatabaseConnection {}
class Logger {}
class ConfigManager {}
class UserRequest {}
class ValidationResult {}
class TemporaryCache {}

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

    public function processRequest(UserRequest $request): ValidationResult {
        $cache = new TemporaryCache();
        return new ValidationResult();
    }
}
CODE;

try {
    $ast = $parser->parse($dummyCode);

    $traverser = new NodeTraverser();
    $visitor = new MoodleUmlVisitor();
    $traverser->addVisitor($visitor);
    $traverser->traverse($ast);

    // Ambil data array dan teruskan ke builder PlantUML
    $umlData = $visitor->getUmlData();
    $builder = new PlantUmlBuilder($umlData);
    $plantUmlOutput = $builder->build();

    echo "=== Hasil Generate PlantUML ===\n";
    echo $plantUmlOutput;

} catch (Error $error) {
    echo "Waduh, ada error saat parsing: {$error->getMessage()}\n";
}