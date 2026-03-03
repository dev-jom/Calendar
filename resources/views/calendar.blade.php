<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendar</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        html, body { height: 100%; margin: 0; background: #000; color: #fff; font-family: system-ui, Arial; }
        .container { max-width: 1200px; margin: 24px auto; padding: 16px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 20px; }
        
        /* BUSCA */
        .search-container { position: relative; flex: 1; max-width: 500px; }
        #globalSearch { width: 100%; padding: 12px; background: #111; color: #fff; border: 1px solid #333; border-radius: 8px; }
        #searchResults { display: none; position: absolute; top: 50px; left: 0; right: 0; background: #0a0a0a; border: 1px solid #444; z-index: 9999; max-height: 400px; overflow-y: auto; border-radius: 8px; }
        .search-item { padding: 12px; border-bottom: 1px solid #222; cursor: pointer; }

        /* CALENDÁRIO */
        table.calendar { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.calendar td { width: 14.28%; height: 120px; vertical-align: top; border: 1px solid #222; padding: 10px; cursor: pointer; overflow: hidden; position: relative; }
        table.calendar td:hover { background: #080808; }
        .day-number { color: #888; font-size: 14px; margin-bottom: 5px; display: block; }

        /* PREVIEWS (COISAS QUE APARECEM NO CARD) */
        .cell-content { display: flex; flex-direction: column; gap: 4px; margin-top: 5px; }
        .note-preview { background: #a37bcc; color: #fff; font-size: 10px; padding: 2px 4px; border-radius: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .test-preview { background: #4a9eff; color: #fff; font-size: 10px; padding: 2px 4px; border-radius: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* MODAL CENTRALIZADO */
        .modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; }
        .modal-content { background: #111; padding: 25px; border: 1px solid #333; width: 95%; max-width: 600px; border-radius: 12px; position: relative; }
        .close { position: absolute; top: 15px; right: 20px; color: #666; font-size: 24px; cursor: pointer; }
        #noteTitle, #noteContent { width: 100%; padding: 12px; background: #050505; color: #fff; border: 1px solid #333; border-radius: 6px; margin-bottom: 15px; box-sizing: border-box; font-family: inherit; }
        .note-item { display: flex; align-items: center; background: #181818; padding: 10px; margin-bottom: 8px; border-radius: 6px; border: 1px solid #222; }
        .save-btn { background: #fff; color: #000; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>{{ $firstOfMonth->format('F Y') }}</h2>
        <div class="search-container">
            <input type="text" id="globalSearch" placeholder="🔍 Ctrl+F: Buscar em notas ou testes...">
            <div id="searchResults"></div>
        </div>
        <div class="month-nav">
            <a href="?month={{ $firstOfMonth->copy()->subMonth()->month }}&year={{ $firstOfMonth->copy()->subMonth()->year }}" style="color:#fff; text-decoration:none;">&larr; Ant</a>
            <a href="?month={{ $firstOfMonth->copy()->addMonth()->month }}&year={{ $firstOfMonth->copy()->addMonth()->year }}" style="color:#fff; text-decoration:none; margin-left:15px;">Próx &rarr;</a>
        </div>
    </div>

    <table class="calendar">
        <thead>
            <tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr>
        </thead>
        <tbody>
            @foreach($weeks as $week)
            <tr>
                @foreach($week as $day)
                    @php 
                        $dateKey = $day->format('Y-m-d'); 
                        // Pega as notas do banco e os testes da sessão
                        $dayNotes = $notesCollection->get($dateKey, []);
                        $dayTests = $previewTests[$dateKey] ?? [];
                    @endphp
                    <td class="cell-link" data-date="{{ $dateKey }}">
                        <span class="day-number">{{ $day->day }}</span>
                        <div class="cell-content">
                            {{-- Mostra as Notas (Roxo) --}}
                            @foreach($dayNotes as $note)
                                <div class="note-preview">
                                    {{ $note->title ?: \Illuminate\Support\Str::limit($note->content, 20) }}
                                </div>
                            @endforeach
                            
                            {{-- Mostra os Testes (Azul) --}}
                            @foreach($dayTests as $test)
                                <div class="test-preview">
                                    {{ $test['titulo'] ?? $test['tarefa_de'] ?? 'Teste' }}
                                </div>
                            @endforeach
                        </div>
                    </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div id="noteModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2 id="modalDateTitle" style="margin-top:0">Notas</h2>
        <div id="noteList" style="margin-bottom: 20px; max-height: 200px; overflow-y: auto;"></div>
        <input type="text" id="noteTitle" placeholder="Título da nota (opcional)">
        <textarea id="noteContent" rows="4" placeholder="Conteúdo da nota..."></textarea>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a id="addTestChoice" href="#" style="color: #4a9eff; text-decoration: none; font-size: 14px;">+ Adicionar Teste</a>
            <button id="saveNoteBtn" class="save-btn">Salvar Nota</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('noteModal');
    const closeBtn = document.getElementById('closeModal');
    const saveBtn = document.getElementById('saveNoteBtn');
    const noteList = document.getElementById('noteList');
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let editingDate = null;
    let editingNoteId = null;

    async function openModal(date) {
        editingDate = date;
        editingNoteId = null;
        document.getElementById('modalDateTitle').innerText = 'Notas: ' + date;
        document.getElementById('noteTitle').value = '';
        document.getElementById('noteContent').value = '';
        document.getElementById('addTestChoice').href = `/task-test/${date}`;
        await loadNoteList(date);
        modal.style.display = 'flex';
    }

    async function loadNoteList(date) {
        const res = await fetch(`/notes/${date}`);
        const data = await res.json();
        noteList.innerHTML = '';
        if (data.notes) {
            data.notes.forEach(n => {
                const div = document.createElement('div');
                div.className = 'note-item';
                div.innerHTML = `
                    <div style="flex:1; cursor:pointer" onclick="editNote(${n.id}, '${n.title||''}', \`${n.content||''}\`)">
                        <strong>${n.title || '(Sem título)'}</strong>
                    </div>
                    <button onclick="deleteNote(${n.id})" style="background:none; border:none; color:#f44; cursor:pointer;">Excluir</button>
                `;
                noteList.appendChild(div);
            });
        }
    }

    window.editNote = (id, title, content) => {
        editingNoteId = id;
        document.getElementById('noteTitle').value = title;
        document.getElementById('noteContent').value = content;
    };

    window.deleteNote = async (id) => {
        if (!confirm('Deseja excluir esta nota?')) return;
        const token = document.querySelector('meta[name="csrf-token"]').content;
        await fetch(`/note/${id}`, { method: 'DELETE', headers: {'X-CSRF-TOKEN': token} });
        loadNoteList(editingDate);
    };

    saveBtn.onclick = async () => {
        const title = document.getElementById('noteTitle').value;
        const content = document.getElementById('noteContent').value;
        const token = document.querySelector('meta[name="csrf-token"]').content;
        
        const res = await fetch(`/note/${editingDate}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ title, content, note_id: editingNoteId })
        });
        
        if (res.ok) {
            window.location.reload(); // Recarrega para atualizar os previews roxinhos no calendário
        }
    };

    document.querySelectorAll('.cell-link').forEach(cell => {
        cell.onclick = () => openModal(cell.dataset.date);
    });

    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };

    searchInput.oninput = async (e) => {
        const q = e.target.value;
        if (q.length < 2) { searchResults.style.display = 'none'; return; }
        const res = await fetch(`/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        const all = [...(data.notes || []), ...(data.tests || [])];
        
        searchResults.innerHTML = all.map(item => `
            <div class="search-item" onclick="goTo('${item.date}', ${item.type==='Teste'}, '${item.id||''}')">
                <small style="color:${item.type==='Teste'?'#4a9eff':'#a37bcc'}">${item.type}</small>
                <div style="font-weight:bold">${item.title}</div>
            </div>`).join('') || '<div style="padding:10px">Nada encontrado</div>';
        searchResults.style.display = 'block';
    };

    window.goTo = (date, isTest, id) => {
        searchResults.style.display = 'none';
        if (isTest) window.location.href = `/task-test/${id}/edit`;
        else openModal(date);
    };
</script>
</body>
</html>