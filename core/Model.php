<?php

namespace PHPFramework;

abstract class Model
{
    protected string $table = '';

    public string $pk = 'id';
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

    public function save(): false|string
    {
        db()->insert($this->table, $this->attributes)->execute();
        return db()->getInsertId();

    }

    public function update()
    {
        if(!isset($this->attributes[$this->pk])){
            return false;
        }

        $data = $this->attributes;
        $id = $data[$this->pk];
        unset($data[$this->pk]);
        if (empty($data)) {
            return 0;
        }

        return db()
            ->update($this->table, $data)
            ->where($this->pk, $id)
            ->execute();
    }

    public function delete(int $id): int
    {
        return db()
            ->delete($this->table)
            ->where($this->pk, $id)
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