@extends('template.app')

@section('title', 'Kode Promo')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-1">{{ $promoCode->exists ? 'Edit Kode Promo' : 'Buat Kode Promo' }}</h4>
                        <p class="text-muted mb-0">Kode promo menggunakan teks bebas dan masa berlaku hingga tingkat tanggal serta waktu.</p>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ $promoCode->exists ? route('promo-codes.update', $promoCode) : route('promo-codes.store') }}">
                            @csrf
                            @if ($promoCode->exists) @method('PUT') @endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="code" class="form-label">Kode Promo *</label>
                                    <input id="code" name="code" class="form-control" value="{{ old('code', $promoCode->code) }}" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nama/Keterangan</label>
                                    <input id="name" name="name" class="form-control" value="{{ old('name', $promoCode->name) }}" maxlength="255">
                                </div>
                                <div class="col-md-4">
                                    <label for="discount_type" class="form-label">Jenis Diskon *</label>
                                    <select id="discount_type" name="discount_type" class="form-select" required>
                                        <option value="percentage" @selected(old('discount_type', $promoCode->discount_type) === 'percentage')>Persentase (%)</option>
                                        <option value="fixed" @selected(old('discount_type', $promoCode->discount_type) === 'fixed')>Nominal (Rp)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="discount_value" class="form-label">Nilai Diskon *</label>
                                    <input id="discount_value" name="discount_value" type="number" min="1" max="100000000" class="form-control" value="{{ old('discount_value', $promoCode->discount_value) }}" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $promoCode->exists ? $promoCode->is_active : true))>
                                        <label class="form-check-label" for="is_active">Kode promo aktif</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="starts_at" class="form-label">Tanggal dan Waktu Mulai *</label>
                                    <input id="starts_at" name="starts_at" type="datetime-local" class="form-control" value="{{ old('starts_at', $promoCode->starts_at?->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ends_at" class="form-label">Tanggal dan Waktu Berakhir *</label>
                                    <input id="ends_at" name="ends_at" type="datetime-local" class="form-control" value="{{ old('ends_at', $promoCode->ends_at?->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">{{ $promoCode->exists ? 'Simpan Perubahan' : 'Simpan Kode Promo' }}</button>
                                @if ($promoCode->exists)
                                    <a href="{{ route('promo-codes.index') }}" class="btn btn-outline-secondary">Batal</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">Daftar Kode Promo</h5>
                            <p class="text-muted mb-0">Pencarian, pengurutan, dan pagination diproses di server.</p>
                        </div>
                        <div id="promoCodesExportButtons"></div>
                    </div>
                    <div class="card-datatable table-responsive">
                        <table id="promoCodesTable" class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Promo</th>
                                    <th>Diskon</th>
                                    <th>Mulai</th>
                                    <th>Berakhir</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableElement = document.querySelector('#promoCodesTable');

            if (!tableElement || typeof DataTable === 'undefined') {
                return;
            }

            const canUpdatePromoStatus = @json(auth()->user()->can('promo-code.update'));
            const promoStatusUrl = @json(route('promo-codes.status', ['promoCode' => '__ID__']));

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

            const table = new DataTable(tableElement, {
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 75, 100], [10, 25, 50, 75, 100]],
                order: [[3, 'desc']],
                ajax: {
                    url: '{{ route('promo-codes.data') }}',
                    type: 'GET'
                },
                dom:
                    '<"row mx-2"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"l>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-2 mt-3 mb-4"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start"i>' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"p>' +
                    '>',
                columns: [
                    {
                        data: null,
                        searchable: false,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        }
                    },
                    {
                        data: 'code',
                        name: 'code',
                        render: function (data, type, row) {
                            return '<div class="fw-semibold">' + escapeHtml(data) + '</div>' +
                                (row.name ? '<small class="text-muted">' + escapeHtml(row.name) + '</small>' : '');
                        }
                    },
                    {
                        data: 'discount_value',
                        name: 'discount_type',
                        render: function (value, type, row) {
                            return row.discount_type === 'percentage'
                                ? escapeHtml(value) + '%'
                                : 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    },
                    { data: 'starts_at', name: 'starts_at' },
                    { data: 'ends_at', name: 'ends_at' },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        searchable: false,
                        render: function (active, type, row) {
                            if (!canUpdatePromoStatus) {
                                return active
                                    ? '<span class="badge bg-label-success">Aktif</span>'
                                    : '<span class="badge bg-label-secondary">Tidak Aktif</span>';
                            }

                            return '<div class="d-flex align-items-center gap-2">' +
                                '<div class="form-check form-switch mb-0">' +
                                '<input type="checkbox" class="form-check-input js-promo-status" role="switch" data-id="' + row.id + '" ' +
                                (active ? 'checked ' : '') +
                                'aria-label="Ubah status kode promo ' + escapeHtml(row.code) + '">' +
                                '</div>' +
                                '<span class="small ' + (active ? 'text-success' : 'text-muted') + '">' +
                                (active ? 'Aktif' : 'Tidak Aktif') + '</span>' +
                                '</div>';
                        }
                    },
                    {
                        data: 'id',
                        searchable: false,
                        orderable: false,
                        render: function (id) {
                            let actions = '<div class="d-flex align-items-center gap-1">';
                            @can('promo-code.update')
                                actions += '<a href="/master-data/kode-promo/' + id + '/edit" class="btn btn-sm btn-icon text-primary rounded-pill" title="Edit"><i class="bx bx-edit"></i></a>';
                            @endcan
                            @can('promo-code.delete')
                                actions += '<button type="button" class="btn btn-sm btn-icon text-danger rounded-pill js-delete-promo" data-id="' + id + '" title="Hapus"><i class="bx bx-trash"></i></button>';
                            @endcan
                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari kode promo...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada kode promo',
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
                drawCallback: function () {
                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                        if (window.bootstrap) new bootstrap.Tooltip(element);
                    });
                }
            });

            document.addEventListener('change', function (event) {
                const toggle = event.target.closest('.js-promo-status');

                if (!toggle) {
                    return;
                }

                const previousValue = !toggle.checked;
                const requestedValue = toggle.checked;
                const id = toggle.dataset.id;
                const csrfToken = document.querySelector('meta[name="csrf-token"]');

                const revertToggle = function () {
                    toggle.checked = previousValue;
                };

                if (!csrfToken) {
                    revertToggle();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'CSRF token tidak ditemukan.'
                    });
                    return;
                }

                Swal.fire({
                    title: requestedValue ? 'Aktifkan kode promo?' : 'Nonaktifkan kode promo?',
                    text: requestedValue
                        ? 'Kode promo akan dapat digunakan selama masa berlakunya.'
                        : 'Kode promo tidak dapat digunakan selama statusnya nonaktif.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: requestedValue ? 'Ya, aktifkan' : 'Ya, nonaktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        revertToggle();
                        return;
                    }

                    toggle.disabled = true;

                    fetch(promoStatusUrl.replace('__ID__', encodeURIComponent(id)), {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                        },
                        body: JSON.stringify({
                            is_active: requestedValue ? 1 : 0
                        })
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Gagal mengubah status kode promo.');
                                }
                                return data;
                            });
                        })
                        .then(function (response) {
                            if (!response.status) {
                                throw new Error(response.message || 'Gagal mengubah status kode promo.');
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message,
                                timer: 1300,
                                showConfirmButton: false
                            });

                            table.ajax.reload(null, false);
                        })
                        .catch(function (error) {
                            revertToggle();
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: error.message || 'Terjadi kesalahan saat mengubah status kode promo.'
                            });
                        })
                        .finally(function () {
                            toggle.disabled = false;
                        });
                });
            });

            document.addEventListener('click', function (event) {
                const button = event.target.closest('.js-delete-promo');

                if (!button) {
                    return;
                }

                const id = button.dataset.id;
                const csrfToken = document.querySelector('meta[name="csrf-token"]');

                Swal.fire({
                    title: 'Hapus kode promo?',
                    text: 'Kode promo yang dihapus tidak dapat digunakan lagi.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

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
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    fetch('/master-data/kode-promo/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                        }
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Gagal menghapus kode promo.');
                                }
                                return data;
                            });
                        })
                        .then(function (response) {
                            if (!response.status) {
                                throw new Error(response.message || 'Gagal menghapus kode promo.');
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            table.ajax.reload(null, false);
                        })
                        .catch(function (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: error.message || 'Terjadi kesalahan saat menghapus kode promo.'
                            });
                        });
                });
            });
        });
    </script>
@endsection
