<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    // Return notes and (optionally) tests for a given date
    public function byDate($date)
    {
        $notes = Note::where('date', $date)->orderBy('created_at')->get();
        return response()->json(['notes' => $notes]);
    }

    // Return a single note by date and id
    public function show($date, $id)
    {
        $note = Note::where('id', $id)->where('date', $date)->first();
        if (!$note) return response()->json(['message' => 'Not found'], 404);
        return response()->json($note);
    }

    // Create or update a note for a given date
    public function store(Request $request, $date)
    {
        $data = $request->only(['title','content','note_id']);
        if (!empty($data['note_id'])) {
            $note = Note::where('id', $data['note_id'])->where('date', $date)->first();
            if (!$note) return response()->json(['message' => 'Not found'], 404);
            $note->title = $data['title'];
            $note->content = $data['content'];
            $note->save();
            return response()->json($note);
        }
        $note = Note::create(['date' => $date, 'title' => $data['title'] ?? null, 'content' => $data['content'] ?? null]);
        return response()->json($note);
    }

    public function destroy($id)
    {
        $note = Note::find($id);
        if (!$note) return response()->json(['message' => 'Not found'], 404);
        $note->delete();
        return response()->json(['ok' => true]);
    }
}
