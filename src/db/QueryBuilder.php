<?php

namespace Php22\Db;

use PDO;

class QueryBuilder
{
    private $pdo;
    private $table;
    private $columns = '*';
    private $conditions = [];
    private $limitCount = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->columns = implode(', ', $columns);
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->conditions[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function limit(int $count): self
    {
        $this->limitCount = $count;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildQuery();
        $stmt = $this->pdo->prepare($sql);

        foreach ($this->conditions as $index => $condition) {
            $stmt->bindValue(":param{$index}", $condition['value']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildQuery(): string
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        $sql = "SELECT {$this->columns} FROM {$this->table}";

        if (!empty($this->conditions)) {
            $whereClauses = array_map(function ($condition, $index) {
                return "{$condition['column']} {$condition['operator']} :param{$index}";
            }, $this->conditions, array_keys($this->conditions));
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if ($this->limitCount !== null) {
            $sql .= " LIMIT {$this->limitCount}";
        }

        return $sql;
    }

    public function insert(array $data): bool
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        // Build the query dynamically
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        // Prepare and execute the query
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }
}
