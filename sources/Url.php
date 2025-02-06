<?php

namespace Arris;

use Arris\Exceptions\IncompleteUrlException;
use Arris\Exceptions\InvalidUrlException;

class Url
{
    const RELATIVE = 0;
    const ABSOLUTE = 1;

    /**
     * @var string|null
     */
    private ?string $scheme;

    /**
     * @var string|null
     */
    private ?string $host;

    /**
     * @var int|null
     */
    private ?int $port;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var array
     */
    private array $query;

    /**
     * @var string|null
     */
    private ?string $fragment;

    /**
     * @var int
     */
    private int $preferredFormat;

    public function __construct(
        ?string $scheme = null,
        ?string $host = null,
        ?int    $port = null,
        string  $path = '',
        array   $query = [],
        ?string $fragment = null,
        int     $preferredFormat = self::ABSOLUTE
    )
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
        $this->preferredFormat = $preferredFormat;
    }

    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * Parse URL
     *
     * @return static
     * @throws InvalidUrlException if the URL is invalid
     */
    public static function parse(string $url, ?int $preferredFormat = self::ABSOLUTE): Url
    {
        $components = parse_url($url);

        if ($components === false) {
            throw new InvalidUrlException(sprintf('The given URL "%s" is invalid', $url));
        }

        $query = [];

        if (isset($components['query'])) {
            parse_str($components['query'], $query);
        }

        return new static(
            $components['scheme'] ?? null,
            $components['host'] ?? null,
            $components['port'] ?? null,
            $components['path'] ?? '',
            $query,
            $components['fragment'] ?? null,
            $preferredFormat
        );
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setScheme(?string $scheme): void
    {
        $this->scheme = $scheme;
    }

    public function hasScheme(): bool
    {
        return $this->scheme !== null;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Get host name, including the port, if defined
     *
     * E.g. example.com:8080
     */
    public function getFullHost(): ?string
    {
        if ($this->host === null) {
            return null;
        }

        $fullHost = $this->host;

        if ($this->port !== null) {
            $fullHost .= ':' . $this->port;
        }

        return $fullHost;
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    public function hasHost(): bool
    {
        return $this->host !== null;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function hasPort(): bool
    {
        return $this->port !== null;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function hasPath(): bool
    {
        return $this->path !== '';
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getQueryString(): string
    {
        return \http_build_query($this->query, '', '&');
    }

    public function setQuery(array $query): void
    {
        $this->query = $query;
    }

    public function hasQuery(): bool
    {
        return !empty($this->query);
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function setFragment(?string $fragment): void
    {
        $this->fragment = $fragment;
    }

    public function hasFragment(): bool
    {
        return $this->fragment !== null;
    }

    public function getPreferredFormat(): int
    {
        return $this->preferredFormat;
    }

    /**
     * Define the preferred URL format to be returned by build()
     *
     * @see Url::RELATIVE
     * @see URL::ABSOLUTE
     */
    public function setPreferredFormat(int $preferredFormat): void
    {
        $this->preferredFormat = $preferredFormat;
    }

    /**
     * See whether a query parameter is defined
     *
     * @param string|int $parameter
     */
    public function has($parameter): bool
    {
        return \key_exists($parameter, $this->query);
    }

    /**
     * Attempt to retrieve a query parameter value
     *
     * Returns NULL if the query parameter is not defined.
     *
     * @param string|int $parameter
     * @return mixed
     */
    public function get($parameter)
    {
        return $this->query[$parameter] ?? null;
    }

    /**
     * Set query parameter
     *
     * @param string|int $parameter
     * @param mixed $value
     */
    public function set($parameter, $value): void
    {
        $this->query[$parameter] = $value;
    }

    /**
     * Add multiple query parameters
     *
     * Already defined parameters with the same key will be overridden.
     */
    public function add(array $parameters): void
    {
        foreach ($parameters as $parameter => $value) {
            $this->query[$parameter] = $value;
        }
    }

    /**
     * Remove a query parameter
     *
     * @param string|int $parameter
     */
    public function remove($parameter): void
    {
        unset($this->query[$parameter]);
    }

    /**
     * Remove all query parameters
     */
    public function removeAll(): void
    {
        $this->query = [];
    }

    /**
     * Build an absolute or relative URL
     *
     * - if no host is specified, a relative URL will be returned
     * - if the host is specified, an absolute URL will be returned
     *   (unless the preferred format option is set to relative)
     *
     * @see Url::setPreferredFormat()
     */
    public function build(): string
    {
        if ($this->host !== null && $this->preferredFormat === static::ABSOLUTE) {
            return $this->buildAbsolute();
        } else {
            return $this->buildRelative();
        }
    }

    /**
     * Build an absolute URL
     *
     * @throws IncompleteUrlException if no host is specified
     */
    public function buildAbsolute(): string
    {
        $output = '';

        if ($this->host === null) {
            throw new IncompleteUrlException('No host specified');
        }

        // scheme
        if ($this->scheme !== null) {
            $output .= $this->scheme;
            $output .= '://';
        } else {
            // protocol-relative
            $output .= '//';
        }

        // host, port
        $output .= $this->getFullHost();

        // ensure a forward slash between host and a non-empty path
        if ($this->path !== '' && $this->path[0] !== '/') {
            $output .= '/';
        }

        // path, query, fragment
        $output .= $this->buildRelative();

        return $output;
    }

    /**
     * Build a relative URL
     */
    public function buildRelative(): string
    {
        $output = '';

        // path
        $output .= $this->path;

        // query
        if ($this->query) {
            $output .= '?';
            $output .= $this->getQueryString();
        }

        // fragment
        if ($this->fragment !== null) {
            $output .= '#';
            $output .= $this->fragment;
        }

        return $output;
    }
}
