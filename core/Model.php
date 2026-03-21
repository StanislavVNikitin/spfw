<?php

namespace PHPFramework;

abstract class Model
{
    protected string $table = '';

    protected string $pk = 'id';
    protected array $fillable = [];
    public array $attributes = [];
    protected array $errors = [];

    protected array $rules = [];
    protected array $labels = [];

    protected array $data_items = [];
    protected array $rules_list = ['required','min','max','email','unique','file','ext','size','match'];
    protected array $messages = [
        'required' => ':fieldname: field is required.',
        'min' => ':fieldname: field must be at least :rulevalue: characters.',
        'max' => ':fieldname: field must be less than :rulevalue: characters.',
        'int' => ':fieldname: field must be an integer.',
        'email' => ':fieldname: field must be a valid email address.',
        'unique' => ':fieldname: is already taken.',
        'file' => ':fieldname: field is required.',
        'ext' => 'File :fieldname: extension does not match. Allowed :rulevalue:.',
        'size' => 'File :fieldname: is too large. Allowed :rulevalue: bytes.',
        'match' => ':fieldname: field must match :rulevalue: field',
    ];

    protected Database $db;

    /**
     * текущий fluent-конструктор для SELECT-запросов
     */
    protected ?Database $query = null;

    public function __construct()
    {
        // Один экземпляр Database на приложение (через helper db()).
        $this->db = db();
    }

    protected function assertTableIsSet(): void
    {
        if (empty($this->table)) {
            abort('Model::$table is not set for ' . static::class, 500);
        }
    }

    protected function ensureQueryStarted(): void
    {
        if ($this->query === null) {
            $this->select('*');
        }
    }

    protected function resetQuery(): void
    {
        $this->query = null;
    }

    /**
     * SELECT-конструктор запросов (ORM-версия).
     *
     * Пример:
     * $posts = (new Post())->select(['id','title'])->where('is_published',1)->get();
     */
    public function select(array|string $columns = '*'): static
    {
        $this->assertTableIsSet();
        $this->query = $this->db->select($this->table, $columns);
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        $this->ensureQueryStarted();
        if (func_num_args() === 2) {
            $this->query->where($column, $operatorOrValue);
        } else {
            $this->query->where($column, $operatorOrValue, $value);
        }
        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        $this->ensureQueryStarted();
        if (func_num_args() === 2) {
            $this->query->orWhere($column, $operatorOrValue);
        } else {
            $this->query->orWhere($column, $operatorOrValue, $value);
        }
        return $this;
    }

    public function whereRaw(string $sql, array $params = [], string $boolean = 'AND'): static
    {
        $this->ensureQueryStarted();
        $this->query->whereRaw($sql, $params, $boolean);
        return $this;
    }

