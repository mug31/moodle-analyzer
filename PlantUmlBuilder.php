<?php

class PlantUmlBuilder {
    private array $umlData;

    public function __construct(array $umlData) {
        $this->umlData = $umlData;
    }

    public function build(): string {
        $output = "@startuml\n";
        $output .= "skinparam classAttributeIconSize 0\n\n";

        // 1. Definisikan Kelas, Interface, Properti, dan Method
        foreach ($this->umlData as $name => $data) {
            $type = $data['type']; // 'class' atau 'interface'
            $output .= "{$type} {$name} {\n";
            
            foreach ($data['properties'] as $prop) {
                $output .= "  {$prop}\n";
            }
            foreach ($data['methods'] as $method) {
                $output .= "  {$method}\n";
            }
            
            $output .= "}\n\n";
        }

        // 2. Definisikan Arah dan Simbol Relasi
        foreach ($this->umlData as $name => $data) {
            $rels = $data['relations'];
            
            if ($rels['inheritance']) {
                $output .= "{$rels['inheritance']} <|-- {$name}\n";
            }
            foreach ($rels['realization'] as $target) {
                $output .= "{$target} <|.. {$name}\n";
            }
            foreach ($rels['composition'] as $target) {
                $output .= "{$name} *-- {$target}\n";
            }
            foreach ($rels['aggregation'] as $target) {
                $output .= "{$name} o-- {$target}\n";
            }
            
            // Filter association agar tidak duplikat dengan komposisi/agregasi
            $strongRels = array_merge($rels['composition'], $rels['aggregation']);
            $filteredAssoc = array_diff($rels['association'], $strongRels);
            foreach ($filteredAssoc as $target) {
                $output .= "{$name} --> {$target}\n";
            }
            
            foreach ($rels['dependency'] as $target) {
                $output .= "{$name} ..> {$target}\n";
            }
        }

        $output .= "@enduml\n";
        return $output;
    }
}