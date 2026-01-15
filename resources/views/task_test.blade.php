<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teste de Tarefa - {{ $date }}</title>
    <style>
        body{background:#000;color:#fff;font-family:system-ui, Arial;margin:0;padding:20px}
        .card{max-width:800px;margin:24px auto;padding:18px;border:1px solid #222}
        label{display:block;margin-top:12px;color:#ccc}
        input[type=text], textarea{width:100%;padding:8px;background:#111;color:#fff;border:1px solid #333}
        .row{display:flex;gap:10px}
        .col{flex:1}
        button{margin-top:12px;padding:8px 12px;background:#222;color:#fff;border:1px solid #444}
    </style>
</head>
<body>
<div class="card">
    <h2>Teste de Tarefa — {{ $date }}</h2>
    @if(isset($task))
        <form method="post" action="{{ route('task_test.update', ['id' => $task->id]) }}">
            @csrf
            @method('PUT')
    @else
        <form method="post" action="{{ route('task_test.store', ['date' => $date]) }}">
            @csrf
    @endif

        <label>Tarefa de:</label>
        <input type="text" name="tarefa_de" value="{{ $task->tarefa_de ?? old('tarefa_de') }}">

        <label>Situação:</label>
        <input type="text" name="situacao" value="{{ $task->situacao ?? old('situacao') }}">

        <label>Estrutura:</label>
        <input type="text" name="estrutura" value="{{ $task->estrutura ?? old('estrutura') }}">

        <label>Link:</label>
        <input type="text" name="link" value="{{ $task->link ?? old('link') }}">

        <hr style="border-color:#222;margin:12px 0">

        <label>Título da Tarefa:</label>
        <input type="text" name="titulo" value="{{ $task->titulo ?? old('titulo') }}">

        <label>Data do Teste:</label>
        <input type="text" name="data_teste" value="{{ $task->data_teste ?? old('data_teste') }}">

        <label>Estrutura do Teste:</label>
        <input type="text" name="estrutura_teste" value="{{ $task->estrutura_teste ?? old('estrutura_teste') }}">

        <label>Navegador:</label>
        <input type="text" name="navegador" value="{{ $task->navegador ?? old('navegador') }}">

        <label>Relatório do Teste:</label>
        <textarea name="relatorio" rows="6">{{ $task->relatorio ?? old('relatorio') }}</textarea>

        <div style="text-align:right">
            <button type="submit">Salvar Teste</button>
            @if(isset($task))
                <button type="button" id="deleteTestBtn" style="margin-left:8px;background:#600">Excluir</button>
            @endif
        </div>
    </form>
    <div style="margin-top:12px"><a href="{{ route('calendar.index') }}">Voltar ao calendário</a></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const del = document.getElementById('deleteTestBtn');
        if(del){
            del.addEventListener('click', async function(){
                if(!confirm('Excluir este teste?')) return;
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const id = {{ $task->id ?? 'null' }};
                const res = await fetch(`/task-test/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token }});
                if(res.ok) window.location.href = '{{ route('calendar.index') }}';
                else alert('Erro ao excluir');
            });
        }
    });
</script>
</body>
</html>