    public function join(string $table, string $on, string $type = 'INNER'): static
    {
        $this->ensureQueryStarted();
        $this->query->join($table, $on, $type);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->ensureQueryStarted();
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function orderByRaw(string $expression): static
    {
        $this->ensureQueryStarted();
        $this->query->orderByRaw($expression);
        return $this;
    }

    public function groupBy(string|array $columns): static
    {
        $this->ensureQueryStarted();
        $this->query->groupBy($columns);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->ensureQueryStarted();
        $this->query->limit($limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->ensureQueryStarted();
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Выполняет собранный SELECT и возвращает массив моделей.
     *
     * В каждый результат добавляет поля в `$attributes`.
     *
     * @return array<static>
     */
    public function get(): array
    {
        if ($this->query === null) {
            return [];
        }

        $rows = $this->query->get();
        $this->resetQuery();

        if ($rows === false) {
            return [];
        }

        $models = [];
        foreach ($rows as $row) {
            $models[] = $this->hydrate($row);
        }
        return $models;
    }

    /**
     * Выполняет собранный SELECT и возвращает массив.
     *
     * В каждый результат добавляет поля в `$attributes`.
     *
     * @return array<static>
     */
    public function getArray(): array
    {
        if ($this->query === null) {
            return [];
        }

        $rows = $this->query->get();
        $this->resetQuery();

        if ($rows === false) {
            return [];
        }

        return $rows;
    }

    /**
     * Выполняет собранный SELECT и возвращает первую строку как массив.
     */
    public function firstArray(): ?array
    {
        if ($this->query === null) {
            return null;
        }

        $row = $this->query->first();
        $this->resetQuery();

        return $row ?: null;
    }

    /**
     * Выполняет собранный SELECT и возвращает первую модель.
     */
    public function first(): ?static
    {
        if ($this->query === null) {
            return null;
        }

        $row = $this->query->first();
        $this->resetQuery();

        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }

    /**
     * Синоним `first()` для совместимости по смыслу.
     */
    public function getOne(): ?static
    {
        return $this->first();
    }

    /**
     * Выполняет собранный SELECT и возвращает ассоциативный массив моделей.
     */
    public function getAssoc(string $key = ''): array
    {
        if (empty($key)) {
            $key = $this->pk;
        }

        if ($this->query === null) {
            return [];
        }

        $rows = $this->query->getAssoc($key);
        $this->resetQuery();

        $models = [];
        foreach ($rows as $modelKey => $row) {
            $models[$modelKey] = $this->hydrate($row);
        }
        return $models;
    }

    /**
     * Выполняет собранный SELECT и возвращает ассоциативный массив массивов.
     */
    public function getAssocArray(string $key = 'id'): array
    {
        if (empty($key)) {
            $key = $this->pk;
        }

        if ($this->query === null) {
            return [];
        }

        $rows = $this->query->getAssoc($key);
        $this->resetQuery();

        return $rows;
    }

    /**
     * Создает экземпляр модели из строки результата.
     */
    protected function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        return $model;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function save(): false|string
    {
        $this->db->insert($this->table, $this->attributes)->execute();
        return $this->db->getInsertId();

    }

    public function update()
    {
        if(!isset($this->attributes[$this->pk])){
            return false;
        }

        $data = $this->attributes;
        $pk = $data[$this->pk];
        unset($data[$this->pk]);
        if (empty($data)) {
            return 0;
        }

        return $this->db
            ->update($this->table, $data)
            ->where($this->pk, $pk)
            ->execute();
    }

    public function delete(int $pk): int
    {
        return $this->db
            ->delete($this->table)
            ->where($this->pk, $pk)
            ->execute();
    }


    public function loadData(): void
    {
        $data = request()->getData();
        foreach ($this->fillable as $v) {
            if (isset($data[$v])) {
                $this->attributes[$v] = $data[$v];
            }else {
                $this->attributes[$v] = '';
            }
        }
    }

    public function validate($data = [], $rules = []): bool
    {
        if (!$data){
            $data = $this->attributes;
        }

        if (!$rules){
            $rules = $this->rules;
        }

        $this->data_items = $data;

        foreach ($data as $fieldname => $value) {
            if (isset($rules[$fieldname])) {
                $this->check([
                    'fieldname' => $fieldname,
                    'value' => $value,
                    'rules' => $rules[$fieldname]
                ]);
            }
        }
        return !$this->hasErrors();
    }

    protected function check(array $field): void
    {
        foreach ($field['rules'] as $rule => $rule_value) {
            if (in_array($rule, $this->rules_list)) {
                if(!call_user_func_array([$this,$rule],[$field['value'],$rule_value]))
                {
                    $this->addErrors(
                        $field['fieldname'],
                        str_replace(
                            [':fieldname:',':rulevalue:'],
                            [$this->labels[$field['fieldname']] ?? $field['fieldname'], $rule_value],
                            $this->messages[$rule]
                        )
                    );
                }
            }
        }
    }

    protected function addErrors($fieldname, $error): void
    {
        $this->errors[$fieldname][] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function listErrors(): string
    {
        $output = '<ul class="list-unstyled">';
        foreach ($this->errors as $fielderrors) {
            foreach ($fielderrors as $error) {
                $output .= "<li>{$error}</li>";
            }
        }
        $output .= '</ul>';
        return $output;
    }

    protected function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    protected function required($value, $rule_value): bool
    {
        return !empty(trim($value));
    }

    protected function min($value, $rule_value): bool
    {
        return mb_strlen($value,'UTF-8') >= $rule_value;
    }

    protected function max($value, $rule_value): bool
    {
        return mb_strlen($value,'UTF-8') <= $rule_value;
    }

    protected function int($value, $rule_value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !==false;
    }

    protected function email($value, $rule_value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    protected function match($value, $rule_value): bool
    {
       return $value === $this->data_items[$rule_value];
    }

    protected function unique($value, $rule_value): bool
    {
        $data = explode(':',$rule_value);
        if (str_contains($data[1],',')){
            $data_fields = explode(',',$data[1]);
            return !db()
                ->select($data[0], $data_fields[0])
                ->where($data_fields[0], $value)
                ->where($data_fields[1], '!=', $this->data_items[$data_fields[1]])
                ->getColumn();
        }
        return !db()
            ->select($data[0], $data[1])
            ->where($data[1], $value)
            ->getColumn();
    }

    protected function file($value, $rule_value)
    {

        if(isset($value['error']) && is_array($value['error']))
        {
            foreach ($value['error'] as $file_error) {
                if($file_error !== 0)
                {
                    return false;
                }
            }

        }elseif (isset($value['error']) && $value['error'] !== 0)
        {
            return false;
        }

        return true;

    }

    protected function ext($value, $rule_value):bool
    {
        //проверка когда массив файлов
        if (is_array($value['name'])){
            if(empty($value['name'][0])){
                return true;
            }

            for ($i = 0; $i < count($value['name']); $i++) {
                $file_ext = get_file_ext($value['name'][$i]);
                $allowed_exts = explode('|',$rule_value);
                if (!in_array($file_ext,$allowed_exts)){
                    return false;
                }
            }
            return true;
        }

        //проверка когда один файл
        if(empty($value['name'])){
            return true;
        }

        $file_ext = get_file_ext($value['name']);
        $allowed_exts = explode('|',$rule_value);
        return in_array($file_ext,$allowed_exts);

    }

    protected function size($value, $rule_value):bool
    {
        if (is_array($value['size'])){
            if(empty($value['size'][0])){
                return true;
            }
            for ($i = 0; $i < count($value['size']); $i++) {
                if ($value['size'][$i] >= $rule_value){
                    return false;
                }            }
            return true;
        }

        if(empty($value['size'])){
            return true;
        }
        return $value['size'] <= $rule_value;
    }


}