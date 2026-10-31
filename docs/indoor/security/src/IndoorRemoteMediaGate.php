<?php
declare(strict_types=1);

/**
 * Preflight policy only. Does NOT fetch media, resolve DNS or authorize publishing.
 * The eventual HTTP consumer MUST resolve and validate every A/AAAA answer,
 * pin the validated address to the actual connection, disable automatic redirects,
 * and repeat validation for every manually followed Location.
 */
final class IndoorRemoteMediaGate
{
    public static function validateUrl(string $url, array $allowedHosts): string
    {
        if ($url === '' || preg_match('/[\\x00-\\x20\\x7f]/', $url)) {
            throw new InvalidArgumentException('Invalid URL');
        }
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || !isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new InvalidArgumentException('URL scheme or authority rejected');
        }
        $host = strtolower(rtrim((string)$parts['host'], '.'));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)
            || !preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])$/D', $host)
            || str_contains($host, '..')) {
            throw new InvalidArgumentException('Host rejected');
        }
        $allowed = array_map(static fn($h) => strtolower(rtrim((string)$h, '.')), $allowedHosts);
        if (!in_array($host, $allowed, true)) {
            throw new InvalidArgumentException('Host not allowlisted');
        }
        return $host;
    }

    /** Validate ALL DNS A/AAAA results, not just one selected address. */
    public static function validateResolvedAddresses(array $addresses): void
    {
        if ($addresses === []) {
            throw new InvalidArgumentException('No DNS addresses');
        }
        foreach ($addresses as $ip) {
            if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Non-public DNS address');
            }
        }
    }

    /**
     * Required consumer rules: CURLOPT_FOLLOWLOCATION=false, TLS verification on,
     * DNS/IP pinning (e.g. CURLOPT_RESOLVE), no proxies unless trusted and constrained,
     * bounded bytes/time, media type checks, and manual redirect revalidation.
     */
    public static function redirectTarget(string $location, array $allowedHosts): string
    {
        self::validateUrl($location, $allowedHosts);
        return $location;
    }
}
