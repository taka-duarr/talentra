<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Material;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Categories, subjects, chapters, topics and a few starter materials.
     */
    public function run(): void
    {
        $catalog = [
            ['TKA SD', '#ff7a6b', 'Tes Kemampuan Akademik jenjang SD.', ['Matematika', 'Bahasa Indonesia']],
            ['TKA SMP', '#ffc23d', 'Tes Kemampuan Akademik jenjang SMP.', ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris']],
            ['TKA SMA', '#5b8cff', 'Tes Kemampuan Akademik jenjang SMA.', ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris']],
            ['UTBK-SNBT', '#14b8a6', 'Seleksi Nasional Berdasarkan Tes untuk masuk PTN.', ['Penalaran Umum', 'Pengetahuan Kuantitatif', 'Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika']],
            ['CPNS', '#1b2f66', 'Seleksi Kompetensi Dasar calon ASN.', ['Tes Wawasan Kebangsaan (TWK)', 'Tes Intelegensia Umum (TIU)', 'Tes Karakteristik Pribadi (TKP)']],
            ['BUMN', '#a26bff', 'Rekrutmen Bersama BUMN.', ['Tes Kemampuan Dasar', 'Core Values AKHLAK', 'Wawasan Kebangsaan']],
            ['Kedinasan', '#0d9488', 'Seleksi sekolah kedinasan.', ['Tes Wawasan Kebangsaan', 'Tes Intelegensia Umum', 'Tes Karakteristik Pribadi']],
        ];

        foreach ($catalog as $index => [$name, $color, $description, $subjects]) {
            $category = Category::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'color' => $color,
                'description' => $description,
                'sort_order' => $index,
            ]);

            foreach ($subjects as $subjectIndex => $subjectName) {
                Subject::query()->updateOrCreate(
                    ['category_id' => $category->id, 'slug' => Str::slug($subjectName)],
                    ['name' => $subjectName, 'sort_order' => $subjectIndex],
                );
            }
        }

        $this->seedCpnsMaterials();
        $this->seedUtbkMaterials();
    }

    private function seedCpnsMaterials(): void
    {
        $tiu = $this->subject('cpns', 'tes-intelegensia-umum-tiu');
        $twk = $this->subject('cpns', 'tes-wawasan-kebangsaan-twk');
        $tkp = $this->subject('cpns', 'tes-karakteristik-pribadi-tkp');

        $numerik = $this->chapter($tiu, 'Kemampuan Numerik', 0);
        $verbal = $this->chapter($tiu, 'Kemampuan Verbal', 1);
        $pancasila = $this->chapter($twk, 'Pancasila dan UUD 1945', 0);
        $pelayanan = $this->chapter($tkp, 'Pelayanan Publik', 0);

        $this->topic($tiu, $numerik, 'Deret Angka');
        $this->topic($tiu, $numerik, 'Aritmetika Sosial');
        $this->topic($tiu, $verbal, 'Analogi');
        $this->topic($tiu, $verbal, 'Silogisme');
        $this->topic($twk, $pancasila, 'Pancasila');
        $this->topic($twk, $pancasila, 'UUD 1945');
        $this->topic($tkp, $pelayanan, 'Pelayanan Publik');
        $this->topic($tkp, $pelayanan, 'Profesionalisme');

        $this->material($numerik, 'Pola Bilangan dan Deret Angka', true, 0, <<<'HTML'
<p>Soal deret angka meminta kamu menemukan aturan yang menghubungkan bilangan-bilangan dalam satu barisan, lalu memakai aturan itu untuk menebak bilangan berikutnya.</p>
<h2>Langkah mengerjakan</h2>
<ol>
<li>Hitung selisih antar suku yang berdekatan.</li>
<li>Jika selisihnya tidak tetap, hitung selisih tingkat kedua atau cek apakah polanya perkalian.</li>
<li>Periksa kemungkinan deret bertingkat: suku ganjil dan suku genap mengikuti pola yang berbeda.</li>
</ol>
<h2>Contoh</h2>
<p>Deret 3, 5, 9, 15, 23, ... memiliki selisih 2, 4, 6, 8. Selisih berikutnya 10, jadi suku berikutnya adalah <strong>33</strong>.</p>
<blockquote>Tips: tulis selisihnya di bawah deret. Pola biasanya langsung terlihat setelah satu atau dua tingkat.</blockquote>
HTML);

        $this->material($numerik, 'Perbandingan dan Persentase', false, 1, <<<'HTML'
<p>Soal aritmetika sosial sering memakai persentase untung, rugi, diskon, dan bunga. Kuncinya adalah menentukan nilai awal (100%) dengan benar.</p>
<h2>Rumus penting</h2>
<ul>
<li>Untung = harga jual − harga beli</li>
<li>Persentase untung = untung ÷ harga beli × 100%</li>
<li>Harga setelah diskon d% = harga awal × (100 − d)%</li>
</ul>
HTML);

        $this->material($verbal, 'Silogisme: Menarik Kesimpulan', false, 0, <<<'HTML'
<p>Silogisme menguji kemampuan menarik kesimpulan yang pasti benar dari dua premis.</p>
<h2>Pola umum</h2>
<ul>
<li><strong>Semua A adalah B. Semua B adalah C.</strong> Kesimpulan: semua A adalah C.</li>
<li><strong>Semua A adalah B. Sebagian C adalah A.</strong> Kesimpulan: sebagian C adalah B.</li>
</ul>
<blockquote>Hindari kesimpulan yang "mungkin benar". Pilih hanya yang pasti benar berdasarkan premis.</blockquote>
HTML);

        $this->material($pancasila, 'Nilai-Nilai Pancasila', true, 0, <<<'HTML'
<p>Pancasila adalah dasar negara yang tercantum dalam Pembukaan UUD 1945 alinea keempat. Soal TWK biasanya menguji penerapan nilai setiap sila dalam kehidupan sehari-hari.</p>
<h2>Ringkasan sila</h2>
<ol>
<li>Ketuhanan Yang Maha Esa</li>
<li>Kemanusiaan yang adil dan beradab</li>
<li>Persatuan Indonesia</li>
<li>Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan</li>
<li>Keadilan sosial bagi seluruh rakyat Indonesia</li>
</ol>
HTML);

        $this->material($pelayanan, 'Sikap dalam Pelayanan Publik', true, 0, <<<'HTML'
<p>Soal TKP tidak memiliki jawaban salah. Setiap pilihan bernilai 1 sampai 5. Jawaban bernilai tertinggi biasanya menunjukkan sikap proaktif, ramah, dan mengutamakan kepentingan masyarakat.</p>
<blockquote>Pilih respons yang menyelesaikan masalah tanpa melanggar aturan dan tanpa melempar tanggung jawab.</blockquote>
HTML);
    }

    private function seedUtbkMaterials(): void
    {
        $pu = $this->subject('utbk-snbt', 'penalaran-umum');
        $pk = $this->subject('utbk-snbt', 'pengetahuan-kuantitatif');

        $logika = $this->chapter($pu, 'Penalaran Logis', 0);
        $aljabar = $this->chapter($pk, 'Aljabar dan Bilangan', 0);

        $this->topic($pu, $logika, 'Logika Proposisi');
        $this->topic($pu, $logika, 'Penalaran Analitik');
        $this->topic($pk, $aljabar, 'Persamaan Linear');
        $this->topic($pk, $aljabar, 'Bilangan');

        $this->material($logika, 'Implikasi dan Kontraposisi', true, 0, <<<'HTML'
<p>Pernyataan "jika P maka Q" bernilai sama dengan kontraposisinya, yaitu "jika bukan Q maka bukan P".</p>
<h2>Yang sering menjebak</h2>
<ul>
<li>Konvers ("jika Q maka P") <strong>tidak</strong> selalu benar.</li>
<li>Invers ("jika bukan P maka bukan Q") <strong>tidak</strong> selalu benar.</li>
</ul>
HTML);

        $this->material($aljabar, 'Sistem Persamaan Linear Dua Variabel', false, 0, <<<'HTML'
<p>SPLDV dapat diselesaikan dengan substitusi atau eliminasi. Untuk soal UTBK, eliminasi biasanya lebih cepat karena koefisiennya dirancang mudah disamakan.</p>
<h2>Contoh</h2>
<p>x + y = 10 dan x − y = 4. Jumlahkan kedua persamaan: 2x = 14, jadi x = 7 dan y = 3.</p>
HTML);
    }

    private function subject(string $categorySlug, string $subjectSlug): Subject
    {
        return Subject::query()
            ->whereHas('category', fn ($query) => $query->where('slug', $categorySlug))
            ->where('slug', $subjectSlug)
            ->firstOrFail();
    }

    private function chapter(Subject $subject, string $title, int $order): Chapter
    {
        return Chapter::query()->updateOrCreate(
            ['subject_id' => $subject->id, 'title' => $title],
            ['sort_order' => $order],
        );
    }

    private function topic(Subject $subject, Chapter $chapter, string $name): Topic
    {
        return Topic::query()->updateOrCreate(
            ['subject_id' => $subject->id, 'name' => $name],
            ['chapter_id' => $chapter->id],
        );
    }

    private function material(Chapter $chapter, string $title, bool $isFree, int $order, string $content): Material
    {
        return Material::query()->updateOrCreate(['slug' => Str::slug($title)], [
            'chapter_id' => $chapter->id,
            'title' => $title,
            'content' => $content,
            'is_free' => $isFree,
            'sort_order' => $order,
            'published_at' => now(),
        ]);
    }
}
