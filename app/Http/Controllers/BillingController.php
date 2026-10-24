<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingFeeRate;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $query = BillingInvoice::query()->with(['student', 'payments']);

        if ($user->can('billing.manage')) {
            $invoices = $query->latest()->paginate(20);
        } else {
            $studentIds = $this->accessibleStudentIds();
            $invoices = $query->whereIn('student_id', $studentIds)->latest()->paginate(20);
        }

        return view('billing.index', [
            'invoices' => $invoices,
            'canManage' => $user->can('billing.manage'),
            'totalOutstanding' => $invoices->getCollection()->sum(fn (BillingInvoice $invoice) => (float) $invoice->outstanding_amount),
        ]);
    }

    public function create(): View
    {
        $students = Student::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'student_number']);
        $feeRates = BillingFeeRate::query()
            ->with(['feeType', 'academicYear', 'classRoom'])
            ->where('is_active', true)
            ->whereHas('feeType', fn ($query) => $query->where('is_active', true))
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->orderBy('period_label')
            ->get();

        return view('billing.create', compact('students', 'feeRates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'fee_rate_id' => ['nullable', 'integer', 'exists:billing_fee_rates,id'],
            'title' => ['required', 'string', 'max:180'],
            'period_type' => ['required', 'in:semester,year'],
            'period_label' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        abort_unless($student->is_active, 422, 'Tagihan hanya dapat dibuat untuk siswa aktif.');

        if (!empty($validated['fee_rate_id'])) {
            $rate = BillingFeeRate::query()->with(['feeType', 'academicYear'])->findOrFail($validated['fee_rate_id']);
            abort_unless($rate->is_active && $rate->feeType->is_active && $rate->academicYear->is_active, 422, 'Tarif yang dipilih tidak aktif.');
            if ($rate->class_id !== null) {
                abort_unless((int) $student->class_id === (int) $rate->class_id, 422, 'Tarif ini hanya berlaku untuk kelas tertentu.');
            }
            $validated['title'] = $rate->feeType->name;
            $validated['period_type'] = $rate->period_type;
            $validated['period_label'] = trim($rate->period_label . ' ' . $rate->academicYear->name);
            $validated['amount'] = $rate->amount;
        }

        $invoice = BillingInvoice::query()->create([
            ...$validated,
            'payment_status' => 'unpaid',
            'fee_rate_id' => $validated['fee_rate_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('billing.show', $invoice)
            ->with('success', 'Tagihan berhasil dibuat. Siswa dan orang tua/wali dapat melihat peringatannya melalui akun masing-masing.');
    }

    public function show(BillingInvoice $invoice): View
    {
        $this->authorizeInvoiceAccess($invoice);
        $invoice->load(['student.parents.user', 'payments.recordedBy', 'payments.verifiedBy']);

        return view('billing.show', [
            'invoice' => $invoice,
            'canManage' => auth()->user()->can('billing.manage'),
            'canSubmitPayment' => auth()->user()->can('billing.payment.submit') || auth()->user()->can('billing.manage'),
        ]);
    }

    public function storePayment(Request $request, BillingInvoice $invoice): RedirectResponse
    {
        $this->authorizeInvoiceAccess($invoice);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'method' => ['required', 'in:transfer,cash'],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($invoice, $validated): void {
            $lockedInvoice = BillingInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $reserved = (float) $lockedInvoice->payments()
                ->whereIn('status', ['pending', 'verified'])
                ->sum('amount');

            if ((float) $validated['amount'] > (float) $lockedInvoice->amount - $reserved + 0.00001) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa tagihan setelah memperhitungkan pembayaran yang menunggu verifikasi.',
                ]);
            }

            if ($lockedInvoice->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'amount' => 'Tagihan ini sudah lunas.',
                ]);
            }

            BillingPayment::query()->create([
                ...$validated,
                'invoice_id' => $lockedInvoice->id,
                'status' => 'pending',
                'recorded_by' => auth()->id(),
            ]);
        });

        return redirect()->route('billing.show', $invoice)
            ->with('success', 'Transaksi dicatat dan menunggu verifikasi admin. Pembayaran belum dihitung sebagai pemasukan sebelum diverifikasi.');
    }

    public function verifyPayment(Request $request, BillingPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $validated): void {
            $lockedPayment = BillingPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = BillingInvoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi ini sudah diproses sebelumnya.',
                ]);
            }

            if ($validated['status'] === 'verified') {
                $verified = (float) $invoice->payments()->where('status', 'verified')->sum('amount');
                if ($verified + (float) $lockedPayment->amount > (float) $invoice->amount + 0.00001) {
                    throw ValidationException::withMessages([
                        'status' => 'Verifikasi ditolak karena total pembayaran akan melebihi nilai tagihan.',
                    ]);
                }
            }

            $lockedPayment->forceFill([
                'status' => $validated['status'],
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'notes' => trim(($lockedPayment->notes ? $lockedPayment->notes . PHP_EOL : '') .
                    ($validated['verification_note'] ?? '')),
            ])->save();

            $invoice->refreshPaymentStatus();
        });

        return redirect()->route('billing.show', $payment->invoice_id)
            ->with('success', $validated['status'] === 'verified'
                ? 'Pembayaran diverifikasi dan pemasukan diperbarui.'
                : 'Pembayaran ditolak. Nominal tidak dihitung sebagai pemasukan.');
    }

    public function report(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $payments = BillingPayment::query()
            ->with(['invoice.student', 'verifiedBy'])
            ->where('status', 'verified')
            ->when($validated['from'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '<=', $date))
            ->orderByDesc('paid_at')
            ->paginate(30)
            ->withQueryString();

        $totals = BillingPayment::query()
            ->where('status', 'verified')
            ->when($validated['from'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '<=', $date))
            ->selectRaw("COALESCE(SUM(amount), 0) as total, COUNT(*) as transaction_count")
            ->first();

        return view('billing.report', [
            'payments' => $payments,
            'totalIncome' => (float) $totals->total,
            'transactionCount' => (int) $totals->transaction_count,
            'from' => $validated['from'] ?? '',
            'to' => $validated['to'] ?? '',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function accessibleStudentIds(): \Illuminate\Support\Collection
    {
        $user = auth()->user();

        if ($user->can('billing.view_own')) {
            return Student::query()->where('user_id', $user->id)->pluck('id');
        }

        if ($user->can('billing.view_child')) {
            return $user->parentProfile
                ? $user->parentProfile->students()->pluck('students.id')
                : collect();
        }

        abort(403);
    }

    private function authorizeInvoiceAccess(BillingInvoice $invoice): void
    {
        if (auth()->user()->can('billing.manage')) {
            return;
        }

        abort_unless($this->accessibleStudentIds()->contains((int) $invoice->student_id), 403);
    }
}
