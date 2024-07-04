<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SQLController1 extends Controller
{
    // public function index(Request $request)
    // {
    //     $userId = auth()->user()->id;
    //     $databases = DB::select("SHOW DATABASES LIKE 'user_{$userId}_%'");
    //     $selectedDatabase = session('selected_database');
    //     $tables = [];

    //     if ($selectedDatabase) {
    //         Config::set('database.connections.dynamic_mysql.database', $selectedDatabase);
    //         DB::purge('dynamic_mysql');
    //         DB::setDefaultConnection('dynamic_mysql');
    //         DB::reconnect('dynamic_mysql');

    //         try {
    //             $tables = DB::connection('dynamic_mysql')->select('SHOW TABLES');
    //         } catch (\Exception $e) {
    //             Log::error('Error fetching tables: ' . $e->getMessage());
    //         }
    //     }

    //     $result = session('result', []);
    //     $message = session('message', '');

    //     return view('sql4', [
    //         'databases' => $databases,
    //         'tables' => $tables,
    //         'selectedDatabase' => $selectedDatabase,
    //         'result' => $result,
    //         'message' => $message
    //     ]);

    // }
    public function index(Request $request)
{
    $userId = auth()->user()->id;
    $databases = DB::select("SHOW DATABASES");
    $userDatabases = [];

    // Filter databases that match the pattern
    foreach ($databases as $database) {
        $dbName = $database->Database; // Adjust this based on the exact structure of the object
        if (strpos($dbName, "user_{$userId}_") === 0) {
            $userDatabases[] = $dbName;
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
        $databaseName = 'user_' . $userId . '_' . $request->input('database_name');
        $message = '';

        try {
            DB::statement("CREATE DATABASE $databaseName");
            $message = "Database $databaseName created successfully.";
        } catch (\Exception $e) {
            $message = "Error creating database: " . $e->getMessage();
            Log::error('Database Creation Error: ' . $e->getMessage());
        }

        return response()->json(['message' => $message]);
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

    // public function execute(Request $request)
    // {
    //     $query = $request->input('query');
    //     $selectedDatabase = session('selected_database');
    //     $result = [];
    //     $message = "";

    //     if ($selectedDatabase) {
    //         Log::info('Selected Database: ' . $selectedDatabase);

    //         Config::set('database.connections.dynamic_mysql.database', $selectedDatabase);
    //         DB::purge('dynamic_mysql');
    //         DB::setDefaultConnection('dynamic_mysql');
    //         DB::reconnect('dynamic_mysql');

    //         Log::info('Current Database: ' . DB::connection('dynamic_mysql')->getDatabaseName());

    //         try {
    //             if (str_starts_with(strtolower(trim($query)), 'select')) {
    //                 $result = DB::connection('dynamic_mysql')->select($query);
    //             } else {
    //                 DB::connection('dynamic_mysql')->statement($query);
    //                 $message = "Query executed successfully.";
    //             }
    //         } catch (\Exception $e) {
    //             $message = $e->getMessage();
    //             Log::error('SQL Error: ' . $e->getMessage());
    //         }
    //     } else {
    //         $message = "No database selected.";
    //         Log::warning('No database selected.');
    //     }

    //     return response()->json(['result' => $result, 'message' => $message, 'query' => $query]);
    // }
    public function execute(Request $request)
    {
        $query = $request->input('query');
        $selectedDatabase = session('selected_database');
        $result = [];
        $message = "";

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
                $message = $e->getMessage();
                Log::error('SQL Error: ' . $e->getMessage());
            }
        } else {
            $message = "No database selected.";
            Log::warning('No database selected.');
        }

        return response()->json(['result' => $result, 'message' => $message, 'query' => $query]);
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
}

