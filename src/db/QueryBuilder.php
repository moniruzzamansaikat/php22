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
    private $offsetCount = null;
    private $joins = [];
    private $groupBy = [];
    private $having = [];
    private $orderBy = [];
    private $bindings = [];
    private $transactionStarted = false;

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

    public function distinct(): self
    {
        $this->columns = 'DISTINCT ' . $this->columns;
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->conditions[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function orWhere(string $column, string $operator, $value): self
    {
        $this->conditions[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->conditions[] = [
            'type' => 'IN',
            'column' => $column,
            'values' => $values,
        ];
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->conditions[] = [
            'type' => 'NOT IN',
            'column' => $column,
            'values' => $values,
        ];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->conditions[] = [
            'type' => 'NULL',
            'column' => $column,
        ];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->conditions[] = [
            'type' => 'NOT NULL',
            'column' => $column,
        ];
        return $this;
    }

    public function whereBetween(string $column, $value1, $value2): self
    {
        $this->conditions[] = [
            'type' => 'BETWEEN',
            'column' => $column,
            'values' => [$value1, $value2],
        ];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    public function having(string $column, string $operator, $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $count): self
    {
        $this->limitCount = $count;
        return $this;
    }

    public function offset(int $count): self
    {
        $this->offsetCount = $count;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    private function buildSelectQuery(): string
    {
        $sql = "SELECT {$this->columns} FROM {$this->table}";
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimitOffset();
        return $sql;
    }

    private function buildJoins(): string
    {
        $joinSql = '';
        foreach ($this->joins as $join) {
            $joinSql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        return $joinSql;
    }

    private function buildWhere(): string
    {
        if (empty($this->conditions)) {
            return '';
        }

        $sql = ' WHERE ';
        $clauses = [];
        foreach ($this->conditions as $condition) {
            $type = $condition['type'];
            switch ($type) {
                case 'AND':
                case 'OR':
                    $placeholder = $this->addBinding($condition['value']);
                    $clauses[] = "{$condition['column']} {$condition['operator']} {$placeholder}";
                    break;
                case 'IN':
                case 'NOT IN':
                    $placeholders = $this->addBindings($condition['values']);
                    $clauses[] = "{$condition['column']} {$type} (" . implode(', ', $placeholders) . ")";
                    break;
                case 'NULL':
                case 'NOT NULL':
                    $clauses[] = "{$condition['column']} IS {$type}";
                    break;
                case 'BETWEEN':
                    $placeholders = $this->addBindings($condition['values']);
                    $clauses[] = "{$condition['column']} BETWEEN {$placeholders[0]} AND {$placeholders[1]}";
                    break;
            }
        }
        $sql .= implode(' AND ', $clauses);
        return $sql;
    }

    private function buildGroupBy(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }
        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    private function buildHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }
        $sql = ' HAVING ';
        $clauses = [];
        foreach ($this->having as $condition) {
            $placeholder = $this->addBinding($condition['value']);
            $clauses[] = "{$condition['column']} {$condition['operator']} {$placeholder}";
        }
        $sql .= implode(' AND ', $clauses);
        return $sql;
    }

    private function buildOrderBy(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $this->orderBy);
    }

    private function buildLimitOffset(): string
    {
        $sql = '';
        if ($this->limitCount !== null) {
            $sql .= " LIMIT {$this->limitCount}";
        }
        if ($this->offsetCount !== null) {
            $sql .= " OFFSET {$this->offsetCount}";
        }
        return $sql;
    }

    private function addBinding($value): string
    {
        $placeholder = ':param' . count($this->bindings);
        $this->bindings[$placeholder] = $value;
        return $placeholder;
    }

    private function addBindings(array $values): array
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }
        return $placeholders;
    }

    private function bindValues($stmt)
    {
        foreach ($this->bindings as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
    }

    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = $this->addBindings(array_values($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt);
        return $stmt->execute();
    }

    public function update(array $data): bool
    {
        if (empty($this->conditions)) {
            throw new \Exception("Update statements require conditions to prevent mass updates.");
        }

        $setClauses = [];
        foreach ($data as $column => $value) {
            $placeholder = $this->addBinding($value);
            $setClauses[] = "{$column} = {$placeholder}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses);
        $sql .= $this->buildWhere();
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if (empty($this->conditions)) {
            throw new \Exception("Delete statements require conditions to prevent mass deletions.");
        }
        $sql = "DELETE FROM {$this->table}" . $this->buildWhere();
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt);
        return $stmt->execute();
    }

    public function beginTransaction(): bool
    {
        $this->transactionStarted = true;
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        $this->transactionStarted = false;
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        $this->transactionStarted = false;
        return $this->pdo->rollBack();
    }

    public function count(string $column = '*'): int
    {
        $this->columns = "COUNT({$column}) AS aggregate";
        $result = $this->get();
        return (int)($result[0]['aggregate'] ?? 0);
    }

    public function max(string $column)
    {
        $this->columns = "MAX({$column}) AS aggregate";
        $result = $this->get();
        return $result[0]['aggregate'] ?? null;
    }

    public function min(string $column)
    {
        $this->columns = "MIN({$column}) AS aggregate";
        $result = $this->get();
        return $result[0]['aggregate'] ?? null;
    }

    public function avg(string $column)
    {
        $this->columns = "AVG({$column}) AS aggregate";
        $result = $this->get();
        return $result[0]['aggregate'] ?? null;
    }

    public function sum(string $column)
    {
        $this->columns = "SUM({$column}) AS aggregate";
        $result = $this->get();
        return $result[0]['aggregate'] ?? null;
    }

    public function paginate(int $perPage, int $currentPage = 1): array
    {
        $this->limit($perPage)->offset(($currentPage - 1) * $perPage);
        $items = $this->get();
        $total = $this->count();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public function raw(string $expression): string
    {
        return $expression;
    }
}
