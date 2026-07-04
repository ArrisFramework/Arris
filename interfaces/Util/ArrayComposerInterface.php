<?php

namespace Arris\Util;

interface ArrayComposerInterface
{
    public function __construct(array $original, bool $nullUnsetKeys = false);

    public function patch(array ...$arrays): self;

    public function merge(array ...$arrays): self;

    public function save(): self;

    public function toArray(): array;

    public function asArray(): array;

    public function __toArray(): array;

    public function jsonSerialize(): array;

    public function __toString(): string;



}