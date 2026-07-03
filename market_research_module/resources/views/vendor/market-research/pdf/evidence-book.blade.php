<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans;font-size:11px}.page{page-break-after:always}img{max-width:100%;border:1px solid #ccc}code{font-size:9px}</style></head><body>
<h1>Caderno de Evidências</h1>
<p><strong>Pesquisa:</strong> {{ $research->titulo }}</p>
@foreach($research->items as $item)
    @foreach($item->references->where('status','valido') as $ref)
        <div class="page">
            <h2>{{ $item->codigo }} - Referência #{{ $ref->id }}</h2>
            <p><strong>Fornecedor:</strong> {{ $ref->fornecedor }}</p>
            <p><strong>Valor:</strong> R$ {{ number_format((float)$ref->valor,2,',','.') }}</p>
            <p><strong>URL:</strong> <code>{{ $ref->url }}</code></p>
            <p><strong>Data:</strong> {{ optional($ref->data_pesquisa)->format('d/m/Y H:i') }}</p>
            <p><strong>Hash:</strong> <code>{{ $ref->hash }}</code></p>
            @if($ref->print_path && Storage::disk(config('market_research.evidence_disk','public'))->exists($ref->print_path))
                <img src="{{ Storage::disk(config('market_research.evidence_disk','public'))->path($ref->print_path) }}">
            @else
                <p>Print pendente.</p>
            @endif
        </div>
    @endforeach
@endforeach
</body></html>
