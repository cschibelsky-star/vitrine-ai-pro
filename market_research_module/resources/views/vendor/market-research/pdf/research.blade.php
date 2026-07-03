<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans;font-size:11px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccc;padding:5px}h1,h2{margin-bottom:6px}</style></head><body>
<h1>Pesquisa Inteligente de Mercado</h1>
<p><strong>Título:</strong> {{ $research->titulo }}</p>
<p><strong>Cliente:</strong> {{ $research->cliente }}</p>
<p><strong>Processo:</strong> {{ $research->processo }}</p>
<p><strong>Objeto:</strong> {{ $research->objeto }}</p>
<table><thead><tr><th>Item</th><th>Descrição</th><th>Fornecedor</th><th>Valor</th><th>URL</th><th>Status</th></tr></thead><tbody>
@foreach($research->items as $item)
    @php($validRefs = $item->references->where('status','valido'))
    @forelse($validRefs as $ref)
        <tr><td>{{ $item->codigo }}</td><td>{{ $item->descricao }}</td><td>{{ $ref->fornecedor }}</td><td>R$ {{ number_format((float)$ref->valor,2,',','.') }}</td><td>{{ $ref->url }}</td><td>{{ $ref->status }}</td></tr>
    @empty
        <tr><td>{{ $item->codigo }}</td><td>{{ $item->descricao }}</td><td colspan="4">Em branco / sem referência válida registrada.</td></tr>
    @endforelse
@endforeach
</tbody></table></body></html>
