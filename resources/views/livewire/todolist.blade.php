<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            <h2 class="text-center">Todo List Harian Account Officer</h2>

            @if (isset($errorlink))
                <div class="alert alert-danger" role="alert">
                    {{ $errorlink }}
                </div>
            @else
                <form wire:submit="sendTodo">
                    <div class="form-group">
                        <label for="nama_ao">Nama AO:</label>
                        <input type="text" class="form-control" id="nama_ao" wire:model.live="nama_ao" readonly>

                    </div>

                    <div class="form-group">
                        <label for="aktivitas">Aktivitas:</label>
                        <select class="form-control" id="aktivitas" wire:model.live="aktivitas">
                            <option value="">Pilih Aktivitas</option>
                            <option value="Kunjungan">Kunjungan</option>
                            <option value="Penagihan">Penagihan</option>
                            <option value="Marketing">Marketing</option>
                        </select>
                        <div>
                            @error('aktivitas')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    @if ($aktivitas == 'Kunjungan')
                        <div class="form-group">
                            <label for="jenis_kunjungan">Jenis Kunjungan:</label>
                            <select class="form-control" id="jenis_kunjungan" wire:model="jenis_kunjungan">
                                <option value="">Pilih Jenis Kunjungan</option>
                                <option value="Survei">Survei</option>
                                <option value="Survei Ulang">Survei Ulang</option>
                                <option value="Pasca Pencairan">Pasca Pencairan</option>
                            </select>
                        </div>
                        <div>
                            @error('jenis_kunjungan')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    @endif

                    @if ($aktivitas == 'Penagihan' || $jenis_kunjungan == 'Penagihan')
                        <div class="form-group">
                            <label for="nama_debitur">Nama Debitur:</label>
                            <input type="text" class="form-control" id="nama_debitur" wire:model="nama_debitur">
                        </div>

                        <div class="form-group">
                            <label for="kolektibilitas">Kolektibilitas:</label>
                            <select class="form-control" id="kolektibilitas" wire:model="kolektibilitas">
                                <option value="1">Kol 1 - Lancar</option>
                                <option value="2">Kol 2 - Dalam Perhatian Khusus</option>
                                <option value="3">Kol 3 - Kurang Lancar</option>
                                <option value="4">Kol 4 - Diragukan</option>
                                <option value="5">Kol 5 - Macet</option>
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="keterangan">Keterangan:</label>
                        <textarea class="form-control" id="keterangan" rows="3" wire:model="keterangan"></textarea>
                    </div>

                    {{-- <button type="button" class="btn btn-primary" wire:click="addTodo">Add</button> --}}
                    <button type="submit" class="btn btn-primary mt-2">Send</button>
                </form>
        </div>
    </div>



    <hr>

    <div class="card">
        <div class="card-body">

            <h4 class="text-center">Todolist Harian</h4>

            <div class="row">
                @php
                    $no = 1;
                @endphp
                @foreach ($todos as $todo)
                    @if ($todo->activity == 'Penagihan')
                        <div class="col-md-6">
                            <div class="card text-black mb-3 " style="background-color: rgba(255, 130, 182, 0.788);">
                                <div class="card-header fw-bold">{{ $no++ }}. {{ strtoupper($todo->activity) }}
                                </div>
                                <div class="card-body">
                                    <p class="card-text">{{ $todo->keterangan }}</p>
                                </div>
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </div>
                        </div>
                    @endif
                    @if ($todo->activity == 'Kunjungan')
                        <div class="col-md-6">
                            <div class="card text-black mb-3" style="background-color: rgba(255, 253, 137, 0.788);">
                                <div class="card-header fw-bold">{{ $no++ }}. {{ strtoupper($todo->activity) }}
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Merencanakan {{ $todo->activity }}
                                        {{ $todo->jenisKunjungan }}. {{ $todo->keterangan }}</p>
                                </div>
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </div>
                        </div>
                    @endif
                    @if ($todo->activity == 'Marketing')
                        <div class="col-md-6">
                            <div class="card text-black mb-3" style="background-color: rgba(104, 255, 162, 0.788);">
                                <div class="card-header fw-bold">{{ $no++ }}.
                                    {{ strtoupper($todo->activity) }}
                                </div>
                                <div class="card-body">
                                    <p class="card-text">{{ $todo->keterangan }}</p>
                                </div>
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- <ul>
        <div>
            @foreach ($todos as $todo)
                {{ $todo }}
            @endforeach
        </div>
    </ul> --}}
    @endif
</div>
