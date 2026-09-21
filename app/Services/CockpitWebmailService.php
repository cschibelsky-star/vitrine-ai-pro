<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class CockpitWebmailService
{
    public function account(string $key): array
    {
        $account = config("cockpit-webmail.accounts.{$key}");

        if (! is_array($account)) {
            throw new RuntimeException('Conta de e-mail não configurada.');
        }

        $secretName = (string) ($account['credential_secret'] ?? '');
        $password = $secretName !== '' ? (string) env($secretName, '') : '';

        if ($password === '') {
            throw new RuntimeException('Credencial da conta não está disponível no runtime.');
        }

        $account['password'] = $password;

        return $account;
    }

    public function folders(string $key): array
    {
        $account = $this->account($key);
        $mailbox = $this->connect($account, 'INBOX');

        try {
            $root = $this->mailboxRoot($account);
            $items = imap_list($mailbox, $root, '*') ?: [];
            $folders = [];

            foreach ($items as $item) {
                $name = str_replace($root, '', $item);
                $decoded = imap_utf7_decode($name);
                $folders[] = $decoded !== '' ? $decoded : $name;
            }

            return array_values(array_unique($folders ?: ['INBOX']));
        } finally {
            imap_close($mailbox);
        }
    }

    public function messages(string $key, string $folder = 'INBOX', int $limit = 30): array
    {
        $account = $this->account($key);
        $mailbox = $this->connect($account, $folder);

        try {
            $count = imap_num_msg($mailbox);
            if ($count < 1) {
                return [];
            }

            $start = max(1, $count - $limit + 1);
            $overview = imap_fetch_overview($mailbox, "{$start}:{$count}", 0) ?: [];
            $messages = [];

            foreach (array_reverse($overview) as $item) {
                $msgno = (int) ($item->msgno ?? 0);
                $messages[] = [
                    'uid' => (int) imap_uid($mailbox, $msgno),
                    'subject' => $this->decodeHeader((string) ($item->subject ?? '(Sem assunto)')),
                    'from' => $this->decodeHeader((string) ($item->from ?? '')),
                    'date' => (string) ($item->date ?? ''),
                    'seen' => ! empty($item->seen),
                    'size' => (int) ($item->size ?? 0),
                ];
            }

            return $messages;
        } finally {
            imap_close($mailbox);
        }
    }

    public function message(string $key, int $uid, string $folder = 'INBOX'): array
    {
        $account = $this->account($key);
        $mailbox = $this->connect($account, $folder);

        try {
            $overview = imap_fetch_overview($mailbox, (string) $uid, FT_UID);
            if (! $overview) {
                throw new RuntimeException('Mensagem não encontrada.');
            }

            $item = $overview[0];
            $structure = imap_fetchstructure($mailbox, $uid, FT_UID);
            $body = $this->extractBody($mailbox, $uid, $structure);

            return [
                'uid' => $uid,
                'subject' => $this->decodeHeader((string) ($item->subject ?? '(Sem assunto)')),
                'from' => $this->decodeHeader((string) ($item->from ?? '')),
                'to' => $this->decodeHeader((string) ($item->to ?? '')),
                'date' => (string) ($item->date ?? ''),
                'body' => trim($body) !== '' ? trim($body) : '(Mensagem sem corpo de texto legível.)',
            ];
        } finally {
            imap_close($mailbox);
        }
    }

    public function send(string $key, string $to, string $subject, string $body): void
    {
        $account = $this->account($key);
        $transport = new EsmtpTransport(
            (string) $account['smtp_host'],
            (int) ($account['smtp_port'] ?? 465),
            true
        );
        $transport->setUsername((string) $account['username']);
        $transport->setPassword((string) $account['password']);

        $email = (new Email())
            ->from((string) $account['address'])
            ->to($to)
            ->subject($subject)
            ->text($body);

        (new Mailer($transport))->send($email);
    }

    public function probe(string $key): array
    {
        $account = $this->account($key);
        $mailbox = $this->connect($account, 'INBOX');

        try {
            $imap = [
                'ok' => true,
                'messages' => imap_num_msg($mailbox),
            ];
        } finally {
            imap_close($mailbox);
        }

        $transport = new EsmtpTransport(
            (string) $account['smtp_host'],
            (int) ($account['smtp_port'] ?? 465),
            true
        );
        $transport->setUsername((string) $account['username']);
        $transport->setPassword((string) $account['password']);
        $transport->start();
        $transport->stop();

        return ['imap' => $imap, 'smtp' => ['ok' => true]];
    }

    private function connect(array $account, string $folder)
    {
        if (! function_exists('imap_open')) {
            throw new RuntimeException('Extensão PHP IMAP não está disponível.');
        }

        $mailbox = @imap_open(
            $this->mailboxRoot($account).$folder,
            (string) $account['username'],
            (string) $account['password'],
            OP_READONLY,
            1
        );

        if ($mailbox === false) {
            $error = imap_last_error() ?: 'Falha de autenticação/conexão IMAP.';
            throw new RuntimeException($error);
        }

        return $mailbox;
    }

    private function mailboxRoot(array $account): string
    {
        $encryption = strtolower((string) ($account['imap_encryption'] ?? 'ssl'));
        $flags = '/imap'.($encryption === 'ssl' || $encryption === 'tls' ? '/ssl' : '');

        return sprintf('{%s:%d%s}', $account['imap_host'], $account['imap_port'] ?? 993, $flags);
    }

    private function decodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $value;
    }

    private function extractBody($mailbox, int $uid, $structure): string
    {
        if (! $structure) {
            return (string) imap_body($mailbox, $uid, FT_UID | FT_PEEK);
        }

        $plain = $this->findBodyPart($mailbox, $uid, $structure, 'PLAIN');
        if ($plain !== null) {
            return $plain;
        }

        $html = $this->findBodyPart($mailbox, $uid, $structure, 'HTML');
        if ($html !== null) {
            return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return (string) imap_body($mailbox, $uid, FT_UID | FT_PEEK);
    }

    private function findBodyPart($mailbox, int $uid, $structure, string $subtype, string $partNumber = ''): ?string
    {
        if (($structure->type ?? null) === 0 && strtoupper((string) ($structure->subtype ?? '')) === $subtype) {
            $raw = $partNumber === ''
                ? imap_body($mailbox, $uid, FT_UID | FT_PEEK)
                : imap_fetchbody($mailbox, $uid, $partNumber, FT_UID | FT_PEEK);

            return $this->decodeBody((string) $raw, (int) ($structure->encoding ?? 0));
        }

        foreach (($structure->parts ?? []) as $index => $part) {
            $number = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
            $body = $this->findBodyPart($mailbox, $uid, $part, $subtype, $number);
            if ($body !== null) {
                return $body;
            }
        }

        return null;
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => (string) base64_decode($body, true),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }
}
