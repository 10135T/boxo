<?php
namespace App\DTO;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductDTO
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    private ?int $id;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[Assert\NotBlank]
    #[Assert\Type('float')]
    private float $price;

    private ?CategoryDTO $category;

    public function __construct(?int $id, string $name, float $price, ?CategoryDTO $category)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->category = $category;

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
            'price' => $this->price,
            'category' => $this->category ? $this->category->jsonConverter() : null, // Dacă categoria există, o serializează, altfel returnează null
        ];
    }
}

