# Moodle Analyzer - UML Class Diagram Reconstruction

Repositori ini berisi instrumen Analisis Kode Statis (*Static Code Analysis*) berbasis *Abstract Syntax Tree* (AST) untuk merekonstruksi Diagram Kelas UML dari kode sumber perangkat lunak Learning Management System (LMS) Moodle. 

Proyek ini secara khusus mengekstraksi metrik arsitektur dan mengeliminasi *architectural noise* (polusi visual) pada tiga modul utama Moodle:
- Modul Aktivitas Pembelajaran (`Assign`)
- Modul Manajemen Kursus (`Course`)
- Modul Manajemen Pengguna (`User`)

## Struktur Repositori Inti

Instrumen ekstraksi dalam repositori ini dibangun menggunakan pustaka `nikic/php-parser` dengan alur kerja yang terbagi dalam tiga skrip utama:
- **`Main.php`**: Skrip eksekusi utama yang memicu proses *parsing* direktori kode sumber Moodle.
- **`MoodleUmlVisitor.php`**: Implementasi *Visitor Pattern* yang bertugas menelusuri setiap *node* AST untuk mengekstraksi deklarasi kelas, antarmuka, atribut, metode, dan injeksi dependensi.
- **`PlantUmlBuilder.php`**: Modul perakit data arsitektur menjadi skrip PlantUML, yang di dalamnya terintegrasi algoritma *filtering* dan *blacklisting* (seperti pengabaian *local dependency*, antarmuka generik Moodle, dan kelas utilitas `stdClass`) untuk menjaga kebersihan visual abstraksi.

## Skrip Hasil Rekonstruksi (*PlantUML*)

Skrip berformat `.puml` hasil pemrosesan otomatis dari alat ini dibagi menjadi dua jenis untuk keperluan perbandingan, dan dapat diakses langsung pada *root* direktori:

**1. Versi Mentah (*Unfiltered*)**
Merupakan hasil ekstraksi murni tanpa melalui proses *blacklisting*, sehingga masih mempertahankan seluruh antarmuka generik bawaan sistem (yang memicu *spaghetti diagram*). Nama filenya dibiarkan standar tanpa awalan khusus:
- `rekonstruksi_modul_assign.puml`
- `rekonstruksi_modul_course.puml`
- `rekonstruksi_modul_user.puml`

**2. Versi Bersih (*Filtered*)**
Merupakan hasil akhir yang sudah melewati algoritma *blacklisting* untuk memotong polusi visual dari kelas utilitas dan antarmuka utama. File ini secara khusus ditandai dengan adanya awalan **`hasil_`** pada nama filenya:
- `hasil_rekonstruksi_modul_assign.puml`
- `hasil_rekonstruksi_modul_course.puml`
- `hasil_rekonstruksi_modul_user.puml`

## Visualisasi Diagram

Untuk melihat visualisasi penuh dari diagram kelas UML yang telah berhasil di-render menjadi gambar beresolusi tinggi (format SVG), silakan kunjungi direktori **`diagram/`** di dalam repositori ini. Di sana terdapat cetak biru arsitektur aktual dari masing-masing modul Moodle yang sudah difilter dari polusi visual.