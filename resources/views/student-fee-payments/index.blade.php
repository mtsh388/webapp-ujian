@extends('template.app')

@section('title', 'Pembayaran Bimbel')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">Pembayaran Bimbel</h4>
            <p class="text-muted mb-0">{{ $isParent ? 'Pantau tagihan bimbel anak dan status cicilannya.' : 'Lihat tagihan bimbel, periode belajar, dan status cicilan Anda.' }}</p>
        </div>
        <div class="card-body">
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if ($isParent)<div id="perpanjang-paket"></div>@endif
            @if ($eligibleStudents->isNotEmpty())
                <div class="border rounded p-3 mb-4">
                    <h5 class="mb-2">Perpanjang Paket Bimbel</h5>
                    <p class="text-muted">Perpanjangan dapat dibuat meskipun masa aktif langganan masih berlaku. Jika masih aktif, periode paket baru dimulai satu hari setelah masa aktif berakhir. Siswa SMA kelas 12 hanya dapat mengambil paket tahunan satu kali dan tidak dapat memperpanjang langganan. Paket semester berlaku sampai 30 Juni atau 31 Desember sesuai semester mulai. Paket tahunan berlaku sampai 30 Juni tahun berikutnya. Nominal mengikuti Master Data Biaya; cicilan maksimal 5 kali.</p>
                    <form method="POST" action="{{ route('student-fee-payments.plan.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Siswa</label>
                                <select class="form-select" name="student_id" id="student_id" required>
                                    @foreach ($eligibleStudents as $eligibleStudent)
                                        <option value="{{ $eligibleStudent->id }}" data-level="{{ $eligibleStudent->classRoom?->education_level }}" data-grade="{{ $eligibleStudent->classRoom?->grade_level }}" data-start-date="{{ $eligibleStudent->is_active && $eligibleStudent->access_expires_at && $eligibleStudent->access_expires_at->greaterThanOrEqualTo(today()) ? $eligibleStudent->access_expires_at->copy()->addDay()->toDateString() : now()->toDateString() }}" @selected((string) old('student_id') === (string) $eligibleStudent->id)>{{ $eligibleStudent->full_name }} — {{ $eligibleStudent->classRoom?->name ?? 'Kelas belum ditentukan' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="package_type" class="form-label">Paket Belajar</label>
                                <select class="form-select" name="package_type" id="package_type" required>
                                    <option value="semester" @selected(old('package_type', 'semester') === 'semester')>Semester (sampai akhir semester)</option>
                                    <option value="yearly" @selected(old('package_type') === 'yearly')>Tahunan (semester ganjil saja, sampai 30 Juni tahun berikutnya)</option>
                                </select>
                                <div id="no-package-message" class="alert alert-warning mt-2 d-none mb-0">Tidak ada paket yang tersedia untuk tanggal mulai periode ini. Siswa SMA kelas 12 hanya dapat mengambil paket tahunan, sedangkan paket tahunan hanya tersedia pada semester ganjil.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="installment_count" class="form-label">Jumlah Pembayaran</label>
                                <select class="form-select" name="installment_count" id="installment_count" required>
                                    @for ($count = 1; $count <= 5; $count++)
                                        <option value="{{ $count }}" @selected((string) old('installment_count', '1') === (string) $count)>{{ $count }} kali{{ $count === 1 ? ' (lunas)' : ' cicilan' }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="promo_code" class="form-label">Kode Promo (Opsional)</label>
                                <input class="form-control" type="text" name="promo_code" id="promo_code" value="{{ old('promo_code') }}" maxlength="100">
                            </div>
                        </div>
                        <div id="billing-preview" class="card border mt-3 d-none">
                            <div class="card-header pb-2">
                                <h6 class="mb-0">Preview Pembayaran</h6>
                                <small class="text-muted">Perhitungan sementara. Tagihan belum dibuat.</small>
                            </div>
                            <div class="card-body pt-2">
                                <div id="billing-preview-loading" class="text-muted small d-none">Menghitung preview pembayaran...</div>
                                <div id="billing-preview-error" class="alert alert-danger d-none mb-0"></div>
                                <div id="billing-preview-content" class="d-none">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6"><small class="text-muted">Siswa</small><div class="fw-semibold" id="preview-student"></div></div>
                                        <div class="col-md-6"><small class="text-muted">Kelas yang ditagihkan</small><div class="fw-semibold" id="preview-class"></div></div>
                                        <div class="col-md-6"><small class="text-muted">Paket belajar</small><div id="preview-package"></div></div>
                                        <div class="col-md-6"><small class="text-muted">Periode</small><div id="preview-period"></div></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-2">
                                            <thead><tr><th>Cicilan</th><th>Jatuh Tempo</th><th class="text-end">Nominal</th></tr></thead>
                                            <tbody id="preview-installments"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold">Total Pembayaran</span>
                                        <span class="fs-5 fw-bold" id="preview-total"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" id="create-plan-button" class="btn btn-primary mt-3" disabled><i class="bx bx-refresh me-1"></i> Buat Tagihan Perpanjangan</button>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const form = document.querySelector('form[action="{{ route('student-fee-payments.plan.store') }}"]');
                            const student = document.getElementById('student_id');
                            const packageSelect = document.getElementById('package_type');
                            const installmentSelect = document.getElementById('installment_count');
                            const promoInput = document.getElementById('promo_code');
                            const previewCard = document.getElementById('billing-preview');
                            const previewLoading = document.getElementById('billing-preview-loading');
                            const previewError = document.getElementById('billing-preview-error');
                            const previewContent = document.getElementById('billing-preview-content');
                            const submitButton = document.getElementById('create-plan-button');
                            let requestController = null;
                            let debounceTimer = null;
                            let previewRequestId = 0;

                            if (!form || !student || !packageSelect || !installmentSelect) return;

                            const rupiah = value => new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0
                            }).format(value);

                            const date = value => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            });

                            function updatePackages() {
                                const selected = student.options[student.selectedIndex];
                                const level = selected?.dataset.level || '';
                                const grade = Number(selected?.dataset.grade || 0);
                                const startDate = selected?.dataset.startDate ? new Date(selected.dataset.startDate + 'T00:00:00') : new Date();
                                const yearlyAvailable = startDate.getMonth() + 1 >= 7;
                                const semesterAvailable = !(level === 'SMA' && grade === 12);

                                Array.from(packageSelect.options).forEach(function (option) {
                                    if (!option.value) return;
                                    option.hidden = option.value === 'semester' ? !semesterAvailable : !yearlyAvailable;
                                    option.disabled = option.hidden;
                                });

                                if (packageSelect.selectedOptions[0]?.disabled) {
                                    packageSelect.value = semesterAvailable ? 'semester' : (yearlyAvailable ? 'yearly' : '');
                                }

                                const available = semesterAvailable || yearlyAvailable;
                                packageSelect.disabled = !available;
                                document.getElementById('no-package-message')?.classList.toggle('d-none', available);
                                if (!available) {
                                    submitButton.disabled = true;
                                    previewCard.classList.add('d-none');
                                }
                                return available;
                            }

                            async function loadPreview() {
                                if (!updatePackages() || !packageSelect.value) return;

                                if (requestController) requestController.abort();
                                const controller = new AbortController();
                                requestController = controller;
                                const requestId = ++previewRequestId;
                                submitButton.disabled = true;
                                previewCard.classList.remove('d-none');
                                previewLoading.classList.remove('d-none');
                                previewError.classList.add('d-none');
                                previewContent.classList.add('d-none');

                                const payload = new FormData(form);
                                try {
                                    const response = await fetch("{{ route('student-fee-payments.plan.preview') }}", {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                                        },
                                        body: payload,
                                        signal: controller.signal
                                    });
                                    const data = await response.json();
                                    if (requestId !== previewRequestId) return;
                                    if (!response.ok) {
                                        const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Preview pembayaran tidak dapat dihitung.'];
                                        throw new Error(messages.join(' '));
                                    }

                                    const quote = data.preview;
                                    document.getElementById('preview-student').textContent = quote.student_name;
                                    document.getElementById('preview-class').textContent = quote.class_name + ' (' + quote.education_level + ' kelas ' + quote.grade_level + ')';
                                    document.getElementById('preview-package').textContent = quote.package_label + ' · ' + quote.installment_count + ' kali pembayaran';
                                    document.getElementById('preview-period').textContent = date(quote.period_starts_at) + ' – ' + date(quote.period_ends_at);
                                    document.getElementById('preview-installments').innerHTML = quote.installments.map(item =>
                                        '<tr><td>' + item.number + ' dari ' + quote.installment_count + '</td><td>' + date(item.due_date) + '</td><td class="text-end">' + rupiah(item.amount) + '</td></tr>'
                                    ).join('');
                                    document.getElementById('preview-total').textContent = rupiah(quote.amount);
                                    previewContent.classList.remove('d-none');
                                    submitButton.disabled = false;
                                } catch (error) {
                                    if (error.name === 'AbortError' || requestId !== previewRequestId) return;
                                    previewError.textContent = error.message;
                                    previewError.classList.remove('d-none');
                                } finally {
                                    if (requestId === previewRequestId) {
                                        previewLoading.classList.add('d-none');
                                    }
                                }
                            }

                            function schedulePreview() {
                                clearTimeout(debounceTimer);
                                debounceTimer = setTimeout(loadPreview, 250);
                            }

                            if (window.jQuery) {
                                window.jQuery(student).on('change', schedulePreview);
                                window.jQuery(packageSelect).on('change', schedulePreview);
                                window.jQuery(installmentSelect).on('change', schedulePreview);
                            } else {
                                student.addEventListener('change', schedulePreview);
                                packageSelect.addEventListener('change', schedulePreview);
                                installmentSelect.addEventListener('change', schedulePreview);
                            }

                            promoInput?.addEventListener('input', schedulePreview);
                            form.addEventListener('submit', function (event) {
                                if (submitButton.disabled) {
                                    event.preventDefault();
                                }
                            });

                            updatePackages();
                            loadPreview();
                        });
                    </script>
                </div>
            @endif
            <form method="GET" action="{{ route('student-fee-payments.index') }}" class="row g-2 align-items-center mb-3" role="search">
                <div class="col-md-8 col-lg-6">
                    <label for="transaction-search" class="form-label">Cari Tagihan Bimbel</label>
                    <div class="input-group">
                        <input type="search" id="transaction-search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama siswa, kelas, paket, periode, atau nominal..." autocomplete="off">
                        <button class="btn btn-outline-primary" type="submit"><i class="bx bx-search me-1"></i> Cari</button>
                        @if ($search !== '')
                            <a href="{{ route('student-fee-payments.index') }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                    </div>
                </div>
            </form>
            @forelse ($transactions as $transaction)
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">{{ $transaction->student?->full_name ?? 'Siswa' }} — {{ $transaction->package_type === 'semester' ? '1 semester' : '1 tahun' }}</h5>
                            <div class="text-muted small">{{ $transaction->class_name_snapshot ?: 'Kelas belum tercatat' }} · Periode {{ $transaction->period_starts_at->format('d M Y') }}–{{ $transaction->period_ends_at->format('d M Y') }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted small">Total tagihan</div>
                            <div class="fs-5 fw-semibold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</div>
                            <div class="small text-muted">Dibayar Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Rincian Kelas</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @foreach ($transaction->details ?? [] as $detail)
                                    <tr><td>{{ $detail['name'] ?? 'Belajar' }} — {{ $detail['education_level'] ?? '' }} Kelas {{ $detail['grade_level'] ?? '' }}
                                        @if ((int) ($detail['discount_amount'] ?? 0) > 0)<div class="small text-success">Nominal Rp {{ number_format((int) $detail['amount'], 0, ',', '.') }} · Diskon Rp {{ number_format((int) $detail['discount_amount'], 0, ',', '.') }}</div>@endif
                                    </td><td class="text-end">Rp {{ number_format((int) ($detail['amount_due'] ?? 0), 0, ',', '.') }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th>Cicilan</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th><th>Bukti / Aksi</th></tr></thead>
                            <tbody>
                                @foreach ($transaction->installments as $installment)
                                    <tr>
                                        <td>{{ $installment->installment_number }} dari {{ $transaction->installment_count }}</td>
                                        <td class="text-nowrap">{{ $installment->due_date?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-nowrap">Rp {{ number_format($installment->amount, 0, ',', '.') }}</td>
                                        <td>
                                            @php($labels = ['awaiting_payment' => 'Menunggu Pembayaran', 'pending_verification' => 'Menunggu Verifikasi', 'verified' => 'Disetujui', 'rejected' => 'Ditolak'])
                                            <span class="badge {{ $installment->status === 'verified' ? 'bg-label-success' : ($installment->status === 'pending_verification' ? 'bg-label-warning' : ($installment->status === 'rejected' ? 'bg-label-danger' : 'bg-label-secondary')) }}">{{ $labels[$installment->status] ?? $installment->status }}</span>
                                            @if ($installment->rejection_reason)<div class="small text-danger mt-1">{{ $installment->rejection_reason }}</div>@endif
                                        </td>
                                        <td>
                                            @if (in_array($installment->status, ['awaiting_payment', 'rejected'], true))
                                                <form method="POST" action="{{ route('student-fee-payments.proof.store', $installment) }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                                    @csrf
                                                    <input type="file" name="payment_proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
                                                    <button type="submit" class="btn btn-sm btn-primary">Kirim Bukti</button>
                                                </form>
                                            @elseif ($installment->status === 'pending_verification')
                                                <span class="text-muted small">Bukti sudah dikirim</span>
                                            @else
                                                <span class="text-success small">Pembayaran terverifikasi</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <h5>{{ $search !== '' ? 'Tagihan Tidak Ditemukan' : 'Belum Ada Tagihan Bimbel' }}</h5>
                    <p class="text-muted mb-0">{{ $search !== '' ? 'Tidak ada tagihan yang cocok dengan pencarian Anda. Coba kata kunci lain.' : 'Setelah pembayaran registrasi disetujui, formulir perpanjangan paket akan tersedia selama tidak ada tagihan bimbel yang belum lunas.' }}</p>
                </div>
            @endforelse
            @if ($transactions->hasPages())<div class="mt-3">{{ $transactions->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
