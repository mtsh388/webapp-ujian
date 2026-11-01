@extends('template.app')

@section('title', 'Verifikasi Pembayaran Bimbel')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">Verifikasi Pembayaran Bimbel</h4>
            <p class="text-muted mb-0">Verifikasi setiap cicilan yang dikirim siswa atau orang tua.</p>
        </div>
        <div class="card-body">
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        </div>
        <div class="card-datatable table-responsive">
            <table id="studentFeePaymentsTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Paket / Periode</th>
                        <th>Total</th>
                        <th>Cicilan / Jatuh Tempo</th>
                        <th>Bukti</th>
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
        const table = document.querySelector('#studentFeePaymentsTable');

        if (!table) {
            return;
        }

        new DataTable(table, {
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('student-fee-payments.admin.data') }}',
                type: 'GET'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'asc']],
            scrollX: true,
            dom: '<"row mx-2"<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>t<"row mx-2 mt-3 mb-4"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-end"p>>',
            columns: [
                { data: 'student', name: 'student', orderable: false, searchable: false },
                { data: 'package', name: 'package', orderable: false, searchable: false },
                { data: 'amount', name: 'amount', orderable: false, searchable: false },
                { data: 'installment', name: 'installment', orderable: false, searchable: false },
                { data: 'proof', name: 'proof', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            language: {
                processing: 'Memuat data...',
                search: '',
                searchPlaceholder: 'Cari siswa, kelas, paket, periode, cicilan, status...',
                lengthMenu: '_MENU_',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: 'Belum ada pembayaran bimbel',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: '›',
                    previous: '‹'
                }
            }
        });
    });
</script>
@endsection
