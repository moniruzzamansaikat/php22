<?php

namespace Php22\Db;

use PDO;
use PDOException;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private string $alias = '';
    private array $columns = ['*'];
    private array $joins = [];
    private array $conditions = [];
    private array $bindings = [];
    private array $groupBy = [];
    private array $having = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private bool $distinct = false;
    private ?string $aggregateFunction = null;
    private ?string $aggregateColumn = null;
    private array $unionQueries = [];
    private bool $forUpdate = false;
    private bool $sharedLock = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Fluent Interface to set the table
    public function table(string $table, ?string $alias = null): self
    {
        $this->table = $table;
        if ($alias) {
            $this->alias = $alias;
        }
        return $this;
    }

    // Distinct
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    // Select columns
    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    // Add a raw expression to the select clause
    public function selectRaw(string $expression): self
    {
        $this->columns[] = $expression;
        return $this;
    }

    // Add a where condition
    public function where(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->conditions[] = compact('column', 'operator', 'value', 'boolean');
        $this->bindings[] = $value;
        return $this;
    }

    // Add an OR where condition
    public function orWhere(string $column, string $operator, $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    // Add a raw where condition
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->conditions[] = ['raw' => $sql, 'boolean' => $boolean];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    // Add an OR raw where condition
    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }

    // Add nested where conditions
    public function whereNested(callable $callback, string $boolean = 'AND'): self
    {
        $nestedQuery = new self($this->pdo);
        $callback($nestedQuery);

        $this->conditions[] = ['nested' => $nestedQuery, 'boolean' => $boolean];
        $this->bindings = array_merge($this->bindings, $nestedQuery->bindings);
        return $this;
    }

    // Add a where in condition
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $placeholders = rtrim(str_repeat('?,', count($values)), ',');
        $operator = $not ? 'NOT IN' : 'IN';
        $this->conditions[] = [
            'raw' => "{$column} {$operator} ({$placeholders})",
            'boolean' => $boolean,
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    // Add an OR where in condition
    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    // Add a join clause
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER', ?string $alias = null): self
    {
        $this->joins[] = compact('type', 'table', 'first', 'operator', 'second', 'alias');
        return $this;
    }

    // Left join
    public function leftJoin(string $table, string $first, string $operator, string $second, ?string $alias = null): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT', $alias);
    }

    // Right join
    public function rightJoin(string $table, string $first, string $operator, string $second, ?string $alias = null): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT', $alias);
    }

    // Cross join
    public function crossJoin(string $table, ?string $alias = null): self
    {
        $this->joins[] = ['type' => 'CROSS', 'table' => $table, 'alias' => $alias];
        return $this;
    }

    // Group by
    public function groupBy(...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    // Having
    public function having(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->having[] = compact('column', 'operator', 'value', 'boolean');
        $this->bindings[] = $value;
        return $this;
    }

    // Order by
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = compact('column', 'direction');
        return $this;
    }

    // Limit
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    // Offset
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // Pagination
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage)->offset($offset);
        $results = $this->get();
        $total = $this->count();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    // Aggregate functions
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    public function sum(string $column)
    {
        return $this->aggregate('SUM', $column);
    }

    public function avg(string $column)
    {
        return $this->aggregate('AVG', $column);
    }

    public function min(string $column)
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column)
    {
        return $this->aggregate('MAX', $column);
    }

    private function aggregate(string $function, string $column)
    {
        $this->aggregateFunction = $function;
        $this->aggregateColumn = $column;
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchColumn();
    }

    // Get the results
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // First result
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    // Build the SELECT query
    private function buildSelectQuery(): string
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        $distinct = $this->distinct ? 'DISTINCT ' : '';
        $columns = implode(', ', $this->columns);
        $table = $this->table . ($this->alias ? ' AS ' . $this->alias : '');
        $sql = "SELECT {$distinct}";

        if ($this->aggregateFunction) {
            $sql .= "{$this->aggregateFunction}({$this->aggregateColumn}) AS aggregate_result";
        } else {
            $sql .= "{$columns}";
        }

        $sql .= " FROM {$table}";

        // Joins
        foreach ($this->joins as $join) {
            $type = $join['type'];
            $joinTable = $join['table'] . (isset($join['alias']) ? ' AS ' . $join['alias'] : '');
            if ($type === 'CROSS') {
                $sql .= " {$type} JOIN {$joinTable}";
            } else {
                $sql .= " {$type} JOIN {$joinTable} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        // Where conditions
        if ($this->conditions) {
            $sql .= ' WHERE ' . $this->buildConditions($this->conditions);
        }

        // Group By
        if ($this->groupBy) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // Having
        if ($this->having) {
            $sql .= ' HAVING ' . $this->buildConditions($this->having);
        }

        // Order By
        if ($this->orderBy) {
            $orders = array_map(function ($order) {
                return "{$order['column']} {$order['direction']}";
            }, $this->orderBy);
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        // Limit and Offset
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if (!is_null($this->offset)) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        // Locking
        if ($this->forUpdate) {
            $sql .= ' FOR UPDATE';
        } elseif ($this->sharedLock) {
            $sql .= ' LOCK IN SHARE MODE';
        }

        return $sql;
    }

    // Build conditions
    private function buildConditions(array $conditions): string
    {
        $sqlParts = [];
        foreach ($conditions as $condition) {
            $boolean = $condition['boolean'] ?? 'AND';

            if (isset($condition['nested'])) {
                $nestedSql = $this->buildConditions($condition['nested']->conditions);
                $sqlParts[] = "{$boolean} ({$nestedSql})";
            } elseif (isset($condition['raw'])) {
                $sqlParts[] = "{$boolean} {$condition['raw']}";
            } else {
                $sqlParts[] = "{$boolean} {$condition['column']} {$condition['operator']} ?";
            }
        }

        $sql = ltrim(implode(' ', $sqlParts), 'ANDOR ');
        return $sql;
    }

    // Insert data
    public function insert(array $data): bool
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = rtrim(str_repeat('?,', count($data)), ',');
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    // Batch insert
    public function insertBatch(array $dataSet): bool
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        if (empty($dataSet)) {
            return false;
        }

        $columns = implode(', ', array_keys($dataSet[0]));
        $placeholders = '(' . rtrim(str_repeat('?,', count($dataSet[0])), ',') . ')';
        $allPlaceholders = rtrim(str_repeat("{$placeholders},", count($dataSet)), ',');
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES {$allPlaceholders}";

        $bindings = [];
        foreach ($dataSet as $data) {
            $bindings = array_merge($bindings, array_values($data));
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }

    // Update data
    public function update(array $data): bool
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        if (empty($data)) {
            throw new \Exception("No data provided for update.");
        }

        $setClause = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$setClause}";

        if ($this->conditions) {
            $sql .= ' WHERE ' . $this->buildConditions($this->conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $bindings = array_merge(array_values($data), $this->bindings);
        return $stmt->execute($bindings);
    }

    // Delete data
    public function delete(): bool
    {
        if (!$this->table) {
            throw new \Exception("Table not specified.");
        }

        $sql = "DELETE FROM {$this->table}";

        if ($this->conditions) {
            $sql .= ' WHERE ' . $this->buildConditions($this->conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    // Transactions
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    // Locking
    public function lockForUpdate(): self
    {
        $this->forUpdate = true;
        return $this;
    }

    public function sharedLock(): self
    {
        $this->sharedLock = true;
        return $this;
    }

    // Get raw SQL with bindings for debugging
    public function toSql(): string
    {
        $sql = $this->buildSelectQuery();
        $bindings = $this->bindings;
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : $this->pdo->quote($binding);
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        return $sql;
    }

    // Reset the builder state
    public function reset(): self
    {
        $this->columns = ['*'];
        $this->joins = [];
        $this->conditions = [];
        $this->bindings = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->distinct = false;
        $this->aggregateFunction = null;
        $this->aggregateColumn = null;
        $this->unionQueries = [];
        $this->forUpdate = false;
        $this->sharedLock = false;
        return $this;
    }
}
