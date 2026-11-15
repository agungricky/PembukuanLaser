<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">

    <title>API Overview - PembukuanLaser</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text: #e6edf3;
            --muted: #8b949e;
            --accent: #a5b4fc;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .topbar {
            height: 66px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .brand-icon {
            color: var(--accent);
            font-family: monospace;
            font-weight: bold;
            font-size: 17px;
        }

        .brand-muted {
            color: var(--muted);
            font-weight: 400;
        }

        .logout {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 8px 15px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            transition: .2s;
        }

        .logout:hover {
            color: #f87171;
            border-color: #f87171;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 55px 24px;
        }

        .eyebrow {
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0 10px;
            font-size: 32px;
            font-weight: 650;
            letter-spacing: -1px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }

        /* SUMMARY */

        .summary {
            display: flex;
            align-items: center;
            gap: 36px;
            padding: 28px 0;
            margin: 32px 0 35px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .summary-value {
            font-size: 27px;
            font-weight: 650;
        }

        .summary-label {
            color: var(--muted);
            font-size: 12px;
        }

        .framework {
            margin-left: auto;
            color: #86efac;
            background: #123025;
            border: 1px solid #22543b;
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        /* SECTION */

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .section-info {
            font-size: 12px;
            color: var(--muted);
        }

        /* GROUP CARDS */

        .grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 22px;
            transition: .2s ease;
            display: flex;
            flex-direction: column;
            min-height: 205px;
        }

        .card:hover {
            border-color: #6366f1;
            background: #1b2330;
            transform: translateY(-2px);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .card-icon {
            color: var(--accent);
            font-size: 21px;
            font-family: monospace;
        }

        .count {
            color: var(--muted);
            font-size: 12px;
        }

        .card h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 10px;
        }

        .middleware {
            font-family: monospace;
            font-size: 12px;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 22px;
            font-size: 12px;
            color: var(--accent);
        }

        .arrow {
            font-size: 19px;
            transition: transform .2s;
        }

        .card:hover .arrow {
            transform: translate(3px, -3px);
        }

        .empty {
            padding: 45px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--border);
            border-radius: 10px;
        }

        /* RESPONSIVE */

        @media (max-width: 600px) {
            .topbar {
                padding: 0 18px;
            }

            .container {
                padding: 35px 18px;
            }

            h1 {
                font-size: 27px;
            }

            .summary {
                gap: 25px;
            }

            .summary-value {
                font-size: 23px;
            }

            .framework {
                font-size: 10px;
                padding: 6px 9px;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header class="topbar">
        <div class="brand">
            <span class="brand-icon">&lt;/&gt;</span>
            <span>PembukuanLaser</span>
            <span class="brand-muted">/ API Docs</span>
        </div>

        <form action="{{ route('docs.logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout">
                Logout
            </button>
        </form>
    </header>

    <main class="container">
        <!-- INTRODUCTION -->
        <div class="eyebrow">
            Developer Documentation
        </div>
        <h1>API Overview</h1>
        <p class="subtitle">
            Pilih kelompok middleware untuk melihat
            dokumentasi dan mencoba endpoint API.
        </p>

        <!-- SUMMARY -->
        <div class="summary">
            <div class="summary-item">
                <span class="summary-value">
                    {{ $totalGroups }}
                </span>
                <span class="summary-label">
                    Middleware Groups
                </span>
            </div>

            <div class="summary-item">
                <span class="summary-value">
                    {{ $totalEndpoints }}
                </span>

                <span class="summary-label">
                    Total Endpoints
                </span>
            </div>

            <div class="framework">
                Laravel Sanctum
            </div>
        </div>

        <!-- GROUPS -->
        <div class="section-title">
            <h2>Middleware Groups</h2>

            <span class="section-info">
                Auto detected
            </span>
        </div>

        <div class="grid">

            @forelse ($groups as $group)
                <a href="{{ route('docs.swagger', ['group' => $group['key']]) }}" class="card">

                    <div class="card-top">

                        <span class="card-icon">
                            @if ($group['key'] === 'public')
                                &#9678;
                            @elseif ($group['key'] === 'authenticated')
                                &#9679;
                            @else
                                &lt;/&gt;
                            @endif
                        </span>

                        <span class="count">
                            {{ $group['total'] }} API
                        </span>

                    </div>

                    <h3>{{ $group['title'] }}</h3>

                    <div class="middleware">
                        {{ strtoupper($group['middleware']) }}
                    </div>

                    <div class="card-footer">

                        <span>View endpoints</span>

                        <span class="arrow">&nearr;</span>

                    </div>

                </a>

            @empty

                <div class="empty">
                    Belum ada endpoint API yang ditemukan.
                </div>
            @endforelse

        </div>

    </main>

</body>

</html>
