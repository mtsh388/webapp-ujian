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

                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <div id="proctoringExportButtons"></div>
                        <span class="badge bg-label-secondary">
                            Total {{ number_format($totalViolations, 0, ',', '.') }} aktivitas
                        </span>
                    </div>
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

                responsive: false,
                scrollX: true,

                dom:
                    '<"row"' +
                    '<"col-12 d-none"B>' +
                    '>' +
                    '<"row mx-2"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"l>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-2 mt-3 mb-4"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"i>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"p>' +
                    '>',

                language: {
                    processing: 'Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ aktivitas',
                    infoEmpty: 'Tidak ada aktivitas untuk ditampilkan',
                    infoFiltered: '(difilter dari _MAX_ total aktivitas)',
                    zeroRecords: 'Aktivitas tidak ditemukan',
                    emptyTable: 'Belum ada aktivitas ujian yang tercatat',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                },

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
                        name: 'id',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'created_date',
                        name: 'created_at',
                        render: function (data, type, row) {
                            if (type !== 'display') {
                                return (data || '') + ' ' + (row.created_time || '');
                            }

                            return '<div class="fw-medium">' + escapeHtml(data || '-') + '</div>' +
                                '<small class="text-muted">' + escapeHtml(row.created_time || '-') + '</small>';
                        }
                    },
                    {
                        data: 'student_name',
                        name: 'student_name',
                        render: function (data, type, row) {
                            if (type !== 'display') {
                                return data || '';
                            }

                            return '<div class="fw-semibold">' + escapeHtml(data || 'Peserta tidak tersedia') + '</div>' +
                                '<small class="text-muted">Sesi #' + escapeHtml(row.exam_attempt_id) + '</small>';
                        }
                    },
                    {
                        data: 'exam_title',
                        name: 'exam_title',
                        render: function (data) {
                            return escapeHtml(data || 'Ujian tidak tersedia');
                        }
                    },
                    {
                        data: 'event_label',
                        name: 'event_type',
                        render: function (data, type, row) {
                            if (type !== 'display') {
                                return data || '';
                            }

                            const visibility = row.visibility_state
                                ? '<small class="d-block text-muted mt-1">' + escapeHtml(row.visibility_state) + '</small>'
                                : '';

                            return '<span class="badge bg-label-warning">' + escapeHtml(data || '-') + '</span>' + visibility;
                        }
                    },
                    {
                        data: 'session_event_count',
                        name: 'session_event_count',
                        searchable: false,
                        render: function (data) {
                            return '<span class="fw-medium">' + escapeHtml(data ?? 0) + '</span>';
                        }
                    },
                    {
                        data: 'attempt_status',
                        name: 'attempt_status',
                        render: function (data) {
                            if (data === 'in_progress') {
                                return '<span class="badge bg-label-primary">Sedang Ujian</span>';
                            }

                            if (!data) {
                                return '<span class="badge bg-label-secondary">Tidak tersedia</span>';
                            }

                            return '<span class="badge bg-label-secondary">' +
                                escapeHtml(data.replace(/_/g, ' ').replace(/\b\w/g, function (letter) {
                                    return letter.toUpperCase();
                                })) +
                                '</span>';
                        }
                    }
                ]
            });

            const exportContainer = document.querySelector('#proctoringExportButtons');

            if (exportContainer) {
                document.querySelectorAll('.dt-buttons').forEach(function (buttons) {
                    exportContainer.appendChild(buttons);
                });
            }
        });
    </script>
@endsection
