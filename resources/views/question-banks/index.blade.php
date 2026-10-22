@extends('template.app')

@section('title', 'Bank Soal')

@section('content')
    <div class="container-fluid py-3">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1">Bank Soal</h4>
                        <p class="text-muted mb-0">Kelola soal dan pilihan jawaban.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="deleteSelectedQuestions" class="btn btn-outline-danger" disabled>
                            <i class="bx bx-trash me-1"></i> Hapus Pilihan (<span id="selectedQuestionCount">0</span>)
                        </button>
                        <a href="{{ route('questions.import') }}" class="btn btn-outline-primary">
                            <i class="bx bx-import me-1"></i> Import Word
                        </a>
                        <a href="{{ route('questions.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Tambah Soal
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Dropdown Filter Custom (Tanpa Submit Manual) -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <label class="form-label">Mata Pelajaran</label>
                        <select id="filter_subject" class="form-select">
                            <option value="">Semua Mata Pelajaran</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Tipe</label>
                        <select id="filter_type" class="form-select">
                            <option value="">Semua</option>
                            <option value="multiple_choice">Pilihan Ganda</option>
                            <option value="multiple_select">Pilih Semua yang Benar</option>
                            <option value="statement_true_false">Benar / Salah per Pernyataan</option>
                            <option value="true_false">Benar / Salah</option>
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Kesulitan</label>
                        <select id="filter_difficulty" class="form-select">
                            <option value="">Semua</option>
                            <option value="easy">Mudah</option>
                            <option value="medium">Sedang</option>
                            <option value="hard">Sulit</option>
                        </select>
                    </div>
                </div>

                <form id="bulkDeleteQuestionsForm" method="POST" action="{{ route('questions.bulk-destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="table-responsive">
                    <table id="questionsTable" class="table table-hover align-middle border-top w-100">
                        <thead>
                        <tr>
                            <th width="40" class="text-center"><input type="checkbox" class="form-check-input" id="selectAllQuestions" aria-label="Pilih semua soal pada halaman ini"></th>
                            <th width="50">No</th>
                            <th>Kode</th>
                            <th>Soal</th>
                            <th>Mata Pelajaran</th>
                            <th>Tipe</th>
                            <th>Kesulitan</th>
                            <th>Media</th>
                            <th>Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                        </thead>
                    </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableElement =
                document.querySelector('#questionsTable');

            if (!tableElement) {
                return;
            }

            const selectAll = document.querySelector('#selectAllQuestions');
            const deleteSelectedButton = document.querySelector('#deleteSelectedQuestions');
            const selectedCount = document.querySelector('#selectedQuestionCount');
            const bulkDeleteForm = document.querySelector('#bulkDeleteQuestionsForm');

            function selectedCheckboxes() {
                return Array.from(tableElement.querySelectorAll('.question-row-checkbox:checked'));
            }

            function updateSelectionState() {
                const checkboxes = Array.from(tableElement.querySelectorAll('.question-row-checkbox'));
                const selected = selectedCheckboxes();
                selectedCount.textContent = selected.length;
                deleteSelectedButton.disabled = selected.length === 0;
                selectAll.checked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
                selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
            }

            const dataTable = new DataTable(
                tableElement,
                {
                    processing: true,
                    serverSide: true,

                    ajax: {
                        url: '{{ route('questions.data') }}',
                        type: 'GET',

                        data: function (d) {
                            d.subject_id =
                                document.querySelector(
                                    '#filter_subject'
                                ).value;

                            d.type =
                                document.querySelector(
                                    '#filter_type'
                                ).value;

                            d.difficulty =
                                document.querySelector(
                                    '#filter_difficulty'
                                ).value;
                        }
                    },

                    pageLength: 10,

                    lengthMenu: [
                        [10, 25, 50, 75, 100],
                        [10, 25, 50, 75, 100]
                    ],

                    order: [
                        [1, 'desc']
                    ],

                    pagingType: 'full_numbers',

                    responsive: true,

                    layout: {
                        topStart: 'pageLength',
                        topEnd: 'search',
                        bottomStart: 'info',
                        bottomEnd: 'paging'
                    },

                    columns: [
                        {
                            data: 'selection',
                            name: 'selection',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        {
                            data: 'question',
                            name: 'question',
                            orderable: false
                        },
                        {
                            data: 'subject_name',
                            name: 'subject_name',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'type',
                            name: 'type'
                        },
                        {
                            data: 'difficulty',
                            name: 'difficulty'
                        },
                        {
                            data: 'media_count',
                            name: 'media_count',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'is_active',
                            name: 'is_active',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {
                        processing: `
                            <div class="d-flex align-items-center gap-2">
                                <div
                                    class="spinner-border spinner-border-sm text-primary"
                                    role="status">
                                </div>
                                <span>Memuat data...</span>
                            </div>
                        `,

                        search: '',

                        searchPlaceholder:
                            'Cari soal atau kode...',

                        lengthMenu:
                            'Tampilkan _MENU_ data',

                        info:
                            'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',

                        infoEmpty:
                            'Menampilkan 0 sampai 0 dari 0 data',

                        zeroRecords:
                            'Data tidak ditemukan',

                        emptyTable:
                            'Belum ada data bank soal',

                        paginate: {
                            first: 'Pertama',
                            last: 'Terakhir',
                            next: 'Berikutnya',
                            previous: 'Sebelumnya'
                        }
                    }
                }
            );

            tableElement.addEventListener('change', function (event) {
                if (event.target.matches('.question-row-checkbox')) {
                    updateSelectionState();
                }
            });

            selectAll.addEventListener('change', function () {
                tableElement.querySelectorAll('.question-row-checkbox').forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                updateSelectionState();
            });

            dataTable.on('draw', function () {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                updateSelectionState();
            });

            deleteSelectedButton.addEventListener('click', async function () {
                const selectedIds = selectedCheckboxes().map((checkbox) => checkbox.value);
                if (selectedIds.length === 0) return;
                const result = await Swal.fire({
                    title: 'Hapus soal terpilih?',
                    text: selectedIds.length + ' soal akan dihapus. Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33'
                });
                if (!result.isConfirmed) return;
                bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
                selectedIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    bulkDeleteForm.appendChild(input);
                });
                bulkDeleteForm.submit();
            });

            document
                .querySelectorAll(
                    '#filter_subject, #filter_type, #filter_difficulty'
                )
                .forEach(function (element) {
                    element.addEventListener(
                        'change',
                        function () {
                            dataTable.ajax.reload(
                                null,
                                false
                            );
                        }
                    );
                });
        });
    </script>
@endsection

