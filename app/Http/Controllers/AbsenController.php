<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'keterangan' => 'string|nullable',
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

        $provider = $this->getProviderFromIP($request->ip);

        $alamat = $this->getAddressFromLatLong($request->latlong);
        if ($alamat === 'Address not found') {
            return redirect()->back()->withErrors(['latlong' => 'Failed to retrieve address from latlong.']);
        }

        $date = now()->format('d/m/Y H:i:s');
        // $googleMapUrl = "https://www.google.com/maps/search/?api=1&query={$request->latlong}";
        $latlong = str_replace(' ', '', $request->latlong);

        // Calculate the number of reports for the current day with the same name
        $jumlah_laporan_hari_ini = Absensi::where('nama', $request->nama)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();

        $data_latlong = Absensi::select('latlong')->where('nama', $request->nama)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->get()
            ->pluck('latlong')
            ->implode('/');
        $urlmap = "https://www.google.com/maps/dir/-7.4250504,112.7226049/";


        $caption = "*LAPORAN BARU TELAH DITERIMA!* \n";
        $caption .= "({$date})\n\n";
        $caption .= "*========================*\n\n";
        $caption .= "*Nama AO:* " . ucwords($request->nama) . "\n";
        $caption .= "*Jenis Laporan:* {$request->aktifitas}\n";
        $caption .= "*Keterangan:* {$request->keterangan}\n";
        $caption .= "*Lokasi:* [$alamat](https://maps.google.com?q=" . urlencode($latlong) . ")\n\n";
        $caption .= "*========================*\n\n";
        $caption .= "*Jumlah laporan hari ini:* {$jumlah_laporan_hari_ini} Laporan\n";
        $caption .= "*Rute Perjalanan:* [Lihat Rute 🚀](" . $urlmap . $data_latlong . ")\n";
        $caption .= "*IP Address:* {$request->ip}\n";
        $caption .= "*Provider:* {$provider}\n";




        $telegramResponse = $this->sendTelegramPhotoNotification($photoPath, $caption);
        if (!$telegramResponse) {
            return redirect()->back()->withErrors(['telegram' => 'Failed to send Telegram notification.']);
        }

        $reportCounts = Absensi::select('nama', DB::raw('count(*) as total'))
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->groupBy('nama')
            ->get();

        $message = "*UPDATE DATA TERBARU*\n" . "(" . now()->translatedFormat('l, d F Y H:i:s') . ")\n\n";

        foreach ($reportCounts as $report) {
            $message .= "✅ *" . ucwords("{$report->nama}") . "* : {$report->total} Laporan\n";
        }

        $telegramMessage = $this->sendTelegramMessage($message);

        return redirect('/')->with('success', 'Form submitted successfully!')->with('sweetalert', true);
    }

    private function getAddressFromLatLong($latLong)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=" . explode(',', $latLong)[0] . "&lon=" . explode(',', $latLong)[1];

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => env('APP_NAME') . '/1.0'
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


    private function getProviderFromIP($ip)
    {
        $url = "https://ipapi.co/{$ip}/org/";

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => env('APP_NAME') . '/1.0'
                ]
            ]);
            $provider = $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error("Failed to retrieve provider: " . $e->getMessage());
            $provider = 'Provider not found';
        }

        return $provider;
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




    private function sendTelegramMessage($message)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, [
                'form_params' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]
            ]);
            return $response->getStatusCode() == 200;
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram message: " . $e->getMessage());
            return false;
        }
    }
}
