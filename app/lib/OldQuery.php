<?php

class OldQuery
{
    private $parse_as;
    private $main_table;
    private $query_type;

    private $count_mode;
    private $and_mode;

    private $primary_key;

    private $select_fields;
    private $count_field;

    private $where_conditions;
    private $where_value_types;
    private $where_values;

    private $order_by_clause;

    private $query_result;
    private $executed_queries;
    private $excluded_fields_for_insert;

    public function __construct($primary_key = "id")
    {
        $this->primary_key = $primary_key;

        $this->parse_as = null;
        $this->main_table = "";
        $this->query_type = "";

        $this->count_mode = false;
        $this->and_mode = false;

        $this->select_fields = array();
        $this->excluded_fields_for_insert = [ $this->primary_key ];
        $this->count_field = "";

        $this->where_conditions = array();
        $this->where_value_types = array();
        $this->where_values = array();

        $this->order_by_clause = "";

        $this->limit_clause = null;
        $this->offset_clause = null;

        $this->query_result = null;
        $this->executed_queries = array();
    }

    public static function new($class = null)
    {
        $instance = new static();

        if ($class !== null)
        {
            $instance->select($class::fields);
            $instance->from($class::table);
            $instance->type($class);
        }

        return $instance;
    }

    public function and()
    {
        $this->and_mode = true;

        return $this;
    }

    public function type($class)
    {
        $this->parse_as = $class;

        return $this;
    }

    public function from($table)
    {
        $this->main_table = $table;

        return $this;
    }

    public function into($table)
    {
        $this->from($table);

        return $this;
    }

    public function select($fields = null)
    {
        $this->query_type = "SELECT";

        if (!empty($fields))
        {
            $this->set_select_fields($fields);
            $this->type(null);
        }

        return $this;
    }

    public function insert($fields = null)
    {
        $this->query_type = "INSERT";

        $this->set_select_fields($fields);

        return $this;
    }

    public function delete($pk = null)
    {
        $this->query_type = "DELETE";

        if ($pk !== null)
        {
            $this->scope_primary_key($pk);
        }

        return $this;
    }

    public function update($pk = null)
    {
        $this->query_type = "UPDATE";

        if ($pk !== null)
        {
            $this->scope_primary_key($pk);
        }

        return $this;
    }

    public function values($values)
    {
        if (gettype($values) == "object" && get_class($values) == static::class)
        {
            $subquery = substr($values->explain(), 0, -1);
            $values = "({$subquery})";

            $this->insert_subquery[] = $value;
            $this->insert_value_types[] = null;
            $this->insert_values[] = null;

            return $this;
        }

        $values = static::wrap_if_single_value($values);

        foreach ($values as $value)
        {
            list($type, $value) = static::convert_value($value);
            $this->insert_subquery[] = null;
            $this->insert_value_types[] = $type;
            $this->insert_values[] = $value;
        }

        return $this;
    }

    public function count(?string $field = null)
    {
        $this->count_mode = true;

        if ($field == null && count($this->select_fields) > 0)
        {
            $field = $this->select_fields[0];
        }

        $this->count_field = $field;

        $this->lazy_execute();

        $this->count_mode = false;

        return $this->query_result;
    }

    public function pluck($key)
    {
        $class = $this->parse_as;
        $this->type(null);

        $plucked = array_map(
            fn($e) => $e[$key],
            $this->all()
        );

        $this->type($class);
        return $plucked;
    }

    public function where(string $clause, $values, $in = false)
    {
        if (strpos($clause, "?") === false)
        {
            throw new Exception("Where clauses need a question mark for preparation.");
        }

        if (gettype($values) == "object" && get_class($values) == static::class)
        {
            $subquery = substr($values->explain(), 0, -1);
            $clause = str_replace("?", "({$subquery})", $clause);

            $values = array();
        }

        $this->where_conditions[] = $clause;

        $values = static::wrap_if_single_value($values);

        foreach ($values as $value)
        {
            list($type, $value) = static::convert_value($value);
            $this->where_value_types[] = $type;
            $this->where_values[] = $value;
        }
        
        return $this;
    }

    public function in($field, $values)
    {
        $in_values = array();
        foreach ($values as $value)
        {
            list($type, $value) = static::convert_value($value);
            $value = static::quote_strval($value);
            $in_values[] = $value;
        }

        $in_values = implode(", ", $in_values);

        $clause = "{$field} IN ({$in_values})";

        $this->where_conditions[] = $clause;

        return $this;
    }

    public function order_by(string $field, bool $asc = true)
    {
        $this->order_by_clause = $field . " " . ($asc ? "ASC" : "DESC");
        
        return $this;
    }

