<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SQLController1 extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        $databases = DB::select("SHOW DATABASES");
        $userDatabases = [];

        // Filter databases that match the pattern and store both full name and display name
        foreach ($databases as $database) {
            $dbName = $database->Database; // Adjust this based on the exact structure of the object
            if (strpos($dbName, "user_{$userId}_") === 0) {
                $displayName = str_replace("user_{$userId}_", '', $dbName);
                $userDatabases[] = ['full' => $dbName, 'display' => $displayName];
            }
        }

        $selectedDatabase = session('selected_database');
        $tables = [];

        if ($selectedDatabase) {
            Config::set('database.connections.dynamic_mysql.database', $selectedDatabase);
            DB::purge('dynamic_mysql');
            DB::setDefaultConnection('dynamic_mysql');
            DB::reconnect('dynamic_mysql');

            try {
                $tables = DB::connection('dynamic_mysql')->select('SHOW TABLES');
            } catch (\Exception $e) {
                Log::error('Error fetching tables: ' . $e->getMessage());
            }
        }

        $result = session('result', []);
        $message = session('message', '');

        return view('sql4', [
            'databases' => $userDatabases,
            'tables' => $tables,
            'selectedDatabase' => $selectedDatabase,
            'result' => $result,
            'message' => $message
        ]);
    }



    public function createDatabase(Request $request)
    {
        $userId = auth()->user()->id;
        $databaseName = $request->input('database_name');
        $fullDatabaseName = 'user_' . $userId . '_' . $databaseName;
        $message = '';

        try {
            DB::statement("CREATE DATABASE $fullDatabaseName");
            $message = "Database $databaseName created successfully.";
        } catch (\Exception $e) {
            $message = "Error creating database: " . $e->getMessage();
            Log::error('Database Creation Error: ' . $e->getMessage());
        }

        return response()->json([
            'message' => $message,
            'database' => $databaseName,
            'user_id' => $userId
        ]);
    }

    public function selectDatabase($database)
    {
        session(['selected_database' => $database]);

        Config::set('database.connections.dynamic_mysql.database', $database);
        DB::purge('dynamic_mysql');
        DB::setDefaultConnection('dynamic_mysql');
        DB::reconnect('dynamic_mysql');

        $tables = DB::connection('dynamic_mysql')->select('SHOW TABLES');

        $tableNames = array_map(function ($table) use ($database) {
            $tableKey = 'Tables_in_' . $database;
            return $table->{"Tables_in_$database"};
        }, $tables);

        return response()->json(['tables' => $tableNames]);
    }

    public function selectTable($database, $table)
    {
        Config::set('database.connections.dynamic_mysql.database', $database);
        DB::purge('dynamic_mysql');
        DB::setDefaultConnection('dynamic_mysql');
        DB::reconnect('dynamic_mysql');

        $columns = DB::connection('dynamic_mysql')->select("SHOW COLUMNS FROM $table");

        return response()->json(['columns' => $columns]);
    }

    public function execute(Request $request)
    {
        $query = $request->input('query');
        $selectedDatabase = session('selected_database');
        $result = [];
        $message = "";
        $isSuccess = true;
        $errorMessage = "";

        if ($selectedDatabase) {
            Log::info('Selected Database: ' . $selectedDatabase);

            Config::set('database.connections.dynamic_mysql.database', $selectedDatabase);
            DB::purge('dynamic_mysql');
            DB::setDefaultConnection('dynamic_mysql');
            DB::reconnect('dynamic_mysql');

            Log::info('Current Database: ' . DB::connection('dynamic_mysql')->getDatabaseName());

            try {
                Log::info('Executing Query: ' . $query);
                if (str_starts_with(strtolower(trim($query)), 'select')) {
                    $result = DB::connection('dynamic_mysql')->select($query);
                } else {
                    DB::connection('dynamic_mysql')->statement($query);
                    $message = "Query executed successfully.";
                    Log::info($message);
                }
            } catch (\Exception $e) {
                $message = "Query failed";
                $errorMessage = $e->getMessage();
                Log::error('SQL Error: ' . $errorMessage);
                $isSuccess = false;
            }
        } else {
            $message = "No database selected.";
            Log::warning('No database selected.');
            $isSuccess = false;
        }

        // Simpan history query ke database utama
        DB::connection('mysql')->table('query_history')->insert([
            'user_id' => auth()->id(),
            'database_name' => $selectedDatabase,
            'query' => $query,
            'is_success' => $isSuccess,
            'error_message' => $errorMessage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'result' => $result,
            'message' => $message,
            'query' => $query,
            'timestamp' => now()->format('H:i:s'),
            'isSuccess' => $isSuccess,
            'errorMessage' => $errorMessage, // Add error message
        ]);
    }


    public function viewTable($table)
    {
        $selectedDatabase = session('selected_database');
        $result = [];
        $message = "";

        if ($selectedDatabase) {
            Config::set('database.connections.dynamic_mysql.database', $selectedDatabase);
            DB::purge('dynamic_mysql');
            DB::setDefaultConnection('dynamic_mysql');
            DB::reconnect('dynamic_mysql');

            try {
                $result = DB::connection('dynamic_mysql')->select("SELECT * FROM $table LIMIT 100"); // Limit to 100 rows
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error('SQL Error: ' . $e->getMessage());
            }
        } else {
            $message = "No database selected.";
            Log::warning('No database selected.');
        }

        return response()->json(['result' => $result, 'message' => $message]);
    }

    public function getDatabases()
    {
        $userId = auth()->user()->id;
        $databases = DB::select("SHOW DATABASES");
        $userDatabases = [];

        foreach ($databases as $database) {
            $dbName = $database->Database; // Adjust this based on the exact structure of the object
            if (strpos($dbName, "user_{$userId}_") === 0) {
                $displayName = str_replace("user_{$userId}_", '', $dbName);
                $userDatabases[] = ['full' => $dbName, 'display' => $displayName];
            }
        }

        return response()->json(['databases' => $userDatabases]);
    }

    public function getHistory(Request $request)
{
    $selectedDatabase = $request->input('selectedDatabase');

    $history = DB::connection('mysql')->table('query_history')
        ->where('user_id', auth()->id())
        ->where('database_name', $selectedDatabase)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($history);
}

}


