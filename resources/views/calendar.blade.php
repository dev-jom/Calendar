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
    <div class="title">
        <h2 style="margin:0">{{ $firstOfMonth->format('F Y') }}</h2>
    </div>
    
    <div class="search-container" style="position: relative; flex: 1; max-width: 400px; margin: 0 20px;">
        <input type="text" id="globalSearch" placeholder="Pesquisar notas ou testes..." 
               style="width: 100%; padding: 10px; background: #111; color: #fff; border: 1px solid #333; border-radius: 4px; outline: none;">
        
        <div id="searchResults" style="display:none; position: absolute; top: 45px; left: 0; right: 0; background: #0a0a0a; border: 1px solid #333; z-index: 1000; max-height: 500px; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.8);">
            </div>
    </div>

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
// --- FUNÇÕES AUXILIARES ---
function truncate(str, n) {
    if (!str) return '';
    return (str.length > n) ? str.substr(0, n - 1) + '...' : str;
}

function highlight(text, term) {
    if (!term || !text) return text;
    const regex = new RegExp(`(${term})`, 'gi');
    return text.replace(regex, '<mark style="background: #ffeb3b; color: #000; padding: 0 2px; border-radius: 2px;">$1</mark>');
}

// --- VARIÁVEIS DO MODAL (JÁ EXISTENTES) ---
const modal = document.getElementById('noteModal');
const closeBtn = document.querySelector('.close');
const saveBtn = document.getElementById('saveNoteBtn');
const noteContent = document.getElementById('noteContent');
const noteList = document.getElementById('noteList');
let editingDate = null;
let editingNoteId = null;

// --- LÓGICA DE BUSCA (CTRL + F) ---
const searchInput = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

if (searchInput) {
    searchInput.addEventListener('input', async (e) => {
        const q = e.target.value;
        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        try {
            const res = await fetch(`/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            const all = [...(data.notes || []), ...(data.tests || [])];

            if (all.length === 0) {
                searchResults.innerHTML = '<div style="padding:15px; color:#666;">Nenhum texto encontrado.</div>';
            } else {
                searchResults.innerHTML = all.map(item => {
                    const cleanContent = item.excerpt ? item.excerpt.replace(/<[^>]*>?/gm, '') : '';
                    const highlightedText = highlight(truncate(cleanContent, 100), q);

                    return `
                        <div onclick="goToResult('${item.date}', ${item.type === 'Teste'}, '${item.id || ''}')" 
                             style="padding: 12px; border-bottom: 1px solid #222; cursor: pointer; background: #0a0a0a;"
                             onmouseover="this.style.background='#151515'" onmouseout="this.style.background='#0a0a0a'">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 4px;">
                                <small style="color:${item.type === 'Teste' ? '#4a9eff' : '#a37bcc'}; font-weight:bold; font-size:10px;">${item.type.toUpperCase()}</small>
                                <small style="color:#444; font-size:10px;">${item.date}</small>
                            </div>
                            <div style="color:#fff; font-size:13px; font-weight:bold; margin-bottom:4px;">${item.title}</div>
                            <div style="color:#888; font-size:12px; line-height:1.4;">
                                ...${highlightedText}...
                            </div>
                        </div>`;
                }).join('');
            }
            searchResults.style.display = 'block';
        } catch (err) {
            console.error("Erro na busca:", err);
        }
    });
}

// Fechar busca ao clicar fora
document.addEventListener('click', (e) => {
    if (searchResults && !e.target.closest('.search-container')) {
        searchResults.style.display = 'none';
    }
});

// Ir para o resultado
window.goToResult = function(date, isTest, id) {
    searchResults.style.display = 'none';
    searchInput.value = '';
    if (isTest && id) {
        window.location.href = `/task-test/${id}/edit`;
    } else {
        const cell = document.querySelector(`.cell-link[data-date="${date}"]`);
        if (cell) cell.click();
    }
};

// --- RESTO DAS FUNÇÕES DO CALENDÁRIO (MANTER) ---
async function openModal(date) {
    editingDate = date;
    editingNoteId = null;
    document.getElementById('modalDateTitle').innerText = 'Notas: ' + date;
    document.getElementById('noteTitle').value = '';
    noteContent.value = '';
    
    const choiceLink = document.getElementById('addTestChoice');
    if(choiceLink) choiceLink.href = `/task-test/${date}`;

    await loadNoteList(date);
    modal.style.display = 'block';
}

async function loadNoteList(date) {
    const res = await fetch(`/notes/${date}`);
    const data = await res.json();
    noteList.innerHTML = '';
    data.notes.forEach(n => {
        const div = document.createElement('div');
        div.className = 'note-item';
        div.dataset.id = n.id;
        div.innerHTML = `
            <div class="note-handle">::</div>
            <div style="flex:1" onclick="editNote(${n.id}, '${n.title||''}', \`${n.content||''}\`)">
                <strong>${n.title || '(Sem título)'}</strong>
            </div>
            <button class="delete-note-btn" onclick="deleteNote(${n.id})">Excluir</button>
        `;
        noteList.appendChild(div);
    });
}

function editNote(id, title, content) {
    editingNoteId = id;
    document.getElementById('noteTitle').value = title;
    noteContent.value = content;
}

async function deleteNote(id) {
    if(!confirm('Excluir esta nota?')) return;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const res = await fetch(`/note/${id}`, { method: 'DELETE', headers: {'X-CSRF-TOKEN': token} });
    if(res.ok) await loadNoteList(editingDate);
}

// Event Listeners básicos
document.querySelectorAll('.cell-link').forEach(link => {
    link.addEventListener('click', (e) => {
        if(e.target.closest('.preview-test-link')) return;
        openModal(link.dataset.date);
    });
});

closeBtn.onclick = () => { modal.style.display = "none"; };
window.onclick = (event) => { if (event.target == modal) modal.style.display = "none"; };

saveBtn.addEventListener('click', async () => {
    if (!editingDate) return;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const payload = { title: document.getElementById('noteTitle').value, content: noteContent.value };
    if (editingNoteId) payload.note_id = editingNoteId;
    
    const res = await fetch(`/note/${editingDate}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(payload)
    });
    if (res.ok) {
        editingNoteId = null;
        document.getElementById('noteTitle').value = '';
        noteContent.value = '';
        await loadNoteList(editingDate);
    }
});
</script>

</body>
</html>
