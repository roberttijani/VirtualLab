<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIRTUAL LAB SQL</title>
    <link rel="stylesheet" href="{{ asset('vendors/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/dracula.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    <header class="menu-top">
        <div class="header-left">
            <button id="toggle-sidebar"><i class="fas fa-bars"></i></button>
            <button>File</button>
            <button><i class="fa fa-download"></i> Export</button>
            <button><i class="fa fa-upload"></i> Import</button>
        </div>
        <div class="header-right">
            <button>
                <i class="fas fa-cog"></i>
            </button>
        </div>
    </header>
    <div class="main-content">
        <div class="sidebar" id="sidebar">
            <div class="database" id="database-list">
                @foreach ($databases as $db)
                <div class="database-header list-group-item collapsed" data-database="{{ $db['full'] }}">
                    <i class="fas fa-database"></i>
                    <span>{{ $db['display'] }}</span>
                </div>
                <div class="database-content table-list" style="display: none;">
                    @if (!empty($tables))
                        @foreach ($tables as $table)
                            @if (isset($db) && is_object($db) && session('selected_database') == $db->Database)
                                <div class="table-header list-group-item collapsed" data-table="{{ $table->{"Tables_in_" . session('selected_database')} }}">
                                    <i class="fas fa-table"></i>
                                    <span>{{ $table->{"Tables_in_" . session('selected_database')} }}</span>
                                </div>
                                <div class="table-content column-list" style="display: none;">
                                    <!-- Kolom tabel akan dimuat di sini -->
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        <div class="editor-container">
            <form id="create-database-form" action="{{ route('create-database') }}" method="POST">
                @csrf
                <input type="text" name="database_name" id="database_name" placeholder="Enter database name" required>
                <button type="submit">Create Database</button>
            </form>
            <div id="database-message" class="alert alert-info" style="display: {{ session('message') ? 'block' : 'none' }};"></div>
            <h2>Selected Database: <span id="selected-database">{{ $selectedDatabase ?? 'None' }}</span></h2>
            <form id="query-form" method="POST" action="{{ route('execute.query') }}">
                @csrf
                <div class="mb-3">
                    <label for="query" class="form-label">SQL Query</label>
                    <textarea id="editor" name="query">{{ old('query') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Execute</button>
            </form>
            <div id="query-result">
                <h2>Result</h2>
                <div class="output">
                    <table>
                        <thead>
                        </thead>
                        <tbody id="table-body">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <div class="history-sidebar">
            <h3>History</h3>
            <div id="query-history" class="query-history">
                <!-- Add more details as needed -->
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="{{ asset('js/scripts2.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/sql/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/addon/selection/active-line.min.js"></script>
    <script>
        const editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
            lineNumbers: true,
            mode: 'text/x-mysql',
            theme: 'dracula',
            matchBrackets: true,
            styleActiveLine: true,
            lineWrapping: true // Prevent auto size adjustment
        });
    </script>
</body>
</html>

