@extends('template.app')

@section('title', 'Log Aktivitas Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Aktivitas hari ini</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="bx bx-error-circle"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($totalToday, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Sesi dengan aktivitas hari ini</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-user-check"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($attemptsFlagged, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Sesi aktif perlu ditinjau</span>
                            <span class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-shield-quarter"></i>
                                </span>
                            </span>
                        </div>
                        <h3 class="mb-0">{{ number_format($activeAttemptsFlagged, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card px-3">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Log Aktivitas Ujian</h4>
                        <p class="card-subtitle text-muted mb-0">
                            Pantau aktivitas ujian yang perlu ditinjau. Waktu dicatat oleh server; tinjau konteks sebelum mengambil tindakan.
                        </p>
                    </div>

                    <div id="proctoringExportButtons" class="d-flex align-items-center gap-2"></div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table id="proctoringTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Peserta</th>
                            <th>Ujian</th>
                            <th>Aktivitas</th>
                            <th>Total Kejadian Sesi</th>
                            <th>Status Sesi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.querySelector('#proctoringTable');

            if (!table) {
                return;
            }

            const escapeHtml = function (value) {
                return String(value ?? '').replace(/[&<>"']/g, function (character) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[character];
                });
            };

            new DataTable(table, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('proctoring.data') }}',
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
                dom:
                    '<"row"<"col-12 d-none"B>>' +
                    '<"row mx-2"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"l>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-2 mt-3 mb-4"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"i>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"p>' +
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
                                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="bx bx-copy me-1"></i>Copy',
                                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bx bx-file me-1"></i>CSV',
                                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bx bx-spreadsheet me-1"></i>Excel',
                                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                            }
                        ]
                    }
                ],
                columns: [
                    {
                        data: 'no',
                        name: 'exam_violations.id',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: null,
                        name: 'exam_violations.created_at',
                        render: function (data, type, row) {
                            return '<div class="text-nowrap"><div class="fw-medium">' +
                                escapeHtml(row.created_date) +
                                '</div><small class="text-muted">' +
                                escapeHtml(row.created_time) +
                                '</small></div>';
                        }
                    },
                    {
                        data: null,
                        name: 'students.full_name',
                        render: function (data, type, row) {
                            return '<div class="fw-semibold">' +
                                escapeHtml(row.student_name) +
                                '</div><small class="text-muted">Sesi #' +
                                escapeHtml(row.attempt_id) +
                                '</small>';
                        }
                    },
                    {
                        data: 'exam_title',
                        name: 'exams.title',
                        render: function (data) {
                            return escapeHtml(data || 'Ujian tidak tersedia');
                        }
                    },
                    {
                        data: null,
                        name: 'exam_violations.event_type',
                        render: function (data, type, row) {
                            let html = '<span class="badge bg-label-warning">' +
                                escapeHtml(row.event_label) +
                                '</span>';

                            if (row.visibility_state) {
                                html += '<small class="d-block text-muted mt-1">' +
                                    escapeHtml(row.visibility_state) +
                                    '</small>';
                            }

                            return html;
                        }
                    },
                    {
                        data: 'violations_count',
                        name: 'attempts.id',
                        searchable: false,
                        render: function (data) {
                            return '<span class="d-block text-center">' +
                                escapeHtml(data ?? 0) +
                                '</span>';
                        }
                    },
                    {
                        data: null,
                        name: 'attempts.status',
                        render: function (data, type, row) {
                            const active = row.status === 'in_progress';
                            const badgeClass = active ? 'bg-label-primary' : 'bg-label-secondary';
                            const label = active ? 'Sedang Ujian' : (row.status_label || 'Tidak tersedia');

                            return '<span class="badge ' + badgeClass + '">' +
                                escapeHtml(label) +
                                '</span>';
                        }
                    }
                ],
                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ aktivitas',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 aktivitas',
                    zeroRecords: 'Aktivitas tidak ditemukan',
                    emptyTable: 'Belum ada aktivitas ujian',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: '›',
                        previous: '‹'
                    }
                },
                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: null,
                    bottomEnd: null
                },
                initComplete: function () {
                    const exportContainer = document.querySelector('#proctoringExportButtons');
                    const buttonsContainer = document.querySelector('#proctoringTable_wrapper .dt-buttons');

                    if (exportContainer && buttonsContainer) {
                        exportContainer.appendChild(buttonsContainer);
                    }
                }
            });
        });
    </script>
@endsection
