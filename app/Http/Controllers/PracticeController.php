<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\Exam\ExamService;
use App\Services\Exam\PracticeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PracticeController extends Controller
{
    public function __invoke(Request $request, Topic $topic, PracticeService $practice, ExamService $exams): RedirectResponse
    {
        if ($practice->availableQuestionCount($topic) < PracticeService::MINIMUM_QUESTIONS) {
            return back()->with('error', 'Soal latihan untuk topik '.$topic->name.' belum cukup.');
        }

        try {
            $attempt = $exams->start($request->user(), $practice->packageFor($topic));
        } catch (AuthorizationException) {
            return redirect()->route('plans.index')->with('error', 'Latihan per topik tersedia untuk pelanggan.');
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()->route('exam.show', $attempt);
    }
}
