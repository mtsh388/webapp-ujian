@extends('template.app')

@section('title', 'Hasil Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card px-3">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Hasil Ujian</h4>
                        <p class="card-subtitle text-muted mb-0">Daftar hasil ujian siswa yang telah selesai dan tersedia untuk ditinjau.</p>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                        <div id="resultsExportButtons"></div>
                        @can('result.view_own')
                            <a href="{{ route('results.my') }}" class="btn btn-primary">
                                <i class="bx bx-user me-1"></i>Hasil Saya
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table id="resultsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Ujian</th>
                            <th>Siswa</th>
                            <th>Nilai</th>
                            <th>Status</th>
                            <th>Dikumpulkan</th>
                            <th>Aksi</th>
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
            const table = document.querySelector('#resultsTable');

            if (!table) {
                return;
            }

            new DataTable(table, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('results.data') }}',
                    type: 'GET'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 75, 100], [10, 25, 50, 75, 100]],
                order: [[5, 'desc']],
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
                        { extend: 'copy', text: '<i class="bx bx-copy me-1"></i>Copy', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                        { extend: 'csv', text: '<i class="bx bx-file me-1"></i>CSV', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                        { extend: 'excel', text: '<i class="bx bx-spreadsheet me-1"></i>Excel', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                        { extend: 'print', text: '<i class="bx bx-printer me-1"></i>Print', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } }
                    ]
                }],
                columns: [
                    { data: 'no', name: 'no', searchable: false, orderable: false },
                    {
                        data: 'exam_title',
                        name: 'exam_title',
                        render: function (data, type, row) {
                            const title = $('<div>').text(data || '-').html();
                            const code = $('<div>').text(row.exam_code || '-').html();
                            return '<div class="fw-semibold">' + title + '</div><div class="small text-muted">' + code + '</div>';
                        }
                    },
                    {
                        data: 'student_name',
                        name: 'student_name',
                        render: function (data) {
                            return $('<div>').text(data || '-').html();
                        }
                    },
                    {
                        data: 'score',
                        name: 'score',
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            const score = Number(data || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            const passing = Number(row.passing_score || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            return '<span class="fw-bold">' + score + '</span><div class="small text-muted">Passing: ' + passing + '</div>';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            if (data === 'expired') return '<span class="badge bg-label-warning">Waktu Habis</span>';
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
                    {
                        data: 'id',
                        name: 'id',
                        searchable: false,
                        orderable: false,
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            return '<div class="d-flex gap-1">' +
                                '<a href="' + row.show_url + '" class="btn btn-sm btn-outline-primary" title="Lihat hasil" aria-label="Lihat hasil"><i class="bx bx-show"></i></a>' +
                                '<a href="' + row.ranking_url + '" class="btn btn-sm btn-outline-success" title="Ranking ujian" aria-label="Ranking ujian"><i class="bx bx-trophy"></i></a>' +
                                '</div>';
                        }
                    }
                ],
                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari ujian atau siswa...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 hasil',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada hasil ujian',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
                },
                initComplete: function () {
                    const exportContainer = document.querySelector('#resultsExportButtons');
                    const buttonsContainer = document.querySelector('#resultsTable_wrapper .dt-buttons');
                    if (exportContainer && buttonsContainer) {
                        buttonsContainer.classList.remove('d-none');
                        exportContainer.appendChild(buttonsContainer);
                    }
                }
            });
        });
    </script>
@endsection
