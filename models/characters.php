<?php

class Character extends DatabaseRecord
{
    public $name;
    public $original_name;
    public $gender;

    private $images;
    private $sources;

    public function __construct($id, $name, $original_name, $gender)
    {
        parent::__construct($id);
        $this->table = "characters";

        $this->name = $name;
        $this->original_name = $original_name;
        $this->gender = $gender;

        $this->images = null;
        $this->sources = null;
    }

    public static $gender_map = array(
        0 => "N/A",
        1 => "Female",
        2 => "Male",
    );

    public function pretty_gender()
    {
        return self::$gender_map[$this->gender];
    }

    public function images()
    {
        if ($this->images == null)
        {
            $character_images_table = new CharacterImages();
            $this->images = $character_images_table->multi_find_by_character_id($this->id);
        }

        return $this->images;
    }

    public function sources()
    {
        if ($this->sources == null)
        {
            $conn_character_source_table = new CharacterSourceConnectors();
            $connections = $conn_character_source_table->multi_find_by_character_id($this->id);

            $sources = array();
            foreach ($connections as $connection)
            {
                $sources[] = $connection->source();
            }

            $this->sources = $sources;
        }

        return $this->sources;
    }
}

class Characters extends DatabaseTable
{
    public function __construct() {
        parent::__construct();
        $this->table = "characters";
        $this->columns = array(
            "id",
            "name",
            "original_name",
            "gender",
        );
    }

    public function find_by_id($id)
    {
        return new Character(...parent::find_by_id($id));
    }
}

class CharacterImage extends DatabaseRecord
{
    public $character_id;
    public $path;

    public function __construct($id, $character_id, $path)
    {
        parent::__construct($id);
        $this->table = "character_images";

        $this->character_id = $character_id;
        $this->path = $path;
    }
}

class CharacterImages extends DatabaseTable
{
    public function __construct() {
        parent::__construct();
        $this->table = "character_images";
        $this->columns = array(
            "id",
            "character_id",
            "path",
        );
    }

    public function find_by_id($id)
    {
        return new CharacterImage(...parent::find_by_id($id));
    }

    public function multi_find_by_character_id($character_id)
    {
        $raw_character_images = $this->multi_find_by("character_id", $character_id);

        $character_images = array();
        foreach($raw_character_images as $raw_character_image)
        {
            $character_images[] = new CharacterImage(...$raw_character_image);
        }

        return $character_images;
    }
}