<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Config;

use Ermtraud\XsdToPhp\Exception\InvalidConfiguration;

/**
 * Immutable settings that control XML serialization and deserialization.
 */
final readonly class XmlConfig
{
    public function __construct(
        public string $version,
        public string $encoding,
        public bool $formatOutput,
        public bool $writeRootNamespaces,
    ) {
    }

    /**
     * Normalizes and validates XML mapper configuration values.
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $version = (string) ($config['version'] ?? '1.0');
        $encoding = (string) ($config['encoding'] ?? 'UTF-8');
        $formatOutput = (bool) ($config['format_output'] ?? true);
        $writeRootNamespaces = (bool) ($config['write_root_namespaces'] ?? false);

        if ($version === '' || $encoding === '') {
            throw new InvalidConfiguration('XML configuration must define non-empty version and encoding values.');
        }

        return new self(
            version: $version,
            encoding: $encoding,
            formatOutput: $formatOutput,
            writeRootNamespaces: $writeRootNamespaces,
        );
    }
}
