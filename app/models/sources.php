<?php

class Source extends DatabaseRecord
{
    public $title;

    private $aliases;

    public function __construct($id, $title)
    {
        parent::__construct($id);
        $this->table = "sources";

        $this->title = $title;

        $this->aliases = null;
    }

    public function aliases()
    {
        if ($this->aliases == null)
        {
            $this->aliases = Database::source_aliases()->multi_find_by_source_id($this->id);
        }

        return $this->aliases;
    }
}

class Sources extends DatabaseTable
{
    public function __construct() {
        parent::__construct();
        $this->table = "sources";
        $this->produces = "Source";
        $this->columns = array(
            "id",
            "title",
        );
    }
}

class SourceAlias extends DatabaseRecord
{
    public $source_id;
    public $alias;

    public function __construct($id, $source_id, $alias)
    {
        parent::__construct($id);
        $this->table = "source_aliases";

        $this->source_id = $source_id;
        $this->alias = $alias;
    }
}

class SourceAliases extends DatabaseTable
{
    public function __construct() {
        parent::__construct();
        $this->table = "source_aliases";
        $this->produces = "SourceAlias";
        $this->columns = array(
            "id",
            "source_id",
            "alias",
        );
    }

    public function multi_find_by_source_id($source_id)
    {
        return $this->multi_find_by("source_id", $source_id);
    }
}
