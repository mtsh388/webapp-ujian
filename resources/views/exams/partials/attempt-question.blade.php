            <div class="col-lg-8" id="examQuestionPanel">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="badge bg-label-primary">
                                    Soal {{ $currentNumber }}
                                </span>

                                <span class="text-muted ms-2">
                                    dari {{ $totalQuestions }}
                                </span>
                            </div>

                            <div class="text-muted small">
                                Terjawab:
                                <strong id="answeredCount">
                                    {{ $answers->whereNotNull('selected_option_id')->count() }}
                                </strong>
                                / {{ $totalQuestions }}
                            </div>
                        </div>

                        <div class="exam-question-content mb-4" id="examQuestionContent">
                            {!! \App\Support\QuestionHtml::sanitize($currentQuestion->question) !!}
                        </div>

                        <div class="d-flex flex-column gap-3">

                            @foreach ($currentQuestion->options as $option)
                                @php
                                    $isSelected =
                                        $currentAnswer &&
                                        (int) $currentAnswer->selected_option_id === (int) $option->id;
                                @endphp

                                <label
                                    class="question-option border rounded p-3 d-flex align-items-start gap-3 {{ $isSelected ? 'selected' : '' }}"
                                    data-option-wrapper="{{ $option->id }}">

                                    <input
                                        type="radio"
                                        class="form-check-input mt-1 answer-option"
                                        name="selected_option_id"
                                        value="{{ $option->id }}"
                                        data-question-id="{{ $currentQuestion->id }}"
                                        {{ $isSelected ? 'checked' : '' }}>

                                    <div class="flex-grow-1">

                                        <div class="fw-semibold mb-1">
                                            {{ $option->option_key }}
                                        </div>

                                        <div class="exam-option-content">
                                            {!! \App\Support\QuestionHtml::sanitize($option->option_text) !!}
                                        </div>

                                    </div>

                                </label>
                            @endforeach

                        </div>

                        <div
                            id="answerStatus"
                            class="mt-3 small text-muted">
                            @if ($currentAnswer && $currentAnswer->selected_option_id)
                                <i class="bx bx-check-circle text-success me-1"></i>
                                Jawaban tersimpan
                            @else
                                <i class="bx bx-info-circle me-1"></i>
                                Pilih salah satu jawaban
                            @endif
                        </div>

                    </div>
                </div>

                <div class="d-flex justify-content-between gap-2">

                    @if ($currentNumber > 1)
                        <a
                            href="{{ route('exams.attempt', [
                                'exam' => $exam->id,
                                'attempt' => $attempt->id,
                                'question' => $currentNumber - 1,
                            ]) }}"
                            class="btn btn-outline-secondary question-navigation-link">
                            <i class="bx bx-chevron-left me-1"></i>
                            Sebelumnya
                        </a>
                    @else
                        <div></div>
                    @endif

                    @if ($currentNumber < $totalQuestions)
                        <a
                            href="{{ route('exams.attempt', [
                                'exam' => $exam->id,
                                'attempt' => $attempt->id,
                                'question' => $currentNumber + 1,
                            ]) }}"
                            class="btn btn-primary question-navigation-link">
                            Selanjutnya
                            <i class="bx bx-chevron-right ms-1"></i>
                        </a>
                    @else
                        <button
                            type="button"
                            id="submitButton"
                            class="btn btn-success">
                            <i class="bx bx-check-circle me-1"></i>
                            Kumpulkan Ujian
                        </button>
                    @endif

                </div>

            </div>