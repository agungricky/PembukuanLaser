<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>API Documentation - PembukuanLaser</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .header {
            height: 60px;
            padding: 0 24px;
            background: #111827;
            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            cursor: pointer;
        }

        iframe {
            display: block;
            width: 100%;
            height: calc(100vh - 60px);
            border: none;
        }
    </style>
</head>

<body>

    <div class="header">

        <strong>PembukuanLaser API Documentation</strong>

        <form action="{{ route('docs.logout') }}"
              method="POST">
            @csrf

            <button type="submit" class="logout-btn">
                Logout
            </button>
        </form>

    </div>

    <iframe
        src="{{ url('/docs/api') }}"
        title="API Documentation">
    </iframe>

</body>
</html>