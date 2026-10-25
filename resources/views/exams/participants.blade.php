@extends('template.app')

@section('title', 'Kelola Peserta Ujian')

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
                            Kelola Peserta Ujian
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Kelola siswa yang terdaftar, status keaktifan, dan keikutsertaan dalam ujian.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">

                        <a href="{{ route('exams.index') }}" class="btn btn-label-secondary">
                            <i class="bx bx-arrow-back me-1"></i>
                            Kembali
                        </a>

                        <a href="{{ route('exams.questions', $exam) }}" class="btn btn-label-primary">
                            <i class="bx bx-list-ol me-1"></i>
                            Kelola Soal
                        </a>

                        @can('exam.update')
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#addParticipantModal">
                                <i class="bx bx-user-plus me-1"></i>
                                Tambah Peserta
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
                                <small class="text-muted d-block">
                                    Informasi Ujian
                                </small>

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
                                <small class="text-muted d-block">
                                    Mata Pelajaran
                                </small>

                                <span class="fw-semibold">
                                    {{ $exam->subject?->name ?? 'Tanpa Mata Pelajaran' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-group me-2 fs-4"></i>

                                <div>
                                    <small class="d-block">
                                        Total Peserta Terdaftar
                                    </small>

                                    <span class="fw-semibold">
                                        <span id="participantTotal">Memuat...</span> peserta
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-group"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Total Peserta
                                </small>

                                <span class="fw-semibold">
                                    {{ $participants->count() }} siswa
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-user-check"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Peserta Aktif
                                </small>

                                <span class="fw-semibold">
                                    {{ $participants->where('is_active', true)->count() }} siswa
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class="bx bx-user-x"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Peserta Nonaktif
                                </small>

                                <span class="fw-semibold">
                                    {{ $participants->where('is_active', false)->count() }} siswa
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-header border-bottom">
                <div>
                    <h5 class="card-title mb-1">
                        Daftar Peserta
                    </h5>

                    <p class="card-subtitle text-muted mb-0">
                        Daftar siswa yang telah ditambahkan ke ujian ini.
                    </p>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table id="participantsTable" class="table table-hover border-top w-100">
                    <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th>Siswa</th>
                        <th>Nomor Siswa</th>
                        <th>Kelas</th>
                        <th>Ditambahkan</th>
                        <th>Status</th>
                        <th style="width: 5%">Aksi</th>
                    </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
    <form
        method="POST"
        action="{{ route('exams.participants.store', $exam) }}">
        <div class="modal fade" id="addParticipantModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">


                    @csrf

                    <div class="modal-header border-bottom">
                        <div>
                            <h5 class="modal-title mb-1">
                                Tambah Peserta Ujian
                            </h5>

                            <small class="text-muted">
                                {{ $exam->title }}
                            </small>
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>

                    <div class="modal-body">

                        @if($availableStudents->isEmpty())
                            <div class="alert alert-label-info mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-info-circle me-2"></i>

                                    <span>
                                        Semua siswa aktif sudah menjadi peserta ujian ini.
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-label-info mb-3">
                                <i class="bx bx-filter-alt me-1"></i>
                                Daftar siswa dibatasi pada jenjang {{ $exam->subject->education_level ?? '-' }}
                                kelas {{ $exam->subject->grade_level ?? '-' }} sesuai mata pelajaran ujian.
                            </div>

                            <div class="mb-3">
                                <label for="studentClassFilter" class="form-label">
                                    Filter Kelas
                                </label>
                                <select id="studentClassFilter" class="form-select">
                                    <option value="">Semua kelas yang sesuai</option>
                                    @foreach($availableStudents->pluck('classRoom')->filter()->unique('id')->sortBy('grade_level') as $classRoom)
                                        <option value="{{ $classRoom->id }}">
                                            {{ $classRoom->name }} ({{ $classRoom->education_level }} - Kelas {{ $classRoom->grade_level }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="studentSearch" class="form-label">
                                    Cari Siswa
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bx bx-search"></i>
                                    </span>

                                    <input
                                        type="text"
                                        id="studentSearch"
                                        class="form-control"
                                        placeholder="Cari nama atau nomor siswa...">
                                </div>
                            </div>

                            <div class="border rounded">
                                <div class="p-3 border-bottom">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="selectAllStudents">

                                        <label
                                            class="form-check-label fw-semibold"
                                            for="selectAllStudents">
                                            Pilih Semua Siswa
                                        </label>
                                    </div>
                                </div>

                                <div id="studentList" style="max-height: 400px; overflow-y: auto;">
                                    @foreach($availableStudents as $student)
                                        <div
                                            class="student-item p-3 border-bottom"
                                            data-class-id="{{ $student->class_id }}"
                                            data-search="{{ strtolower($student->full_name . ' ' . $student->student_number . ' ' . ($student->user?->email ?? '')) }}">

                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input student-checkbox"
                                                    id="student-{{ $student->id }}"
                                                    name="student_ids[]"
                                                    value="{{ $student->id }}">

                                                <label
                                                    class="form-check-label w-100"
                                                    for="student-{{ $student->id }}">

                                                    <span class="fw-semibold d-block">
                                                        {{ $student->full_name }}
                                                    </span>

                                                    <small class="text-muted">
                                                        {{ $student->student_number }}

                                                        @if($student->classRoom)
                                                            · {{ $student->classRoom->name }}
                                                        @endif
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div
                                        id="noStudentSearchResult"
                                        class="text-center text-muted py-4 d-none">
                                        Siswa tidak ditemukan.
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bx bx-check-circle me-1"></i>
                                    <span id="selectedStudentCount">0</span>
                                    siswa dipilih.
                                </small>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer border-top">
                        <button
                            type="button"
                            class="btn btn-label-secondary mt-3"
                            data-bs-dismiss="modal">
                            Batal
                        </button>

                        @if($availableStudents->isNotEmpty())
                            <button
                                type="submit"
                                class="btn btn-primary mt-3"
                                id="submitParticipants"
                                disabled>
                                <i class="bx bx-user-plus me-1"></i>
                                Tambahkan Peserta
                            </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>

    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const participantsTable = new DataTable('#participantsTable', {
                processing: true,
                serverSide: true,
                ajax: @json(route('exams.participants.data', $exam)),
                pageLength: 10,
                order: [[4, 'desc']],
                columns: [
                    { data: 'no', orderable: false, searchable: false },
                    { data: 'student_name', render: (value, type, row) => '<div class="d-flex align-items-center"><div class="avatar avatar-sm flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-primary">' + escapeHtml((value || 'S').charAt(0).toUpperCase()) + '</span></div><div><span class="fw-semibold d-block">' + escapeHtml(value || '-') + '</span><small class="text-muted">' + escapeHtml(row.email || '-') + '</small></div></div>' },
                    { data: 'student_number', render: value => escapeHtml(value || '-') },
                    { data: 'class_name', render: value => escapeHtml(value || '-') },
                    { data: 'assigned_at', render: value => formatParticipantDate(value) },
                    { data: 'is_active', render: value => value ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-secondary">Nonaktif</span>' },
                    { data: null, orderable: false, searchable: false, className: 'text-center', render: (value, type, row) => {
                        if (!row.can_update) return '-';
                        const examId = @json($exam->id);
                        return '<div class="dropdown"><button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button><div class="dropdown-menu dropdown-menu-end"><button type="button" class="dropdown-item" onclick="toggleParticipantStatus(' + examId + ', ' + row.id + ', ' + (row.is_active ? 'true' : 'false') + ')">' + (row.is_active ? '<i class="bx bx-user-x me-1"></i>Nonaktifkan' : '<i class="bx bx-user-check me-1"></i>Aktifkan') + '</button><button type="button" class="dropdown-item text-danger" onclick="deleteParticipant(' + examId + ', ' + row.id + ')"><i class="bx bx-trash me-1"></i>Hapus</button></div></div>';
                    }}
                ],
                language: {
                    processing: 'Memuat data...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ peserta',
                    infoEmpty: 'Tidak ada peserta',
                    zeroRecords: 'Peserta tidak ditemukan',
                    emptyTable: 'Belum ada peserta untuk ujian ini.',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                },
                drawCallback: function (settings) {
                    document.getElementById('participantTotal').textContent = settings.json.recordsFiltered;
                }
            });

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
            }

            function formatParticipantDate(value) {
                if (!value) return '-';
                const date = new Date(value.replace(' ', 'T'));
                return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }

            const studentSearch = document.querySelector('#studentSearch');
            const studentClassFilter = document.querySelector('#studentClassFilter');
            const selectAllStudents = document.querySelector('#selectAllStudents');
            const studentCheckboxes = Array.from(
                document.querySelectorAll('.student-checkbox')
            );
            const selectedStudentCount = document.querySelector('#selectedStudentCount');
            const submitButton = document.querySelector('#submitParticipants');
            const noSearchResult = document.querySelector('#noStudentSearchResult');

            function updateSelection() {
                const checkedCount = studentCheckboxes.filter(
                    checkbox => checkbox.checked
                ).length;

                const visibleCheckboxes = studentCheckboxes.filter(
                    checkbox => checkbox.closest('.student-item').style.display !== 'none'
                );

                if (selectedStudentCount) {
                    selectedStudentCount.textContent = checkedCount;
                }

                if (submitButton) {
                    submitButton.disabled = checkedCount === 0;
                }

                if (selectAllStudents) {
                    selectAllStudents.checked =
                        visibleCheckboxes.length > 0 &&
                        visibleCheckboxes.every(checkbox => checkbox.checked);

                    selectAllStudents.indeterminate =
                        visibleCheckboxes.some(checkbox => checkbox.checked) &&
                        !visibleCheckboxes.every(checkbox => checkbox.checked);
                }
            }

            function filterStudents() {
                const keyword = studentSearch ? studentSearch.value.toLowerCase().trim() : '';
                const selectedClassId = studentClassFilter ? studentClassFilter.value : '';
                let visibleCount = 0;

                document.querySelectorAll('.student-item').forEach(function (item) {
                    const searchText = item.dataset.search || '';
                    const matchesSearch = searchText.includes(keyword);
                    const matchesClass = !selectedClassId || item.dataset.classId === selectedClassId;
                    const isVisible = matchesSearch && matchesClass;

                    item.style.display = isVisible ? '' : 'none';

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (noSearchResult) {
                    noSearchResult.classList.toggle('d-none', visibleCount > 0);
                }

                updateSelection();
            }

            if (studentSearch) {
                studentSearch.addEventListener('input', filterStudents);
            }

            if (studentClassFilter) {
                studentClassFilter.addEventListener('change', filterStudents);
            }

            if (selectAllStudents) {
                selectAllStudents.addEventListener('change', function () {
                    studentCheckboxes.forEach(function (checkbox) {
                        const item = checkbox.closest('.student-item');

                        if (item && item.style.display !== 'none') {
                            checkbox.checked = selectAllStudents.checked;
                        }
                    });

                    updateSelection();
                });
            }

            studentCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateSelection);
            });

            updateSelection();
        });

        function toggleParticipantStatus(examId, participantId, currentStatus) {
            Swal.fire({
                title: currentStatus ? 'Nonaktifkan Peserta?' : 'Aktifkan Peserta?',
                text: currentStatus
                    ? 'Peserta tidak akan dapat mengikuti ujian ini selama statusnya nonaktif.'
                    : 'Peserta akan diaktifkan kembali untuk ujian ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: currentStatus ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: currentStatus
                        ? 'btn btn-warning ms-2'
                        : 'btn btn-success ms-2',
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
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                fetch(`/ujian/${examId}/peserta/${participantId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    }
                })
                    .then(async function (response) {
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Gagal mengubah status peserta.');
                        }

                        return data;
                    })
                    .then(function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Status peserta berhasil diubah.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.reload();
                        });
                    })
                    .catch(function (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message
                        });
                    });
            });
        }

        function deleteParticipant(examId, participantId) {
            Swal.fire({
                title: 'Hapus Peserta?',
                text: 'Peserta akan dihapus dari daftar ujian ini.',
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

                fetch(`/ujian/${examId}/peserta/${participantId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    }
                })
                    .then(async function (response) {
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Gagal menghapus peserta.');
                        }

                        return data;
                    })
                    .then(function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Peserta berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.reload();
                        });
                    })
                    .catch(function (error) {
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

