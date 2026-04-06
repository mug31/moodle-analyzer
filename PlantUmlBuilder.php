<?php

class PlantUmlBuilder {
    private array $umlData;
    // Daftar hitam kelas bawaan sistem atau kelas umum yang memicu polusi visual
    private array $blacklist = ['stdClass', 'Exception', 'Throwable'];

    public function __construct(array $umlData) {
        $this->umlData = $umlData;
    }

    public function build(): string {
        $output = "@startuml\n";
        $output .= "skinparam classAttributeIconSize 0\n\n";

        // 1. Definisikan Kelas, Interface, Properti, dan Method
        foreach ($this->umlData as $name => $data) {
            // Lewati pembuatan blok kelas jika namanya ada di daftar hitam
            if (in_array($name, $this->blacklist)) {
                continue;
            }

            $type = $data['type']; 
            $output .= "{$type} {$name} {\n";
            
            foreach ($data['properties'] as $prop) {
                $output .= "  {$prop}\n";
            }
            foreach ($data['methods'] as $method) {
                $output .= "  {$method}\n";
            }
            
            $output .= "}\n\n";
        }

        // 2. Definisikan Arah dan Simbol Relasi Utama Saja
        foreach ($this->umlData as $name => $data) {
            // Abaikan penarikan garis dari kelas yang di-blacklist
            if (in_array($name, $this->blacklist)) {
                continue;
            }

            $rels = $data['relations'];
            
            // Render Inheritance
            if ($rels['inheritance'] && !in_array($rels['inheritance'], $this->blacklist)) {
                $output .= "{$rels['inheritance']} <|-- {$name}\n";
            }
            
            // Render Realization
            foreach ($rels['realization'] as $target) {
                if (!in_array($target, $this->blacklist)) {
                    $output .= "{$target} <|.. {$name}\n";
                }
            }
            
            // Render Composition
            foreach ($rels['composition'] as $target) {
                if (!in_array($target, $this->blacklist)) {
                    $output .= "{$name} *-- {$target}\n";
                }
            }
            
            // Render Aggregation
            foreach ($rels['aggregation'] as $target) {
                if (!in_array($target, $this->blacklist)) {
                    $output .= "{$name} o-- {$target}\n";
                }
            }
            
            // Render Association (dengan filter anti-duplikasi)
            $strongRels = array_merge($rels['composition'], $rels['aggregation']);
            $filteredAssoc = array_diff($rels['association'], $strongRels);
            foreach ($filteredAssoc as $target) {
                if (!in_array($target, $this->blacklist)) {
                    $output .= "{$name} --> {$target}\n";
                }
            }
            
            // Relasi Dependency sengaja tidak dirender untuk mencegah Spaghetti Diagram
        }

        $output .= "@enduml\n";
        return $output;
    }
}