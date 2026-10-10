@extends('template.app')

@section('title', 'Detail Kelas')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">


        <div class="card px-3">


            <div class="card-header border-bottom mb-3">


                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">


                    <div>

                        <h4 class="card-title mb-1">
                            Detail Kelas
                        </h4>


                        <p class="card-subtitle text-muted mb-0">
                            Informasi lengkap kelas bimbingan belajar.
                        </p>


                    </div>




                    <div class="d-flex gap-2 justify-content-end">


                        <a href="{{ route('classes.index') }}"
                           class="btn btn-secondary">


                            <i class="bx bx-arrow-back me-1"></i>

                            Kembali


                        </a>




                        @can('class.update')

                            <a href="{{ route('classes.edit', $classRoom) }}"
                               class="btn btn-primary">


                                <i class="bx bx-edit me-1"></i>

                                Edit Kelas


                            </a>

                        @endcan


                    </div>



                </div>


            </div>





            <div class="card-body">


                <div class="row g-4">



                    <div class="col-lg-8">


                        <div class="card border shadow-none h-100">


                            <div class="card-header">


                                <h5 class="mb-0">
                                    Informasi Kelas
                                </h5>


                            </div>



                            <div class="card-body">


                                <div class="row g-4">



                                    <div class="col-md-6">


                                        <small class="text-muted d-block mb-1">
                                            Nama Kelas
                                        </small>


                                        <div class="fw-semibold">
                                            {{ $classRoom->name }}
                                        </div>


                                    </div>





                                    <div class="col-md-6">


                                        <small class="text-muted d-block mb-1">
                                            Kode Kelas
                                        </small>


                                        <span class="badge bg-label-primary">
                                            {{ $classRoom->code }}
                                        </span>


                                    </div>





                                    <div class="col-12">


                                        <small class="text-muted d-block mb-1">
                                            Deskripsi
                                        </small>


                                        <div>
                                            {{ $classRoom->description ?: '-' }}
                                        </div>


                                    </div>





                                    <div class="col-md-6">


                                        <small class="text-muted d-block mb-1">
                                            Status
                                        </small>



                                        @if($classRoom->is_active)

                                            <span class="badge bg-label-success">

                                                <i class="bx bx-check-circle me-1"></i>

                                                Aktif

                                            </span>


                                        @else

                                            <span class="badge bg-label-secondary">

                                                <i class="bx bx-x-circle me-1"></i>

                                                Tidak Aktif

                                            </span>


                                        @endif


                                    </div>





                                    <div class="col-md-6">


                                        <small class="text-muted d-block mb-1">
                                            Dibuat
                                        </small>


                                        <div>
                                            {{ $classRoom->created_at?->format('d M Y H:i') ?? '-' }}
                                        </div>


                                    </div>





                                    <div class="col-md-6">


                                        <small class="text-muted d-block mb-1">
                                            Terakhir Update
                                        </small>


                                        <div>
                                            {{ $classRoom->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </div>


                                    </div>



                                </div>


                            </div>


                        </div>


                    </div>






                    <div class="col-lg-4">


                        <div class="card border shadow-none h-100">


                            <div class="card-header">


                                <h5 class="mb-0">
                                    Ringkasan
                                </h5>


                            </div>



                            <div class="card-body">



                                <div class="d-flex align-items-center gap-3 mb-4">


                                    <div class="avatar">


                                        <span class="avatar-initial rounded bg-label-primary">

                                            <i class="bx bx-group"></i>

                                        </span>


                                    </div>



                                    <div>


                                        <div class="text-muted small">
                                            Jumlah Siswa
                                        </div>


                                        <h4 class="mb-0">
                                            {{ $classRoom->students_count ?? $classRoom->students()->count() }}
                                        </h4>


                                    </div>


                                </div>





                                <div class="d-flex align-items-center gap-3">


                                    <div class="avatar">


                                        <span class="avatar-initial rounded bg-label-success">

                                            <i class="bx bx-check-circle"></i>

                                        </span>


                                    </div>



                                    <div>


                                        <div class="text-muted small">
                                            Status Kelas
                                        </div>


                                        <div class="fw-semibold">

                                            {{ $classRoom->is_active ? 'Aktif' : 'Tidak Aktif' }}

                                        </div>


                                    </div>


                                </div>




                            </div>


                        </div>


                    </div>



                </div>


            </div>





        </div>


    </div>


@endsection
