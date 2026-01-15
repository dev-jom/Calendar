<?php

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Models\Note;
use App\Http\Controllers\NoteController;

Route::get('/', function () {
    return view('welcome');
});

// Preview routes to view the new blades locally
Route::get('/preview/calendar', function () {
    $firstOfMonth = Carbon::now()->startOfMonth();
    $start = (clone $firstOfMonth)->startOfWeek();
    $weeks = [];
    for ($w = 0; $w < 6; $w++) {
        $week = [];
        for ($d = 0; $d < 7; $d++) {
            $week[] = (clone $start)->addDays($w * 7 + $d);
        }
        $weeks[] = $week;
    }
    // load notes from database for the month so saved items persist
    $startDate = $weeks[0][0]->format('Y-m-d');
    $endDate = $weeks[5][6]->format('Y-m-d');
    $notesCollection = Note::whereBetween('date', [$startDate, $endDate])->orderBy('date')->orderBy('created_at')->get()->groupBy(function($n){ return $n->date->format('Y-m-d'); });
    $notes = $notesCollection->toArray();
    $tests = session('preview_tests', []);
    return view('calendar', compact('firstOfMonth','weeks','notes','tests'));
});

Route::get('/preview/task-test/{date}', function ($date) {
    $task = null;
    return view('task_test', ['date' => $date, 'task' => $task]);
});

// Notes API (persistent) using NoteController
Route::get('/note/{date}', [NoteController::class, 'byDate']);
Route::get('/note/{date}/{id}', [NoteController::class, 'show']);
Route::post('/note/{date}', [NoteController::class, 'store']);
Route::delete('/note/{id}', [NoteController::class, 'destroy']);

// Preview API for task-tests (stored in session)
Route::post('/task-test/{date}', function ($date) {
    $data = request()->all();
    $tests = session('preview_tests', []);
    if (!isset($tests[$date])) $tests[$date] = [];
    $id = (string) time() . mt_rand(1000,9999);
    $task = array_merge(['id' => $id, 'created_at' => now()->toDateTimeString()], $data);
    $tests[$date][] = $task;
    session(['preview_tests' => $tests]);
    return redirect()->route('calendar.index');
})->name('task_test.store');

Route::put('/task-test/{id}', function ($id) {
    $data = request()->all();
    $tests = session('preview_tests', []);
    foreach ($tests as $date => &$list) {
        foreach ($list as &$t) {
            if ((string)($t['id'] ?? '') === (string)$id) {
                $t = array_merge($t, $data);
                session(['preview_tests' => $tests]);
                return redirect()->route('calendar.index');
            }
        }
        unset($t);
    }
    abort(404, 'Test not found');
})->name('task_test.update');

Route::delete('/task-test/{id}', function ($id) {
    $tests = session('preview_tests', []);
    $found = false;
    foreach ($tests as $date => $list) {
        foreach ($list as $i => $t) {
            if ((string)($t['id'] ?? '') === (string)$id) {
                array_splice($tests[$date], $i, 1);
                $found = true;
                break 2;
            }
        }
    }
    if ($found) {
        session(['preview_tests' => $tests]);
        return response()->json(['ok' => true]);
    }
    abort(404, 'Test not found');
});

Route::get('/task-test/{id}/edit', function ($id) {
    $tests = session('preview_tests', []);
    foreach ($tests as $date => $list) {
        foreach ($list as $t) {
            if ((string)($t['id'] ?? '') === (string)$id) {
                return view('task_test', ['date' => $date, 'task' => (object)$t]);
            }
        }
    }
    abort(404, 'Test not found');
});
