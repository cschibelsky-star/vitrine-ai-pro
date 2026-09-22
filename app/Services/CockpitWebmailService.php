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
        $secretB64Name = (string) ($account['credential_secret_b64'] ?? '');
        $password = '';

        if ($secretB64Name !== '') {
            $encoded = (string) env($secretB64Name, '');
            if ($encoded !== '') {
                $decoded = base64_decode($encoded, true);
                if ($decoded === false || $decoded === '') {
                    throw new RuntimeException('Credencial codificada da conta é inválida.');
                }
                $password = $decoded;
            }
        }

        if ($password === '' && $secretName !== '') {
            $password = (string) env($secretName, '');
        }

        if ($password === '') {
            throw new RuntimeException('Credencial da conta não está disponível no runtime.');
        }

        $account['password'] = $password;

        return $account;
    }

    public function folders(string $key): array
    {
        $account = $this->account($key);
        $client = $this->imapConnect($account);

        try {
            $lines = $this->imapCommand($client, 'LIST "" "*"');
            $folders = ['INBOX'];

            foreach ($lines as $line) {
                if (preg_match('/\* LIST .*? "(.*?)"$/', $line, $match)) {
                    $name = trim($match[1], '"');
                    if ($name !== '') {
                        $folders[] = $name;
                    }
                }
            }

            return array_values(array_unique($folders));
        } finally {
            $this->imapLogout($client);
        }
    }

    public function messages(string $key, string $folder = 'INBOX', int $limit = 30): array
    {
        $account = $this->account($key);
        $client = $this->imapConnect($account);

        try {
            $this->imapCommand($client, 'SELECT '.$this->quoteImap($folder));
            $search = $this->imapCommand($client, 'UID SEARCH ALL');
            $uids = [];

            foreach ($search as $line) {
                if (str_starts_with($line, '* SEARCH')) {
                    $uids = array_values(array_filter(array_map('intval', preg_split('/\s+/', trim(substr($line, 8))))));
                }
            }

            $uids = array_slice(array_reverse($uids), 0, $limit);
            $messages = [];

            foreach ($uids as $uid) {
                $lines = $this->imapCommand(
                    $client,
                    'UID FETCH '.$uid.' (FLAGS RFC822.SIZE BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE TO)])'
                );

                $raw = implode("\r\n", $lines);
                $headers = $this->parseHeaders($raw);

                $messages[] = [
                    'uid' => $uid,
                    'subject' => $this->decodeHeader($headers['subject'] ?? '(Sem assunto)'),
                    'from' => $this->decodeHeader($headers['from'] ?? ''),
                    'date' => $headers['date'] ?? '',
                    'seen' => stripos($raw, '\\Seen') !== false,
                    'size' => $this->extractSize($raw),
                ];
            }

            return $messages;
        } finally {
            $this->imapLogout($client);
        }
    }

    public function message(string $key, int $uid, string $folder = 'INBOX'): array
    {
        $account = $this->account($key);
        $client = $this->imapConnect($account);

        try {
            $this->imapCommand($client, 'SELECT '.$this->quoteImap($folder));
            $lines = $this->imapCommand($client, 'UID FETCH '.$uid.' (BODY.PEEK[])');
            $raw = $this->extractFetchPayload($lines);

            if ($raw === '') {
                throw new RuntimeException('Mensagem não encontrada.');
            }

            [$headerText, $bodyText] = $this->splitMessage($raw);
            $headers = $this->parseHeaders($headerText);
            $body = $this->decodeMessageBody($headers, $bodyText);

            return [
                'uid' => $uid,
                'subject' => $this->decodeHeader($headers['subject'] ?? '(Sem assunto)'),
                'from' => $this->decodeHeader($headers['from'] ?? ''),
                'to' => $this->decodeHeader($headers['to'] ?? ''),
                'date' => $headers['date'] ?? '',
                'body' => trim($body) !== '' ? trim($body) : '(Mensagem sem corpo de texto legível.)',
            ];
        } finally {
            $this->imapLogout($client);
        }
    }

    public function send(string $key, string $to, string $subject, string $body): void
    {
        $account = $this->account($key);
        $transport = $this->smtpTransport($account);

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
        $client = $this->imapConnect($account);

        try {
            $lines = $this->imapCommand($client, 'STATUS INBOX (MESSAGES)');
            $messages = null;

            foreach ($lines as $line) {
                if (preg_match('/MESSAGES\s+(\d+)/i', $line, $match)) {
                    $messages = (int) $match[1];
                    break;
                }
            }
        } finally {
            $this->imapLogout($client);
        }

        $transport = $this->smtpTransport($account);
        $transport->start();
        $transport->stop();

        return [
            'imap' => ['ok' => true, 'messages' => $messages],
            'smtp' => ['ok' => true],
        ];
    }

    private function smtpTransport(array $account): EsmtpTransport
    {
        $secure = in_array(strtolower((string) ($account['smtp_encryption'] ?? 'ssl')), ['ssl', 'tls'], true);
        $transport = new EsmtpTransport(
            (string) $account['smtp_host'],
            (int) ($account['smtp_port'] ?? 465),
            $secure
        );
        $transport->setUsername((string) $account['username']);
        $transport->setPassword((string) $account['password']);

        return $transport;
    }

    private function imapConnect(array $account)
    {
        $host = (string) $account['imap_host'];
        $port = (int) ($account['imap_port'] ?? 993);
        $scheme = strtolower((string) ($account['imap_encryption'] ?? 'ssl')) === 'ssl' ? 'ssl' : 'tcp';

        $socket = @stream_socket_client(
            $scheme.'://'.$host.':'.$port,
            $errno,
            $errstr,
            12,
            STREAM_CLIENT_CONNECT
        );

        if (! is_resource($socket)) {
            throw new RuntimeException("Falha ao conectar ao IMAP: {$errstr} ({$errno}).");
        }

        stream_set_timeout($socket, 12);
        $greeting = fgets($socket, 8192);

        if ($greeting === false || ! str_starts_with($greeting, '* OK')) {
            fclose($socket);
            throw new RuntimeException('Servidor IMAP não retornou saudação válida.');
        }

        try {
            $this->imapCommand(
                $socket,
                'LOGIN '.$this->quoteImap((string) $account['username']).' '.$this->quoteImap((string) $account['password'])
            );
        } catch (\Throwable $e) {
            fclose($socket);
            throw $e;
        }

        return $socket;
    }

    private function imapCommand($socket, string $command): array
    {
        static $counter = 0;
        $tag = 'C'.str_pad((string) (++$counter), 4, '0', STR_PAD_LEFT);

        if (fwrite($socket, $tag.' '.$command."\r\n") === false) {
            throw new RuntimeException('Falha ao escrever no servidor IMAP.');
        }

        $lines = [];

        while (! feof($socket)) {
            $line = fgets($socket, 65536);
            if ($line === false) {
                break;
            }

            $line = rtrim($line, "\r\n");
            $lines[] = $line;

            if (preg_match('/\{(\d+)\}$/', $line, $literal)) {
                $remaining = (int) $literal[1];
                $payload = '';

                while ($remaining > 0 && ! feof($socket)) {
                    $chunk = fread($socket, $remaining);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    $payload .= $chunk;
                    $remaining -= strlen($chunk);
                }

                $lines[] = $payload;
            }

            if (str_starts_with($line, $tag.' ')) {
                if (! str_contains($line, ' OK')) {
                    throw new RuntimeException('IMAP recusou a operação: '.$this->safeImapError($line));
                }

                return $lines;
            }
        }

        throw new RuntimeException('Resposta IMAP incompleta ou timeout.');
    }

    private function imapLogout($socket): void
    {
        if (! is_resource($socket)) {
            return;
        }

        try {
            $this->imapCommand($socket, 'LOGOUT');
        } catch (\Throwable) {
        }

        fclose($socket);
    }

    private function quoteImap(string $value): string
    {
        return '"'.addcslashes($value, "\\\"").'"';
    }

    private function safeImapError(string $line): string
    {
        return preg_replace('/LOGIN\s+".*?"\s+".*?"/i', 'LOGIN [redacted]', $line) ?: 'operação recusada';
    }

    private function extractFetchPayload(array $lines): string
    {
        foreach ($lines as $index => $line) {
            if (preg_match('/\{\d+\}$/', $line) && isset($lines[$index + 1])) {
                return (string) $lines[$index + 1];
            }
        }

        return '';
    }

    private function splitMessage(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function parseHeaders(string $raw): array
    {
        $headerBlock = preg_replace('/^.*?\{\d+\}\r?\n/s', '', $raw) ?? $raw;
        $headerBlock = preg_replace("/\r?\n[ \t]+/", ' ', $headerBlock) ?? $headerBlock;
        $headers = [];

        foreach (preg_split("/\r?\n/", $headerBlock) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }

    private function decodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $value;
    }

    private function decodeMessageBody(array $headers, string $body): string
    {
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
        $contentType = strtolower($headers['content-type'] ?? 'text/plain');

        if ($encoding === 'base64') {
            $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?: '', true);
            if ($decoded !== false) {
                $body = $decoded;
            }
        } elseif ($encoding === 'quoted-printable') {
            $body = quoted_printable_decode($body);
        }

        if (str_contains($contentType, 'text/html')) {
            return html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (str_contains($contentType, 'multipart/')) {
            return $this->decodeMultipart($contentType, $body);
        }

        return $body;
    }

    private function decodeMultipart(string $contentType, string $body): string
    {
        if (! preg_match('/boundary="?([^";]+)"?/i', $contentType, $match)) {
            return $body;
        }

        $boundary = $match[1];
        foreach (explode('--'.$boundary, $body) as $part) {
            [$headersText, $partBody] = $this->splitMessage($part);
            $headers = $this->parseHeaders($headersText);
            $type = strtolower($headers['content-type'] ?? '');

            if (str_contains($type, 'text/plain')) {
                return $this->decodeMessageBody($headers, $partBody);
            }
        }

        return strip_tags($body);
    }

    private function extractSize(string $raw): int
    {
        return preg_match('/RFC822\.SIZE\s+(\d+)/i', $raw, $match) ? (int) $match[1] : 0;
    }
}
