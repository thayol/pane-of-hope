<?php

class Source extends DatabaseRecord
{
    const fields = [ "id", "title" ];
    const table = "sources";

    public $title;

    private $aliases;
    private $characters;

    public function __construct($id, $title)
    {
        parent::__construct($id);

        $this->title = $title;

        $this->aliases = null;
        $this->characters = null;
    }

    public function set_aliases($aliases)
    {
        $this->aliases = $aliases;
    }

    public function set_characters($characters)
    {
        $this->characters = $characters;
    }

    public function aliases()
    {
        if ($this->aliases == null)
        {
            $this->aliases = SourceAlias::select()->where("source_id = ?", $this->id)->each();
        }

        return $this->aliases;
    }

    public function characters()
    {
        if ($this->characters == null)
        {
            $this->characters = array_map(
                fn($conn) => $conn->character(),
                CharacterSourceConnector::select()
                    ->where("source_id = ?", $this->id)
                    ->each()
            ) ?? array();
        }

        return $this->characters;
    }
}
