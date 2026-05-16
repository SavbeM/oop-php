<?php

interface ModelInterface
{
    public function getAll(): array;

    public function getById(int $id): ?array;

    public function getCount(): int;

    public function delete(int $id): bool;

    public function describe(): string;
}
