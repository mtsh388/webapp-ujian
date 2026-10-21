            <div class="col-lg-8" id="examQuestionPanel">

                <div class="card exam-panel-card mb-3">
                    <div class="card-body">

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
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
                                    {{ $answers->filter(fn ($answer) => !empty($answer->selected_option_ids) || $answer->selected_option_id !== null)->count() }}
                                </strong>
                                / {{ $totalQuestions }}
                            </div>
                        </div>

                        <div class="exam-question-content mb-4 py-2" id="examQuestionContent">
                            {!! \App\Support\QuestionHtml::sanitize($currentQuestion->question) !!}
                        </div>

                        @if ($currentQuestion->type === 'multiple_select')
                            <div class="alert alert-info py-2">Pilih semua jawaban yang benar. Jawaban dinilai benar jika seluruh pilihan tepat.</div>
                        @elseif ($currentQuestion->type === 'statement_true_false')
                            <div class="alert alert-info py-2">Pilih Benar atau Salah untuk setiap pernyataan. Skor diberikan sesuai jumlah pernyataan yang dijawab dengan tepat.</div>
                        @endif

                        <div class="d-flex flex-column gap-3">

                            @if ($currentQuestion->type === 'statement_true_false')
                                @php
                                    $statementGroups = $currentQuestion->options->groupBy(fn ($option) => preg_replace('/[TF]$/', '', (string) $option->option_key));
                                    $selectedOptionIds = $currentAnswer?->selected_option_ids ?? [];
                                    if (!is_array($selectedOptionIds)) $selectedOptionIds = [];
                                    if (empty($selectedOptionIds) && $currentAnswer?->selected_option_id) $selectedOptionIds = [(int) $currentAnswer->selected_option_id];
                                @endphp

                                @foreach ($statementGroups as $groupKey => $groupOptions)
                                    @php
                                        $trueOption = $groupOptions->firstWhere('option_key', $groupKey . 'T');
                                        $falseOption = $groupOptions->firstWhere('option_key', $groupKey . 'F');
                                        $isTrueSelected = $trueOption && in_array((int) $trueOption->id, array_map('intval', $selectedOptionIds), true);
                                        $isFalseSelected = $falseOption && in_array((int) $falseOption->id, array_map('intval', $selectedOptionIds), true);
                                    @endphp

                                    <div class="border rounded-3 p-3 p-md-4">
                                        <div class="fw-semibold mb-3">Pernyataan {{ preg_replace('/^S/', '', $groupKey) }}</div>
                                        <div class="exam-option-content mb-3">
                                            {!! \App\Support\QuestionHtml::sanitize($trueOption?->option_text ?? '') !!}
                                        </div>

                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach ([
                                                ['option' => $trueOption, 'label' => 'Benar', 'selected' => $isTrueSelected],
                                                ['option' => $falseOption, 'label' => 'Salah', 'selected' => $isFalseSelected],
                                            ] as $statementChoice)
                                                @if ($statementChoice['option'])
                                                    <label class="question-option border rounded px-3 py-2 d-flex align-items-center gap-2 {{ $statementChoice['selected'] ? 'selected' : '' }}" data-option-wrapper="{{ $statementChoice['option']->id }}" data-statement-group="{{ $groupKey }}">
                                                        <input
                                                            type="radio"
                                                            class="form-check-input answer-option"
                                                            name="statement-answer-{{ $currentQuestion->id }}-{{ $groupKey }}"
                                                            value="{{ $statementChoice['option']->id }}"
                                                            data-question-id="{{ $currentQuestion->id }}"
                                                            data-multiple="1"
                                                            data-statement="1"
                                                            data-statement-group="{{ $groupKey }}"
                                                            data-statement-count="{{ $statementGroups->count() }}"
                                                            {{ $statementChoice['selected'] ? 'checked' : '' }}>
                                                        <span>{{ $statementChoice['label'] }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @else
                            @foreach ($currentQuestion->options as $option)
                                @php
                                    $selectedOptionIds = $currentAnswer?->selected_option_ids ?? [];
                                    if (!is_array($selectedOptionIds)) $selectedOptionIds = [];
                                    if (empty($selectedOptionIds) && $currentAnswer?->selected_option_id) $selectedOptionIds = [(int) $currentAnswer->selected_option_id];
                                    $isSelected = in_array((int) $option->id, array_map('intval', $selectedOptionIds), true);
                                    $isMultiSelect = $currentQuestion->type === 'multiple_select';
                                @endphp

                                <label
                                    class="question-option border rounded p-3 d-flex align-items-start gap-3 {{ $isSelected ? 'selected' : '' }}"
                                    data-option-wrapper="{{ $option->id }}">

                                    <input
                                        type="{{ $isMultiSelect ? 'checkbox' : 'radio' }}"
                                        class="form-check-input mt-1 answer-option"
                                        name="{{ $isMultiSelect ? 'selected_option_ids[]' : 'selected_option_id' }}"
                                        value="{{ $option->id }}"
                                        data-question-id="{{ $currentQuestion->id }}"
                                        data-multiple="{{ $isMultiSelect ? '1' : '0' }}"
                                        {{ $isSelected ? 'checked' : '' }}>

                                    <div class="flex-grow-1">

                                        <div class="fw-semibold mb-1">
                                            {{ chr(64 + $loop->iteration) }}
                                        </div>

                                        <div class="exam-option-content">
                                            {!! \App\Support\QuestionHtml::sanitize($option->option_text) !!}
                                        </div>

                                    </div>

                                </label>
                            @endforeach
                            @endif

                        </div>

                        <div
                            id="answerStatus"
                            class="mt-3 small text-muted">
                            @if ($currentAnswer && (!empty($currentAnswer->selected_option_ids) || $currentAnswer->selected_option_id))
                                <i class="bx bx-check-circle text-success me-1"></i> Jawaban tersimpan
                            @else
                                <i class="bx bx-info-circle me-1"></i>
                                {{ $currentQuestion->type === 'multiple_select' ? 'Pilih satu atau beberapa jawaban' : ($currentQuestion->type === 'statement_true_false' ? 'Jawab setiap pernyataan' : 'Pilih salah satu jawaban') }}
                            @endif
                        </div>

                    </div>
                </div>

                <div class="d-flex justify-content-between gap-2 mt-3">

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