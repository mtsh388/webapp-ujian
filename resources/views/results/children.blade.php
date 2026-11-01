@extends('template.app')

@section('title', 'Hasil Ujian Anak')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Hasil Ujian /</span>
                    Hasil Ujian Anak
                </h4>
                <p class="text-muted mb-0">
                    Pantau hasil dan perkembangan ujian anak Anda.
                </p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Jumlah Anak</span>
                                <h3 class="mb-0 mt-2">
                                    {{ $totalChildren }}
                                </h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-primary p-3">
                                <i class="bx bx-group fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Total Percobaan Ujian</span>
                                <h3 class="mb-0 mt-2">{{ $totalAttempts }}</h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-info p-3">
                                <i class="bx bx-book-open fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted">Ujian Selesai</span>
                                <h3 class="mb-0 mt-2">
                                    {{ $totalFinished }}
                                </h3>
                            </div>
                            <span class="avatar-initial rounded bg-label-success p-3">
                                <i class="bx bx-check-circle fs-3"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="mb-1">Daftar Hasil Ujian Anak</h5>
                    <p class="text-muted mb-0">
                        Informasi ujian seluruh anak yang terhubung dengan akun Anda.
                    </p>
                </div>

                <div class="input-group input-group-merge" style="max-width: 300px;">
                    <span class="input-group-text">
                        <i class="bx bx-search"></i>
                    </span>
                    <input type="search" id="searchResults" class="form-control" placeholder="Cari nama anak atau ujian...">
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="childrenResultsTable">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Anak</th>
                        <th>Nama Ujian</th>
                        <th>Mulai Ujian</th>
                        <th>Selesai Ujian</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="card-footer text-muted" id="childrenResultsFooter"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = new DataTable('#childrenResultsTable', {
                processing: true,
                serverSide: true,
                ajax: @json(route('results.children.data')),
                pageLength: 10,
                order: [[4, 'desc']],
                columns: [
                    { data: 'no', orderable: false, searchable: false },
                    { data: 'student_name', render: (value, type, row) => '<div class="d-flex flex-column"><span class="fw-semibold">' + escapeHtml(value) + '</span><small class="text-muted">' + escapeHtml(row.student_number || '-') + '</small></div>' },
                    { data: 'exam_title', render: value => escapeHtml(value || '-') },
                    { data: 'started_at', render: value => formatDate(value) },
                    { data: 'submitted_at', render: value => formatDate(value) },
                    { data: 'score', render: value => value === null ? '-' : Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
                    { data: 'status', render: value => value === 'Selesai' ? '<span class="badge bg-label-success">Selesai</span>' : '<span class="badge bg-label-warning">Belum Selesai</span>' },
                    { data: 'detail_url', orderable: false, searchable: false, className: 'text-center', render: value => value ? '<a href="' + escapeHtml(value) + '" class="btn btn-sm btn-primary"><i class="bx bx-show-alt me-1"></i>Detail</a>' : '<button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bx bx-time me-1"></i>Menunggu</button>' }
                ],
                language: {
                    processing: 'Memuat data...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada hasil ujian anak.',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                },
                drawCallback: function (settings) {
                    document.getElementById('childrenResultsFooter').textContent = 'Total ' + settings.json.recordsFiltered + ' percobaan ujian.';
                }
            });

            document.getElementById('searchResults').addEventListener('input', function () {
                table.search(this.value).draw();
            });

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[char]);
            }

            function formatDate(value) {
                if (!value) return '-';
                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString('id-ID', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
            }
        });
    </script>
@endsection
