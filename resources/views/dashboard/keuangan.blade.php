@extends('template.app')

@section('title', 'Dashboard Keuangan')

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl container-p-y">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-1">Dashboard Keuangan</h4>
                        <p class="text-muted mb-0">Selamat datang, {{ auth()->user()->name }}. Kelola pemasukan dan tagihan bimbel.</p>
                    </div>
                    <span class="badge bg-label-success px-3 py-2">Keuangan</span>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted mb-2">Total Tagihan</div>
                            <h3 class="mb-0">{{ number_format($totalInvoices, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted mb-2">Tagihan Belum Lunas</div>
                            <h3 class="mb-0">{{ number_format($outstandingInvoices, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Menu Keuangan</h5>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @can('billing.view')
                        <a href="{{ route('billing.index') }}" class="btn btn-primary">
                            <i class="bx bx-receipt me-1"></i> Daftar Tagihan
                        </a>
                    @endcan
                    @can('billing.manage')
                        <a href="{{ route('billing.create') }}" class="btn btn-outline-primary">
                            <i class="bx bx-plus-circle me-1"></i> Buat Tagihan
                        </a>
                        <a href="{{ route('billing.report') }}" class="btn btn-outline-success">
                            <i class="bx bx-bar-chart-alt-2 me-1"></i> Laporan Pemasukan
                        </a>
                        <a href="{{ route('billing.master.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-cog me-1"></i> Master Data Keuangan
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
