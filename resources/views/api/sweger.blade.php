
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">

    <title>API Documentation - PembukuanLaser</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0b1120;
            color: white;
        }

        .header {
            min-height: 65px;
            padding: 12px 24px;
            background: #111827;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .back-btn {
            background: #374151;
            color: white;
            padding: 9px 14px;
            text-decoration: none;
            border-radius: 7px;
            font-size: 13px;
        }

        .back-btn:hover {
            background: #4b5563;
        }

        .env-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            background: {{ $environment === 'local' ? '#16a34a' : '#d97706' }};
            color: white;
        }

        .group-badge {
            background: #312e81;
            color: #c7d2fe;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
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
            height: calc(100vh - 65px);
            overflow: auto;
            background: white;
        }

        .loading,
        .error {
            padding: 50px 24px;
            text-align: center;
            color: #334155;
        }

        .error {
            color: #dc2626;
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px;
            }

            .header-left {
                gap: 8px;
            }

            .header-left strong {
                font-size: 12px;
            }

            .env-badge,
            .group-badge {
                font-size: 10px;
            }

            #app {
                height: calc(100vh - 100px);
            }
        }
    </style>
</head>

<body>

    <div class="header">

        <div class="header-left">

            <a href="{{ route('docs.panel') }}" class="back-btn">
                &larr; Overview
            </a>

            <strong>PembukuanLaser API Documentation</strong>

            <span class="env-badge">
                {{ strtoupper($environment) }}
                -
                {{ $environment === 'local' ? 'FULL ACCESS' : 'GET ONLY' }}
            </span>

            @if ($selectedGroup)
                <span class="group-badge">
                    {{ $selectedGroup['title'] }}
                </span>
            @else
                <span class="group-badge">
                    All API
                </span>
            @endif

        </div>

        <form action="{{ route('docs.logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>

    </div>

    <div id="app">
        <div class="loading">Memuat dokumentasi API...</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>

    <script>
        const trialToken = @json($token);

        // null = tampilkan semua endpoint
        const allowedEndpoints = @json($allowedEndpoints);

        const openApiUrl = @json(url('/docs/api.json'));

        async function loadApiDocumentation() {
            const container = document.getElementById('app');

            try {
                /*
                |--------------------------------------------
                | AMBIL OPENAPI JSON DARI SCRAMBLE
                |--------------------------------------------
                */

                const response = await fetch(openApiUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                });

                if (!response.ok) {
                    throw new Error(
                        'Gagal mengambil OpenAPI JSON. HTTP ' + response.status
                    );
                }

                const spec = await response.json();

                /*
                |--------------------------------------------
                | FILTER BERDASARKAN METHOD DAN PATH
                |--------------------------------------------
                */

                if (allowedEndpoints !== null) {

                    const allowed = new Set(
                        allowedEndpoints.map(endpoint =>
                            endpoint.method.toLowerCase()
                            + ' '
                            + endpoint.path.replace(/\/+$/, '')
                        )
                    );

                    const filteredPaths = {};

                    for (const [path, pathItem] of Object.entries(spec.paths || {})) {

                        const normalizedPath =
                            '/' + path.replace(/^\/+|\/+$/g, '');

                        const filteredItem = {};

                        for (const [key, value] of Object.entries(pathItem)) {

                            const method = key.toLowerCase();

                            const isHttpMethod = [
                                'get', 'post', 'put',
                                'patch', 'delete',
                                'head', 'options', 'trace'
                            ].includes(method);

                            if (!isHttpMethod) {
                                // Simpan metadata seperti parameters, summary, servers
                                filteredItem[key] = value;
                                continue;
                            }

                            const matches = [
                                normalizedPath,
                                normalizedPath.startsWith('/api/')
                                    ? normalizedPath
                                    : '/api' + normalizedPath
                            ];

                            const exists = matches.some(candidate =>
                                allowed.has(method + ' ' + candidate)
                            );

                            if (exists) {
                                filteredItem[key] = value;
                            }
                        }

                        const hasOperations = Object.keys(filteredItem).some(
                            key => [
                                'get', 'post', 'put',
                                'patch', 'delete',
                                'head', 'options', 'trace'
                            ].includes(key.toLowerCase())
                        );

                        if (hasOperations) {
                            filteredPaths[path] = filteredItem;
                        }
                    }

                    spec.paths = filteredPaths;

                    // Sembunyikan tag kosong.
                    if (Array.isArray(spec.tags)) {

                        const usedTags = new Set();

                        Object.values(spec.paths).forEach(pathItem => {
                            Object.values(pathItem).forEach(operation => {
                                if (
                                    operation
                                    && typeof operation === 'object'
                                    && Array.isArray(operation.tags)
                                ) {
                                    operation.tags.forEach(tag => usedTags.add(tag));
                                }
                            });
                        });

                        spec.tags = spec.tags.filter(tag =>
                            usedTags.has(tag.name)
                        );
                    }
                }

                /*
                |--------------------------------------------
                | VALIDASI HASIL FILTER
                |--------------------------------------------
                */

                if (Object.keys(spec.paths || {}).length === 0) {
                    container.innerHTML = `
                        <div class="error">
                            Tidak ada endpoint dalam kelompok ini
                            pada dokumen OpenAPI.
                            <p>Periksa apakah endpoint sudah masuk
                            ke /docs/api.json.</p>
                        </div>
                    `;
                    return;
                }

                /*
                |--------------------------------------------
                | RENDER SCALAR
                |--------------------------------------------
                */

                container.innerHTML = '';

                Scalar.createApiReference('#app', {

                    content: JSON.stringify(spec),

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

            } catch (error) {

                console.error('Swagger Error:', error);

                container.innerHTML = `
                    <div class="error">
                        Gagal memuat dokumentasi API.
                        <p>Periksa akses /docs/api.json
                        dan Console browser.</p>
                    </div>
                `;
            }
        }

        loadApiDocumentation();
    </script>

</body>
</html>
