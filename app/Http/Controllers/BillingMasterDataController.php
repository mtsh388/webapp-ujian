<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\BillingBankAccount;
use App\Models\BillingFeeRate;
use App\Models\BillingFeeType;
use App\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
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
            'feeTypes' => BillingFeeType::query()->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(),
            'classes' => ClassRoom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'education_level', 'grade_level']),
            'bankAccounts' => BillingBankAccount::query()->orderBy('bank_name')->get(),
        ]);
    }


    public function data(Request $request, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['fee-types', 'academic-years', 'fee-rates', 'bank-accounts'], true), 404);

        $query = match ($type) {
            'fee-types' => BillingFeeType::query()->withCount('rates'),
            'academic-years' => AcademicYear::query()->withCount('feeRates'),
            'fee-rates' => BillingFeeRate::query()->with(['feeType', 'academicYear', 'classRoom']),
            'bank-accounts' => BillingBankAccount::query(),
        };

        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($type, $search) {
                if ($type === 'fee-types') {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                } elseif ($type === 'academic-years') {
                    $q->where('name', 'like', "%{$search}%");
                } elseif ($type === 'fee-rates') {
                    $q->where('period_label', 'like', "%{$search}%")
                        ->orWhereHas('feeType', fn ($rel) => $rel->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('academicYear', fn ($rel) => $rel->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('classRoom', fn ($rel) => $rel->where('name', 'like', "%{$search}%"));
                } else {
                    $q->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('branch', 'like', "%{$search}%");
                }
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = match ($type) {
            'fee-types' => ['code', 'name', 'rates_count', 'is_active'],
            'academic-years' => ['name', 'starts_on', 'ends_on', 'fee_rates_count', 'is_active'],
            'fee-rates' => ['id', 'amount', 'period_type', 'is_active'],
            default => ['bank_name', 'account_name', 'account_number', 'branch', 'is_active'],
        };
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $orderColumns[$orderIndex] ?? $orderColumns[0];
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? 100 : max(1, min($length, 100));
        $start = max(0, (int) $request->input('start', 0));

        $items = $query->orderBy($orderColumn, $direction)
            ->skip($start)->take($length)->get();

        $data = $items->map(function ($item) use ($type) {
            $active = (bool) $item->is_active;
            $row = ['id' => $item->id, 'is_active' => $active];
            if ($type === 'fee-types') {
                $row += ['code' => $item->code, 'name' => $item->name, 'description' => $item->description, 'rates_count' => $item->rates_count];
            } elseif ($type === 'academic-years') {
                $row += ['name' => $item->name, 'starts_on' => $item->starts_on?->format('d/m/Y'), 'ends_on' => $item->ends_on?->format('d/m/Y'), 'starts_on_value' => $item->starts_on?->format('Y-m-d'), 'ends_on_value' => $item->ends_on?->format('Y-m-d'), 'fee_rates_count' => $item->fee_rates_count];
            } elseif ($type === 'fee-rates') {
                $row += ['fee_type' => $item->feeType?->name ?? '-', 'academic_year' => $item->academicYear?->name ?? '-', 'class_name' => $item->classRoom?->name ?? 'Semua kelas', 'period_label' => $item->period_label, 'period_type' => $item->period_type, 'amount' => (float) $item->amount, 'due_day' => $item->due_day, 'fee_type_id' => $item->fee_type_id, 'academic_year_id' => $item->academic_year_id, 'class_id' => $item->class_id];
            } else {
                $row += ['bank_name' => $item->bank_name, 'account_name' => $item->account_name, 'account_number' => $item->account_number, 'branch' => $item->branch];
            }
            return $row;
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
