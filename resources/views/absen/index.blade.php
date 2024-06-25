<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }

        .container {
            margin-top: 50px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #343a40;
            margin-bottom: 30px;
        }

        .form-label {
            color: #495057;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        #imagePreview {
            margin-top: 10px;
            max-width: 100%;
            height: auto;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Form Laporan Account Officer</h1>
        <form id="aoForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama"
                    value="{{ request()->get('ao') }}" readonly>
            </div>
            <div class="mb-3">
                <label for="aktifitas" class="form-label">Aktifitas</label>
                <select class="form-select" id="aktifitas" name="aktifitas">
                    <option value="Marketing">Marketing</option>
                    <option value="Kunjungan">Kunjungan</option>
                    <option value="Penagihan">Penagihan</option>
                </select>
            </div>
            <div class="mb-3" hidden>
                <label for="latlong" class="form-label">LatLong</label>
                <input type="text" class="form-control" id="latlong" name="latlong" readonly>
            </div>
            <div class="mb-3" hidden>
                <label for="ip" class="form-label">IP Address</label>
                <input type="text" class="form-control" id="ip" name="ip" readonly>
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Photo</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*"
                    capture="camera">
                <div id="imagePreviewContainer">
                    <img id="imagePreview" src="#" alt="Image Preview">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@latest/dist/browser-image-compression.min.js">
    </script>
    <script>
        document.getElementById('photo').addEventListener('change', async function(event) {
            const [file] = event.target.files;
            if (file) {
                try {
                    // Konfigurasi opsi kompresi
                    const options = {
                        maxSizeMB: 1, // Maksimal ukuran file dalam MB
                        maxWidthOrHeight: 780, // Maksimal lebar atau tinggi gambar
                        useWebWorker: true
                    };

                    const compressedFile = await imageCompression(file, options);

                    // Tampilkan informasi ukuran sebelum dan sesudah kompresi
                    console.log('Original file size:', file.size / 1024, 'KB');
                    console.log('Compressed file size:', compressedFile.size / 1024, 'KB');

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = new Image();
                        img.src = e.target.result;
                        img.onload = function() {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            canvas.width = img.width;
                            canvas.height = img.height;
                            ctx.drawImage(img, 0, 0);

                            // Menyesuaikan ukuran font berdasarkan ukuran gambar
                            const fontSize = Math.min(img.width, img.height) * 0.04;
                            ctx.font = `${fontSize}px Arial`;
                            ctx.fillStyle = 'black';
                            ctx.strokeStyle = 'white';
                            ctx.lineWidth = 2;

                            // Get watermark text
                            const nama = document.getElementById('nama').value;
                            const latlong = document.getElementById('latlong').value;
                            const dateTime = new Date().toLocaleString('en-GB', {
                                hour12: false
                            });
                            const watermarkText =
                                `Nama: ${nama}\nLatlong: ${latlong}\nTanggal: ${dateTime}`;

                            // Add watermark text to image
                            const lines = watermarkText.split('\n');
                            lines.forEach((line, index) => {
                                const textWidth = ctx.measureText(line).width;
                                const x = Math.min(10, img.width - textWidth - 10);
                                ctx.strokeText(line, x, fontSize * (index + 1) +
                                    10); // Outline text
                                ctx.fillText(line, x, fontSize * (index + 1) + 10); // Fill text
                            });

                            const watermarkedDataUrl = canvas.toDataURL('image/png');
                            canvas.toBlob(function(blob) {
                                const fileInput = document.getElementById('photo');
                                const newFile = new File([blob], file.name, {
                                    type: 'image/png'
                                });
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(newFile);
                                fileInput.files = dataTransfer.files;
                            }, 'image/png');

                            // Display the original image preview without watermark
                            const imagePreview = document.getElementById('imagePreview');
                            imagePreview.src = e.target.result;
                            imagePreview.style.display = 'block';
                        };
                    };
                    reader.readAsDataURL(compressedFile);
                } catch (error) {
                    console.error('Error during compression:', error);
                }
            }
        });

        // Get user's current location and set it to the latlong input field
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latlong').value = position.coords.latitude + ',' + position.coords
                    .longitude;
                document.getElementById('aoForm').style.pointerEvents = 'auto';
                document.getElementById('submitBtn').disabled = false;
            }, function(error) {
                alert("Please enable GPS to use this form.");
                document.getElementById('aoForm').style.pointerEvents = 'none';
                document.getElementById('submitBtn').disabled = true;
            });
        } else {
            alert("Geolocation is not supported by this browser.");
            document.getElementById('aoForm').style.pointerEvents = 'none';
            document.getElementById('submitBtn').disabled = true;
        }

        // Get user's IP address and set it to the ip input field
        fetch('https://api.ipify.org?format=json')
            .then(response => response.json())
            .then(data => {
                document.getElementById('ip').value = data.ip;
            })
            .catch(error => {
                console.error('Error fetching IP address:', error);
            });

        @if (session('sweetalert'))
            Swal.fire({
                title: 'Success!',
                text: '{{ session('success') }}',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                title: 'Error!',
                html: '<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        @endif
    </script>
</body>

</html>
