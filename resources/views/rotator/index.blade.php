<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mengalihkan...</title>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            text-align: center;
        }

        .redirect-box {
            padding: 30px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 20px;
            border: 4px solid #ddd;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        h3 {
            margin-bottom: 8px;
        }

        p {
            color: #666;
        }
    </style>
</head>

<body>

    <div class="redirect-box">
        <div class="spinner"></div>

        <h3>Menghubungkan ke WhatsApp</h3>

        <p>Mohon tunggu, Anda sedang dialihkan...</p>
    </div>

    <script>
        setTimeout(function () {
            window.location.href = "{{ route('redirect.cs') }}";
        }, 1000);
    </script>

</body>
</html>