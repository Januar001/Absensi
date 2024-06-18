<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AbsenController extends Controller
{
    public function index()
    {
        return view('absen.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'aktifitas' => 'required|string',
            'latlong' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $photoPath = $request->file('photo')->store('photos', 'public');

        Absensi::create([
            'nama' => $request->nama,
            'aktifitas' => $request->aktifitas,
            'latlong' => $request->latlong,
            'photo' => $photoPath,
        ]);

        $date = now()->format('d/m/Y H:i:s');
        $googleMapUrl = "https://www.google.com/maps/search/?api=1&query={$request->latlong}";
        $latlong = str_replace(' ', '', $request->latlong);

        // Calculate the number of reports for the current day with the same name
        $jumlah_laporan_hari_ini = Absensi::where('nama', $request->nama)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();

        $caption = "*Laporan baru telah diterima!*\n\n";
        $caption .= "*Nama AO:* {$request->nama}\n";
        $caption .= "*Jenis Laporan:* {$request->aktifitas}\n";
        $caption .= "*Lokasi:* [Lihat Lokasi](https://maps.google.com?q=" . urlencode($latlong) . ")\n";
        $caption .= "*Submit:* {$date}\n";
        $caption .= "*Jumlah laporan hari ini:* {$jumlah_laporan_hari_ini} Lap";
        $this->sendTelegramPhotoNotification($photoPath, $caption);


        return redirect('/absen')->with('success', 'Form submitted successfully!')->with('sweetalert', true);
    }

    private function sendTelegramPhotoNotification($photoPath, $caption)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, [
                'multipart' => [
                    [
                        'name' => 'chat_id',
                        'contents' => $chatId
                    ],
                    [
                        'name' => 'photo',
                        'contents' => fopen(storage_path("app/public/{$photoPath}"), 'r')
                    ],
                    [
                        'name' => 'caption',
                        'contents' => $caption
                    ],
                    [
                        'name' => 'parse_mode',
                        'contents' => 'Markdown'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram photo notification: " . $e->getMessage());
        }
    }
}
