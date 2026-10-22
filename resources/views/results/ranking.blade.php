@extends('template.app')

@section('title', 'Ranking Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card px-3">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Ranking Ujian</h4>
                        <p class="card-subtitle text-muted mb-0">{{ $exam->title }} · {{ $exam->code }}</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($isRankingManager)
                            <div id="rankingExportButtons"></div>
                            <a href="{{ route('results.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Kembali ke Hasil Ujian
                            </a>
                        @else
                            <a href="{{ route('results.my') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Kembali ke Hasil Saya
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @if (!$isRankingManager)
                <div class="card-body border-bottom">
                    @if ($myRanking)
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Peringkat Anda</div>
                                <div class="fs-3 fw-bold">#{{ $myRanking->rank_position }} <span class="fs-6 text-muted">dari {{ $totalRankedStudents }} siswa</span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Nilai Terbaik Anda</div>
                                <div class="fs-3 fw-bold">{{ number_format((float) $myRanking->score, 2, ',', '.') }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Waktu Pengerjaan</div>
                                <div class="fs-3 fw-bold">
                                    @if ($myRanking->duration_seconds === null)
                                        -
                                    @else
                                        {{ gmdate('H:i:s', max(0, (int) $myRanking->duration_seconds)) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">Hasil Anda belum masuk dalam ranking.</div>
                    @endif
                </div>
                @endif
                <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Aturan Ranking</div>
                        <div class="fw-semibold">Nilai tertinggi, lalu waktu tercepat</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Hasil yang dihitung</div>
                        <div class="fw-semibold">Hasil terbaik setiap siswa</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Peringkat sama</div>
                        <div class="fw-semibold">Nilai dan waktu identik mendapat peringkat sama</div>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table id="rankingTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Peringkat</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Nilai</th>
                            <th>Waktu Pengerjaan</th>
                            <th>Status</th>
                            <th>Dikumpulkan</th>
                            @if ($isRankingManager)
                                <th>Aksi</th>
                            @endif
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
            const table = document.querySelector('#rankingTable');

            if (!table) {
                return;
            }

            new DataTable(table, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('results.ranking.data', $exam) }}',
                    type: 'GET'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 75, 100], [10, 25, 50, 75, 100]],
                order: [[1, 'asc']],
                responsive: false,
                scrollX: true,
                dom:
                    '<"row"<"col-12 d-none"B>>' +
                    '<"row mx-2"<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"l>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>' +
                    't' +
                    '<"row mx-2 mt-3 mb-4"<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"i>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"p>>',
                buttons: [{
                    extend: 'collection',
                    className: 'btn btn-label-primary dropdown-toggle',
                    text: '<i class="bx bx-export me-1"></i>Export',
                    buttons: [
                        { extend: 'copy', text: '<i class="bx bx-copy me-1"></i>Copy', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } },
                        { extend: 'csv', text: '<i class="bx bx-file me-1"></i>CSV', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } },
                        { extend: 'excel', text: '<i class="bx bx-spreadsheet me-1"></i>Excel', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } },
                        { extend: 'print', text: '<i class="bx bx-printer me-1"></i>Print', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } }
                    ]
                }],
                columns: [
                    { data: 'no', name: 'no', searchable: false, orderable: false },
                    {
                        data: 'rank',
                        name: 'rank_position',
                        searchable: false,
                        render: function (data, type) {
                            if (type !== 'display') return data;
                            return Number(data) === 1
                                ? '<span class="badge bg-label-warning">🥇 1</span>'
                                : '<span class="fw-semibold">' + data + '</span>';
                        }
                    },
                    {
                        data: 'student_name',
                        name: 'student_name',
                        render: function (data, type, row) {
                            const name = $('<div>').text(data || '-').html();
                            @if ($isRankingManager)
                                const number = $('<div>').text(row.student_number || '-').html();
                                return '<div class="fw-semibold">' + name + '</div><div class="small text-muted">' + number + '</div>';
                            @else
                                return '<div class="fw-semibold">' + name + '</div>';
                            @endif
                        }
                    },
                    {
                        data: 'class_name',
                        name: 'class_name',
                        render: function (data) {
                            return $('<div>').text(data || '-').html();
                        }
                    },
                    {
                        data: 'score',
                        name: 'score',
                        render: function (data, type) {
                            if (type !== 'display') return data;
                            return '<span class="fw-bold">' + Number(data || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span>';
                        }
                    },
                    {
                        data: 'duration_seconds',
                        name: 'duration_seconds',
                        render: function (data, type) {
                            if (data === null || data === undefined) return '-';
                            if (type !== 'display') return Number(data);
                            const seconds = Math.max(0, Number(data));
                            const hours = Math.floor(seconds / 3600);
                            const minutes = Math.floor((seconds % 3600) / 60);
                            const remainder = seconds % 60;
                            return [hours, minutes, remainder].map(value => String(value).padStart(2, '0')).join(':');
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            if (data === 'expired') return '<span class="badge bg-label-warning">Waktu Habis</span>';
                            if (row.passed === null || row.passed === undefined) return '<span class="badge bg-label-secondary">KKM belum ditentukan</span>';
                            return row.passed
                                ? '<span class="badge bg-label-success">Lulus</span>'
                                : '<span class="badge bg-label-danger">Tidak Lulus</span>';
                        }
                    },
                    {
                        data: 'submitted_at',
                        name: 'submitted_at',
                        render: function (data, type) {
                            if (!data) return '-';
                            if (type !== 'display') return data;
                            const date = new Date(data.replace(' ', 'T'));
                            if (Number.isNaN(date.getTime())) return $('<div>').text(data).html();
                            return '<div class="text-nowrap">' + date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + '</div><div class="small text-muted">' + date.toLocaleTimeString('id-ID', { hour12: false }) + '</div>';
                        }
                    },
                    @if ($isRankingManager)
                    {
                        data: 'id',
                        name: 'id',
                        searchable: false,
                        orderable: false,
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            return '<a href="' + row.show_url + '" class="btn btn-sm btn-outline-primary" title="Lihat hasil" aria-label="Lihat hasil"><i class="bx bx-show"></i></a>';
                        }
                    }
                    @endif
                ],
                createdRow: function (row, data) {
                    if (data.is_current_student) {
                        row.classList.add('table-primary');
                        const nameCell = row.cells[2];
                        if (nameCell) {
                            nameCell.insertAdjacentHTML('beforeend', ' <span class="badge bg-label-primary ms-1">Anda</span>');
                        }
                    }
                },
                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari siswa atau kelas...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ peserta',
                    infoEmpty: 'Menampilkan 0 peserta',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada peserta dengan hasil ujian',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
                },
                initComplete: function () {
                    const exportContainer = document.querySelector('#rankingExportButtons');
                    const buttonsContainer = document.querySelector('#rankingTable_wrapper .dt-buttons');
                    if (exportContainer && buttonsContainer) {
                        buttonsContainer.classList.remove('d-none');
                        exportContainer.appendChild(buttonsContainer);
                    }
                }
            });
        });
    </script>
@endsection
