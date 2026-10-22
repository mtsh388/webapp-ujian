@extends('template.app')

@section('title', 'Hasil Ujian')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card px-3">
            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Daftar Ujian</h4>
                        <p class="card-subtitle text-muted mb-0">Pilih ujian untuk melihat hasil peserta dan ranking.</p>
                    </div>
                    <div id="resultsExportButtons"></div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table id="resultsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Ujian</th>
                            <th>Nama Ujian</th>
                            <th>Mata Pelajaran</th>
                            <th>Peserta Selesai</th>
                            <th>Hasil Terakhir</th>
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
                        data: 'exam_code',
                        name: 'exam_code',
                        render: function (data) {
                            return $('<div>').text(data || '-').html();
                        }
                    },
                    {
                        data: 'exam_title',
                        name: 'exam_title',
                        render: function (data) {
                            return $('<div>').text(data || '-').html();
                        }
                    },
                    {
                        data: 'subject_name',
                        name: 'subject_name',
                        render: function (data) {
                            return $('<div>').text(data || '-').html();
                        }
                    },
                    {
                        data: 'completed_attempts',
                        name: 'completed_attempts',
                        render: function (data, type) {
                            if (type !== 'display') return Number(data || 0);
                            return '<span class="badge bg-label-primary">' + Number(data || 0).toLocaleString('id-ID') + ' peserta</span>';
                        }
                    },
                    {
                        data: 'latest_submitted_at',
                        name: 'latest_submitted_at',
                        render: function (data, type) {
                            if (!data) return '-';
                            if (type !== 'display') return data;
                            const date = new Date(data.replace(' ', 'T'));
                            if (Number.isNaN(date.getTime())) return $('<div>').text(data).html();
                            return '<div class="text-nowrap">' + date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + '</div><div class="small text-muted">' + date.toLocaleTimeString('id-ID', { hour12: false }) + '</div>';
                        }
                    },
                    {
                        data: 'ranking_url',
                        name: 'ranking_url',
                        searchable: false,
                        orderable: false,
                        render: function (data, type) {
                            if (type !== 'display') return data;
                            return '<a href="' + data + '" class="btn btn-sm btn-outline-success" title="Lihat hasil dan ranking" aria-label="Lihat hasil dan ranking"><i class="bx bx-trophy me-1"></i>Lihat Hasil</a>';
                        }
                    }
                ],
                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari ujian atau mata pelajaran...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ ujian',
                    infoEmpty: 'Menampilkan 0 ujian',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada ujian dengan hasil yang tersedia',
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
