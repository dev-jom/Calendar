<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendar</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        /* Black & white theme */
        html,body{height:100%;margin:0;background:#000;color:#fff;font-family:system-ui, Arial}
        .container{max-width:1200px;margin:24px auto;padding:16px}
        .header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
        .month-nav a{color:#fff;text-decoration:none;padding:8px;border:1px solid #333;margin:0 6px}
        table.calendar{width:100%;border-collapse:collapse}
        table.calendar th{padding:12px;text-align:center;color:#bbb}
        table.calendar td{width:14.2857%;height:120px;vertical-align:top;border:1px solid #111;padding:8px;position:relative}
        .day-number{position:absolute;top:6px;left:8px;color:#bbb;font-size:14px}
        .today{background:#111;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;color:#fff}
        .note-badge{display:block;background:#1a1a1a;color:#9f9;height:auto;padding:4px;border-radius:4px;margin-top:28px;font-size:13px}
        /* stacked note titles inside calendar cell */
        /* use top+bottom to reserve space and allow the +N badge to stick to the bottom */
        .day-notes{position:absolute;top:36px;left:8px;right:8px;bottom:10px;display:flex;flex-direction:column;gap:6px;max-width:calc(100% - 16px);overflow:hidden}
        .day-note-line{background:#3e3e3e52;color:#a37bcc;padding:6px 8px;border-radius:0px;font-size:12px;line-height:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;box-shadow:0 1px 0 rgba(255,255,255,0.03);width:100%}
        /* push the more badge to the bottom of the .day-notes area without changing DOM order */
        .more-badge{background:#111;color:#ccc;padding:1px 54px;border-radius:0px;font-size:10px;display:inline-block;margin-top:auto;border:1px solid #222}
        .test-badge{display:block;background:#111;color:#9bf;height:auto;padding:4px;border-radius:4px;margin-top:6px;font-size:12px}
        .cell-link{display:block;height:100%;cursor:pointer}
            
        /* modal */
        .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);z-index:40}
        .modal .card{background:#000;border:1px solid #333;padding:18px;width:600px;color:#fff;border-radius:8px}
        textarea{width:100%;height:220px;background:#000;color:#fff;border:1px solid #333;padding:8px}
        button{background:#222;color:#fff;border:1px solid #444;padding:8px 12px;margin-right:8px}
        .drag-handle{cursor:move; padding: 0 5px;}
        @media(max-width:700px){table.calendar td{height:90px} .modal .card{width:90%}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="title"><h2 style="margin:0">{{ $firstOfMonth->format('F Y') }}</h2></div>
        <div class="month-nav">
            @php
                $prev = (clone $firstOfMonth)->subMonth();
                $next = (clone $firstOfMonth)->addMonth();
            @endphp
            <a href="?month={{ $prev->month }}&year={{ $prev->year }}">&larr; Prev</a>
            <a href="?month={{ $next->month }}&year={{ $next->year }}">Next &rarr;</a>
        </div>
    </div>

    <table class="calendar">
        <thead>
        <tr>
            <th>Mon</th>
            <th>Tue</th>
            <th>Wed</th>
            <th>Thu</th>
            <th>Fri</th>
            <th>Sat</th>
            <th>Sun</th>
        </tr>
        </thead>
        <tbody>
        @foreach($weeks as $week)
            <tr>
                @foreach($week as $day)
                    @php $dateStr = $day->format('Y-m-d'); @endphp
                    <td>
                        <a class="cell-link" data-date="{{ $dateStr }}">
                            <div class="day-number">
                                @if($day->isToday())
                                    <span class="today">{{ $day->day }}</span>
                                @else
                                    {{ $day->day }}
                                @endif
                            </div>
                                            @if(isset($notes[$dateStr]))
                                                @php
                                                    $dayNotes = $notes[$dateStr];
                                                    // build a simple array of titles for arrays or collections
                                                    $titles = [];
                                                    if (is_array($dayNotes)) {
                                                        foreach ($dayNotes as $n) {
                                                            $titles[] = $n['title'] ?? $n['content'] ?? null;
                                                        }
                                                    } else {
                                                        foreach ($dayNotes as $n) {
                                                            $titles[] = $n->title ?? $n->content ?? null;
                                                        }
                                                    }
                                                    $titles = array_values(array_filter($titles));
                                                @endphp
                                                @if(!empty($titles))
                                                    @php
                                                        $visible = array_slice($titles, 0, 3);
                                                        $more = max(0, count($titles) - 3);
                                                    @endphp
                                                    <div class="day-notes">
                                                        @foreach($visible as $t)
                                                            <div class="day-note-line">{{ Str::limit($t, 60) }}</div>
                                                        @endforeach
                                                        @if($more > 0)
                                                            <div class="more-badge">+{{ $more }} mais</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                            @if(isset($tests) && isset($tests[$dateStr]))
                                @php
                                    $dayTests = $tests[$dateStr];
                                    if (is_array($dayTests)) {
                                        $ft = $dayTests[0] ?? null;
                                        $testNum = $ft['teste'] ?? $ft['titulo'] ?? $ft['tarefa_de'] ?? null;
                                    } else {
                                        $ft = $dayTests->first();
                                        $testNum = $ft->teste ?? $ft->titulo ?? $ft->tarefa_de ?? null;
                                    }
                                @endphp
                                @if($testNum)
                                    <span class="test-badge">Teste da Tarefa: {{ Str::limit($testNum, 30) }}</span>
                                @endif
                            @endif
                        </a>
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="noteModal" class="modal">
    <div class="card">
        <h3 id="modalDate">Date</h3>
        <div id="noteList" style="max-height:160px;overflow:auto;margin-bottom:8px;color:#ddd"></div>
        <input id="noteTitle" placeholder="Título da nota" style="width:100%;padding:8px;margin-bottom:8px;background:#111;color:#fff;border:1px solid #333" />
        <textarea id="noteContent" placeholder="Escreva o conteúdo da nota (opcional)..." style="height:140px"></textarea>
        <div style="margin-top:12px;text-align:right">
            <button id="saveBtn">Salvar</button>
            <button id="closeBtn">Fechar</button>
        </div>
    </div>
</div>

<!-- Choice Modal -->
<div id="choiceModal" class="modal">
    <div class="card">
        <h3 id="choiceDate">Escolha ação</h3>
        <div id="choicePreviews" style="margin-top:12px;color:#ddd;max-height:220px;overflow:auto"></div>
        <div style="margin-top:12px">
            <button id="openNoteBtn">Adicionar nota</button>
            <button id="choiceCloseBtn" style="margin-left:8px">Cancelar</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('noteModal');
    const noteContent = document.getElementById('noteContent');
    const modalDate = document.getElementById('modalDate');
    let editingDate = null;
    let editingNoteId = null;

    // show choice modal first
    document.querySelectorAll('.cell-link').forEach(el => {
        el.addEventListener('click', async (e) => {
            e.preventDefault();
            const date = el.getAttribute('data-date');
            document.getElementById('choiceDate').textContent = date;
            // store selected date on modal buttons
            document.getElementById('openNoteBtn').dataset.date = date;
            // load inline previews for this date
            try{ loadChoicePreviews(date); } catch(err){}
            document.getElementById('choiceModal').style.display = 'flex';
        });
    });

    document.getElementById('choiceCloseBtn').addEventListener('click', () => {
        document.getElementById('choiceModal').style.display = 'none';
    });

    document.getElementById('openNoteBtn').addEventListener('click', async (e) => {
        const date = e.currentTarget.dataset.date;
        editingDate = date;
        editingNoteId = null;
        modalDate.textContent = date;
        noteContent.value = '';
        document.getElementById('noteTitle').value = '';
        await loadNoteList(date);
        document.getElementById('choiceModal').style.display = 'none';
        modal.style.display = 'flex';
    });

    // removed task-test button per user request

        // load inline previews into choice modal (shows tests and notes with fields)
        async function loadChoicePreviews(dateStr){
            const container = document.getElementById('choicePreviews');
            container.innerHTML = 'Carregando...';
            try{
                const res = await fetch('/note/' + dateStr, { headers: { 'Accept': 'application/json' }});
                if(!res.ok){
                    const text = await res.text().catch(()=>'');
                    container.innerHTML = `<div style="color:#f66">Erro ${res.status} ${res.statusText}: ${escapeHtml(text.substring(0,200))}</div>`;
                    console.error('loadChoicePreviews fetch error', res.status, res.statusText, text);
                    return;
                }
                const data = await res.json();
                let parts = [];
                if(data.tests && data.tests.length){
                    parts.push('<div style="margin-bottom:8px;color:#ddd"><strong>Testes</strong></div>');
                    data.tests.forEach(t => {
                        const title = t.titulo || t.tarefa_de || '(sem título)';
                        const short = truncate(title, 60);
                        let left = `<div style="flex:1"><div style="font-size:13px;color:#eee"><strong>${escapeHtml(short)}</strong></div>`;
                        if(t.teste) left += `<div style="font-size:12px;color:#bbb;margin-top:4px">${escapeHtml(t.teste)}</div>`;
                        if(t.link) left += `<div style="font-size:11px;color:#666;margin-top:6px">${truncate('Link: ' + (t.link||''), 60)}</div>`;
                        left += '</div>';
                        const actions = `<div style="margin-left:8px;display:flex;flex-direction:column;gap:6px"><button data-id="${t.id}" data-date="${dateStr}" class="edit-test">Editar</button><button data-id="${t.id}" class="delete-test" style="background:#600">Excluir</button></div>`;
                        parts.push(`<div style="padding:6px 8px;border-bottom:1px solid #111;display:flex;justify-content:space-between;align-items:flex-start">${left}${actions}</div>`);
                    });
                }
                if(data.notes && data.notes.length){
                    parts.push('<div style="margin-top:6px;margin-bottom:8px;color:#ddd"><strong>Notas</strong></div>');
                    let notes_html = '<div id="notes-list">';
                    data.notes.forEach(n => {
                        const created = n.created_at ? ` <span style="font-size:11px;color:#666">(${escapeHtml(n.created_at)})</span>` : '';
                        const title = n.title || n.content || '(sem título)';
                        const short = truncate(title, 80);
                        notes_html += `<div data-id="${n.id}" style="padding:6px 8px;border-bottom:1px solid #111;color:#fff;font-size:13px;display:flex;justify-content:space-between;align-items:flex-start"><span class="drag-handle">&#9776;</span><div style="flex:1">${escapeHtml(short)}${created}</div><div style="margin-left:8px"><button data-id="${n.id}" data-date="${dateStr}" class="edit-note-inline">Editar</button> <button data-id="${n.id}" class="delete-note-inline" style="background:#600">Excluir</button></div></div>`;
                    });
                    notes_html += '</div>';
                    parts.push(notes_html);
                }
                container.innerHTML = parts.length ? parts.join('') : '<div style="color:#999">Sem registros</div>';
                initSortable();
            }catch(err){ console.error(err); container.innerHTML = `<div style="color:#f66">Erro ao carregar: ${escapeHtml(err.message || String(err))}</div>`; }
        }

        // load list of notes inside note modal (with edit buttons)
        async function loadNoteList(dateStr){
            const list = document.getElementById('noteList');
            list.innerHTML = 'Carregando...';
            try{
                const res = await fetch('/note/' + dateStr, { headers: { 'Accept': 'application/json' }});
                if(!res.ok){
                    const text = await res.text().catch(()=>'');
                    list.innerHTML = `<div style="color:#f66">Erro ${res.status}: ${escapeHtml(text.substring(0,200))}</div>`;
                    console.error('loadNoteList fetch error', res.status, res.statusText, text);
                    return;
                }
                const data = await res.json();
                const notes = data.notes || [];
                if(!notes.length){
                    list.innerHTML = '<div style="color:#999">Nenhuma nota ainda</div>';
                    return;
                }
                let html = '';
                    notes.forEach(n => {
                        const created = n.created_at ? ` <div style="font-size:11px;color:#666;margin-top:6px">${escapeHtml(n.created_at)}</div>` : '';
                        const title = n.title || n.content || '(sem título)';
                        const short = truncate(title, 120);
                        html += `<div style="padding:6px 8px;border-bottom:1px solid #111;display:flex;justify-content:space-between;align-items:flex-start"><div style="flex:1;color:#ddd;font-size:13px">${escapeHtml(short)}${created}</div><div style="margin-left:8px"><button data-id="${n.id}" class="edit-note">Editar</button> <button data-id="${n.id}" class="delete-note" style="margin-left:6px;background:#600">Excluir</button></div></div>`;
                });
                list.innerHTML = html;
                // attach edit handlers
                    // attach edit handlers
                    list.querySelectorAll('.edit-note').forEach(b => {
                        b.addEventListener('click', (ev) => {
                            const id = ev.currentTarget.dataset.id;
                            const note = notes.find(x => x.id == id);
                            if(note){ editingNoteId = id; noteContent.value = note.content || ''; document.getElementById('noteTitle').value = note.title || ''; }
                        });
                    });
                    // attach delete handlers
                    list.querySelectorAll('.delete-note').forEach(b => {
                        b.addEventListener('click', async (ev) => {
                            const id = ev.currentTarget.dataset.id;
                            if(!confirm('Excluir esta nota?')) return;
                            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const res = await fetch(`/note/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token }});
                            if(res.ok){ await loadNoteList(editingDate); try{ loadChoicePreviews(editingDate); }catch(e){} } else { alert('Erro ao excluir'); }
                        });
                    });
            }catch(e){ console.error(e); list.innerHTML = '<div style="color:#999">Erro ao carregar</div>'; }
        }

            // attach delete handlers for tests in choice preview (delegated)
            document.addEventListener('click', async function(ev){
                const target = ev.target;
                if(target && target.classList && target.classList.contains('delete-test')){
                    const id = target.dataset.id;
                    if(!confirm('Excluir este teste?')) return;
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(`/task-test/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token }});
                    if(res.ok){
                        // refresh previews and calendar
                        try{ if(editingDate) loadChoicePreviews(editingDate); }catch(e){}
                        window.location.reload();
                    } else {
                        alert('Erro ao excluir teste');
                    }
                }
                // edit test (navigate to edit page)
                if(target && target.classList && target.classList.contains('edit-test')){
                    const id = target.dataset.id;
                    if(id) window.location.href = `/task-test/${id}/edit`;
                }
                // inline edit on note items displayed in choice previews
            if(target && target.classList && target.classList.contains('edit-note-inline')){
                    const id = target.dataset.id;
                    const date = target.dataset.date;
                    // fetch note and open modal for editing
                    const res = await fetch(`/note/${date}/${id}`);
                    if(res.ok){
                        const note = await res.json();
                        editingDate = date;
                        editingNoteId = note.id;
                        modalDate.textContent = date;
                        document.getElementById('noteTitle').value = note.title || '';
                        noteContent.value = note.content || '';
                        document.getElementById('choiceModal').style.display = 'none';
                        modal.style.display = 'flex';
                    } else {
                        alert('Erro ao abrir nota');
                    }
                }
            // delete note inline from choice preview
            if(target && target.classList && target.classList.contains('delete-note-inline')){
                const id = target.dataset.id;
                if(!confirm('Excluir esta nota?')) return;
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch(`/note/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token }});
                if(res.ok){ try{ if(editingDate) loadChoicePreviews(editingDate); }catch(e){} window.location.reload(); } else { alert('Erro ao excluir'); }
            }
            });

    function initSortable() {
        const list = document.getElementById('notes-list');
        if (!list) return;
        new Sortable(list, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: async function (evt) {
                const noteIds = Array.from(list.children).map(item => item.dataset.id);
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/notes/reorder-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ noteIds: noteIds })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Erro ao reordenar');
                }
            }
        });
    }

    // preview button removed; previews are inline in the choice modal

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function truncate(s, n){
        if(!s) return '';
        return s.length > n ? s.slice(0,n-1) + '…' : s;
    }

    document.getElementById('closeBtn').addEventListener('click', async () => {
        // close note modal and reopen choice modal showing the list for the same date
        modal.style.display = 'none';
        if (editingDate) {
            document.getElementById('choiceDate').textContent = editingDate;
            document.getElementById('openNoteBtn').dataset.date = editingDate;
            try{ await loadChoicePreviews(editingDate); } catch(e){}
            document.getElementById('choiceModal').style.display = 'flex';
        }
    });

    document.getElementById('saveBtn').addEventListener('click', async () => {
        if (!editingDate) return;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const payload = { title: document.getElementById('noteTitle').value, content: noteContent.value };
        if (editingNoteId) payload.note_id = editingNoteId;
        const res = await fetch(`/note/${editingDate}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        if (res.ok) {
            // refresh list and previews, keep modal open for adding more
            editingNoteId = null;
            document.getElementById('noteTitle').value = '';
            noteContent.value = '';
            await loadNoteList(editingDate);
            try{ loadChoicePreviews(editingDate); }catch(e){}
        } else {
            // try to parse JSON error message, otherwise text
            let text = '';
            try{
                const json = await res.json();
                text = json.message || JSON.stringify(json);
            }catch(e){
                try{ text = await res.text(); }catch(e2){ text = String(e2); }
            }
            console.error('Save note failed', res.status, res.statusText, text);
            alert(`Error saving: ${res.status} ${res.statusText}\n${text}`);
        }
    });
</script>

</body>
</html>
