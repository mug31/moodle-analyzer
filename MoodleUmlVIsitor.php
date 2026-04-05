<?php
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;

class MoodleUmlVisitor extends NodeVisitorAbstract {
    // Wadah utama penyimpan struktur dan relasi UML
    private array $umlData = [];

    // Menangkap Node saat pertama kali dibaca
    public function enterNode(Node $node) {
        if ($node instanceof Class_ || $node instanceof Interface_) {
            if ($node->name === null) {
                return null; 
            }

            $className = $node->name->toString();

            if (!isset($this->umlData[$className])) {
                $this->umlData[$className] = [
                    'type'       => $node instanceof Interface_ ? 'interface' : 'class',
                    'properties' => [],
                    'methods'    => [],
                    'relations'  => [
                        'inheritance' => null, 
                        'realization' => [],   
                        'association' => [],   
                        'aggregation' => [],   
                        'composition' => [],   
                        'dependency'  => []    
                    ]
                ];
            }

            if ($node instanceof Class_) {
                // Tangkap Inheritance dan Realization
                if ($node->extends !== null) {
                    $this->umlData[$className]['relations']['inheritance'] = $node->extends->toString();
                }

                foreach ($node->implements as $interface) {
                    $this->umlData[$className]['relations']['realization'][] = $interface->toString();
                }

                // --- LOGIKA BARU: Ekstraksi Properti dan Relasi Association ---
                // --- LOGIKA BARU: Ekstraksi Properti, Method, Association, Aggregation, & Composition ---
                foreach ($node->stmts as $stmt) {
                    // 1. Ekstraksi Properti dan Association
                    if ($stmt instanceof Node\Stmt\Property) {
                        $propName = $stmt->props[0]->name->toString();
                        
                        $visibility = '+'; 
                        if ($stmt->isPrivate()) {
                            $visibility = '-';
                        } elseif ($stmt->isProtected()) {
                            $visibility = '#';
                        }

                        $this->umlData[$className]['properties'][] = $visibility . ' ' . $propName;

                        if ($stmt->type instanceof Node\Name) {
                            $associatedClass = $stmt->type->toString();
                            $this->umlData[$className]['relations']['association'][] = $associatedClass;
                        }
                    }

                    // 2. Ekstraksi Method, Aggregation, dan Composition
                    if ($stmt instanceof Node\Stmt\ClassMethod) {
                        $methodName = $stmt->name->toString();
                        
                        $visibility = '+'; 
                        if ($stmt->isPrivate()) {
                            $visibility = '-';
                        } elseif ($stmt->isProtected()) {
                            $visibility = '#';
                        }

                        // Simpan nama method
                        $this->umlData[$className]['methods'][] = $visibility . ' ' . $methodName . '()';

                        // Deteksi khusus di dalam Constructor
                        if ($methodName === '__construct') {
                            
                            // A. Tangkap Aggregation dari parameter (Dependency Injection)
                            foreach ($stmt->params as $param) {
                                if ($param->type instanceof Node\Name) {
                                    $this->umlData[$className]['relations']['aggregation'][] = $param->type->toString();
                                }
                            }

                            // B. Tangkap Composition dari inisiasi objek baru (keyword 'new') di dalam body constructor
                            if ($stmt->stmts) {
                                foreach ($stmt->stmts as $methodStmt) {
                                    // Mengecek pola: $this->sesuatu = new KelasLain();
                                    if ($methodStmt instanceof Node\Stmt\Expression && $methodStmt->expr instanceof Node\Expr\Assign) {
                                        if ($methodStmt->expr->expr instanceof Node\Expr\New_ && $methodStmt->expr->expr->class instanceof Node\Name) {
                                            $this->umlData[$className]['relations']['composition'][] = $methodStmt->expr->expr->class->toString();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }
    // Method untuk mengambil output akhir setelah proses parse selesai
    public function getUmlData(): array {
        return $this->umlData;
    }
}