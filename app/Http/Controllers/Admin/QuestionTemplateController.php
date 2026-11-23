<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Questions\QuestionTemplate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionTemplateController extends Controller
{
    public function __invoke(QuestionTemplate $template): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'talentra-template').'.xlsx';
        $template->write($path);

        return response()
            ->download($path, 'template-import-soal-talentra.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
