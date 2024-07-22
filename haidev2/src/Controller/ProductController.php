<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;


class ProductController extends AbstractController
{   
    //dependency injection
    public function __construct(private readonly EntityManagerInterface $em,
                                private readonly ProductRepository $productRepository) {
        
    }

    //----CREATE----
    #[Route('/products', name: 'app_conference_test1', methods: 'POST')]
    public function addProduct(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name']) || !isset($data['price']) || !isset($data['category'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($data['category']);

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setCategory($category);
        $this->em->persist($product);
        $this->em->persist($category);
        $this->em->flush();

        return new JsonResponse([
        'name' => $product->getName(),
        'price' => $product->getPrice()], JsonResponse::HTTP_CREATED);
    }
    //----READ----
    #[Route('/products', name: 'app_conference_test_r', methods: 'GET')]
    public function getAllProducts(Request $request): JsonResponse
    {   
        //price prin query builders!! in product repository
        $name = $request->query->get('name');
        if ($name) {
            $entries = $this->productRepository->findBy(['name'=> $name]);
        } else {
            $entries = $this->productRepository->findAll();
        }
        
        $arr = [];
        //replace with serializer
        foreach ($entries as $entry) {
            $arr[] = [
                'id' => $entry->getId(),
                'name' => $entry->getName(),
                'price' => $entry->getPrice()
            ];
        }
        $jsonData = json_encode($arr);

        if($jsonData === false){
            throw new \RuntimeException('Failed JSON encoding');
        }

        return new JsonResponse([
         'data' => $arr
        ]);
    }

    #[Route('/products/{entry}', name: 'app_conference_test_rfdsfsdf', methods: 'GET')]
    public function getOneProduct(Product $entry): JsonResponse
    {
        if(!$entry){
            return new JsonResponse(['error' => 'Product not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        return new JsonResponse([
         'data' => [
            'id' => $entry->getId(),
            'name' => $entry->getName(),
            'price' => $entry->getPrice()
        ]
        ]);
    }


    //----DELETE----
    #[Route('/products/{entry}', name: 'app_conference_test223423', methods: 'DELETE')]
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
    #[Route('/products/{entry}', name: 'app_conference_test2998', methods: 'POST')]
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
