<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TodolistController extends Controller
{
    public function index()
    {
        return view('todolist.index');
    }

    public function send(Request $request)
    {
        $data = $request->all();

        $telegramBotToken = env('TELEGRAM_BOT_TOKEN');
        $telegramChatId = "-1002177390374";
        $message = "Nama AO: {$data['nama_ao']}\nAktivitas: {$data['aktivitas']}\nKunjungan: {$data['kunjungan']}\nNama Debitur: {$data['nama_debitur']}\nKolektibilitas: {$data['kolektibilitas']}\nKeterangan: {$data['keterangan']}";

        Http::post("https://api.telegram.org/bot{$telegramBotToken}/sendMessage", [
            'chat_id' => $telegramChatId,
            'text' => $message
        ]);

        return response()->json(['success' => true]);
    }
}
