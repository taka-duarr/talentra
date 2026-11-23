<?php

namespace App\Services\Questions;

use App\Models\Category;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes the import template: example rows, instructions, and the valid category/subject names.
 */
class QuestionTemplate
{
    public const HEADERS = [
        'kategori', 'mata_uji', 'topik', 'tipe', 'soal',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'kunci', 'pembahasan', 'kesulitan', 'sumber',
    ];

    public function write(string $path): void
    {
        $options = new Options;
        $options->setColumnWidth(14, 1, 3, 4, 11, 13);
        $options->setColumnWidth(26, 2, 6, 7, 8, 9, 10, 14);
        $options->setColumnWidth(60, 5, 12);

        $writer = new Writer($options);
        $writer->openToFile($path);

        $header = (new Style)->withFontBold(true)->withBackgroundColor('C8F2EA');

        $writer->getCurrentSheet()->setName('Soal');
        $writer->addRow(Row::fromValuesWithStyle(self::HEADERS, $header));
        foreach ($this->exampleRows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        $writer->addRow(Row::fromValuesWithStyle(['Kolom', 'Cara mengisi'], $header));
        foreach ($this->instructions() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Daftar mata uji');
        $writer->addRow(Row::fromValuesWithStyle(['kategori', 'mata_uji', 'topik yang sudah ada'], $header));
        foreach (Category::query()->ordered()->with('subjects.topics')->get() as $category) {
            foreach ($category->subjects as $subject) {
                $writer->addRow(Row::fromValues([$category->name, $subject->name, $subject->topics->pluck('name')->join(', ')]));
            }
        }

        $writer->close();
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function exampleRows(): array
    {
        return [
            ['CPNS', 'Tes Intelegensia Umum (TIU)', 'Deret Angka', 'pg', '1, 4, 9, 16, 25, ...', '30', '36', '35', '49', '32', 'B', 'Pola bilangan kuadrat: 1², 2², 3², 4², 5², jadi berikutnya 6² = 36.', 'mudah', 'Soal orisinal'],
            ['UTBK-SNBT', 'Penalaran Umum', 'Penalaran Analitik', 'pgk', 'Pilih semua bilangan prima di bawah ini.', '2', '9', '11', '15', '', 'A,C', '2 dan 11 hanya habis dibagi 1 dan dirinya sendiri.', 'sedang', ''],
            ['UTBK-SNBT', 'Penalaran Umum', 'Logika Proposisi', 'bs', 'Jika semua siswa rajin, maka nilai kelas naik. Nilai kelas tidak naik. Tentukan benar atau salah setiap pernyataan.', 'Ada siswa yang tidak rajin.', 'Semua siswa rajin.', '', '', '', 'B,S', 'Modus tollens: nilai tidak naik berarti tidak semua siswa rajin.', 'sedang', ''],
            ['UTBK-SNBT', 'Pengetahuan Kuantitatif', 'Bilangan', 'isian', 'Berapakah 25% dari 80?', '', '', '', '', '', '20', '0,25 × 80 = 20.', 'mudah', ''],
            ['CPNS', 'Tes Karakteristik Pribadi (TKP)', 'Pelayanan Publik', 'tkp', 'Rekan kerja meminta bantuan saat Anda sibuk. Sikap Anda ...', 'Membantu setelah pekerjaan mendesak selesai dan memberi tahu waktunya', 'Langsung membantu dan menunda pekerjaan sendiri', 'Menyarankan bertanya ke rekan lain', 'Membantu sebentar lalu kembali bekerja', 'Menolak karena sibuk', '5,3,2,4,1', 'Sikap terbaik tetap kooperatif tanpa mengorbankan tanggung jawab utama.', 'sedang', ''],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function instructions(): array
    {
        return [
            ['kategori', 'Wajib. Nama kategori persis seperti di sheet "Daftar mata uji", misalnya CPNS atau UTBK-SNBT.'],
            ['mata_uji', 'Wajib. Nama mata uji di kategori tersebut (lihat sheet "Daftar mata uji").'],
            ['topik', 'Opsional, tapi disarankan untuk analisis penguasaan. Topik yang belum ada akan dibuat otomatis.'],
            ['tipe', 'Wajib. pg = pilihan ganda, pgk = pilihan ganda kompleks, bs = benar/salah per pernyataan, isian = isian singkat, tkp = berbobot per opsi.'],
            ['soal', 'Wajib. Teks biasa (baris kosong = paragraf baru) atau HTML sederhana (<p>, <strong>, <table>, <img>).'],
            ['opsi_a – opsi_e', 'Isi opsi jawaban berurutan. Untuk tipe bs, setiap kolom opsi adalah satu pernyataan. Kosongkan untuk tipe isian. Kolom opsi_f boleh ditambahkan jika perlu.'],
            ['kunci', 'pg: satu huruf (B). pgk: huruf yang benar dipisah koma (A,C). bs: B atau S untuk setiap pernyataan (B,S,B). isian: jawaban benar, alternatif dipisah | (2,5|2.5). tkp: bobot 1–5 untuk setiap opsi (5,3,2,4,1).'],
            ['pembahasan', 'Opsional. Penjelasan jawaban yang tampil setelah ujian.'],
            ['kesulitan', 'Opsional. 1/mudah, 2/sedang, 3/sulit. Kosong = sedang.'],
            ['sumber', 'Opsional. Catat sumber soal untuk mencegah masalah hak cipta.'],
            ['', ''],
            ['Catatan', 'Maksimal '.QuestionImporter::MAX_ROWS.' soal per file. Soal yang sama persis dengan soal di bank soal akan dilewati. Hapus baris contoh sebelum mengunggah.'],
        ];
    }
}
