<x-filament-panels::page>
    <div
        class="mx-auto flex w-full max-w-5xl flex-col gap-4"
        x-data
        x-on:via-message-sent.window="$nextTick(() => { const el = document.getElementById('via-messages'); if (el) el.scrollTop = el.scrollHeight })"
    >
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <header class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">VIA — Vitrine IA Assistant</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Assistente interno em ambiente de homologação</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-success-500"></span>
                    Histórico ativo
                    @if ($conversationId)
                        <span aria-hidden="true">•</span>
                        Conversa #{{ $conversationId }}
                    @endif
                </div>
            </header>

            <div
                id="via-messages"
                class="flex min-h-[420px] max-h-[62vh] flex-col gap-5 overflow-y-auto bg-gray-50/70 px-4 py-6 dark:bg-gray-950/40 sm:px-6"
            >
                @foreach ($messages as $index => $chatMessage)
                    @php($isUser = $chatMessage['role'] === 'user')

                    <div wire:key="via-message-{{ $index }}" class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                        <div class="flex max-w-[88%] items-start gap-3 {{ $isUser ? 'flex-row-reverse' : '' }} sm:max-w-[78%]">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isUser ? 'bg-gray-900 text-white dark:bg-gray-700' : 'bg-primary-600 text-white' }}">
                                @if ($isUser)
                                    <x-heroicon-o-user class="h-5 w-5" />
                                @else
                                    <x-heroicon-o-sparkles class="h-5 w-5" />
                                @endif
                            </div>

                            <div class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm {{ $isUser ? 'rounded-tr-md bg-primary-600 text-white' : 'rounded-tl-md border border-gray-200 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100' }}">
                                <div class="whitespace-pre-wrap break-words">{{ $chatMessage['content'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div wire:loading.flex wire:target="sendMessage" class="items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white">
                        <x-heroicon-o-sparkles class="h-5 w-5" />
                    </div>
                    <div class="rounded-2xl rounded-tl-md border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        VIA está processando
                        <span class="inline-flex gap-1" aria-hidden="true">
                            <span class="animate-pulse">.</span><span class="animate-pulse">.</span><span class="animate-pulse">.</span>
                        </span>
                    </div>
                </div>
            </div>

            <form wire:submit="sendMessage" class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                <div class="flex items-end gap-3">
                    <div class="min-w-0 flex-1">
                        <label for="via-message" class="sr-only">Mensagem para a VIA</label>
                        <textarea
                            id="via-message"
                            wire:model="message"
                            rows="2"
                            maxlength="8000"
                            placeholder="Digite sua mensagem para a VIA..."
                            class="block w-full resize-none rounded-xl border-gray-300 bg-white text-sm text-gray-950 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Nesta fase, as mensagens já são persistidas. A resposta inteligente será conectada ao roteador de IA na próxima etapa.
                        </p>
                    </div>

                    <x-filament::button
                        type="submit"
                        icon="heroicon-m-paper-airplane"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                    >
                        Enviar
                    </x-filament::button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('via-messages');
            if (el) el.scrollTop = el.scrollHeight;
        });
    </script>
</x-filament-panels::page>
