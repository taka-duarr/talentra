<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\User;
use App\Services\Analysis\AnalysisDriver;
use App\Services\Analysis\NvidiaAnalysisDriver;
use App\Services\Analysis\RuleBasedAnalysisDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class NvidiaAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'integrate.api.nvidia.com/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        config([
            'talentra.analysis.driver' => 'nvidia',
            'talentra.analysis.nvidia.api_key' => 'nvapi-test',
            'talentra.analysis.nvidia.models' => ['primary/model', 'backup/model'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function analysis(): array
    {
        return [
            'summary' => 'Kamu memperoleh 36% dan belum lolos TIU.',
            'strengths' => ['Pancasila'],
            'weaknesses' => [['topic' => 'Deret Angka', 'reason' => '0 dari 3 soal benar.']],
            'recommendations' => [['topic' => 'Deret Angka', 'action' => 'Latih 10 soal pola deret per hari.']],
            'study_plan' => [['day' => 'Hari 1', 'task' => 'Pelajari pola deret aritmetika.']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completion(string $content, string $model = 'primary/model'): array
    {
        return [
            'model' => $model,
            'choices' => [['message' => ['role' => 'assistant', 'content' => $content], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 900, 'completion_tokens' => 700],
        ];
    }

    public function test_driver_returns_structured_analysis_from_the_primary_model(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->completion(json_encode($this->analysis())))]);

        $result = app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo SKD CPNS']);

        $this->assertSame('ai', $result->source);
        $this->assertSame('primary/model', $result->model);
        $this->assertSame('Deret Angka', $result->data['weaknesses'][0]['topic']);
        $this->assertSame(900, $result->inputTokens);
        $this->assertSame(700, $result->outputTokens);

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer nvapi-test')
            && $request['model'] === 'primary/model'
            && $request['response_format']['type'] === 'json_schema'
            && str_contains($request['messages'][1]['content'], 'Demo SKD CPNS'));
    }

    public function test_driver_falls_back_to_the_next_model(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['error' => 'overloaded'], 503)
            ->push(['error' => 'overloaded'], 503)
            ->push($this->completion(json_encode($this->analysis()), 'backup/model'))]);

        $result = app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo']);

        $this->assertSame('backup/model', $result->model);
        Http::assertSentCount(3);
    }

    public function test_driver_retries_a_temporary_error_on_the_same_model(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['error' => 'content safety screening is temporarily unavailable'], 503)
            ->push($this->completion(json_encode($this->analysis())))]);

        $result = app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo']);

        $this->assertSame('primary/model', $result->model);
        Http::assertSentCount(2);
    }

    public function test_placeholder_items_are_dropped_from_the_result(): void
    {
        $analysis = $this->analysis();
        $analysis['strengths'] = ['/** Topik dengan penguasaan tinggi **/', '  ', 'Pancasila (100%)'];
        $analysis['weaknesses'] = array_fill(0, 5, ['topic' => 'Deret Angka', 'reason' => 'Belum ada yang benar.']);

        Http::fake([self::ENDPOINT => Http::response($this->completion(json_encode($analysis)))]);

        $result = app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo']);

        $this->assertSame(['Pancasila (100%)'], $result->data['strengths']);
        $this->assertCount(3, $result->data['weaknesses']);
    }

    public function test_driver_reads_json_wrapped_in_reasoning_and_code_fences(): void
    {
        $content = "<think>menghitung…</think>\n```json\n".json_encode($this->analysis())."\n```";
        Http::fake([self::ENDPOINT => Http::response($this->completion($content))]);

        $result = app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo']);

        $this->assertSame('Pancasila', $result->data['strengths'][0]);
    }

    public function test_driver_throws_when_every_model_fails(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->completion('bukan json'))]);

        $this->expectException(RuntimeException::class);

        app(NvidiaAnalysisDriver::class)->analyze(['exam' => 'Demo']);
    }

    public function test_container_uses_nvidia_only_when_a_key_is_set(): void
    {
        $this->assertInstanceOf(NvidiaAnalysisDriver::class, app(AnalysisDriver::class));

        config(['talentra.analysis.nvidia.api_key' => null]);

        $this->assertInstanceOf(RuleBasedAnalysisDriver::class, app(AnalysisDriver::class));
    }

    public function test_submitted_exam_is_analysed_by_nvidia_and_falls_back_on_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push($this->completion(json_encode($this->analysis())))
            ->whenEmpty(Http::response(['error' => 'down'], 500))]);

        $user = User::factory()->create();
        $package = ExamPackage::factory()->for(Category::factory())->create(['is_free' => true, 'max_attempts' => 3]);
        $section = ExamSection::factory()->for($package, 'package')->create();
        $section->questions()->sync(Question::factory()->withOptions()->count(2)->create()->pluck('id'));

        $this->actingAs($user)->post(route('exam.start', $package));
        $first = ExamAttempt::query()->latest('id')->first();
        $this->post(route('exam.submit', $first));

        $this->assertSame('ai', $first->refresh()->analysis->source);
        $this->assertSame('primary/model', $first->analysis->model);

        $this->post(route('exam.start', $package));
        $second = ExamAttempt::query()->latest('id')->first();
        $this->post(route('exam.submit', $second));

        $this->assertSame('rules', $second->refresh()->analysis->source);
        $this->assertNotNull($second->analysis->error);
    }
}
