<?php

class Account extends DatabaseRecord
{
    const fields = [ "id", "username", "displayname", "password", "email", "permission_level" ];
    const table = "accounts";

    public $username;
    public $displayname;
    public $email;
    public $permission_level;

    private $password_hash;

    public function __construct($id, $username, $displayname, $password, $email, $permission_level)
    {
        parent::__construct($id);

        $this->username = $username;
        $this->displayname = $displayname;
        $this->email = $email;
        $this->permission_level = $permission_level;
        
        $this->password_hash = $password;
    }

    public function password_verify($password)
    {
        return password_verify($password, $this->password_hash);
    }

    public function is_admin()
    {
        return $this->permission_level >= Config::$admin_permission_level;
    }
}
