<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;




class CategoryDTO
{
    private ?int $id;

    #[Assert\NotBlank(message: 'The name shall not be left blank!')]
    #[Assert\Length(max: 255)]
    private string $name;

    public function __construct(?int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;

//        $errors = $validator->validate($this);
//        if (count($errors) > 0) {
//            $errorsString = (string)$errors;
//            throw new \Exception($errorsString);
//        }
    }
    public function jsonConverter(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
