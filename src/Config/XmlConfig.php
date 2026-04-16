<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Config;

use Ermtraud\XsdToPhp\Exception\InvalidConfiguration;

/**
 * Immutable settings that control XML serialization and deserialization.
 */
final readonly class XmlConfig
{
    public const FALLBACK_NAMESPACE_PARENT = 'parent';
    public const FALLBACK_NAMESPACE_ROOT = 'root';
    public const FALLBACK_NAMESPACE_NONE = 'none';

    public function __construct(
        public string $version,
        public string $encoding,
        public bool $formatOutput,
        public bool $writeRootNamespaces,
        public string $fallbackNamespace,
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
        $fallbackNamespace = (string) ($config['fallback_namespace'] ?? self::FALLBACK_NAMESPACE_PARENT);

        if ($version === '' || $encoding === '') {
            throw new InvalidConfiguration('XML configuration must define non-empty version and encoding values.');
        }

        if (!in_array($fallbackNamespace, [
            self::FALLBACK_NAMESPACE_PARENT,
            self::FALLBACK_NAMESPACE_ROOT,
            self::FALLBACK_NAMESPACE_NONE,
        ], true)) {
            throw new InvalidConfiguration(sprintf(
                'XML configuration "fallback_namespace" must be one of "%s", "%s", or "%s".',
                self::FALLBACK_NAMESPACE_PARENT,
                self::FALLBACK_NAMESPACE_ROOT,
                self::FALLBACK_NAMESPACE_NONE,
            ));
        }

        return new self(
            version: $version,
            encoding: $encoding,
            formatOutput: $formatOutput,
            writeRootNamespaces: $writeRootNamespaces,
            fallbackNamespace: $fallbackNamespace,
        );
    }
}
