<?php

namespace Tests\Feature;

use App\Livewire\Admin\Courses\CourseIndex;
use App\Livewire\Admin\Courses\CourseShow;
use App\Livewire\Learn\MaterialShow;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Material;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseFlowAndAdminCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_coursera_flow_requires_completing_materials_before_starting_tryout(): void
    {
        $user = User::factory()->create();
        $course = Category::factory()->create(['name' => 'Persiapan CPNS']);
        $subject = Subject::factory()->for($course)->create(['name' => 'Tes Intelegensia Umum']);
        $chapter = Chapter::factory()->for($subject)->create(['title' => 'Deret Angka']);
        $material = Material::factory()->free()->for($chapter)->create([
            'title' => 'Konsep Dasar Deret',
            'content' => '<p>Pelajari pola bilangan terlebih dahulu.</p>',
        ]);

        $package = ExamPackage::factory()->for($course)->create([
            'title' => 'Try Out Evaluasi CPNS',
            'is_free' => true,
        ]);
        $section = ExamSection::factory()->for($package, 'package')->create();
        $questions = Question::factory()->withOptions()->count(2)->create(['subject_id' => $subject->id]);
        $section->questions()->sync($questions->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());

        // Course player renders curriculum sidebar & final evaluation section
        $this->actingAs($user)
            ->get(route('learn.category', $course))
            ->assertOk()
            ->assertSee('Progres kursus')
            ->assertSee('Modul 1')
            ->assertSee('Konsep Dasar Deret')
            ->assertSee('Try Out Evaluasi CPNS');

        // Starting Try Out before completing course material is blocked
        $this->post(route('exam.start', $package))
            ->assertSessionHas('error');
        $this->assertSame(0, ExamAttempt::query()->count());

        // Completing material via completeAndAdvance advances straight to the Try Out stage
        Livewire::actingAs($user)
            ->test(MaterialShow::class, ['material' => $material])
            ->call('completeAndAdvance')
            ->assertRedirect(route('learn.category', ['category' => $course, 'tryout' => $package->slug]));

        // Now Try Out prerequisites are met and starting succeeds
        $this->actingAs($user)
            ->post(route('exam.start', $package))
            ->assertRedirect();
        $this->assertSame(1, ExamAttempt::query()->count());
    }

    public function test_admin_manages_modules_materials_questions_and_tryouts_grouped_by_course(): void
    {
        $admin = User::factory()->contentAdmin()->create();

        $courseA = Category::factory()->create(['name' => 'Kursus UTBK']);
        $subjectA = Subject::factory()->for($courseA)->create(['name' => 'Penalaran Umum']);
        $chapterA = Chapter::factory()->for($subjectA)->create(['title' => 'Logika']);
        Material::factory()->for($chapterA)->create(['title' => 'Materi Khusus UTBK']);
        Question::factory()->withOptions()->create(['subject_id' => $subjectA->id, 'body' => '<p>Soal Khusus UTBK</p>']);
        ExamPackage::factory()->for($courseA)->create(['title' => 'TO Khusus UTBK']);

        $courseB = Category::factory()->create(['name' => 'Kursus BUMN']);
        $subjectB = Subject::factory()->for($courseB)->create(['name' => 'AKHLAK']);
        $chapterB = Chapter::factory()->for($subjectB)->create(['title' => 'Core Values']);
        Material::factory()->for($chapterB)->create(['title' => 'Materi Khusus BUMN']);
        Question::factory()->withOptions()->create(['subject_id' => $subjectB->id, 'body' => '<p>Soal Khusus BUMN</p>']);
        ExamPackage::factory()->for($courseB)->create(['title' => 'TO Khusus BUMN']);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('Kursus UTBK')
            ->assertSee('Kursus BUMN');

        // Course A workspace isolates Course A content from Course B across all tabs
        $component = Livewire::actingAs($admin)
            ->test(CourseShow::class, ['category' => $courseA])
            ->assertSee('Materi Khusus UTBK')
            ->assertDontSee('Materi Khusus BUMN')
            ->call('setTab', 'soal')
            ->assertSee('Soal Khusus UTBK')
            ->assertDontSee('Soal Khusus BUMN')
            ->call('setTab', 'tryout')
            ->assertSee('TO Khusus UTBK')
            ->assertDontSee('TO Khusus BUMN');

        // Adding a new module inside Course A works cleanly
        $component->call('setTab', 'kurikulum')
            ->set('newSubject', 'Pengetahuan Kuantitatif')
            ->call('addSubject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', [
            'category_id' => $courseA->id,
            'name' => 'Pengetahuan Kuantitatif',
        ]);

        // Creating a new course from CourseIndex redirects straight to its CourseShow workspace
        Livewire::actingAs($admin)
            ->test(CourseIndex::class)
            ->call('openCreate')
            ->set('form.name', 'Kursus Kedinasan Baru')
            ->set('form.description', 'Persiapan STAN & STIS')
            ->call('createCourse')
            ->assertHasNoErrors()
            ->assertRedirect();
    }
}
