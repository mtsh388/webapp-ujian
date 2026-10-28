<!doctype html>
<html lang="id" class="layout-wide customizer-hide" data-assets-path="{{ asset('assets/sneat') }}/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi | QSEE</title>
    <meta name="description" content="Registrasi akun siswa aplikasi QSEE">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/sneat/img/Logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/fonts/iconify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/css/demo.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/pages/page-auth.css') }}">
    <script src="{{ asset('assets/sneat/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/sneat/js/config.js') }}"></script>
</head>
<body>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner" style="max-width: 760px;">
            <div class="card px-sm-6 px-0">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{ route('login') }}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo"><img src="{{ asset('assets/sneat/img/Logo.jpeg') }}" alt="QSEE" style="width: 48px;height:48px;object-fit:contain;"></span>
                            <span class="app-brand-text demo text-heading fw-bold">QSEE</span>
                        </a>
                    </div>

                    <h4 class="mb-1">Daftar sebagai Siswa</h4>
                    <p class="mb-5">Pilih jenis pendaftaran. Biaya Berbayar mengikuti Master Data Biaya QSEE.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger mb-5">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <ul class="nav nav-pills nav-fill mb-5" id="registrationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ old('registration_type', 'trial') === 'trial' ? 'active' : '' }}" type="button" data-registration-type="trial">Trial</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ old('registration_type') === 'paid' ? 'active' : '' }}" type="button" data-registration-type="paid">Berbayar</button>
                        </li>
                    </ul>

                    <form id="formRegistration" method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="registration_type" id="registration_type" value="{{ old('registration_type', 'trial') }}">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Siswa <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div id="paidFields" class="mt-5 {{ old('registration_type') === 'paid' ? '' : 'd-none' }}">
                            <h5 class="mb-3">Data Siswa</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="255" autocomplete="off" autocapitalize="words" placeholder="Masukkan nama lengkap siswa" readonly onfocus="this.removeAttribute('readonly')">
                                </div>
                                <div class="col-md-6">
                                    <label for="school_name" class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" value="{{ old('school_name') }}" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label for="education_level" class="form-label">Jenjang <span class="text-danger">*</span></label>
                                    <select class="form-select" id="education_level" name="education_level">
                                        <option value="">Pilih Jenjang</option>
                                        @foreach (['SD' => 'SD', 'SMP' => 'SMP', 'SMA' => 'SMA'] as $level => $label)
                                            <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="class_id" class="form-label">Kelas <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id">
                                        <option value="">Pilih Kelas</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}" data-level="{{ $class->education_level }}" data-registration-type="{{ $class->registration_type }}" data-grade="{{ $class->grade_level }}" data-fee="{{ $fees->get($class->education_level . '-' . $class->grade_level)?->amount ?? '' }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }} ({{ $class->education_level }} {{ $class->grade_level }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">Jenis Kelamin</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki" @selected(old('gender') === 'Laki-laki')>Laki-laki</option>
                                        <option value="Perempuan" @selected(old('gender') === 'Perempuan')>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" max="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Nomor HP Siswa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label for="parent_phone" class="form-label">Nomor HP Orang Tua <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="parent_phone" name="parent_phone" value="{{ old('parent_phone') }}" maxlength="30">
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Alamat Siswa</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" maxlength="255">{{ old('address') }}</textarea>
                                </div>
                            </div>

                            <hr class="my-5">
                            <h5 class="mb-3">Data Orang Tua/Wali</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="parent_full_name" class="form-label">Nama Orang Tua/Wali <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="parent_full_name" name="parent_full_name" value="{{ old('parent_full_name') }}" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label for="parent_email" class="form-label">Email Orang Tua/Wali <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="parent_email" name="parent_email" value="{{ old('parent_email') }}" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label for="parent_address" class="form-label">Alamat Orang Tua/Wali</label>
                                    <textarea class="form-control" id="parent_address" name="parent_address" rows="2" maxlength="255">{{ old('parent_address') }}</textarea>
                                </div>
                            </div>

                            <hr class="my-5">
                            <h5 class="mb-3">Biaya Registrasi</h5>
                            <div class="mb-4">
                                <label for="registration_fee_display" class="form-label">Biaya Pendaftaran (Rp)</label>
                                <input type="text" id="registration_fee_display" class="form-control fw-semibold" value="" placeholder="Pilih jenjang dan kelas terlebih dahulu" readonly aria-live="polite">
                                <div id="registrationFeeDescription" class="form-text">Nominal diambil dari Master Data Biaya QSEE dan tidak dapat diubah pada formulir ini.</div>
                                <div class="form-text">Nominal mengikuti biaya pendaftaran yang ditetapkan Admin. Pendaftaran tidak dapat dicicil.</div>
                            </div>
                                                    </div>

                        <div class="mt-5">
                            <button class="btn btn-primary d-grid w-100" type="submit" id="submitRegistration">Daftar Trial</button>
                        </div>
                    </form>

                    <p class="text-center mt-5 mb-0">Sudah punya akun? <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('assets/sneat/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/sneat/vendor/js/menu.js') }}"></script>