    public function limit($limit)
    {
        $this->limit_clause = $limit;

        return $this;
    }

    public function offset($offset)
    {
        $this->offset_clause = $offset;

        return $this;
    }

    public function explain()
    {
        return $this->fake_substitute();
    }

    public function commit()
    {
        $this->execute();

        return $this->query_result;
    }

    public function find($id)
    {
        $this->query_type = "SELECT";
        $field = $this->primary_key;
        $this->where("{$field} = ?", $id);

        return $this->first();
    }

    public function find_by($field, $id)
    {
        $this->query_type = "SELECT";
        $this->where("{$field} = ?", $id);

        return $this->first();
    }

    public function all()
    {
        $this->query_type = "SELECT";
        $this->lazy_execute();
        
        return $this->query_result ?? null;
    }

    public function first($amount = null)
    {
        $this->query_type = "SELECT";
        $this->lazy_execute();

        if ($amount == null)
        {
            return $this->query_result[0] ?? null;
        }

        if (count($this->query_result) < $amount)
        {
            $amount = count($this->query_result);
        }

        return array_slice($this->query_result, 0, $amount) ?? null;
    }

    public function history()
    {
        return array_keys($this->executed_queries);
    }

    private function scope_primary_key($pk)
    {
        $this->where("{$this->primary_key} = ?", $pk);
    }

    private function set_select_fields($fields)
    {
        if ($fields == null)
        {
            return $this->select_fields;
        }

        $fields = $this->parse_fields($fields);

        if ($this->and_mode)
        {
            $this->and_mode = false;
            $this->select_fields = array_merge($this->select_fields, $fields);
        }
        else
        {
            $this->select_fields = $fields;
        }

        return $this->select_fields;
    }

    private function parse_fields($fields)
    {
        if (is_string($fields))
        {
            $fields = explode(",", str_replace(" ", "", $fields));
        }

        if (is_array($fields))
        {
            return $fields;
        }
        else
        {
            throw new Exception("Unknown select field variable type passed.");
        }
    }

    private function execute()
    {
        $query = $this->build_query();

        $this->query_result = static::prepared_query($query, $this->build_values());

        $this->executed_queries[$query] = $this->query_result;

        return $this->process_query_result();
    }

    private function process_query_result()
    {
        if ($this->query_type == "SELECT")
        {
            if ($this->count_mode)
            {
                $this->query_result = $this->query_result[0]["count"] ?? 0;
            }
            else if ($this->parse_as !== null)
            {
                $class = $this->parse_as;
                $parsed = array();

                foreach ($this->query_result as $record)
                {
                    $parsed[] = new $class(...$record);
                }

                $this->query_result = $parsed;
            }
        }

        return $this->query_result;
    }

    private function lazy_execute()
    {
        $query = $this->build_query();
        if (in_array($query, $this->executed_queries))
        {
            $this->query_result = $this->executed_queries[$query];
            return $this->process_query_result();
        }

        return $this->execute();
    }

    private function fake_substitute()
    {
        $statement = $this->build_query();

        foreach ($this->build_values() as $value)
        {
            $sub = $value;
            if (gettype($value) == "string")
            {
                $sub = "'{$sub}'";
            }

            $pos = strpos($statement, "?");
            $before = substr($statement, 0, $pos);
            $after = substr($statement, $pos + 1);
            $statement = $before . $sub . $after;
        }
        
        return $statement;
    }

    private function build_select()
    {
        if ($this->count_mode)
        {
            $field = $this->count_field;
            $fields = "COUNT({$field}) AS count";
        }
        else
        {
            $fields = implode(", ", $this->select_fields);
        }
        
        $main_table = $this->main_table;
        return "SELECT {$fields} FROM {$main_table}";
    }

    private function build_insert()
    {
        $insert_fields = array_diff($this->select_fields, $this->excluded_fields_for_insert);
        $fields = implode(", ", $insert_fields);

        $field_placeholders = array();
        for ($i = 0; $i < count($insert_fields); $i++)
        {
            $field_placeholders[] = "?";
        }

        $field_placeholders = implode(", ", $field_placeholders);

        $main_table = $this->main_table;
        return "INSERT INTO {$main_table} ({$fields}) VALUES ({$field_placeholders})";
    }

    private function build_delete()
    {
        $main_table = $this->main_table;
        return "DELETE FROM {$main_table}";
    }

    private function build_update()
    {
        $main_table = $this->main_table;

        $fields = array_diff($this->select_fields, $this->excluded_fields_for_insert);

        $fields = array_map(
            fn($field) => "{$field} = ?",
            $fields
        );

        $fields = implode(", ", $fields);

        return "UPDATE {$main_table} SET {$fields}";
    }

