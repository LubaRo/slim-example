<?php

namespace App;

class Validator
{
    public function validate(array $course)
    {
        $errors = [];

        if (empty($course['name'])) {
            $errors['name'] = "Can't be blank";
        }
        if (empty($course['email'])) {
            $errors['email'] = "Can't be blank";
        }
        if (empty($course['phone'])) {
            $errors['phone'] = "Can't be blank";
        }

        return $errors;
    }
}
