<?php

namespace App\Controller;

use App\DTO\ProductDTO;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Category;
use App\DTO\CategoryDTO;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ProductController extends AbstractController
{   
    //dependency injection
    public function __construct(EntityManagerInterface $em, ProductRepository $productRepository,CategoryRepository $categoryRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

//----CREATE----
    #[Route('/products', name: 'create_product', methods: 'POST')]
    public function addProduct(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name']) || !isset($data['price']) || !isset($data['category'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $category = $this->categoryRepository->findOneBy(['name' => $data['category']]);

        if (!$category) {
            return new JsonResponse(['error' => 'The category does not exist!'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setCategory($category);

        $this->em->persist($product);
        $this->em->flush();

        $categoryDTO = $category ? new CategoryDTO($category->getId(), $category->getName()) : null;

        $productDTO = new ProductDTO(
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $categoryDTO
        );

        return new JsonResponse($productDTO->jsonConverter());
    }

    //----READ ALL----
    #[Route('/products', name: 'read_all_products', methods: 'GET')]
    public function getAllProducts(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        if ($name) {
            $entries = $this->productRepository->findBy(['name'=> $name]);
        } else {
            $entries = $this->productRepository->findAll();
        }

        $arr = [];
        //pentru dto nu avem nevoie de serializer
        foreach ($entries as $entry) {
            $category = $entry->getCategory();
            $categoryDTO = $category ? new CategoryDTO($category->getId(), $category->getName()) : null;
            $productDTO = new ProductDTO(
                $entry->getId(),
                $entry->getName(),
                $entry->getPrice(),
                $categoryDTO,
            );
            $arr[] = $productDTO->jsonConverter();
        }

        return new JsonResponse([
            'data' => $arr
        ]);
    }

    //--READ ONE--
    #[Route('/products/{entry}', name: 'read_one_product', methods: 'GET')]
    public function getOneProduct(Product $entry): JsonResponse
    {
        if(!$entry){
            return new JsonResponse(['error' => 'Product not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $categoryDTO = $entry->getCategory() ? new CategoryDTO($entry->getCategory()->getId(), $entry->getCategory()->getName()) : null;


        $productDTO = new ProductDTO(
            $entry->getId(),
            $entry->getName(),
            $entry->getPrice(),
            $categoryDTO,
        );

        return new JsonResponse($productDTO->jsonConverter());
    }


    //----DELETE----
    #[Route('/products/{entry}', name: 'delete_product', methods: 'DELETE')]
    public function deleteItem(Product $entry): JsonResponse
    {
        //$entry = $this->productRepository->find($id);

        if (!$entry) {
            return new JsonResponse(['error' => 'No product found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $this->em->remove($entry);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Product deleted succesfully'
        ]);
    }
    //----UPDATE----
    #[Route('/products/{entry}', name: 'update_product', methods: 'POST')]
    public function modifyItem(Product $entry, Request $request): JsonResponse
    {
        //cautam entry-ul cu id-ul din url    
        if (!$entry) {
            return new JsonResponse(['error' => 'No product found'], JsonResponse::HTTP_NOT_FOUND);
        }

        //prelucrare json de intrare
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //inlocuire date din bd pentru json cu id-ul din url
        if(isset($data['name'])) {
            $entry->setName($data['name']);
        }
        if(isset($data['price'])) {
            $entry->setPrice($data['price']);
        }
       
        $this->em->persist($entry);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Product updated succesfully'
        ]);
    }

}
