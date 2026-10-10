<?php
function conheca_render_event_card(array $event): void {
    $escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
    ?><article class="i-event-card" data-event-id="<?= $escape($event['id'] ?? '') ?>">
    <?php if (conheca_public_url($event['image'] ?? null)): ?><img src="<?= $escape($event['image']) ?>" alt="" loading="lazy"><?php else: ?><div class="i-event-fallback">SUMARÉ</div><?php endif; ?>
    <div><small><?= $escape($event['category'] ?? 'Evento') ?> · <?= $escape(conheca_event_date_label($event)) ?></small>
    <h3><?= $escape($event['title']) ?></h3><p><?= $escape($event['place']) ?> <?= $escape($event['start_time']) ?></p>
    <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?><p>Até <?= $escape($event['end_date']) ?></p><?php endif; ?>
    <p><?= $escape($event['summary']) ?></p><p>Conferido em <?= $escape($event['verified_at']) ?></p>
    <a href="<?= $escape($event['source_url']) ?>" target="_blank" rel="noopener noreferrer">Fonte: <?= $escape($event['source_name'] ?? 'confirmação pública') ?> →</a></div></article><?php
}
