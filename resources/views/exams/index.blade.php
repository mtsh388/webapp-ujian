@extends('template.app')

@section('title', 'Daftar Ujian')

@section('content')

    <style>
        #examsTable th:nth-child(3),
        #examsTable td:nth-child(3) {
            min-width: 210px;
            max-width: 320px;
            white-space: normal;
            vertical-align: middle;
        }

        #examsTable th:nth-child(5),
        #examsTable td:nth-child(5) {
            min-width: 190px;
            vertical-align: middle;
        }

        #examsTable td {
            vertical-align: middle;
        }

        .exam-title-cell,
        .exam-schedule-cell {
            line-height: 1.45;
        }

        @media (max-width: 767.98px) {
            #examsTable th:nth-child(3),
            #examsTable td:nth-child(3) {
                min-width: 180px;
            }

            #examsTable th:nth-child(5),
            #examsTable td:nth-child(5) {
                min-width: 175px;
            }
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        @endif

        <div class="card">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Daftar Ujian
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Kelola jadwal, mata pelajaran, status, dan konfigurasi ujian.
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">

                        <div id="examsExportButtons"></div>

                        @can('exam.create')
                            <a
                                href="{{ route('exams.create') }}"
                                class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>
                                Buat Ujian
                            </a>
                        @endcan

                    </div>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-notepad"></i>
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block">
                                    Manajemen Ujian
                                </small>
                                <span class="fw-semibold">
                                    Jadwal & Soal
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="alert alert-label-primary mb-0 py-2 px-3">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2"></i>
                                <small>
                                    Gunakan menu <strong>Kelola Soal</strong> untuk menambahkan soal ke dalam ujian.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-datatable table-responsive">
                <!-- Hapus table-bordered dan ganti dengan table-hover -->
                <table id="examsTable" class="table table-hover border-top">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Judul & Durasi</th>
                        <th>Mata Pelajaran</th>
                        <th>Jadwal Pelaksanaan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.querySelector('#examsTable');

            if (!table) {
                return;
            }

            // Simpan instance ke window agar bisa dipanggil saat delete
            window.examDataTable = new DataTable(table, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('exams.data') }}',
                    type: 'GET'
                },
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 75, 100],
                    [10, 25, 50, 75, 100]
                ],
                order: [
                    [1, 'desc']
                ],
                responsive: true,

                // PERBAIKAN DOM: Hapus class flex pada info (i) dan pagination (p)
                // Biarkan Bootstrap 5 dan DataTables yang mengatur alignment otomatis
                dom:
                    '<"row mx-2 pt-3 pb-2"' +
                    '<"col-sm-12 col-md-6"l>' +
                    '<"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-2 mt-4 mb-3"' +
                    '<"col-sm-12 col-md-6"i>' +
                    '<"col-sm-12 col-md-6"p>' +
                    '>',

                buttons: [
                    {
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle',
                        text: '<i class="bx bx-export me-1"></i>Export',
                        buttons: [
                            {
                                extend: 'print',
                                text: '<i class="bx bx-printer me-1"></i>Print',
                                exportOptions: {columns: [0, 1, 2, 3, 4, 5]}
                            },
                            {
                                extend: 'copy',
                                text: '<i class="bx bx-copy me-1"></i>Copy',
                                exportOptions: {columns: [0, 1, 2, 3, 4, 5]}
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bx bx-file me-1"></i>CSV',
                                exportOptions: {columns: [0, 1, 2, 3, 4, 5]}
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bx bx-spreadsheet me-1"></i>Excel',
                                exportOptions: {columns: [0, 1, 2, 3, 4, 5]}
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                                exportOptions: {columns: [0, 1, 2, 3, 4, 5]}
                            }
                        ]
                    }
                ],
                columns: [
                    {data: 'no', name: 'no', searchable: false, orderable: false},
                    {
                        data: 'code',
                        name: 'code',
                        render: function (data) {
                            return `<span class="fw-bold">${data}</span>`;
                        }
                    },
                    {
                        data: 'title',
                        name: 'title',
                        render: function (data, type, row) {
                            const examType = row.exam_type === 'trial'
                                ? '<span class="badge bg-label-info mb-1">Trial</span>'
                                : '<span class="badge bg-label-primary mb-1">Ujian</span>';
                            const duration = row.has_duration
                                ? `<small class="text-muted d-block mt-1"><i class="bx bx-time-five me-1"></i>${row.duration_minutes} menit</small>`
                                : '<small class="text-muted d-block mt-1"><i class="bx bx-infinite me-1"></i>Tanpa batas durasi</small>';

                            return `
                                <div class="exam-title-cell">
                                    ${examType}
                                    <span class="fw-semibold d-block text-break">${data || '-'}</span>
                                    ${duration}
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'subject_name',
                        name: 'subject_name',
                        render: function (data) {
                            return data ? data : '<span class="text-muted">-</span>';
                        }
                    },
                    {
                        data: 'start_at',
                        name: 'start_at',
                        searchable: false,
                        render: function (data, type, row) {
                            if (!row.has_duration) {
                                return '<span class="badge bg-label-secondary"><i class="bx bx-calendar-x me-1"></i>Tanpa jadwal</span>';
                            }

                            return `
                                <div class="exam-schedule-cell">
                                    <small class="d-block text-nowrap"><span class="text-muted">Mulai</span><br><i class="bx bx-calendar-check me-1"></i>${data || '-'}</small>
                                    <small class="d-block text-nowrap mt-2"><span class="text-muted">Berakhir</span><br><i class="bx bx-calendar-x me-1"></i>${row.end_at || '-'}</small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable: false,
                        render: function (data) {
                            if (data === 'published') return '<span class="badge bg-label-success">Published</span>';
                            if (data === 'draft') return '<span class="badge bg-label-secondary">Draft</span>';
                            return '<span class="badge bg-label-danger">Closed</span>';
                        }
                    },
                    {
                        data: 'id',
                        name: 'id',
                        searchable: false,
                        orderable: false,
                        render: function (id) {
                            return `
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="/ujian/${id}/soal"><i class="bx bx-list-ol me-1"></i> Kelola Soal</a>
                                        <a class="dropdown-item" href="/ujian/${id}/peserta"><i class="bx bx-group me-1"></i> Peserta</a>
                                        <div class="dropdown-divider"></div>
                                        @can('exam.update')
                            <a class="dropdown-item text-primary" href="/ujian/${id}/edit"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                                        @endcan
                            @can('exam.delete')
                            <button type="button" class="dropdown-item text-danger" onclick="deleteExam(${id})">
                                            <i class="bx bx-trash me-1"></i> Hapus
                                        </button>
                                        @endcan
                            </div>
                        </div>
`;
                        }
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: '',
                    searchPlaceholder: 'Cari ujian...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 hasil',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data ujian',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                },
                initComplete: function () {
                    // Penyesuaian class Bootstrap untuk komponen DataTables
                    $('.dataTables_filter input').addClass('form-control');
                    $('.dataTables_length select').addClass('form-select');

                    // Pindahkan tombol Export
                    const exportContainer = document.querySelector('#examsExportButtons');
                    const buttonsContainer = document.querySelector('#examsTable_wrapper .dt-buttons');

                    if (exportContainer && buttonsContainer) {
                        buttonsContainer.classList.remove('d-none');
                        exportContainer.appendChild(buttonsContainer);
                    }
                }
            });
        });

        function deleteExam(id) {
            Swal.fire({
                title: 'Hapus Ujian?',
                text: 'Data ujian dan konfigurasi terkait akan dihapus.',
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
                    Swal.fire({icon: 'error', title: 'Gagal', text: 'CSRF token tidak ditemukan.'});
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

                fetch(`/ujian/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    }
                })
                    .then(response => response.json().then(data => {
                        if (!response.ok) throw new Error(data.message || 'Gagal menghapus ujian.');
                        return data;
                    }))
                    .then(response => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Ujian berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Memanggil instance window yang baru saja disimpan
                            if (window.examDataTable) {
                                window.examDataTable.ajax.reload(null, false);
                            }
                        });
                    })
                    .catch(error => {
                        Swal.fire({icon: 'error', title: 'Gagal', text: error.message});
                    });
            });
        }
    </script>
@endsection
