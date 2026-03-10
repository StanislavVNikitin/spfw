<?php

namespace PHPFramework;

//    Базовые конструкции
//
//    select($table, $columns='*')
//    update($table, array $data)
//    delete($table)
//
//    Расшираения
//
//    where($column, $operatorOrValue, $value = null) (поддерживает where('id', 5) и where('age','>',18))
//    orWhere(...)
//    whereRaw($sql, $params=[])
//    join($table, $on, $type='INNER')
//    orderBy($column, $direction='ASC')
//    limit($n)
//    offset($n)
//
//    Финализация
//
//    get() — выполнит собранный SELECT и вернёт fetchAll() (как и раньше)
//    first() / getOne() — вернёт одну строку
//    execute() — для UPDATE/DELETE, вернёт rowCount()
//    Примеры использования
//    // SELECT
//    $rows = $db->select('users', ['id','email'])
//        ->where('status', 'active')
//        ->orderBy('id', 'DESC')
//        ->limit(10)
//        ->get();
//    // JOIN + WHERE
//    $rows = $db->select('posts', ['posts.id','users.email'])
//        ->join('users', 'users.id = posts.user_id', 'LEFT')
//        ->where('posts.published', 1)
//        ->get();
//    // UPDATE
//    $affected = $db->update('users', ['email' => 'a@b.com'])
//        ->where('id', 123)
//        ->execute();
//    // DELETE
//    $affected = $db->delete('sessions')
//        ->where('user_id', 123)
//        ->execute();





class Database
{
    protected \PDO $connection;
    protected \PDOStatement $stmt;

    protected array $queries = [];

    /**
     * Fluent query builder state.
     */
    protected ?string $builderSql = null;
    protected array $builderParams = [];

    public function __construct()
    {
        $dsn = 'mysql:host='. DB['host'] . ';dbname='. DB['dbname'] .';charset=' . DB['charset'];
        try
        {
            $this->connection = new \PDO($dsn, DB['username'], DB['password'],DB['options']);
        }
        catch (\PDOException $e)
        {
            error_log("[" . date('Y-m-d H:i:s') . "] DB Error: " . $e->getMessage() . PHP_EOL, 3, ERROR_LOG_FILE);
            abort($e->getMessage(), 500);
        }

        return $this;
    }

    protected function resetBuilder(): void
    {
        $this->builderSql = null;
        $this->builderParams = [];
    }

    protected function quoteIdentifier(string $name): string
    {
        $name = trim($name);
        if ($name === '*') {
            return '*';
        }

        // If user passes raw SQL (functions/aliases), don't try to quote.
        if (strpbrk($name, " ()`") !== false) {
            return $name;
        }

        $parts = explode('.', $name);
        $parts = array_map(static fn ($p) => '`' . str_replace('`', '``', $p) . '`', $parts);
        return implode('.', $parts);
    }

    protected function ensureBuilderStarted(): void
    {
        if ($this->builderSql === null) {
            abort('Query builder is not initialized. Call select(), update(), or delete() first.', 500);
        }
    }

    protected function builderAppend(string $sqlPart): void
    {
        $this->ensureBuilderStarted();
        $this->builderSql .= ' ' . trim($sqlPart);
    }

    /**
     * Starts SELECT query.
     */
    public function select(string $table, array|string $columns = '*'): static
    {
        $this->resetBuilder();

        if (is_array($columns)) {
            $cols = array_map(fn ($c) => $this->quoteIdentifier((string)$c), $columns);
            $columnsSql = implode(', ', $cols);
        } else {
            $columnsSql = $columns === '*' ? '*' : $this->quoteIdentifier($columns);
        }

        $this->builderSql = 'SELECT ' . $columnsSql . ' FROM ' . $this->quoteIdentifier($table);
        return $this;
    }

    /**
     * Starts DELETE query.
     */
    public function delete(string $table): static
    {
        $this->resetBuilder();
        $this->builderSql = 'DELETE FROM ' . $this->quoteIdentifier($table);
        return $this;
    }

    /**
     * Starts UPDATE query.
     */
    public function update(string $table, array $data): static
    {
        $this->resetBuilder();

        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = $this->quoteIdentifier((string)$col) . ' = ?';
            $this->builderParams[] = $val;
        }

