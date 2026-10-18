@extends('template.app')

@section('title', 'Kelola Soal Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Kelola Soal Ujian
                        </h4>
                        <p class="card-subtitle text-muted mb-0">
                            Kelola daftar pertanyaan, tipe soal, dan bobot nilai untuk ujian.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                        <a href="{{ route('exams.index') }}" class="btn btn-label-secondary">
                            <i class="bx bx-arrow-back me-1"></i>
                            Kembali
                        </a>

                        @can('exam.update')
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAddQuestions">
                                <i class="bx bx-plus me-1"></i>
                                Tambah Soal
                            </button>
                        @endcan
                    </div>

                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <div class="row g-3 align-items-center">

                    <div class="col-12 col-lg-5">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-notepad"></i>
                                </span>
                            </div>

                            <div class="min-w-0">
                                <small class="text-muted d-block">Informasi Ujian</small>
                                <span class="fw-semibold d-block text-break">
                                    {{ $exam->title }}
                                </span>
                                <small class="text-muted">
                                    Kode: {{ $exam->code }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-book-open"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">Mata Pelajaran</small>
                                <span class="fw-semibold">
                                    {{ $exam->subject->name ?? 'Umum' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-list-ol me-2 fs-4"></i>
                                <div>
                                    <small class="d-block">Total Soal</small>
                                    <span class="fw-semibold">
                                        {{ $questions->count() }} soal tersimpan
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table class="table table-hover border-top">
                    <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 15%">Tipe Soal</th>
                        <th>Pertanyaan</th>
                        <th style="width: 10%">Bobot</th>
                        <th style="width: 10%">Aksi</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($questions as $index => $q)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                    <span
                                        class="badge bg-label-{{ $q->type === 'multiple_choice' ? 'info' : 'warning' }}">
                                        {{ $q->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah' }}
                                    </span>
                            </td>

                            <td class="text-wrap" style="min-width: 250px; max-width: 600px;">
                                {!! strip_tags(\Illuminate\Support\Str::limit($q->question, 120)) !!}
                            </td>

                            <td>
                                    <span class="badge bg-label-primary">
                                        {{ $q->score }}
                                    </span>
                            </td>

                            <td>
                                @can('exam.update')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-icon text-danger rounded-pill"
                                        onclick="deleteQuestion({{ $q->id }})"
                                        data-bs-toggle="tooltip"
                                        title="Hapus Soal">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="avatar mb-3">
                                            <span class="avatar-initial rounded bg-label-secondary">
                                                <i class="bx bx-notepad fs-3"></i>
                                            </span>
                                    </div>

                                    <h6 class="mb-1">Belum Ada Soal</h6>

                                    <p class="text-muted mb-3">
                                        Belum ada soal yang ditambahkan ke ujian ini.
                                    </p>

                                    @can('exam.update')
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAddQuestions">
                                            <i class="bx bx-plus me-1"></i>
                                            Tambah Soal Sekarang
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <form action="{{ route('exams.questions.store', $exam->id) }}" method="POST">
        <div class="modal fade" id="modalAddQuestions" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">

                    <div class="modal-header border-bottom">
                        <div>
                            <h5 class="modal-title mb-1">Tambah Soal dari Bank Soal</h5>
                            <small class="text-muted">
                                Mata Pelajaran: {{ $exam->subject->name ?? 'Umum' }}
                            </small>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>


                    @csrf

                    <div class="modal-body">
                        <div class="alert alert-label-primary mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            Pilih satu atau beberapa soal yang ingin ditambahkan ke ujian.
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover border-top">
                                <thead class="sticky-top bg-body">
                                <tr>
                                    <th style="width: 5%">
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </th>
                                    <th style="width: 15%">Kode</th>
                                    <th style="width: 15%">Tipe</th>
                                    <th>Pertanyaan</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($availableQuestions as $avail)
                                    <tr>
                                        <td>
                                            <input
                                                class="form-check-input question-checkbox"
                                                type="checkbox"
                                                name="question_ids[]"
                                                value="{{ $avail->id }}">
                                        </td>

                                        <td>
                                            <span class="fw-semibold">{{ $avail->code }}</span>
                                        </td>

                                        <td>
                                                <span
                                                    class="badge bg-label-{{ $avail->type === 'multiple_choice' ? 'info' : 'warning' }}">
                                                    {{ $avail->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah' }}
                                                </span>
                                        </td>

                                        <td class="text-wrap" style="min-width: 250px;">
                                            {!! strip_tags(\Illuminate\Support\Str::limit($avail->question, 150)) !!}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="bx bx-info-circle fs-2 text-muted d-block mb-2"></i>
                                            <span class="text-muted">
                                                    Tidak ada soal yang tersedia untuk mata pelajaran ini atau semua soal sudah ditambahkan.
                                                </span>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary mt-3" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary mt-3"
                            id="btnSubmitSoal"
                            {{ $availableQuestions->isEmpty() ? 'disabled' : '' }}>
                            <i class="bx bx-save me-1"></i>
                            Simpan Soal Terpilih
                        </button>
                    </div>


                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.question-checkbox');
            const submitButton = document.getElementById('btnSubmitSoal');

            function updateSubmitButton() {
                if (submitButton) {
                    submitButton.disabled = !Array.from(checkboxes).some(checkbox => checkbox.checked);
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = checkAll.checked;
                    });

                    updateSubmitButton();
                });
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    if (checkAll) {
                        checkAll.checked = Array.from(checkboxes).every(item => item.checked);
                    }

                    updateSubmitButton();
                });
            });

            updateSubmitButton();
        });

        function deleteQuestion(id) {
            Swal.fire({
                title: 'Hapus Soal?',
                text: 'Soal akan dihapus dari ujian ini, tetapi tetap tersimpan di Bank Soal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger ms-2',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]');

                if (!csrfToken) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'CSRF token tidak ditemukan.'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                fetch(`/ujian/{{ $exam->id }}/soal/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    }
                })
                    .then(async response => {
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Gagal menghapus soal.');
                        }

                        return data;
                    })
                    .then(response => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Soal berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message
                        });
                    });
            });
        }
    </script>
@endsection
