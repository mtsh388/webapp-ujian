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
            <p class="text-muted mb-0">Atur satu tarif untuk setiap kombinasi jenjang dan kelas.</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="education_level" class="form-label">Jenjang Pendidikan <span class="text-danger">*</span></label>
                    <select id="education_level" name="education_level" class="form-select" required>
                        <option value="">Pilih Jenjang</option>
                        @foreach(['SD', 'SMP', 'SMA'] as $level)
                            <option value="{{ $level }}" @selected(old('education_level', $registrationFee->education_level ?? '') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    @error('education_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="grade_level" class="form-label">Kelas <span class="text-danger">*</span></label>
                    <select id="grade_level" name="grade_level" class="form-select" required>
                        <option value="">Pilih Kelas</option>
                        @foreach([4, 5, 6, 7, 8, 9, 10, 11, 12] as $grade)
                            <option value="{{ $grade }}" @selected((string) old('grade_level', $registrationFee->grade_level ?? '') === (string) $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                    @error('grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="amount" class="form-label">Biaya Pendaftaran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" min="0" max="999999999" step="1" id="amount" name="amount" value="{{ old('amount', $registrationFee->amount ?? '') }}" class="form-control" required>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="is_active" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="is_active" name="is_active" class="form-select" required>
                        <option value="1" @selected((string) old('is_active', isset($registrationFee) && $registrationFee->is_active ? '1' : '1') === '1')>Aktif</option>
                        <option value="0" @selected((string) old('is_active', isset($registrationFee) && !$registrationFee->is_active ? '0' : '1') === '0')>Tidak Aktif</option>
                    </select>
                    @error('is_active')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="alert alert-info mt-4 mb-0">
                Kelas SD: 4–6, SMP: 7–9, SMA: 10–12. Pastikan tarif diisi sesuai kebijakan QSEE.
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
    const level = document.getElementById('education_level');
    const grade = document.getElementById('grade_level');
    const gradesByLevel = { SD: [4, 5, 6], SMP: [7, 8, 9], SMA: [10, 11, 12] };

    function filterGrades() {
        const allowed = gradesByLevel[level.value] || [];
        Array.from(grade.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            option.hidden = !allowed.includes(Number(option.value));
            option.disabled = !allowed.includes(Number(option.value));
        });
        if (grade.value && !allowed.includes(Number(grade.value))) grade.value = '';
    }

    level.addEventListener('change', filterGrades);
    filterGrades();
});
</script>
