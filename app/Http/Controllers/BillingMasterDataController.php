<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\BillingBankAccount;
use App\Models\BillingFeeRate;
use App\Models\BillingFeeType;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingMasterDataController extends Controller
{
    public function index(): View
    {
        return view('billing.master-data', [
            'feeTypes' => BillingFeeType::query()->withCount('rates')->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->withCount('feeRates')->orderByDesc('starts_on')->get(),
            'feeRates' => BillingFeeRate::query()->with(['feeType', 'academicYear', 'classRoom'])->latest()->get(),
            'classes' => ClassRoom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'education_level', 'grade_level']),
            'bankAccounts' => BillingBankAccount::query()->orderBy('bank_name')->get(),
        ]);
    }

    public function storeFeeType(Request $request): RedirectResponse
    {
        $id = $request->input('id');
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:billing_fee_types,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('billing_fee_types', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:120', Rule::unique('billing_fee_types', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $record = $id ? BillingFeeType::query()->findOrFail($id) : new BillingFeeType();
        $record->fill($data);
        $record->is_active = $request->boolean('is_active');
        $record->save();

        return back()->with('success', 'Jenis biaya berhasil disimpan.');
    }

    public function destroyFeeType(BillingFeeType $feeType): RedirectResponse
    {
        if ($feeType->rates()->exists()) {
            return back()->with('error', 'Jenis biaya tidak dapat dihapus karena sudah dipakai pada tarif. Nonaktifkan saja.');
        }
        $feeType->delete();

        return back()->with('success', 'Jenis biaya dihapus.');
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $id = $request->input('id');
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:40', Rule::unique('academic_years', 'name')->ignore($id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $record = $id ? AcademicYear::query()->findOrFail($id) : new AcademicYear();
        $record->fill($data);
        $record->is_active = $request->boolean('is_active');
        $record->save();

        return back()->with('success', 'Tahun ajaran berhasil disimpan.');
    }

    public function destroyAcademicYear(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->feeRates()->exists()) {
            return back()->with('error', 'Tahun ajaran tidak dapat dihapus karena sudah dipakai pada tarif. Nonaktifkan saja.');
        }
        $academicYear->delete();

        return back()->with('success', 'Tahun ajaran dihapus.');
    }

    public function storeFeeRate(Request $request): RedirectResponse
    {
        $id = $request->input('id');
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:billing_fee_rates,id'],
            'fee_type_id' => ['required', 'integer', 'exists:billing_fee_types,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'period_type' => ['required', 'in:semester,year'],
            'period_label' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $record = $id ? BillingFeeRate::query()->findOrFail($id) : new BillingFeeRate();
        $record->fill($data);
        $record->is_active = $request->boolean('is_active');
        $record->save();

        return back()->with('success', 'Tarif biaya berhasil disimpan.');
    }

    public function destroyFeeRate(BillingFeeRate $feeRate): RedirectResponse
    {
        if ($feeRate->invoices()->exists()) {
            return back()->with('error', 'Tarif sudah dipakai pada tagihan dan tidak dapat dihapus. Nonaktifkan saja.');
        }
        $feeRate->delete();

        return back()->with('success', 'Tarif biaya dihapus.');
    }

    public function storeBankAccount(Request $request): RedirectResponse
    {
        $id = $request->input('id');
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:billing_bank_accounts,id'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $record = $id ? BillingBankAccount::query()->findOrFail($id) : new BillingBankAccount();
        $record->fill($data);
        $record->is_active = $request->boolean('is_active');
        $record->save();

        return back()->with('success', 'Rekening pembayaran berhasil disimpan.');
    }

    public function destroyBankAccount(BillingBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return back()->with('success', 'Rekening pembayaran dihapus.');
    }
}
