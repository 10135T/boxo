<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;


class ProductController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em,
                                private readonly ProductRepository $productRepository) {
        
    }
    #[Route('/products', name: 'app_conference_test1', methods: 'POST')]
    public function addProduct(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name']) || !isset($data['price'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse([
        'status' => 200,
        'name' => $product->getName(),
        'price' => $product->getPrice()]);
    }
        #[Route('/products', name: 'app_conference_test_penis', methods: 'GET')]
    public function getAllProducts(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $name = $request->query->get('name');
        if ($name) {
            $entries = $this->productRepository->findBy(['name'=> $name]);
        } else {
            $entries = $this->productRepository->findAll();
        }
        
        $arr = [];
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
    #[Route('/products/{id}', name: 'app_conference_test2', methods: 'DELETE')]
    public function deleteItem(int $id): JsonResponse
    {
        $entry = $this->productRepository->find($id);

        if (!$entry) {
            return new JsonResponse(['error' => 'No product found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->em->remove($entry);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Product deleted succesfully'
        ]);
    }
       #[Route('/products/{id}', name: 'app_conference_test2', methods: 'POST')]
    public function modifyItem(int $id, Request $request): JsonResponse
    {
        //cautam entry-ul cu id-ul din url
        $entry = $this->productRepository->find($id);      
        if (!$entry) {
            return new JsonResponse(['error' => 'No product found'], JsonResponse::HTTP_NOT_FOUND);
        }

        //prelucrare json de intrare
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name']) || !isset($data['price'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //inlocuire date din bd pentru json cu id-ul din url
        $entry->setName($data['name']);
        $entry->setPrice($data['price']);
        
        //$this->em->remove($entry);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Product updated succesfully'
        ]);
    }

}