        $this->builderSql = 'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . implode(', ', $sets);
        return $this;
    }

    public function join(string $table, string $on, string $type = 'INNER'): static
    {
        $type = strtoupper(trim($type));
        $allowed = ['INNER', 'LEFT', 'RIGHT', 'CROSS'];
        if (!in_array($type, $allowed, true)) {
            abort('Unsupported join type: ' . $type, 500);
        }

        $this->builderAppend($type . ' JOIN ' . $this->quoteIdentifier($table) . ' ON ' . $on);
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null, string $boolean = 'AND'): static
    {
        $boolean = strtoupper(trim($boolean));
        $boolean = $boolean === 'OR' ? 'OR' : 'AND';

        if (func_num_args() === 2) {
            $operator = '=';
            $val = $operatorOrValue;
        } else {
            $operator = (string)$operatorOrValue;
            $val = $value;
        }

        $prefix = str_contains($this->builderSql ?? '', ' WHERE ') ? $boolean : 'WHERE';
        $this->builderAppend($prefix . ' ' . $this->quoteIdentifier($column) . ' ' . $operator . ' ?');
        $this->builderParams[] = $val;
        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            return $this->where($column, $operatorOrValue, null, 'OR');
        }
        return $this->where($column, $operatorOrValue, $value, 'OR');
    }

    public function whereRaw(string $sql, array $params = [], string $boolean = 'AND'): static
    {
        $boolean = strtoupper(trim($boolean));
        $boolean = $boolean === 'OR' ? 'OR' : 'AND';

        $prefix = str_contains($this->builderSql ?? '', ' WHERE ') ? $boolean : 'WHERE';
        $this->builderAppend($prefix . ' (' . $sql . ')');
        array_push($this->builderParams, ...array_values($params));
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper(trim($direction));
        $direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'ASC';

        $this->builderAppend('ORDER BY ' . $this->quoteIdentifier($column) . ' ' . $direction);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->builderAppend('LIMIT ' . max(0, $limit));
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->builderAppend('OFFSET ' . max(0, $offset));
        return $this;
    }

    /**
     * Executes fluent-built query (if any) and keeps compatibility
     * with manual query() usage.
     */
    protected function runBuilderIfNeeded(): void
    {
        if ($this->builderSql === null) {
            return;
        }
        $sql = $this->builderSql;
        $params = $this->builderParams;
        $this->resetBuilder();
        $this->query($sql, $params);
    }

    public function query(string $query, array $params = [])
    {
        try
        {
            //$this->queries[] = $query;
            $this->stmt = $this->connection->prepare($query);
            $this->stmt->execute($params);
            if(DEBUG){
                ob_start();
                $this->stmt->debugDumpParams();
                $this->queries[] = ob_get_clean();
            }
        }catch (\PDOException $e)
        {
            error_log("[" . date('Y-m-d H:i:s') . "] DB Error: " . $e->getMessage() . PHP_EOL, 3, ERROR_LOG_FILE);
            abort($e->getMessage(), 500);
        }

        return $this;
    }

    public function get():array|false
    {
        $this->runBuilderIfNeeded();
        return $this->stmt->fetchAll();
    }

    public function getAssoc($key = 'id'): array
    {
        $data = [];
        while ($row = $this->stmt->fetch()) {
            $data[$row[$key]] = $row;
        }
        return $data;
    }

    public function getColumn():mixed
    {
        $this->runBuilderIfNeeded();
        return $this->stmt->fetchColumn();
    }

    public function getOne():array|false
    {
        $this->runBuilderIfNeeded();
        return $this->stmt->fetch();
    }

    /**
     * Fluent alias for fetching first row.
     */
    public function first(): array|false
    {
        return $this->getOne();
    }

    /**
     * Executes fluent-built non-select query.
     * Returns affected rows (or 0).
     */
    public function execute(): int
    {
        $this->runBuilderIfNeeded();
        return $this->rowCount();
    }

    public function findAll($tbl):array|false
    {
        $this->query("SELECT * FROM `$tbl`");
        return $this->stmt->fetchAll();
    }

    public function findOne($tbl,$value, $key = 'id')
    {
        $this->query("SELECT * FROM `$tbl` WHERE  $key = ? LIMIT 1",[$value]);
        return $this->stmt->fetch();
    }

//    public function findOrFail($tbl,$id)
//    {
//        $res = $this->findOne($tbl,$id);
//        if (!$res) {
//            abort();
//        }
//        return $res;
//    }


    public function findOrFail($tbl, $value, $key = 'id')
    {
        $res = $this->findOne($tbl, $value, $key);
        if (!$res) {
            if ($_SERVER['HTTP_ACCEPT'] == 'application/json') {
                response()->json(['status' => 'error', 'answer' => 'Not found'], 404);
            }
            abort();
        }
        return $res;
    }

    public function getInsertId(): false|string
    {
        return $this->connection->lastInsertId();
    }

    public function rowCount():int
    {
        return $this->stmt->rowCount();
    }

    public function count($tbl):int
    {
        $this->query("SELECT COUNT(*) FROM `$tbl`");
        return $this->getColumn();
    }
    public function getQueries():array
    {
        $res = [];
        foreach ($this->queries as $k => $query) {
            $line = strtok($query,PHP_EOL);
            while (false !== $line) {
                if(str_contains($line,'SQL:')||str_contains($line,'Sent SQL:')){
                    $res[$k][] = $line;
                }
                $line = strtok(PHP_EOL);
            }
        }
        return $res;
    }

}