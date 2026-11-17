<?php

declare(strict_types=1);

namespace WavyKit\Subjects;

/**
 * A resolved subject, the same in every reader.
 *
 * Identity is the kind plus the object's route key, written `project:42`. The
 * URL is the kind's canonical page, or null for a kind without one. Serializes
 * to `{ kind, id, label, url }`.
 */
final readonly class SubjectItem
{
    /** @internal Created by Subjects. */
    public function __construct(
        public string $kind,
        public string $id,
        public string $label,
        public ?string $url,
    ) {}

    /** The subject's identity, for example `project:42`. */
    public function key(): string
    {
        return "{$this->kind}:{$this->id}";
    }
}
