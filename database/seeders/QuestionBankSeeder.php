<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionBankSeeder extends Seeder
{
    /**
     * Original sample questions plus demo and paid try out packages for CPNS and UTBK.
     */
    public function run(): void
    {
        $twk = $this->questions('cpns', 'tes-wawasan-kebangsaan-twk', $this->twkQuestions());
        $tiu = $this->questions('cpns', 'tes-intelegensia-umum-tiu', $this->tiuQuestions());
        $tkp = $this->questions('cpns', 'tes-karakteristik-pribadi-tkp', $this->tkpQuestions());

        $cpns = Category::query()->where('slug', 'cpns')->firstOrFail();
        $cpnsScoring = ['score_correct' => 5, 'score_wrong' => 0, 'score_blank' => 0];

        $this->package($cpns, 'Demo SKD CPNS', 'Coba format SKD dengan 10 soal pilihan TWK, TIU, dan TKP.', 15, true, $cpnsScoring, [
            ['TWK', $twk->take(3), 0.433],
            ['TIU', $tiu->take(4), 0.457],
            ['TKP', $tkp->take(3), 0.738],
        ]);

        $this->package($cpns, 'Try Out SKD CPNS #1', 'Simulasi SKD lengkap dengan ambang batas per bagian.', 30, false, $cpnsScoring, [
            ['TWK', $twk, 0.433],
            ['TIU', $tiu, 0.457],
            ['TKP', $tkp, 0.738],
        ]);

        $pu = $this->questions('utbk-snbt', 'penalaran-umum', $this->penalaranUmumQuestions());
        $pk = $this->questions('utbk-snbt', 'pengetahuan-kuantitatif', $this->kuantitatifQuestions());

        $utbk = Category::query()->where('slug', 'utbk-snbt')->firstOrFail();
        $utbkScoring = ['score_correct' => 4, 'score_wrong' => 0, 'score_blank' => 0];

        $this->package($utbk, 'Demo UTBK-SNBT', 'Rasakan format soal Penalaran Umum dan Pengetahuan Kuantitatif.', 15, true, $utbkScoring, [
            ['Penalaran Umum', $pu->take(3), null],
            ['Pengetahuan Kuantitatif', $pk->take(3), null],
        ]);

        $this->package($utbk, 'Try Out UTBK-SNBT #1', 'Simulasi subtes Penalaran Umum dan Pengetahuan Kuantitatif.', 30, false, $utbkScoring, [
            ['Penalaran Umum', $pu, null],
            ['Pengetahuan Kuantitatif', $pk, null],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Question>
     */
    private function questions(string $categorySlug, string $subjectSlug, array $rows): Collection
    {
        $subject = Subject::query()
            ->whereHas('category', fn ($query) => $query->where('slug', $categorySlug))
            ->where('slug', $subjectSlug)
            ->firstOrFail();

        return collect($rows)->map(function (array $row) use ($subject) {
            $topic = Topic::query()->firstOrCreate(['subject_id' => $subject->id, 'name' => $row['topic']]);

            $question = Question::query()->updateOrCreate(
                ['subject_id' => $subject->id, 'body' => $row['body']],
                [
                    'topic_id' => $topic->id,
                    'type' => $row['type'] ?? QuestionType::SingleChoice,
                    'explanation' => $row['explanation'],
                    'answer_text' => $row['answer'] ?? null,
                    'difficulty' => $row['difficulty'] ?? 2,
                    'status' => ContentStatus::Published,
                    'source' => 'Talentra (soal orisinal)',
                ],
            );

            $question->options()->delete();

            foreach ($row['options'] ?? [] as $index => [$body, $value]) {
                $question->options()->create([
                    'body' => $body,
                    'is_correct' => $value === true,
                    'weight' => is_int($value) ? $value : 0,
                    'sort_order' => $index,
                ]);
            }

            return $question->load('options');
        });
    }

    /**
     * @param  array{score_correct: int, score_wrong: int, score_blank: int}  $scoring
     * @param  array<int, array{0: string, 1: Collection<int, Question>, 2: float|null}>  $sections
     */
    private function package(Category $category, string $title, string $description, int $minutes, bool $isFree, array $scoring, array $sections): void
    {
        $package = ExamPackage::query()->updateOrCreate(['slug' => Str::slug($title)], [
            'category_id' => $category->id,
            'title' => $title,
            'description' => $description,
            'duration_minutes' => $minutes,
            'is_free' => $isFree,
            'max_attempts' => $isFree ? 3 : null,
            'shuffle_questions' => ! $isFree,
            'shuffle_options' => false,
            'counts_for_ranking' => ! $isFree,
            ...$scoring,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $package->sections()->delete();

        foreach ($sections as $order => [$name, $questions, $passingRatio]) {
            $maxScore = $questions->sum(fn (Question $question) => $question->maxPoints($package));

            $section = $package->sections()->create([
                'name' => $name,
                'subject_id' => $questions->first()?->subject_id,
                'passing_score' => $passingRatio ? (int) ceil($maxScore * $passingRatio) : null,
                'sort_order' => $order,
            ]);

            $section->questions()->sync(
                $questions->values()->mapWithKeys(fn (Question $question, int $index) => [$question->id => ['sort_order' => $index]])->all()
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function twkQuestions(): array
    {
        return [
            [
                'topic' => 'Pancasila',
                'body' => '<p>Rumusan Pancasila sebagai dasar negara yang sah tercantum dalam ...</p>',
                'explanation' => '<p>Rumusan Pancasila yang sah dan berlaku tercantum dalam Pembukaan UUD 1945 alinea keempat.</p>',
                'options' => [['Pembukaan UUD 1945 alinea keempat', true], ['Piagam Jakarta', false], ['Pembukaan UUD 1945 alinea pertama', false], ['Batang tubuh UUD 1945 Pasal 1', false], ['Dekret Presiden 5 Juli 1959', false]],
            ],
            [
                'topic' => 'Pancasila',
                'body' => '<p>Warga sebuah kampung bergotong royong memperbaiki rumah ibadah agama lain yang rusak akibat banjir. Sikap tersebut paling mencerminkan pengamalan sila ...</p>',
                'explanation' => '<p>Menghormati dan membantu pemeluk agama lain adalah wujud toleransi beragama, yang merupakan pengamalan sila pertama.</p>',
                'options' => [['Pertama', true], ['Kedua', false], ['Ketiga', false], ['Keempat', false], ['Kelima', false]],
            ],
            [
                'topic' => 'UUD 1945',
                'body' => '<p>Menurut UUD 1945, kedaulatan berada di tangan rakyat dan dilaksanakan ...</p>',
                'explanation' => '<p>Pasal 1 ayat (2) UUD 1945 hasil amandemen: kedaulatan berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar.</p>',
                'options' => [['Menurut Undang-Undang Dasar', true], ['Sepenuhnya oleh MPR', false], ['Oleh Presiden', false], ['Oleh DPR dan DPD', false], ['Melalui partai politik', false]],
            ],
            [
                'topic' => 'UUD 1945',
                'body' => '<p>Lembaga negara yang berwenang menguji undang-undang terhadap UUD 1945 adalah ...</p>',
                'explanation' => '<p>Pengujian undang-undang terhadap UUD 1945 adalah kewenangan Mahkamah Konstitusi (Pasal 24C ayat 1).</p>',
                'options' => [['Mahkamah Konstitusi', true], ['Mahkamah Agung', false], ['Komisi Yudisial', false], ['MPR', false], ['DPR', false]],
            ],
            [
                'topic' => 'Pancasila',
                'body' => '<p>Dalam rapat RT, keputusan diambil melalui musyawarah meskipun memakan waktu lebih lama. Hal ini sesuai dengan nilai sila ...</p>',
                'explanation' => '<p>Musyawarah untuk mufakat adalah inti sila keempat.</p>',
                'options' => [['Keempat', true], ['Ketiga', false], ['Kelima', false], ['Kedua', false], ['Pertama', false]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tiuQuestions(): array
    {
        return [
            [
                'topic' => 'Deret Angka',
                'body' => '<p>3, 5, 9, 15, 23, ...</p>',
                'explanation' => '<p>Selisihnya 2, 4, 6, 8, sehingga selisih berikutnya 10. Jadi 23 + 10 = 33.</p>',
                'options' => [['33', true], ['31', false], ['35', false], ['29', false], ['37', false]],
            ],
            [
                'topic' => 'Deret Angka',
                'body' => '<p>2, 6, 18, 54, ...</p>',
                'explanation' => '<p>Setiap suku dikali 3. Jadi 54 × 3 = 162.</p>',
                'difficulty' => 1,
                'options' => [['162', true], ['108', false], ['148', false], ['216', false], ['72', false]],
            ],
            [
                'topic' => 'Deret Angka',
                'body' => '<p>4, 9, 7, 12, 10, 15, ...</p>',
                'explanation' => '<p>Polanya +5, −2 bergantian. Setelah 15 adalah −2, jadi 13.</p>',
                'difficulty' => 3,
                'options' => [['13', true], ['20', false], ['17', false], ['18', false], ['12', false]],
            ],
            [
                'topic' => 'Aritmetika Sosial',
                'body' => '<p>Sebuah tas dibeli seharga Rp200.000 lalu dijual dengan untung 15%. Harga jual tas tersebut adalah ...</p>',
                'explanation' => '<p>Untung = 15% × 200.000 = 30.000. Harga jual = 200.000 + 30.000 = Rp230.000.</p>',
                'options' => [['Rp230.000', true], ['Rp215.000', false], ['Rp250.000', false], ['Rp170.000', false], ['Rp235.000', false]],
            ],
            [
                'topic' => 'Aritmetika Sosial',
                'body' => '<p>Harga sebuah buku setelah diskon 20% adalah Rp48.000. Harga buku sebelum diskon adalah ...</p>',
                'explanation' => '<p>48.000 adalah 80% dari harga awal. Harga awal = 48.000 ÷ 0,8 = Rp60.000.</p>',
                'difficulty' => 3,
                'options' => [['Rp60.000', true], ['Rp57.600', false], ['Rp56.000', false], ['Rp58.000', false], ['Rp64.000', false]],
            ],
            [
                'topic' => 'Silogisme',
                'body' => '<p>Semua peserta pelatihan menerima sertifikat. Sebagian pegawai baru adalah peserta pelatihan. Kesimpulan yang pasti benar adalah ...</p>',
                'explanation' => '<p>Pegawai baru yang ikut pelatihan pasti menerima sertifikat, sehingga sebagian pegawai baru menerima sertifikat.</p>',
                'options' => [['Sebagian pegawai baru menerima sertifikat', true], ['Semua pegawai baru menerima sertifikat', false], ['Semua penerima sertifikat adalah pegawai baru', false], ['Tidak ada pegawai baru yang menerima sertifikat', false], ['Sebagian peserta pelatihan bukan pegawai baru', false]],
            ],
            [
                'topic' => 'Analogi',
                'body' => '<p>PENA : MENULIS = GUNTING : ...</p>',
                'explanation' => '<p>Hubungannya adalah alat dan fungsinya. Gunting berfungsi untuk memotong.</p>',
                'difficulty' => 1,
                'options' => [['Memotong', true], ['Kertas', false], ['Tajam', false], ['Menjahit', false], ['Besi', false]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tkpQuestions(): array
    {
        return [
            [
                'topic' => 'Pelayanan Publik',
                'type' => QuestionType::Weighted,
                'body' => '<p>Seorang warga datang mengurus dokumen 10 menit sebelum jam layanan berakhir dengan berkas yang belum lengkap. Sikap Anda ...</p>',
                'explanation' => '<p>Nilai tertinggi diberikan pada sikap yang tetap melayani, menjelaskan kekurangan berkas dengan jelas, dan memudahkan warga untuk kembali.</p>',
                'options' => [['Melayani, menjelaskan berkas yang kurang, dan memberi daftar tertulis agar warga bisa melengkapi esok hari', 5], ['Menjelaskan berkas yang kurang secara lisan', 4], ['Meminta warga datang lagi besok', 3], ['Menyarankan warga bertanya ke petugas lain', 2], ['Menolak karena jam layanan hampir habis', 1]],
            ],
            [
                'topic' => 'Profesionalisme',
                'type' => QuestionType::Weighted,
                'body' => '<p>Atasan memberi tugas mendesak, padahal Anda sedang mengerjakan laporan yang tenggatnya juga hari ini. Anda ...</p>',
                'explanation' => '<p>Sikap terbaik adalah mengomunikasikan prioritas kepada atasan dan mencari solusi agar kedua pekerjaan selesai.</p>',
                'options' => [['Menyampaikan kondisi ke atasan, menyepakati prioritas, lalu menyelesaikan keduanya', 5], ['Mengerjakan tugas atasan lebih dulu lalu lembur untuk laporan', 4], ['Meminta bantuan rekan untuk salah satu pekerjaan', 3], ['Menyelesaikan laporan lebih dulu tanpa memberi kabar', 2], ['Menolak tugas dari atasan', 1]],
            ],
            [
                'topic' => 'Pelayanan Publik',
                'type' => QuestionType::Weighted,
                'body' => '<p>Seorang warga marah-marah karena antrean lama. Respons Anda ...</p>',
                'explanation' => '<p>Tetap tenang, mendengarkan keluhan, dan memberi informasi yang jelas adalah sikap pelayanan yang paling baik.</p>',
                'options' => [['Mendengarkan dengan tenang, meminta maaf atas ketidaknyamanan, dan menjelaskan estimasi waktu', 5], ['Meminta warga bersabar karena semua orang juga antre', 3], ['Mempercepat layanan warga tersebut agar tidak ribut', 2], ['Meminta petugas keamanan menenangkan warga', 4], ['Mengabaikan karena sedang sibuk', 1]],
            ],
            [
                'topic' => 'Profesionalisme',
                'type' => QuestionType::Weighted,
                'body' => '<p>Anda menemukan kesalahan data dalam laporan yang sudah dikirim ke pimpinan. Anda ...</p>',
                'explanation' => '<p>Mengakui kesalahan dan segera mengirim perbaikan menunjukkan integritas dan tanggung jawab.</p>',
                'options' => [['Segera melapor ke pimpinan dan mengirim laporan yang sudah diperbaiki', 5], ['Memperbaiki laporan dan menunggu ditanya', 3], ['Menunggu apakah pimpinan menyadarinya', 1], ['Bertanya ke rekan apakah perlu dilaporkan', 2], ['Memperbaiki dan mengirim ulang tanpa penjelasan', 4]],
            ],
            [
                'topic' => 'Pelayanan Publik',
                'type' => QuestionType::Weighted,
                'body' => '<p>Kantor Anda menerapkan sistem layanan daring baru yang belum Anda kuasai. Anda ...</p>',
                'explanation' => '<p>Sikap adaptif dan proaktif belajar sistem baru mendapat nilai tertinggi.</p>',
                'options' => [['Mempelajarinya secara aktif dan membantu rekan yang kesulitan', 5], ['Mengikuti pelatihan yang diadakan kantor', 4], ['Belajar bila ada waktu luang', 3], ['Meminta rekan mengoperasikan sistem untuk saya', 2], ['Tetap memakai cara lama selama masih bisa', 1]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function penalaranUmumQuestions(): array
    {
        return [
            [
                'topic' => 'Logika Proposisi',
                'body' => '<p>Jika hari hujan, jalan di depan sekolah basah. Hari ini jalan di depan sekolah tidak basah. Kesimpulan yang tepat adalah ...</p>',
                'explanation' => '<p>Modus tollens: dari "jika P maka Q" dan "bukan Q", disimpulkan "bukan P". Jadi hari ini tidak hujan.</p>',
                'options' => [['Hari ini tidak hujan', true], ['Hari ini hujan', false], ['Jalan basah karena disiram', false], ['Mungkin hari ini hujan', false], ['Tidak dapat disimpulkan', false]],
            ],
            [
                'topic' => 'Logika Proposisi',
                'type' => QuestionType::TrueFalse,
                'body' => '<p>Semua siswa kelas XII yang mengikuti bimbingan belajar lulus tes simulasi. Rina adalah siswa kelas XII yang lulus tes simulasi. Tentukan benar atau salah untuk setiap pernyataan berikut.</p>',
                'explanation' => '<p>Lulus tes tidak menjamin Rina ikut bimbingan (konvers belum tentu benar). Satu-satunya yang pasti: jika ada siswa XII ikut bimbingan, ia lulus.</p>',
                'options' => [['Rina pasti mengikuti bimbingan belajar.', false], ['Siswa kelas XII yang ikut bimbingan belajar pasti lulus tes simulasi.', true], ['Siswa yang tidak lulus tes simulasi pasti tidak ikut bimbingan belajar.', true]],
            ],
            [
                'topic' => 'Penalaran Analitik',
                'body' => '<p>Lima siswa (A, B, C, D, E) duduk berjajar dari kiri ke kanan. E duduk paling kiri dan D paling kanan. B duduk tepat di sebelah kanan C, dan A tidak duduk bersebelahan dengan E. Siapa yang duduk di tengah?</p>',
                'explanation' => '<p>Susunan: E _ _ _ D. Kursi 2–4 diisi A, C, B dengan C-B berdampingan. Jika A di kursi 2, A bersebelahan dengan E (tidak boleh). Jadi susunannya E, C, B, A, D dan yang duduk di tengah adalah B.</p>',
                'difficulty' => 3,
                'options' => [['B', true], ['A', false], ['C', false], ['D', false], ['E', false]],
            ],
            [
                'topic' => 'Penalaran Analitik',
                'type' => QuestionType::MultipleChoice,
                'body' => '<p>Dalam sebuah survei, setiap responden menyukai teh atau kopi atau keduanya. 30 orang suka teh, 25 orang suka kopi, dan 10 orang suka keduanya. Pilih <strong>semua</strong> pernyataan yang benar.</p>',
                'explanation' => '<p>Total responden = 30 + 25 − 10 = 45. Yang hanya suka teh = 20, yang hanya suka kopi = 15.</p>',
                'options' => [['Jumlah responden 45 orang', true], ['Yang hanya suka teh ada 20 orang', true], ['Yang hanya suka kopi ada 25 orang', false], ['Jumlah responden 55 orang', false]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kuantitatifQuestions(): array
    {
        return [
            [
                'topic' => 'Persamaan Linear',
                'body' => '<p>Jika x + y = 10 dan x − y = 4, maka nilai x × y adalah ...</p>',
                'explanation' => '<p>Jumlahkan kedua persamaan: 2x = 14, x = 7, maka y = 3. Jadi x × y = 21.</p>',
                'options' => [['21', true], ['24', false], ['18', false], ['28', false], ['14', false]],
            ],
            [
                'topic' => 'Bilangan',
                'type' => QuestionType::ShortAnswer,
                'body' => '<p>Berapakah hasil dari 15% dari 240? Tulis jawaban dalam bentuk angka.</p>',
                'explanation' => '<p>15% × 240 = 0,15 × 240 = 36.</p>',
                'answer' => '36',
                'difficulty' => 1,
            ],
            [
                'topic' => 'Bilangan',
                'body' => '<p>Rata-rata lima bilangan adalah 12. Jika satu bilangan dikeluarkan, rata-ratanya menjadi 11. Bilangan yang dikeluarkan adalah ...</p>',
                'explanation' => '<p>Jumlah lima bilangan = 60. Jumlah empat bilangan = 44. Bilangan yang dikeluarkan = 60 − 44 = 16.</p>',
                'options' => [['16', true], ['12', false], ['14', false], ['11', false], ['15', false]],
            ],
            [
                'topic' => 'Persamaan Linear',
                'body' => '<p>Harga 2 buku dan 3 pensil Rp21.000, sedangkan harga 1 buku dan 2 pensil Rp12.000. Harga 1 buku adalah ...</p>',
                'explanation' => '<p>Kalikan persamaan kedua dengan 2: 2b + 4p = 24.000. Kurangkan persamaan pertama: p = 3.000. Maka b = 12.000 − 6.000 = Rp6.000.</p>',
                'difficulty' => 2,
                'options' => [['Rp6.000', true], ['Rp3.000', false], ['Rp4.500', false], ['Rp7.500', false], ['Rp5.000', false]],
            ],
        ];
    }
}