<script src="{{ asset('assets/sneat/js/main.js') }}"></script>
<script>
    (() => {
        const typeInput = document.getElementById('registration_type');
        const paidFields = document.getElementById('paidFields');
        const submitButton = document.getElementById('submitRegistration');
        const typeButtons = document.querySelectorAll('[data-registration-type]');
        const paidControls = paidFields.querySelectorAll('input, select, textarea');
        const requiredFields = ['full_name', 'school_name', 'education_level', 'class_id', 'phone', 'parent_phone', 'parent_full_name', 'parent_email'];
        const levelSelect = document.getElementById('education_level');
        const classSelect = document.getElementById('class_id');

        function setType(type) {
            typeInput.value = type;
            paidFields.classList.toggle('d-none', type !== 'paid');
            paidControls.forEach((field) => {
                field.disabled = type !== 'paid';
                field.required = type === 'paid' && requiredFields.includes(field.name);
            });
            typeButtons.forEach((button) => button.classList.toggle('active', button.dataset.registrationType === type));
            submitButton.textContent = type === 'paid' ? 'Lanjut ke Pembayaran Registrasi' : 'Daftar Trial';
        }

        const feeInput = document.getElementById('registration_fee_display');
        const feeDescription = document.getElementById('registrationFeeDescription');
        const rupiah = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        function updateFee() {
            const selected = classSelect.options[classSelect.selectedIndex];
            if (!selected || !selected.value || selected.dataset.level !== levelSelect.value) {
                feeInput.value = '';
                feeInput.placeholder = 'Pilih jenjang dan kelas terlebih dahulu';
                feeDescription.textContent = 'Nominal diambil dari Master Data Biaya QSEE.';
                return;
            }

            const amount = selected.dataset.fee;
            if (!amount) {
                feeInput.value = '';
                feeInput.placeholder = 'Biaya belum tersedia';
                feeDescription.textContent = 'Biaya untuk jenjang dan kelas ini belum tersedia atau tidak aktif. Silakan hubungi Admin QSEE.';
                return;
            }

            feeInput.value = rupiah.format(Number(amount));
            feeInput.placeholder = '';
            feeDescription.textContent = 'Tarif aktif dari Master Data Biaya untuk ' + selected.textContent.trim() + '. Nominal akhir diperiksa kembali oleh server.';
        }

        function filterClasses() {
            const level = levelSelect.value;
            Array.from(classSelect.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const matchesLevel = Boolean(level) && option.dataset.level === level;
                const matchesType = option.dataset.registrationType === 'paid';
                const visible = matchesLevel && matchesType;
                option.hidden = !visible;
                option.disabled = !visible;
            });

            const selected = classSelect.options[classSelect.selectedIndex];
            if (selected && selected.value && (selected.dataset.level !== level || selected.dataset.registrationType !== 'paid')) {
                classSelect.value = '';
            }

            updateFee();
        }

        typeButtons.forEach((button) => button.addEventListener('click', () => setType(button.dataset.registrationType)));
        levelSelect.addEventListener('change', filterClasses);
        classSelect.addEventListener('change', updateFee);
        setType(typeInput.value);
        filterClasses();
    })();
</script>
</body>
</html>
