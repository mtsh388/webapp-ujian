@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($formMethod === 'PUT')
        @method('PUT')
    @endif
    <div class="card">
        <div class="card-header border-bottom">
            <h4 class="card-title mb-1">{{ $formTitle }}</h4>
            <p class="text-muted mb-0">Isi jenis biaya dengan teks bebas, lalu tentukan nominal dan sasaran jika diperlukan.</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="fee_type" class="form-label">Jenis Biaya <span class="text-danger">*</span></label>
                    <input type="text" id="fee_type" name="fee_type" maxlength="100" value="{{ old('fee_type', $registrationFee->fee_type_label ?? 'Pendaftaran') }}" class="form-control" placeholder="Contoh: SPP Bulanan, Study Tour, Laboratorium" required>
                    <div class="form-text">Bebas diisi sesuai kebutuhan. Untuk tarif registrasi berbayar, gunakan jenis Pendaftaran.</div>
                    @error('fee_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" id="fee_name_group">
                    <label for="name" class="form-label">Nama Biaya <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" maxlength="255" value="{{ old('name', $registrationFee->name ?? '') }}" class="form-control">
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" id="education_level_group">
                    <label for="education_level" class="form-label">Jenjang Pendidikan <span class="text-danger" id="level_required_mark">*</span></label>
                    <select id="education_level" name="education_level" class="form-select">
                        <option value="">Semua Jenjang</option>
                        @foreach(['SD', 'SMP', 'SMA'] as $level)
                            <option value="{{ $level }}" @selected(old('education_level', $registrationFee->education_level ?? '') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    @error('education_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" id="grade_level_group">
                    <label for="grade_level" class="form-label">Kelas <span class="text-danger" id="grade_required_mark">*</span></label>
                    <select id="grade_level" name="grade_level" class="form-select">
                        <option value="">Semua Kelas</option>
                        @foreach([4, 5, 6, 7, 8, 9, 10, 11, 12] as $grade)
                            <option value="{{ $grade }}" @selected((string) old('grade_level', $registrationFee->grade_level ?? '') === (string) $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                    @error('grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6" id="billing_period_group">
                    <label for="billing_period" class="form-label">Periode Biaya</label>
                    <select id="billing_period" name="billing_period" class="form-select">
                        <option value="">Tidak ditentukan</option>
                        @foreach(['once' => 'Sekali', 'monthly' => 'Bulanan', 'semester' => 'Per Semester', 'yearly' => 'Tahunan'] as $period => $label)
                            <option value="{{ $period }}" @selected(old('billing_period', $registrationFee->billing_period ?? '') === $period)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('billing_period')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="amount" class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                    <input type="number" min="0" max="999999999" step="1" id="amount" name="amount" value="{{ old('amount', $registrationFee->amount ?? '') }}" class="form-control" required>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="is_active" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="is_active" name="is_active" class="form-select" required>
                        <option value="1" @selected((string) old('is_active', isset($registrationFee) ? ($registrationFee->is_active ? '1' : '0') : '1') === '1')>Aktif</option>
                        <option value="0" @selected((string) old('is_active', isset($registrationFee) ? ($registrationFee->is_active ? '1' : '0') : '1') === '0')>Tidak Aktif</option>
                    </select>
                    @error('is_active')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-12" id="description_group">
                    <label for="description" class="form-label">Keterangan</label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="form-control">{{ old('description', $registrationFee->description ?? '') }}</textarea>
                    @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="alert alert-info mt-4 mb-0" id="fee_help">
                Biaya pendaftaran harus ditentukan berdasarkan jenjang dan kelas. Biaya lain dapat berlaku untuk semua siswa atau ditujukan ke jenjang/kelas tertentu.
            </div>
        </div>
        <div class="card-footer border-top d-flex justify-content-end gap-2">
            <a href="{{ route('registration-fees.index') }}" class="btn btn-label-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('fee_type');
    const name = document.getElementById('name');
    const nameGroup = document.getElementById('fee_name_group');
    const level = document.getElementById('education_level');
    const grade = document.getElementById('grade_level');
    const levelGroup = document.getElementById('education_level_group');
    const gradeGroup = document.getElementById('grade_level_group');
    const billing = document.getElementById('billing_period');
    const gradesByLevel = { SD: [4, 5, 6], SMP: [7, 8, 9], SMA: [10, 11, 12] };

    function isRegistrationType() {
        const normalized = type.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return ['registration', 'pendaftaran', 'biaya-pendaftaran'].includes(normalized);
    }

    function filterGrades() {
        const allowed = gradesByLevel[level.value] || [];
        Array.from(grade.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            const show = !level.value || allowed.includes(Number(option.value));
            option.hidden = !show;
            option.disabled = !show;
        });
        if (grade.value && level.value && !allowed.includes(Number(grade.value))) grade.value = '';
    }

    function toggleFields() {
        const registration = isRegistrationType();
        nameGroup.hidden = registration;
        name.required = !registration;
        level.required = registration;
        grade.required = registration;
        document.getElementById('level_required_mark').hidden = !registration;
        document.getElementById('grade_required_mark').hidden = !registration;
        if (registration) {
            name.value = 'Biaya Pendaftaran';
            billing.value = 'once';
            billing.disabled = true;
        } else {
            if (name.value === 'Biaya Pendaftaran') name.value = '';
            billing.disabled = false;
        }
        filterGrades();
    }

    type.addEventListener('input', toggleFields);
    level.addEventListener('change', filterGrades);
    toggleFields();
});
</script>
