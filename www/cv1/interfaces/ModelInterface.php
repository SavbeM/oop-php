<?php

interface ModelInterface
{
    /**
     * Vráti všetky záznamy.
     */
    public function getAll(): array;

    /**
     * Vráti záznam podľa ID alebo false.
     */
    public function getById(int $id): array|false;

    /**
     * Vráti počet záznamov.
     */
    public function getCount(): int;

    /**
     * Maže záznam podľa ID.
     */
    public function delete(int $id): bool;

    /**
     * Vráti opis modelu.
     */
    public function describe(): string;

    /**
     * Vráti počet súvisiacich záznamov (pre kontrolu pred zmazaním).
     */
    public function countRelated(int $id): int;
}
