<?php

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Models\Note;
use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;

Route::get('/', function (Request $request) {
    // Permite navegar pelos meses via ?month=MM&year=YYYY
    $m = $request->get('month', date('m'));
    $y = $request->get('year', date('Y'));

    try {
        $firstOfMonth = Carbon::createFromDate((int)$y, (int)$m, 1)->startOfMonth();
    } catch (Exception $e) {
        $firstOfMonth = Carbon::now()->startOfMonth();
    }

    $start = (clone $firstOfMonth)->startOfWeek(Carbon::SUNDAY);
    $weeks = [];
    
    // Gera 6 semanas para garantir que o calendário sempre fique preenchido
    for ($w = 0; $w < 6; $w++) {
        $week = [];
        for ($d = 0; $d < 7; $d++) {
            $week[] = (clone $start)->addDays($w * 7 + $d);
        }
        $weeks[] = $week;
    }

    $startDate = $weeks[0][0]->format('Y-m-d');
    $endDate = $weeks[5][6]->format('Y-m-d');

    // BUSCA AS NOTAS NO BANCO
    $notesCollection = Note::whereBetween('date', [$startDate, $endDate])
        ->orderBy('date')
        ->orderBy('order')
        ->orderBy('created_at')
        ->get()
        ->groupBy(function($n){ 
            // Garante que a chave do grupo seja a string Y-m-d
            return is_string($n->date) ? $n->date : $n->date->format('Y-m-d'); 
        });

    // TESTES ARMAZENADOS NA SESSÃO
    $previewTests = session('preview_tests', []);

    // IMPORTANTE: O nome no compact deve ser 'notesCollection' para bater com o Blade
    return view('calendar', compact('firstOfMonth', 'weeks', 'notesCollection', 'previewTests'));
});

// Rota de busca global
Route::get('/search', [NoteController::class, 'search'])->name('notes.search');

// API de Notas (Persistente no DBeaver/Banco)
Route::get('/notes/{date}', [NoteController::class, 'byDate']); // Ajustado para bater com o fetch do JS
Route::get('/note/{date}/{id}', [NoteController::class, 'show']);
Route::post('/note/{date}', [NoteController::class, 'store']);
Route::delete('/note/{id}', [NoteController::class, 'destroy']);
Route::post('/notes/reorder-all', [NoteController::class, 'reorderAll']);

// API de Testes (Temporário em Sessão)
Route::post('/task-test/{date}', function ($date) {
    $data = request()->all();
    $tests = session('preview_tests', []);
    if (!isset($tests[$date])) $tests[$date] = [];
    $id = (string) time() . mt_rand(1000,9999);
    $task = array_merge(['id' => $id, 'created_at' => now()->toDateTimeString()], $data);
    $tests[$date][] = $task;
    session(['preview_tests' => $tests]);
    return redirect('/');
})->name('task_test.store');

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

// Outras rotas de teste (Update/Delete)
Route::put('/task-test/{id}', function ($id) {
    $data = request()->all();
    $tests = session('preview_tests', []);
    foreach ($tests as $date => &$list) {
        foreach ($list as &$t) {
            if ((string)($t['id'] ?? '') === (string)$id) {
                $t = array_merge($t, $data);
                session(['preview_tests' => $tests]);
                return redirect('/');
            }
        }
    }
    abort(404);
})->name('task_test.update');