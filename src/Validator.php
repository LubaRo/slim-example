<?php

namespace App;

class Validator
{
    private $errorTxt = "Can't be blank";

    public function validate(array $course)
    {
        $errors = [];

        if (empty($course['name'])) {
            $errors['name'] = $this->errorTxt;
        }
        if (empty($course['email'])) {
            $errors['email'] = $this->errorTxt;
        }
        if (empty($course['phone'])) {
            $errors['phone'] = $this->errorTxt;
        }

        return $errors;
    }
}
