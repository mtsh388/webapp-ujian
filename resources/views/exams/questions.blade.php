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
                            @if($questions->isNotEmpty())
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    id="deleteAllQuestions">
                                    <i class="bx bx-trash me-1"></i>
                                    Hapus Semua Soal
                                </button>
                            @endif
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

            <div class="card-body border-bottom py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-7">
                        <label for="searchAssignedQuestions" class="form-label">Cari soal dalam ujian</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" id="searchAssignedQuestions" class="form-control" placeholder="Cari tipe, pertanyaan, atau bobot..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-12 col-md-5 text-md-end">
                        <small class="text-muted" id="assignedQuestionSearchInfo">{{ $questions->count() }} soal ditampilkan</small>
                    </div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table id="assignedQuestionsTable" class="table table-hover border-top w-100">
                    <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 15%">Tipe Soal</th>
                        <th>Pertanyaan</th>
                        <th style="width: 10%">Bobot</th>
                        <th style="width: 10%">Aksi</th>
                    </tr>
                    </thead>

                    <tbody></tbody>
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
                            <div class="small text-muted">
                                <div>Mata Pelajaran: {{ $exam->subject->name ?? 'Umum' }}</div>
                                <div>Jenjang: {{ $exam->subject->education_level ?? '-' }} · Kelas {{ $exam->subject->grade_level ?? '-' }} · Topik: {{ $exam->subject->topic ?? '-' }}</div>
                            </div>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>


                    @csrf

                    <div class="modal-body">
                        <div class="alert alert-label-primary mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            Daftar di bawah hanya menampilkan soal aktif dari mata pelajaran, jenjang, kelas, dan topik yang dipilih untuk ujian ini.
                        </div>

                        <div class="mb-3">
                            <label for="searchAvailableQuestions" class="form-label">Cari soal dari Bank Soal</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="search" id="searchAvailableQuestions" class="form-control" placeholder="Cari kode, tipe, atau isi pertanyaan..." autocomplete="off">
                            </div>
                            <small class="text-muted" id="availableQuestionSearchInfo"></small>
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table id="availableQuestionsTable" class="table table-hover border-top w-100">
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

                                <tbody></tbody>
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
            const selectedQuestionIds = new Set();
            const submitButton = document.getElementById('btnSubmitSoal');
            const checkAll = document.getElementById('checkAll');
            const addForm = document.querySelector('#modalAddQuestions')?.closest('form');
            const assignedTable = new DataTable('#assignedQuestionsTable', {
                processing: true,
                serverSide: true,
                ajax: @json(route('exams.questions.data', $exam)),
                pageLength: 10,
                order: [[0, 'asc']],
                columns: [
                    { data: 'no', orderable: false, searchable: false },
                    { data: 'type', render: value => '<span class="badge bg-label-info">' + escapeHtml(questionType(value)) + '</span>' },
                    { data: 'question', render: value => escapeHtml(value || '-') },
                    { data: 'score', render: value => '<span class="badge bg-label-primary">' + escapeHtml(value) + '</span>' },
                    { data: null, orderable: false, searchable: false, render: (value, type, row) => row.can_update ? '<button type="button" class="btn btn-sm btn-icon text-danger" onclick="deleteQuestion(' + row.id + ')"><i class="bx bx-trash"></i></button>' : '-' }
                ],
                language: dataTableLanguage()
            });
            const availableTable = new DataTable('#availableQuestionsTable', {
                processing: true,
                serverSide: true,
                ajax: @json(route('exams.questions.available.data', $exam)),
                pageLength: 10,
                order: [[1, 'asc']],
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (id, type, row) => '<input class="form-check-input question-checkbox" type="checkbox" value="' + Number(id) + '"' + (selectedQuestionIds.has(Number(id)) ? ' checked' : '') + '>' },
                    { data: 'code', render: value => escapeHtml(value || '-') },
                    { data: 'type', render: value => '<span class="badge bg-label-info">' + escapeHtml(questionType(value)) + '</span>' },
                    { data: 'question', render: value => escapeHtml(value || '-') }
                ],
                language: dataTableLanguage(),
                drawCallback: function () {
                    const boxes = Array.from(document.querySelectorAll('#availableQuestionsTable .question-checkbox'));
                    checkAll.checked = boxes.length > 0 && boxes.every(box => selectedQuestionIds.has(Number(box.value)));
                    checkAll.indeterminate = boxes.some(box => selectedQuestionIds.has(Number(box.value))) && !checkAll.checked;
                    updateSubmitButton();
                }
            });

            document.getElementById('searchAssignedQuestions')?.addEventListener('input', function () {
                assignedTable.search(this.value).draw();
            });
            document.getElementById('searchAvailableQuestions')?.addEventListener('input', function () {
                availableTable.search(this.value).draw();
            });

            document.getElementById('availableQuestionsTable').addEventListener('change', function (event) {
                const checkbox = event.target.closest('.question-checkbox');
                if (!checkbox) return;
                const id = Number(checkbox.value);
                if (checkbox.checked) selectedQuestionIds.add(id);
                else selectedQuestionIds.delete(id);
                updateSubmitButton();
            });

            checkAll?.addEventListener('change', function () {
                document.querySelectorAll('#availableQuestionsTable .question-checkbox').forEach(box => {
                    const id = Number(box.value);
                    if (checkAll.checked) selectedQuestionIds.add(id);
                    else selectedQuestionIds.delete(id);
                    box.checked = checkAll.checked;
                });
                updateSubmitButton();
            });

            addForm?.addEventListener('submit', function () {
                addForm.querySelectorAll('input.selected-question-hidden').forEach(input => input.remove());
                selectedQuestionIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'question_ids[]';
                    input.value = String(id);
                    input.className = 'selected-question-hidden';
                    addForm.appendChild(input);
                });
            });

            function updateSubmitButton() {
                if (submitButton) submitButton.disabled = selectedQuestionIds.size === 0;
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
            }

            function questionType(value) {
                return value === 'multiple_select' ? 'Pilih Semua yang Benar' : value === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah';
            }

            function dataTableLanguage() {
                return {
                    processing: 'Memuat data...', search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan', emptyTable: 'Tidak ada data tersedia',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                };
            }

            const deleteAllButton = document.getElementById('deleteAllQuestions');
            if (deleteAllButton) {
                deleteAllButton.addEventListener('click', async function () {
                    const confirmation = await Swal.fire({
                        title: 'Hapus semua soal?',
                        text: 'Semua soal akan dilepas dari ujian ini. Soal tetap tersimpan di Bank Soal.',
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Ya, hapus semua', cancelButtonText: 'Batal'
                    });
                    if (!confirmation.isConfirmed) return;
                    const response = await fetch(@json(route('exams.questions.bulk-destroy', $exam)), {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Gagal menghapus soal.' });
                        return;
                    }
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'Semua soal dihapus.' });
                    assignedTable.ajax.reload();
                    availableTable.ajax.reload();
                });
            }
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
