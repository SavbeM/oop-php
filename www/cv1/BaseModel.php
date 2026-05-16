<?php

require_once 'interfaces/ModelInterface.php';

abstract class BaseModel implements ModelInterface
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function describe(): string;

    abstract public function countRelated(int $id): int;

    public function getAll(): array
    {
        $stmt = $this->pdo->query($this->resolveSql('all'));
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare($this->resolveSql('by_id'));
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getCount(): int
    {
        $stmt = $this->pdo->query($this->resolveSql('count'));
        return (int) $stmt->fetchColumn();
    }

    public function getAllWithMetric(): array
    {
        if ($this->table !== 'exercises') {
            throw new BadMethodCallException('getAllWithMetric() je dostupné len pre exercises.');
        }

        return $this->getAll();
    }

    public function getByMetricId(int $metricId): array
    {
        if ($this->table !== 'exercises') {
            throw new BadMethodCallException('getByMetricId() je dostupné len pre exercises.');
        }

        $stmt = $this->pdo->prepare($this->resolveSql('by_metric_id'));
        $stmt->execute([':metric_id' => $metricId]);
        return $stmt->fetchAll();
    }

    public function insert(mixed ...$args): int
    {
        if ($this->table === 'metrics') {
            if (count($args) !== 3) {
                throw new InvalidArgumentException('insert() pre metrics očakáva: code, unit, name.');
            }

            $code = $this->validateCode((string) $args[0]);
            $unit = $this->validateUnit((string) $args[1]);
            $name = $this->validateName((string) $args[2]);

            $this->ensureUniqueMetricCode($code);

            $stmt = $this->pdo->prepare($this->resolveSql('insert'));
            $stmt->execute([
                ':code' => $code,
                ':unit' => $unit,
                ':name' => $name,
            ]);

            return (int) $this->pdo->lastInsertId();
        }

        if ($this->table === 'exercises') {
            if (count($args) !== 3) {
                throw new InvalidArgumentException('insert() pre exercises očakáva: name, description, metricId.');
            }

            $name = $this->validateName((string) $args[0]);
            $description = $this->validateDescription($args[1]);
            $metricId = (int) $args[2];

            $this->ensureMetricExists($metricId);

            $stmt = $this->pdo->prepare($this->resolveSql('insert'));
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':metric_id' => $metricId,
            ]);

            return (int) $this->pdo->lastInsertId();
        }

        throw new LogicException('insert() nie je implementované pre tabuľku ' . $this->table . '.');
    }

    public function update(int $id, mixed ...$args): bool
    {
        if ($this->table === 'metrics') {
            if (count($args) !== 3) {
                throw new InvalidArgumentException('update() pre metrics očakáva: code, unit, name.');
            }

            $code = $this->validateCode((string) $args[0]);
            $unit = $this->validateUnit((string) $args[1]);
            $name = $this->validateName((string) $args[2]);

            $this->ensureUniqueMetricCode($code, $id);

            $stmt = $this->pdo->prepare($this->resolveSql('update'));
            return $stmt->execute([
                ':id' => $id,
                ':code' => $code,
                ':unit' => $unit,
                ':name' => $name,
            ]);
        }

        if ($this->table === 'exercises') {
            if (count($args) !== 3) {
                throw new InvalidArgumentException('update() pre exercises očakáva: name, description, metricId.');
            }

            $name = $this->validateName((string) $args[0]);
            $description = $this->validateDescription($args[1]);
            $metricId = (int) $args[2];

            $this->ensureMetricExists($metricId);

            $stmt = $this->pdo->prepare($this->resolveSql('update'));
            return $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description,
                ':metric_id' => $metricId,
            ]);
        }

        throw new LogicException('update() nie je implementované pre tabuľku ' . $this->table . '.');
    }

    public function delete(int $id): bool
    {
        if ($this->countRelated($id) > 0) {
            throw new RuntimeException($this->getDeleteBlockedMessage());
        }

        $stmt = $this->pdo->prepare($this->resolveSql('delete'));
        return $stmt->execute([':id' => $id]);
    }

    protected function countUsages(array $usageQueries, int $id): int
    {
        $usedCount = 0;

        foreach ($usageQueries as $query) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id' => $id]);
            $usedCount += (int) $stmt->fetchColumn();
        }

        return $usedCount;
    }

    private function resolveSql(string $queryKey): string
    {
        return match ($this->table) {
            'metrics' => match ($queryKey) {
                'all' => 'SELECT id, code, unit, name FROM metrics ORDER BY id',
                'by_id' => 'SELECT id, code, unit, name FROM metrics WHERE id = :id',
                'count' => 'SELECT COUNT(*) FROM metrics',
                'insert' => 'INSERT INTO metrics (code, unit, name) VALUES (:code, :unit, :name)',
                'update' => 'UPDATE metrics SET code = :code, unit = :unit, name = :name WHERE id = :id',
                'delete' => 'DELETE FROM metrics WHERE id = :id',
                'count_code' => 'SELECT COUNT(*) FROM metrics WHERE code = :code',
                'count_code_excluding_id' => 'SELECT COUNT(*) FROM metrics WHERE code = :code AND id <> :id',
                default => throw new InvalidArgumentException('Neznámy SQL kľúč pre metrics: ' . $queryKey),
            },
            'exercises' => match ($queryKey) {
                'all' => 'SELECT
                    e.id,
                    e.name,
                    e.description,
                    e.metric_id,
                    m.name AS metric_name,
                    m.unit AS metric_unit
                 FROM exercises e
                 INNER JOIN metrics m ON m.id = e.metric_id
                 ORDER BY e.id',
                'by_id' => 'SELECT id, name, description, metric_id FROM exercises WHERE id = :id',
                'by_metric_id' => 'SELECT
                    e.id,
                    e.name,
                    e.description,
                    m.name AS metric_name,
                    m.unit AS metric_unit
                 FROM exercises e
                 INNER JOIN metrics m ON m.id = e.metric_id
                 WHERE e.metric_id = :metric_id
                 ORDER BY e.id',
                'count' => 'SELECT COUNT(*) FROM exercises',
                'insert' => 'INSERT INTO exercises (name, description, metric_id) VALUES (:name, :description, :metric_id)',
                'update' => 'UPDATE exercises SET name = :name, description = :description, metric_id = :metric_id WHERE id = :id',
                'delete' => 'DELETE FROM exercises WHERE id = :id',
                'metric_exists' => 'SELECT COUNT(*) FROM metrics WHERE id = :id',
                default => throw new InvalidArgumentException('Neznámy SQL kľúč pre exercises: ' . $queryKey),
            },
            default => throw new LogicException('Nepodporovaná tabuľka v BaseModel: ' . $this->table),
        };
    }

    private function validateName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            $message = $this->table === 'metrics'
                ? 'Názov metriky nemôže byť prázdny.'
                : 'Názov cviku nemôže byť prázdny.';
            throw new InvalidArgumentException($message);
        }

        return $name;
    }

    private function validateUnit(string $unit): string
    {
        $unit = trim($unit);
        if ($unit === '') {
            throw new InvalidArgumentException('Jednotka nemôže byť prázdna.');
        }
        return $unit;
    }

    private function validateCode(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            throw new InvalidArgumentException('Code metriky nemôže byť prázdny.');
        }

        if (!preg_match('/^[a-z0-9_]{2,30}$/', $code)) {
            throw new InvalidArgumentException('Code môže obsahovať len malé písmená, čísla a _ (2-30 znakov).');
        }

        return $code;
    }

    private function validateDescription(mixed $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = trim((string) $description);
        return $description === '' ? null : $description;
    }

    private function ensureUniqueMetricCode(string $code, ?int $excludeId = null): void
    {
        $sqlKey = $excludeId === null ? 'count_code' : 'count_code_excluding_id';
        $stmt = $this->pdo->prepare($this->resolveSql($sqlKey));

        $params = [':code' => $code];
        if ($excludeId !== null) {
            $params[':id'] = $excludeId;
        }

        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Tento code už existuje. Zvoľte iný code.');
        }
    }

    private function ensureMetricExists(int $metricId): void
    {
        $stmt = $this->pdo->prepare($this->resolveSql('metric_exists'));
        $stmt->execute([':id' => $metricId]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new InvalidArgumentException('Vybraná metrika neexistuje.');
        }
    }

    private function getDeleteBlockedMessage(): string
    {
        return match ($this->table) {
            'metrics' => 'Metriku sa nedá zmazať: používajú ju iné záznamy.',
            'exercises' => 'Cvik sa nedá zmazať: používajú ho iné záznamy.',
            default => 'Záznam sa nedá zmazať: používajú ho iné záznamy.',
        };
    }
}