    private function build_where()
    {
        $clause = implode(" AND ", $this->where_conditions);
        return "WHERE {$clause}";
    }

    private function build_order_by()
    {
        if (empty($this->order_by_clause))
        {
            $order = $this->select_fields[0];
        }
        else
        {
            $order = $this->order_by_clause;
        }
        
        return "ORDER BY {$order}";
    }

    private function build_limit()
    {
        $limit = $this->limit_clause;
        return "LIMIT {$limit}";
    }

    private function build_offset()
    {
        $offset = $this->offset_clause;
        return "OFFSET {$offset}";
    }

    private function build_query()
    {
        $query_clauses = array();
        
        if ($this->query_type == "SELECT")
        {
            $query_clauses[] = $this->build_select();
        }
        else if ($this->query_type == "INSERT")
        {
            $query_clauses[] = $this->build_insert();
        }
        else if ($this->query_type == "DELETE")
        {
            $query_clauses[] = $this->build_delete();
        }
        else if ($this->query_type == "UPDATE")
        {
            $query_clauses[] = $this->build_update();
        }

        if ($this->query_type == "SELECT" || $this->query_type == "DELETE" || $this->query_type == "UPDATE")
        {
            if (!empty($this->where_conditions))
            {
                $query_clauses[] = $this->build_where();
            }
            else if ($this->query_type == "DELETE" || $this->query_type == "UPDATE")
            {
                throw new Exception("{$this->query_type} queries without WHERE conditions are not allowed.");
            }
        }

        if ($this->query_type == "SELECT" && !$this->count_mode)
        {
            $query_clauses[] = $this->build_order_by();

            if ($this->limit_clause !== null)
            {
                $query_clauses[] = $this->build_limit();
            }

            if ($this->offset_clause !== null)
            {
                $query_clauses[] = $this->build_offset();
            }
        }

        $query = implode(" ", $query_clauses);
        $query .= ";";

        if ($this->query_type == "SELECT" && $this->parse_as !== null)
        {
            $class = $this->parse_as;
            $query .= " /* {$class}::class */";
        }

        return $query;
    }

    private function build_values()
    {
        $values = array();

        if ($this->query_type == "INSERT" || $this->query_type == "UPDATE")
        {
            if (!empty($this->insert_values))
            {
                $values = array_merge($values, $this->insert_values);
            }
        }

        if ($this->query_type == "SELECT" || $this->query_type == "DELETE" || $this->query_type == "UPDATE")
        {
            if (!empty($this->where_values))
            {
                $values = array_merge($values, $this->where_values);
            }
        }

        return $values;
    }

    private static function wrap_if_single_value($values)
    {
        if (is_array($values))
        {
            return $values;
        }

        return array($values);
    }

    private static function convert_value($value)
    {
        switch (gettype($value))
        {
            case "integer":
                return [ "i", intval($value) ];
            case "double":
                return [ "d", doubelval($value) ];
            default:
                return [ "s", strval($value) ];
        }
    }

    private static function quote_strval($value)
    {
        switch (gettype($value))
        {
            case "integer":
                return $value;
            case "double":
                return $value;
            default:
                return "'{$value}'";
        }
    }

    private static function prepared_query($statement, $substitutions)
    {
        $db = Database::connect();
        $prepared = $db->prepare($statement);

        if ($prepared === false)
        {
            throw new Exception("Malformed prepared statement: {$statement}");
        }

        $types = "";
        foreach ($substitutions as $key => $substitution)
        {
            switch (gettype($substitution))
            {
                case "integer":
                    $types .= "i";
                    break;
                case "double":
                    $types .= "d";
                    break;
                case "string":
                default:
                    $types .= "s";
                    $substitutions[$key] = strval($substitution);
                    break;
            }
        }

        if (!empty($substitutions))
        {
            $prepared->bind_param($types, ...$substitutions);
        }
        
        $success = $prepared->execute();

        if (!$success)
        {
            throw new Exception("Query error: " . print_r($prepared->error_list, true));
        }

        if (strtoupper(explode(" ", $statement)[0]) == "SELECT")
        {
            $result = $prepared->get_result();
            $assoc = mysqli_fetch_all($result, MYSQLI_ASSOC);
            return $assoc;
        }
        else if (strtoupper(explode(" ", $statement)[0]) == "INSERT")
        {
            return $prepared->insert_id;
        }
        else if (strtoupper(explode(" ", $statement)[0]) == "DELETE")
        {
            return $prepared->affected_rows;
        }
        else if (strtoupper(explode(" ", $statement)[0]) == "UPDATE")
        {
            return $prepared->affected_rows;
        }

        return null;
    }
}
