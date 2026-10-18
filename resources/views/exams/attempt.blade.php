@extends('template.app')

@section('title', 'Kerjakan Ujian')

@section('content')
    <style>
        .exam-timer {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .exam-timer-warning {
            animation: timerPulse 1s infinite;
        }

        @keyframes timerPulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.45;
            }

            100% {
                opacity: 1;
            }
        }

        .question-option {
            transition: all 0.15s ease;
        }

        .question-option:hover {
            background-color: rgba(105, 108, 255, 0.05);
        }

        .question-option.selected {
            background-color: rgba(105, 108, 255, 0.08);
            border-color: rgba(105, 108, 255, 0.5) !important;
        }

        .question-number {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .question-number.current {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .question-number.answered {
            background-color: var(--bs-success);
            color: #fff;
        }

        .question-number.current.answered {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .exam-question-content img {
            max-width: 100%;
            height: auto;
        }

        .exam-question-content table {
            max-width: 100%;
        }

        .exam-option-content img {
            max-width: 100%;
            height: auto;
        }

        .exam-navigation-loading {
            pointer-events: none;
            opacity: 0.65;
        }
    </style>

    <div class="container-fluid py-3">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    {{ $exam->title }}
                </h4>

                <div class="text-muted">
                    {{ $exam->code }}
                    @if ($exam->subject)
                        <span class="mx-1">•</span>
                        {{ $exam->subject->name }}
                    @endif
                </div>
            </div>

            @if ($exam->is_timed)
            <div class="card shadow-sm border-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-time-five fs-4 text-primary"></i>

                        <div>
                            <div class="small text-muted">
                                Sisa Waktu
                            </div>

                            <div
                                id="examTimer"
                                class="exam-timer text-primary">
                                {{ gmdate('H:i:s', max(0, $remainingSeconds)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4" role="status">
            <div>
                <strong>Mode ujian terpantau</strong>
                <div class="small">Perpindahan tab dan keluar dari fullscreen dicatat untuk ditinjau. Peringatan tidak otomatis menggugurkan peserta.</div>
                <div id="proctorWarningStatus" class="small mt-1">Belum ada peringatan pada sesi ini.</div>
            </div>
            <button type="button" id="enterFullscreenButton" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-fullscreen me-1"></i> Masuk Fullscreen
            </button>
        </div>

        <div class="row g-4">

            @include('exams.partials.attempt-question')

            <div class="col-lg-4">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            Navigasi Soal
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="d-flex flex-wrap gap-2">

                            @foreach ($questions as $index => $question)
                                @php
                                    $number = $index + 1;
                                    $questionAnswer = $answers->get($question->id);
                                    $isAnswered =
                                        $questionAnswer &&
                                        $questionAnswer->selected_option_id;
                                    $isCurrent = $number === $currentNumber;
                                @endphp

                                <a
                                    href="{{ route('exams.attempt', [
                                        'exam' => $exam->id,
                                        'attempt' => $attempt->id,
                                        'question' => $number,
                                    ]) }}"
                                    data-question-id="{{ $question->id }}" class="question-number rounded-circle text-decoration-none question-navigation-link {{ $isCurrent ? 'current' : ($isAnswered ? 'answered' : 'bg-light text-dark') }}">
                                    {{ $number }}
                                </a>
                            @endforeach

                        </div>

                        <hr>

                        <div class="d-flex flex-column gap-2 small">

                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="rounded-circle bg-primary"
                                    style="width: 14px; height: 14px;">
                                </span>

                                <span>
                                    Soal saat ini
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="rounded-circle bg-success"
                                    style="width: 14px; height: 14px;">
                                </span>

                                <span>
                                    Sudah dijawab
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="rounded-circle bg-light border"
                                    style="width: 14px; height: 14px;">
                                </span>

                                <span>
                                    Belum dijawab
                                </span>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body">

                        <div class="d-flex align-items-start gap-3">
                            <i class="bx bx-info-circle fs-3 text-primary"></i>

                            <div>
                                <h6 class="mb-1">
                                    Petunjuk
                                </h6>

                                <ul class="mb-0 ps-3 small text-muted">
                                    <li>
                                        Pilih satu jawaban yang paling tepat.
                                    </li>

                                    <li>
                                        Jawaban tersimpan otomatis.
                                    </li>

                                    <li>
                                        Gunakan navigasi untuk berpindah soal.
                                    </li>

                                    <li>
                                        Ujian akan dikumpulkan otomatis ketika waktu habis.
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const timerElement = document.getElementById('examTimer');
            let submitButton = document.getElementById('submitButton');
            let answeredCountElement = document.getElementById('answeredCount');
            let answerStatusElement = document.getElementById('answerStatus');

            const answerUrl = {{ Illuminate\Support\Js::from(route('exams.answer', [
                'exam' => $exam->id,
                'attempt' => $attempt->id,
            ])) }};

            const submitUrl = {{ Illuminate\Support\Js::from(route('exams.submit', [
                'exam' => $exam->id,
                'attempt' => $attempt->id,
            ])) }};

            const csrfToken = {{ Illuminate\Support\Js::from(csrf_token()) }};

            let remainingSeconds = {{ max(0, (int) $remainingSeconds) }};
            let isSubmitting = false;
            let isNavigating = false;
            let shouldPushHistory = true;
            let saveAnswerPromise = null;

            const violationUrl = {{ Illuminate\Support\Js::from(route('exams.violations.store', [
                'exam' => $exam->id,
                'attempt' => $attempt->id,
            ])) }};
            const fullscreenButton = document.getElementById('enterFullscreenButton');
            const proctorWarningStatus = document.getElementById('proctorWarningStatus');
            let lastViolationAt = {};
            let fullscreenPrompted = false;

            async function recordViolation(eventType, metadata = {}) {
                const now = Date.now();
                if (lastViolationAt[eventType] && now - lastViolationAt[eventType] < 1500) {
                    return;
                }
                lastViolationAt[eventType] = now;

                try {
                    const response = await fetch(violationUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            event_type: eventType,
                            metadata: metadata
                        })
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        return;
                    }

                    if (proctorWarningStatus) {
                        proctorWarningStatus.textContent =
                            'Aktivitas tercatat: ' + data.event_count + ' kali pada sesi ini.';
                    }

                    if (data.warning) {
                        await Swal.fire({
                            icon: data.warning.level >= 3 ? 'warning' : 'info',
                            title: data.warning.title,
                            text: data.warning.message,
                            confirmButtonText: 'Saya Mengerti',
                            allowOutsideClick: true,
                            didOpen: () => {
                                const confirmButton = Swal.getConfirmButton();

                                if (confirmButton) {
                                    confirmButton.addEventListener('click', function () {
                                        if (
                                            !document.fullscreenElement &&
                                            document.documentElement.requestFullscreen
                                        ) {
                                            document.documentElement.requestFullscreen().catch(() => {});
                                        }
                                    }, { once: true });
                                }
                            }
                        });
                    }
                } catch (error) {
                    if (proctorWarningStatus) {
                        proctorWarningStatus.textContent =
                            'Status pencatatan aktivitas belum dapat dikonfirmasi.';
                    }
                }
            }

            if (fullscreenButton) {
                fullscreenButton.addEventListener('click', async function () {
                    try {
                        if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                            await document.documentElement.requestFullscreen();
                        } else if (!document.fullscreenElement) {
                            await Swal.fire({
                                icon: 'info',
                                title: 'Fullscreen tidak didukung',
                                text: 'Browser ini tidak mendukung fullscreen. Anda tetap dapat melanjutkan ujian.',
                                confirmButtonText: 'Mengerti'
                            });
                        }
                    } catch (error) {
                        await Swal.fire({
                            icon: 'info',
                            title: 'Fullscreen tidak tersedia',
                            text: 'Browser menolak permintaan fullscreen. Ujian tetap dapat dilanjutkan.',
                            confirmButtonText: 'Mengerti'
                        });
                    }
                });
            }

            document.addEventListener('visibilitychange', function () {
                if (document.hidden && !isSubmitting) {
                    recordViolation('tab_hidden', {
                        visibility_state: document.visibilityState
                    });
                }
            });

            document.addEventListener('fullscreenchange', function () {
                if (!document.fullscreenElement && !isSubmitting && remainingSeconds > 0) {
                    recordViolation('fullscreen_exit', {
                        fullscreen_supported: Boolean(document.documentElement.requestFullscreen)
                    });
                    if (!fullscreenPrompted) {
                        fullscreenPrompted = true;
                        if (proctorWarningStatus) {
                            proctorWarningStatus.textContent =
                                'Anda keluar dari fullscreen. Anda dapat masuk kembali menggunakan tombol di atas.';
                        }
                    }
                }
                if (document.fullscreenElement) {
                    fullscreenPrompted = false;
                }
            });



            function formatTime(seconds) {
                seconds = Math.max(0, Number(seconds));

                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;

                return [hours, minutes, secs]
                    .map(value => String(value).padStart(2, '0'))
                    .join(':');
            }

            function updateTimer() {
                if (!timerElement) {
                    return;
                }

                timerElement.textContent = formatTime(
                    remainingSeconds
                );

                if (remainingSeconds <= 60) {
                    timerElement.classList.add(
                        'exam-timer-warning'
                    );
                    timerElement.classList.remove(
                        'text-primary'
                    );
                    timerElement.classList.add(
                        'text-danger'
                    );
                }

                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = '00:00:00';
                    handleTimeExpired();
                    return;
                }

                remainingSeconds--;
            }

            async function handleTimeExpired() {
                if (isSubmitting) {
                    return;
                }

                isSubmitting = true;

                if (submitButton) {
                    submitButton.disabled = true;
                }

                document
                    .querySelectorAll(
                        '.question-navigation-link'
                    )
                    .forEach(link => {
                        link.classList.add(
                            'exam-navigation-loading'
                        );
                    });

                await Swal.fire({
                    icon: 'warning',
                    title: 'Waktu Habis',
                    text: 'Waktu pengerjaan ujian telah habis. Jawaban Anda akan dikumpulkan secara otomatis.',
                    confirmButtonText: 'Kumpulkan Ujian',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });

                await submitExam(true);
            }

            async function saveAnswer(
                questionId,
                selectedOptionId
            ) {
                saveAnswerPromise = (async () => {

                    answerStatusElement.innerHTML =
                        '<i class="bx bx-loader-alt bx-spin me-1"></i> Menyimpan jawaban...';

                    try {
                        const response = await fetch(
                            answerUrl,
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    exam_question_id: questionId,
                                    selected_option_id: selectedOptionId
                                })
                            }
                        );

                        let data = {};

                        try {
                            data = await response.json();
                        } catch (error) {
                            data = {};
                        }

                        if (!response.ok || !data.success) {
                            throw new Error(
                                data.message ||
                                'Jawaban gagal disimpan.'
                            );
                        }

                        answerStatusElement.innerHTML =
                            '<i class="bx bx-check-circle text-success me-1"></i> Jawaban tersimpan';

                        updateAnsweredCount();

                        return data;

                    } catch (error) {

                        answerStatusElement.innerHTML =
                            '<i class="bx bx-error-circle text-danger me-1"></i> Gagal menyimpan jawaban';

                        await Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: error.message,
                            confirmButtonText: 'OK'
                        });

                        throw error;

                    } finally {
                        saveAnswerPromise = null;
                    }

                })();

                return saveAnswerPromise;
            }

            function updateAnsweredCount() {
                if (!answeredCountElement) {
                    return;
                }

                const answeredQuestions =
                    document.querySelectorAll(
                        '.question-number.answered'
                    ).length;

                const currentQuestionNumber =
                    document.querySelector(
                        '.question-number.current'
                    );

                let count = answeredQuestions;

                if (
                    currentQuestionNumber &&
                    currentQuestionNumber.classList.contains(
                        'answered'
                    )
                ) {
                    count = answeredQuestions;
                }

                const currentSelected =
                    document.querySelector(
                        '.answer-option:checked'
                    );

                if (
                    currentSelected &&
                    currentQuestionNumber &&
                    !currentQuestionNumber.classList.contains(
                        'answered'
                    )
                ) {
                    count++;
                }

                answeredCountElement.textContent = count;
            }

            function markCurrentQuestionAnswered() {
                const currentNumberElement =
                    document.querySelector(
                        '.question-number.current'
                    );

                if (!currentNumberElement) {
                    return;
                }

                currentNumberElement.classList.add(
                    'answered'
                );
            }

            document.addEventListener('change', async function (event) {
                const option = event.target.closest('.answer-option');
                if (!option || isSubmitting) {
                    return;
                }

                document.querySelectorAll('[data-option-wrapper]').forEach(wrapper => {
                    wrapper.classList.remove('selected');
                });

                const wrapper = document.querySelector(`[data-option-wrapper="${option.value}"]`);
                if (wrapper) {
                    wrapper.classList.add('selected');
                }

                markCurrentQuestionAnswered();

                try {
                    await saveAnswer(
                        Number(option.dataset.questionId),
                        Number(option.value)
                    );
                } catch (error) {
                }
            });

            document.addEventListener('click', async function (event) {
                const link = event.target.closest('.question-navigation-link');
                if (!link) {
                    return;
                }

                if (isSubmitting || isNavigating) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                const targetUrl = new URL(link.href, window.location.origin);
                const questionNumber = Number(targetUrl.searchParams.get('question') || 1);
                isNavigating = true;

                document.querySelectorAll('.question-navigation-link').forEach(item => {
                    item.classList.add('exam-navigation-loading');
                });

                try {
                    if (saveAnswerPromise) {
                        await saveAnswerPromise;
                    }

                    const requestUrl = new URL(window.location.href);
                    requestUrl.searchParams.set('question', String(questionNumber));

                    const response = await fetch(requestUrl.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success || !data.html) {
                        throw new Error(data.message || 'Soal gagal dimuat.');
                    }

                    document.getElementById('examQuestionPanel').outerHTML = data.html;
                    if (shouldPushHistory) {
                        history.pushState({ question: data.currentNumber }, '', requestUrl.toString());
                    }
                    shouldPushHistory = true;
                    submitButton = document.getElementById('submitButton');
                    answeredCountElement = document.getElementById('answeredCount');
                    answerStatusElement = document.getElementById('answerStatus');

                    document.querySelectorAll('.question-number').forEach(numberLink => {
                        const number = Number(numberLink.textContent.trim());
                        numberLink.classList.remove('current');
                        if (number === Number(data.currentNumber)) {
                            numberLink.classList.add('current');
                        }
                        numberLink.classList.toggle('answered', data.answeredQuestionIds.includes(Number(numberLink.dataset.questionId)));
                        if (!numberLink.classList.contains('current') && !numberLink.classList.contains('answered')) {
                            numberLink.classList.add('bg-light', 'text-dark');
                        } else {
                            numberLink.classList.remove('bg-light', 'text-dark');
                        }
                    });

                    const activeLink = Array.from(document.querySelectorAll('.question-number')).find(item =>
                        Number(item.textContent.trim()) === Number(data.currentNumber)
                    );
                    if (activeLink) {
                        activeLink.classList.add('current');
                    }

                    if (answeredCountElement) {
                        answeredCountElement.textContent = data.answeredCount;
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (error) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Soal',
                        text: error.message,
                        confirmButtonText: 'OK'
                    });
                } finally {
                    shouldPushHistory = true;
                    isNavigating = false;
                    document.querySelectorAll('.question-navigation-link').forEach(item => {
                        item.classList.remove('exam-navigation-loading');
                    });
                }
            });

            window.addEventListener('popstate', function () {
                const questionNumber = Number(new URL(window.location.href).searchParams.get('question') || 1);
                const link = Array.from(document.querySelectorAll('.question-navigation-link')).find(item => {
                    return Number(new URL(item.href, window.location.origin).searchParams.get('question') || 1) === questionNumber;
                });
                if (link) {
                    shouldPushHistory = false;
                    link.click();
                }
            });

            document.addEventListener('click', async function (event) {
                const clickedSubmit = event.target.closest('#submitButton');
                if (!clickedSubmit || isSubmitting) {
                    return;
                }

                const result = await Swal.fire({
                    icon: 'question',
                    title: 'Kumpulkan Ujian?',
                    text: 'Setelah dikumpulkan, Anda tidak dapat mengubah jawaban lagi.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kumpulkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    allowOutsideClick: false
                });

                if (!result.isConfirmed) {
                    return;
                }

                if (saveAnswerPromise) {
                    try {
                        await saveAnswerPromise;
                    } catch (error) {
                        return;
                    }
                }

                await submitExam(false);
            });

            async function submitExam(
                isExpired = false
            ) {

                if (isSubmitting === false) {
                    isSubmitting = true;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                }

                document
                    .querySelectorAll(
                        '.question-navigation-link'
                    )
                    .forEach(link => {
                        link.classList.add(
                            'exam-navigation-loading'
                        );
                    });

                try {

                    const response =
                        await fetch(
                            submitUrl,
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({})
                            }
                        );

                    if (response.redirected) {
                        if (isExpired) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Ujian Dikumpulkan',
                                text: 'Ujian telah dikumpulkan secara otomatis karena waktu telah habis.',
                                confirmButtonText: 'Lihat Hasil',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            });
                        } else {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Ujian Berhasil Dikumpulkan',
                                text: 'Jawaban Anda telah berhasil dikumpulkan.',
                                confirmButtonText: 'Lihat Hasil',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            });
                        }

                        window.location.replace(
                            response.url
                        );

                        return;
                    }

                    if (!response.ok) {

                        let message =
                            'Gagal mengumpulkan ujian.';

                        try {
                            const data =
                                await response.json();

                            message =
                                data.message ||
                                message;
                        } catch (error) {
                        }

                        throw new Error(message);
                    }

                    let data = {};

                    try {
                        data = await response.json();
                    } catch (error) {
                        data = {};
                    }

                    if (data.redirect) {

                        await Swal.fire({
                            icon: 'success',
                            title: isExpired
                                ? 'Ujian Dikumpulkan'
                                : 'Ujian Berhasil Dikumpulkan',
                            text: isExpired
                                ? 'Ujian telah dikumpulkan karena waktu telah habis.'
                                : 'Jawaban Anda telah berhasil dikumpulkan.',
                            confirmButtonText: 'Lihat Hasil',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });

                        window.location.replace(
                            data.redirect
                        );

                        return;
                    }

                    throw new Error(
                        data.message ||
                        'Respons server tidak valid.'
                    );

                } catch (error) {

                    isSubmitting = false;

                    if (submitButton) {
                        submitButton.disabled = false;
                    }

                    document
                        .querySelectorAll(
                            '.question-navigation-link'
                        )
                        .forEach(link => {
                            link.classList.remove(
                                'exam-navigation-loading'
                            );
                        });

                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message,
                        confirmButtonText: 'OK'
                    });
                }
            }

            window.addEventListener(
                'beforeunload',
                function (event) {

                    if (
                        remainingSeconds > 0 &&
                        !isSubmitting &&
                        !isNavigating
                    ) {
                        event.preventDefault();
                        event.returnValue = '';
                    }

                }
            );

            updateTimer();

            const timerInterval =
                setInterval(
                    updateTimer,
                    1000
                );

            const fullscreenPromptKey = 'exam-fullscreen-prompt-{{ $attempt->id }}';
            const isResumingExam = @json((bool) session('resume_exam'));

            if (
                !document.fullscreenElement &&
                document.documentElement.requestFullscreen &&
                (isResumingExam || !sessionStorage.getItem(fullscreenPromptKey))
            ) {
                sessionStorage.setItem(fullscreenPromptKey, 'shown');

                Swal.fire({
                    icon: 'info',
                    title: isResumingExam ? 'Lanjutkan Ujian' : 'Masuk Mode Fullscreen',
                    text: isResumingExam
                        ? 'Ujian Anda masih berlangsung. Klik tombol di bawah untuk kembali ke mode fullscreen dan melanjutkan ujian.'
                        : 'Untuk memulai ujian dalam tampilan penuh, klik tombol di bawah. Browser memerlukan tindakan langsung dari Anda untuk mengaktifkan fullscreen.',
                    confirmButtonText: isResumingExam ? 'Lanjutkan Ujian Fullscreen' : 'Mulai Ujian Fullscreen',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCancelButton: false,
                    preConfirm: async () => {
                        try {
                            await document.documentElement.requestFullscreen();
                            return { fullscreen: true };
                        } catch (error) {
                            return { fullscreen: false };
                        }
                    }
                }).then(result => {
                    if (
                        result.isConfirmed &&
                        result.value &&
                        !result.value.fullscreen
                    ) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Fullscreen Belum Aktif',
                            text: 'Browser tidak mengizinkan fullscreen otomatis. Klik tombol Masuk Fullscreen pada halaman ujian untuk mencoba kembali.',
                            confirmButtonText: 'Saya Mengerti'
                        });
                    }
                });
            }

        });
    </script>
@endsection

