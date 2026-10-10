@extends('template.app')

@section('title', 'Data Orang Tua')

@section('content')
    <style>
        .parent-status-switch {
            width: 2.75rem !important;
            height: 1.5rem !important;
            cursor: pointer;
            margin-top: 0 !important;
        }

        .parent-status-switch:checked {
            background-size: 1.25rem 1.25rem;
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
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
            <div class="alert alert-danger alert-dismissible" role="alert">
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

        <div class="card px-3">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                    <div>
                        <h4 class="card-title mb-1">
                            Data Orang Tua
                        </h4>

                        <p class="card-subtitle text-muted mb-0">
                            Kelola data orang tua atau wali siswa.
                        </p>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2">

                        <div id="parentsExportButtons"></div>

                        @can('parent.create')
                            <a
                                href="{{ route('parents.create') }}"
                                class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>
                                Tambah Orang Tua
                            </a>
                        @endcan

                    </div>

                </div>
            </div>

            <div class="card-datatable table-responsive">

                <table
                    id="parentsTable"
                    class="table table-bordered">

                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>No. Telepon</th>
                        <th>Jumlah Siswa</th>
                        <th>Status</th>
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
            const table = document.querySelector('#parentsTable');

            if (!table) {
                return;
            }

            new DataTable(table, {
                processing: true,
                serverSide: true,

                ajax: {
                    url: '{{ route('parents.data') }}',
                    type: 'GET'
                },

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 75, 100],
                    [10, 25, 50, 75, 100]
                ],

                order: [
                    [1, 'asc']
                ],

                responsive: true,

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

                buttons: [
                    {
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle',
                        text: '<i class="bx bx-export me-1"></i>Export',
                        buttons: [
                            {
                                extend: 'print',
                                text: '<i class="bx bx-printer me-1"></i>Print',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="bx bx-copy me-1"></i>Copy',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                }
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bx bx-file me-1"></i>CSV',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bx bx-spreadsheet me-1"></i>Excel',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bx bxs-file-pdf me-1"></i>PDF',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                }
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
                        data: 'full_name',
                        name: 'full_name',
                        render: function (data) {
                            if (!data) {
                                return '<span class="text-muted">-</span>';
                            }

                            return `
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="bx bx-user"></i>
                                        </span>
                                    </div>

                                    <div>
                                        <span class="fw-medium">${data}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },

                    {
                        data: 'phone',
                        name: 'phone',
                        render: function (data) {
                            if (!data) {
                                return '<span class="text-muted">-</span>';
                            }

                            return `
                                <span>
                                    ${data}
                                </span>
                            `;
                        }
                    },

                    {
                        data: 'students_count',
                        name: 'students_count',
                        searchable: false,
                        render: function (data) {
                            return `
                                <span class="badge bg-label-primary">
                                    ${data ?? 0} Siswa
                                </span>
                            `;
                        }
                    },

                    {
                        data: 'is_active',
                        name: 'is_active',
                        searchable: false,
                        orderable: false,
                        render: function (data, type, row) {
                            const checked = data ? 'checked' : '';
                            const label = data ? 'Aktif' : 'Tidak Aktif';

                            return `
                                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                    <input
                                        class="form-check-input parent-status-switch"
                                        type="checkbox"
                                        role="switch"
                                        data-id="${row.id}"
                                        ${checked}>
                                    <label class="form-check-label mb-0">
                                        ${label}
                                    </label>
                                </div>
                            `;
                        }
                    },

                    {
                        data: 'id',
                        name: 'id',
                        searchable: false,
                        orderable: false,
                        render: function (id) {
                            return `
                                <div class="d-flex align-items-center gap-1">

                                    <a
                                        href="/master-data/orang-tua/${id}"
                                        class="btn btn-sm btn-icon text-secondary rounded-pill"
                                        data-bs-toggle="tooltip"
                                        title="Detail">
                                        <i class="bx bx-show"></i>
                                    </a>

                                    @can('parent.update')
                            <a
                                href="/master-data/orang-tua/${id}/edit"
                                            class="btn btn-sm btn-icon text-primary rounded-pill"
                                            data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                    @endcan

                            @can('parent.delete')
                            <button
                                type="button"
                                class="btn btn-sm btn-icon text-danger rounded-pill"
                                data-bs-toggle="tooltip"
                                title="Hapus"
                                onclick="deleteParent(${id})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    @endcan

                            </div>
`;
                        }
                    }
                ],

                language: {
                    processing: 'Memuat data...',
                    search: '',
                    searchPlaceholder: 'Cari...',
                    lengthMenu: '_MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data orang tua',
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
                    const tooltipTriggerList = document.querySelectorAll(
                        '[data-bs-toggle="tooltip"]'
                    );

                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                },

                initComplete: function () {
                    const exportContainer = document.querySelector(
                        '#parentsExportButtons'
                    );

                    const buttonsContainer = document.querySelector(
                        '#parentsTable_wrapper .dt-buttons'
                    );

                    if (exportContainer && buttonsContainer) {
                        exportContainer.appendChild(buttonsContainer);
                    }
                }
            });
        });

        document.addEventListener('change', function (event) {
            const switchElement = event.target.closest(
                '.parent-status-switch'
            );

            if (!switchElement) {
                return;
            }

            const id = switchElement.dataset.id;
            const isActive = switchElement.checked;

            updateParentStatus(
                id,
                isActive,
                switchElement
            );
        });

        function updateParentStatus(
            id,
            isActive,
            switchElement
        ) {
            const previousState = !isActive;

            const csrfToken = document.querySelector(
                'meta[name="csrf-token"]'
            );

            if (!csrfToken) {
                switchElement.checked = previousState;

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'CSRF token tidak ditemukan.'
                });

                return;
            }

            Swal.fire({
                title: 'Memperbarui Status...',
                text: 'Mohon tunggu sebentar.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            fetch(
                `/master-data/orang-tua/${id}/status`,
                {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    },
                    body: JSON.stringify({
                        is_active: isActive
                    })
                }
            )
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(
                                data.message ||
                                'Gagal memperbarui status.'
                            );
                        }

                        return data;
                    });
                })
                .then(function (response) {
                    if (!response.status) {
                        throw new Error(
                            response.message ||
                            'Gagal memperbarui status.'
                        );
                    }

                    const label = switchElement
                        .closest('.form-check')
                        .querySelector('.form-check-label');

                    label.textContent = response.is_active
                        ? 'Aktif'
                        : 'Tidak Aktif';

                    switchElement.checked =
                        response.is_active;

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                })
                .catch(function (error) {
                    switchElement.checked =
                        previousState;

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message ||
                            'Terjadi kesalahan saat memperbarui status.'
                    });
                });
        }

        function deleteParent(id) {
            Swal.fire({
                title: 'Hapus Orang Tua?',
                text: 'Data akan dipindahkan ke tempat sampah.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const csrfToken = document.querySelector(
                    'meta[name="csrf-token"]'
                );

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

                fetch(
                    `/master-data/orang-tua/${id}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.getAttribute(
                                'content'
                            )
                        }
                    }
                )
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw new Error(
                                    data.message ||
                                    'Gagal menghapus orang tua.'
                                );
                            }

                            return data;
                        });
                    })
                    .then(function (response) {
                        if (!response.status) {
                            throw new Error(
                                response.message ||
                                'Gagal menghapus orang tua.'
                            );
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function () {
                            DataTable
                                .tables({
                                    api: true
                                })
                                .table('#parentsTable')
                                .ajax.reload(null, false);
                        });
                    })
                    .catch(function (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message ||
                                'Terjadi kesalahan saat menghapus orang tua.'
                        });
                    });
            });
        }
    </script>
@endsection
