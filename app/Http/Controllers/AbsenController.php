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
            'latlong' => 'required|string|regex:/^-?\d{1,2}\.\d+,-?\d{1,3}\.\d+$/',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'keterangan' => 'string',
            'ip' => 'required|string|ip', // Added validation for IP address
        ]);

        $photoPath = $request->file('photo')->store('photos', 'public');
        if (!$photoPath) {
            return redirect()->back()->withErrors(['photo' => 'Failed to upload photo.']);
        }

        Absensi::create([
            'nama' => $request->nama,
            'aktifitas' => $request->aktifitas,
            'latlong' => $request->latlong,
            'photo' => $photoPath,
            'keterangan' => $request->keterangan,
            'ip' => $request->ip, // Added IP address to be stored
        ]);

        $alamat = $this->getAddressFromLatLong($request->latlong);
        if ($alamat === 'Address not found') {
            return redirect()->back()->withErrors(['latlong' => 'Failed to retrieve address from latlong.']);
        }

        $date = now()->format('d/m/Y H:i:s');
        $googleMapUrl = "https://www.google.com/maps/search/?api=1&query={$request->latlong}";
        $latlong = str_replace(' ', '', $request->latlong);

        // Calculate the number of reports for the current day with the same name
        $jumlah_laporan_hari_ini = Absensi::where('nama', $request->nama)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();

        $caption = "*LAPORAN BARU TELAH DITERIMA!* \n";
        $caption .= "({$date})\n\n";
        $caption .= "*Nama AO:* " . ucwords($request->nama) . "\n";
        $caption .= "*Jenis Laporan:* {$request->aktifitas}\n";
        $caption .= "*Keterangan:* {$request->keterangan}\n";
        $caption .= "*Lokasi:* [$alamat](https://maps.google.com?q=" . urlencode($latlong) . ")\n";
        $caption .= "*===================*\n\n";
        $caption .= "*Jumlah laporan hari ini:* {$jumlah_laporan_hari_ini} Lap\n";
        $caption .= "*IP Address:* {$request->ip}\n";

        $telegramResponse = $this->sendTelegramPhotoNotification($photoPath, $caption);
        if (!$telegramResponse) {
            return redirect()->back()->withErrors(['telegram' => 'Failed to send Telegram notification.']);
        }

        return redirect('/')->with('success', 'Form submitted successfully!')->with('sweetalert', true);
    }

    private function getAddressFromLatLong($latLong)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=" . explode(',', $latLong)[0] . "&lon=" . explode(',', $latLong)[1];

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'YourAppName/1.0'
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            $address = $data['display_name'] ?? 'Address not found';
        } catch (\Exception $e) {
            Log::error("Failed to retrieve address: " . $e->getMessage());
            $address = 'Address not found';
        }

        return $address;
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
            return $response->getStatusCode() == 200;
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram photo notification: " . $e->getMessage());
            return false;
        }
    }
}
