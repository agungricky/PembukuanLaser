<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
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

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .env-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            background: {{ $environment === 'local' ? '#16a34a' : '#d97706' }};
            color: white;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            cursor: pointer;
        }

        #app {
            height: calc(100vh - 60px);
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <strong>PembukuanLaser API Documentation</strong>
            <span class="env-badge">
                {{ strtoupper($environment) }}
                -
                {{ $environment === 'local' ? 'FULL ACCESS' : 'GET ONLY' }}
            </span>
        </div>

        <form action="{{ route('docs.logout') }}" method="POST">
            @csrf

            <button type="submit" class="logout-btn">
                Logout
            </button>
        </form>
    </div>

    <div id="app"></div>

    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
    <script>
        const trialToken = @json($token);
        Scalar.createApiReference('#app', {
            url: @json(url('/docs/api.json')),
            theme: 'default',
            hideModels: true,
            onBeforeRequest: ({ requestBuilder }) => {
                requestBuilder.options.disableSecurity = true;
                requestBuilder.headers.set(
                    'Authorization',
                    'Bearer ' + trialToken
                );

                requestBuilder.headers.set(
                    'Accept',
                    'application/json'
                );
            }
        });
    </script>
</body>
</html>
