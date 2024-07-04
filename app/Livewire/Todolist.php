<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use App\Models\Todo; // Import the Todo model

class Todolist extends Component
{
    public $nama_ao;
    public $aktivitas;
    public $jenis_kunjungan;
    public $nama_debitur;
    public $kolektibilitas;
    public $keterangan;


    public function mount()
    {
        $this->nama_ao = request()->get('ao');
    }

    public function sendTodo()
    {
        if ($this->aktivitas == 'Kunjungan') {
            $this->validate([
                'aktivitas' => 'required',
                'nama_ao' => 'required',
                'jenis_kunjungan' => 'required',
            ], [
                'nama_ao.required' => 'Jenis Kunjungan tidak boleh kosong',
                'jenis_kunjungan.required' => 'Jenis Kunjungan tidak boleh kosong',
            ]);
        } else {
            $this->validate([
                'aktivitas' => 'required',
                'nama_ao' => 'required',
            ], [
                'nama_ao.required' => 'Nama AO tidak boleh kosong',
                'aktivitas.required' => 'Aktifitas tidak boleh kosong',
            ]);
        }

        // Save todo to the database
        Todo::create([
            'namaAO' => $this->nama_ao,
            'activity' => $this->aktivitas,
            'keterangan' => $this->keterangan,
            'debiturName' => $this->nama_debitur,
            'kolektibilitas' => $this->kolektibilitas,
            'jenisKunjungan' => $this->jenis_kunjungan,
        ]);

        $this->resetFields();
        session()->flash('success', 'Data berhasil ditambahkan!');
    }

    private function resetFields()
    {
        $this->aktivitas = '';
        $this->jenis_kunjungan = '';
        $this->nama_debitur = '';
        $this->kolektibilitas = '';
        $this->keterangan = '';
    }

    public function render()
    {
        $nama = ['iwan', 'agus', 'ima', 'hendra', 'imam', 'kawi', 'dewa', 'galuh', 'hendro', 'mega'];

        if (!in_array($this->nama_ao, $nama)) {
            return view('livewire.todolist')->with('errorlink', 'Link Tidak Valid');
        }

        $todos = Todo::where('namaAO', $this->nama_ao)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        return view('livewire.todolist')->with('todos', $todos);
        // return dd($todos);
    }
}
