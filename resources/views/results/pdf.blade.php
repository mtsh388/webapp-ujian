<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Hasil Ujian - {{ $attempt->exam?->title ?? '-' }}
    </title>

    <style>
        @page {
            margin: 25px 30px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.5;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p {
            margin-top: 0;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
        }

        .header-subtitle {
            color: #666;
            margin-top: 4px;
        }

        .score-box {
            text-align: right;
        }

        .score-label {
            color: #666;
            font-size: 10px;
        }

        .score {
            font-size: 28px;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .status-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-danger {
            background: #f8d7da;
            color: #842029;
        }

        .status-warning {
            background: #fff3cd;
            color: #664d03;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .info-table td {
            border: 1px solid #ddd;
            padding: 7px 9px;
        }

        .info-label {
            width: 25%;
            background: #f5f5f5;
            color: #666;
        }

        .stat-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .stat-table td {
            width: 25%;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 17px;
            font-weight: bold;
        }

        .stat-label {
            color: #666;
            font-size: 9px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #ccc;
        }

        .question {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .question-header {
            background: #f5f5f5;
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        .question-number {
            font-weight: bold;
        }

        .question-status {
            float: right;
            font-weight: bold;
        }

        .correct {
            color: #198754;
        }

        .wrong {
            color: #dc3545;
        }

        .unanswered {
            color: #6c757d;
        }

        .question-body {
            padding: 10px;
        }

        .question-content img,
        .option-content img,
        .explanation-content img {
            max-width: 100%;
            height: auto;
        }

        .question-content table,
        .option-content table,
        .explanation-content table {
            border-collapse: collapse;
            border: 1px solid #666;
            max-width: 100%;
            width: 100%;
            margin: 8px 0;
            table-layout: auto;
        }

        .question-content th,
        .question-content td,
        .option-content th,
        .option-content td,
        .explanation-content th,
        .explanation-content td {
            border: 1px solid #666;
            padding: 5px 7px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .question-content th,
        .option-content th,
        .explanation-content th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .question-content tr,
        .option-content tr,
        .explanation-content tr {
            page-break-inside: avoid;
        }

        .option {
            border: 1px solid #ddd;
            padding: 7px 9px;
            margin-bottom: 6px;
        }

        .option-selected {
            border-width: 2px;
        }

        .option-correct {
            background: #edf8f1;
            border-color: #198754;
        }

        .option-wrong {
            background: #fdf0f1;
            border-color: #dc3545;
        }

        .option-key {
            font-weight: bold;
            display: inline-block;
            width: 25px;
            vertical-align: top;
        }

        .option-text {
            display: inline;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            border-radius: 3px;
            margin-left: 5px;
        }

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-danger {
            background: #f8d7da;
            color: #842029;
        }

        .explanation {
            margin-top: 10px;
            padding: 9px;
            background: #e7f1ff;
            border: 1px solid #b6d4fe;
            page-break-inside: avoid;
        }

        .explanation-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #084298;
        }

        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 9px;
            text-align: center;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

@php
    $showCorrectAnswers = (bool) ($attempt->exam?->show_correct_answers ?? false);
    $duration = (int) $attempt->duration_seconds;
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
@endphp

<div class="header">

    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">
                    Hasil Ujian
                </div>

                <div class="header-subtitle">
                    {{ $attempt->exam?->title ?? '-' }}
                </div>

                @if ($attempt->exam?->code)
                    <div class="header-subtitle">
                        Kode: {{ $attempt->exam->code }}
                    </div>
                @endif
            </td>

            <td class="score-box">
                <div class="score-label">
                    Nilai Akhir
                </div>

                <div class="score">
                    {{ number_format((float) $attempt->score, 2, ',', '.') }}
                </div>

                @if ($attempt->passed === true)

                    <span class="status status-success">
                            LULUS
                        </span>

                @elseif ($attempt->passed === false)

                    <span class="status status-danger">
                            TIDAK LULUS
                        </span>

                @else

                    <span class="status status-warning">
                            HASIL TERSEDIA
                        </span>

                @endif
            </td>
        </tr>
    </table>

</div>

<table class="info-table">

    <tr>
        <td class="info-label">
            Ujian
        </td>

        <td>
            {{ $attempt->exam?->title ?? '-' }}
        </td>

        <td class="info-label">
            Passing Grade
        </td>

        <td>
            {{ number_format((float) ($attempt->exam?->passing_score ?? 0), 2, ',', '.') }}
        </td>
    </tr>

    <tr>
        <td class="info-label">
            Percobaan
        </td>

        <td>
            Ke-{{ $attempt->attempt_number }}
        </td>

        <td class="info-label">
            Total Soal
        </td>

        <td>
            {{ $attempt->total_questions }}
        </td>
    </tr>

    <tr>
        <td class="info-label">
            Mulai
        </td>

        <td>
            {{ $attempt->started_at?->format('d M Y H:i:s') ?? '-' }}
        </td>

        <td class="info-label">
            Dikumpulkan
        </td>

        <td>
            {{ $attempt->submitted_at?->format('d M Y H:i:s') ?? '-' }}
        </td>
    </tr>

    <tr>
        <td class="info-label">
            Durasi
        </td>

        <td colspan="3">
            @if ($hours > 0)
                {{ $hours }} jam
            @endif

            {{ $minutes }} menit
            {{ $seconds }} detik
        </td>
    </tr>

</table>

<table class="stat-table">

    <tr>
        <td>
            <div class="stat-value correct">
                {{ $attempt->correct_answers }}
            </div>

            <div class="stat-label">
                BENAR
            </div>
        </td>

        <td>
            <div class="stat-value wrong">
                {{ $attempt->wrong_answers }}
            </div>

            <div class="stat-label">
                SALAH
            </div>
        </td>

        <td>
            <div class="stat-value unanswered">
                {{ $attempt->unanswered }}
            </div>

            <div class="stat-label">
                TIDAK DIJAWAB
            </div>
        </td>

        <td>
            <div class="stat-value">
                {{ number_format((float) $attempt->total_score, 2, ',', '.') }}
            </div>

            <div class="stat-label">
                TOTAL SKOR
            </div>
        </td>
    </tr>

</table>

<div class="section-title">
    Detail Jawaban
</div>

@foreach ($questions as $index => $question)

    @php
        $answer = $attempt->answers->firstWhere(
            'exam_question_id',
            $question->id
        );

        $selectedOptionId = $answer?->selected_option_id;

        $selectedOption = $answer?->selectedOption;

        $isAnswered = $selectedOptionId !== null;

        $isCorrect = $answer?->is_correct === true;

        $correctOption = $question->options->firstWhere(
            'is_correct',
            true
        );
    @endphp

    <div class="question">

        <div class="question-header">

                <span class="question-number">
                    Soal {{ $index + 1 }}
                </span>

            <span class="question-status">

                    @if (!$isAnswered)

                    <span class="unanswered">
                            Tidak Dijawab
                        </span>

                @elseif ($isCorrect)

                    <span class="correct">
                            Benar
                        </span>

                @else

                    <span class="wrong">
                            Salah
                        </span>

                @endif

                </span>

        </div>

        <div class="question-body">

            <div class="question-content">
                {!! \App\Support\QuestionHtml::sanitize($question->question) !!}
            </div>

            <div style="margin-top: 10px;">

                @foreach ($question->options as $option)

                    @php
                        $isSelected =
                            $selectedOptionId !== null &&
                            (int) $selectedOptionId === (int) $option->id;

                        $isCorrectOption = (bool) $option->is_correct;

                        $isWrongSelected =
                            $isSelected &&
                            !$isCorrectOption;
                    @endphp

                    <div
                        class="option
                            {{ $isSelected ? 'option-selected' : '' }}
                            {{ $showCorrectAnswers && $isCorrectOption ? 'option-correct' : '' }}
                            {{ $showCorrectAnswers && $isWrongSelected ? 'option-wrong' : '' }}">

                            <span class="option-key">
                                {{ $option->option_key }}.
                            </span>

                        <span class="option-text option-content">
                                {!! \App\Support\QuestionHtml::sanitize($option->option_text) !!}
                            </span>

                        @if ($isSelected)
                            <span class="badge badge-primary">
                                    Jawaban Anda
                                </span>
                        @endif

                        @if ($showCorrectAnswers && $isCorrectOption)
                            <span class="badge badge-success">
                                    Jawaban Benar
                                </span>
                        @endif

                    </div>

                @endforeach

            </div>

            @if (!$isAnswered)

                <div class="explanation">
                    <div class="explanation-title">
                        Status
                    </div>

                    Soal tidak dijawab.
                </div>

            @elseif ($isCorrect)

                <div class="explanation">
                    <div class="explanation-title">
                        Jawaban Benar
                    </div>

                    Jawaban Anda mendapatkan
                    {{ number_format((float) $question->score, 2, ',', '.') }}
                    poin.
                </div>

            @else

                <div class="explanation">

                    <div class="explanation-title">
                        Jawaban Salah
                    </div>

                    @if ($showCorrectAnswers && $correctOption)
                        Jawaban yang benar adalah
                        <strong>
                            {{ $correctOption->option_key }}
                        </strong>.
                    @endif

                </div>

            @endif

            @if ($question->explanation)

                <div class="explanation">

                    <div class="explanation-title">
                        Pembahasan
                    </div>

                    <div class="explanation-content">
                        {!! \App\Support\QuestionHtml::sanitize($question->explanation) !!}
                    </div>

                </div>

            @endif

        </div>

    </div>

@endforeach

<div class="footer">
    Dokumen hasil ujian ini dibuat secara otomatis oleh sistem.
</div>

</body>

</html>
