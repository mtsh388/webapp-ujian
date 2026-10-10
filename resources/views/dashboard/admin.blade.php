@extends('template.app')

@section('title', 'Dashboard Admin')

@section('content')

    <div class="content-wrapper">

        <div class="container-xxl flex-grow-1 container-p-y">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-6">

                <div>
                    <h4 class="fw-bold mb-1">
                        Dashboard Admin
                    </h4>

                    <p class="text-body-secondary mb-0">
                        Selamat datang, {{ auth()->user()->name }}. Kelola sistem ujian bimbel dari sini.
                    </p>
                </div>

                <div class="mt-3 mt-md-0">
                    <span class="badge bg-label-primary">
                        <i class="bx bx-shield-quarter me-1"></i>
                        Administrator
                    </span>
                </div>

            </div>


            <div class="row">


                <div class="col-sm-6 col-xl-3 mb-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-start justify-content-between">

                                <div class="avatar">

                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx bx-user icon-lg"></i>
                                    </span>

                                </div>

                            </div>

                            <span class="fw-medium d-block mb-1 mt-4">
                                Total Siswa
                            </span>

                            <h3 class="card-title mb-1">
                                {{ number_format($totalStudents, 0, ',', '.') }}
                            </h3>

                            <small class="text-body-secondary">
                                Siswa terdaftar
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3 mb-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-start justify-content-between">

                                <div class="avatar">

                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="bx bx-user-voice icon-lg"></i>
                                    </span>

                                </div>

                            </div>

                            <span class="fw-medium d-block mb-1 mt-4">
                                Total Tentor
                            </span>

                            <h3 class="card-title mb-1">
                                {{ number_format($totalTentors, 0, ',', '.') }}
                            </h3>

                            <small class="text-body-secondary">
                                Tentor aktif
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3 mb-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-start justify-content-between">

                                <div class="avatar">

                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="bx bx-edit icon-lg"></i>
                                    </span>

                                </div>

                            </div>

                            <span class="fw-medium d-block mb-1 mt-4">
                                Total Ujian
                            </span>

                            <h3 class="card-title mb-1">
                                {{ number_format($totalExams, 0, ',', '.') }}
                            </h3>

                            <small class="text-body-secondary">
                                Ujian tersedia
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-sm-6 col-xl-3 mb-6">

                    <div class="card h-100">

                        <div class="card-body">

                            <div class="d-flex align-items-start justify-content-between">

                                <div class="avatar">

                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="bx bx-book-open icon-lg"></i>
                                    </span>

                                </div>

                            </div>

                            <span class="fw-medium d-block mb-1 mt-4">
                                Bank Soal
                            </span>

                            <h3 class="card-title mb-1">
                                {{ number_format($totalQuestions, 0, ',', '.') }}
                            </h3>

                            <small class="text-body-secondary">
                                Soal tersimpan
                            </small>

                        </div>

                    </div>

                </div>


            </div>


            <div class="row">


                <div class="col-12 col-xl-8 mb-6">

                    <div class="card h-100">

                        <div class="card-header d-flex align-items-center justify-content-between">

                            <div>
                                <h5 class="card-title mb-1">
                                    Ujian Terbaru
                                </h5>

                                <p class="card-subtitle mb-0">
                                    Daftar ujian yang baru dibuat
                                </p>
                            </div>

                            <a href="#" class="btn btn-sm btn-outline-primary">
                                Lihat Semua
                            </a>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead>

                                <tr>

                                    <th>
                                        Kode
                                    </th>

                                    <th>
                                        Ujian
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Durasi
                                    </th>

                                </tr>

                                </thead>


                                <tbody>

                                @forelse ($recentExams as $exam)

                                    <tr>

                                        <td>

                                                <span class="fw-medium">
                                                    {{ $exam->code }}
                                                </span>

                                        </td>


                                        <td>

                                            <div class="d-flex align-items-center">

                                                <div class="avatar avatar-sm me-3">

                                                        <span class="avatar-initial rounded bg-label-info">
                                                            <i class="bx bx-edit"></i>
                                                        </span>

                                                </div>

                                                <div>

                                                        <span class="fw-medium d-block">
                                                            {{ $exam->title }}
                                                        </span>

                                                    <small class="text-body-secondary">
                                                        {{ $exam->subject?->name ?? 'Tanpa mata pelajaran' }}
                                                    </small>

                                                </div>

                                            </div>

                                        </td>


                                        <td>

                                            @if ($exam->status === 'published')

                                                <span class="badge bg-label-success">
                                                        Published
                                                    </span>

                                            @elseif ($exam->status === 'draft')

                                                <span class="badge bg-label-warning">
                                                        Draft
                                                    </span>

                                            @elseif ($exam->status === 'closed')

                                                <span class="badge bg-label-secondary">
                                                        Closed
                                                    </span>

                                            @else

                                                <span class="badge bg-label-secondary">
                                                        {{ ucfirst($exam->status) }}
                                                    </span>

                                            @endif

                                        </td>


                                        <td>

                                                <span>
                                                    {{ $exam->duration_minutes }} menit
                                                </span>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4" class="text-center py-5">

                                            <div class="text-body-secondary">

                                                <i class="bx bx-info-circle fs-3 d-block mb-2"></i>

                                                Belum ada data ujian.

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-xl-4 mb-6">

                    <div class="card h-100">

                        <div class="card-header">

                            <h5 class="card-title mb-1">
                                Ringkasan Sistem
                            </h5>

                            <p class="card-subtitle mb-0">
                                Informasi aplikasi
                            </p>

                        </div>


                        <div class="card-body">

                            <ul class="p-0 m-0">


                                <li class="d-flex align-items-center mb-5">

                                    <div class="avatar flex-shrink-0 me-3">

                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="bx bx-user"></i>
                                        </span>

                                    </div>

                                    <div class="d-flex w-100 justify-content-between">

                                        <div>

                                            <h6 class="mb-0">
                                                Siswa
                                            </h6>

                                            <small class="text-body-secondary">
                                                Pengguna peserta ujian
                                            </small>

                                        </div>

                                        <span class="fw-semibold">
                                            {{ number_format($totalStudents, 0, ',', '.') }}
                                        </span>

                                    </div>

                                </li>


                                <li class="d-flex align-items-center mb-5">

                                    <div class="avatar flex-shrink-0 me-3">

                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class="bx bx-user-voice"></i>
                                        </span>

                                    </div>

                                    <div class="d-flex w-100 justify-content-between">

                                        <div>

                                            <h6 class="mb-0">
                                                Tentor
                                            </h6>

                                            <small class="text-body-secondary">
                                                Pengelola materi dan ujian
                                            </small>

                                        </div>

                                        <span class="fw-semibold">
                                            {{ number_format($totalTentors, 0, ',', '.') }}
                                        </span>

                                    </div>

                                </li>


                                <li class="d-flex align-items-center mb-5">

                                    <div class="avatar flex-shrink-0 me-3">

                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="bx bx-edit"></i>
                                        </span>

                                    </div>

                                    <div class="d-flex w-100 justify-content-between">

                                        <div>

                                            <h6 class="mb-0">
                                                Ujian
                                            </h6>

                                            <small class="text-body-secondary">
                                                Ujian yang tersedia
                                            </small>

                                        </div>

                                        <span class="fw-semibold">
                                            {{ number_format($totalExams, 0, ',', '.') }}
                                        </span>

                                    </div>

                                </li>


                                <li class="d-flex align-items-center">

                                    <div class="avatar flex-shrink-0 me-3">

                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="bx bx-book-open"></i>
                                        </span>

                                    </div>

                                    <div class="d-flex w-100 justify-content-between">

                                        <div>

                                            <h6 class="mb-0">
                                                Bank Soal
                                            </h6>

                                            <small class="text-body-secondary">
                                                Soal dalam sistem
                                            </small>

                                        </div>

                                        <span class="fw-semibold">
                                            {{ number_format($totalQuestions, 0, ',', '.') }}
                                        </span>

                                    </div>

                                </li>


                            </ul>

                        </div>

                    </div>

                </div>


            </div>


        </div>


        <div class="content-backdrop fade"></div>

    </div>

@endsection
