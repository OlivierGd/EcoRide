<?php

namespace class;

class user
{
    private string $firstname;
    private string $name;
    private string $email;
    private string $password;

    public function __construct($firstname, $name, $email, $password)
    {
        $this->firstname = $firstname;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }
}