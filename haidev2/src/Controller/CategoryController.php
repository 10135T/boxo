<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoryRepository;
use App\DTO\CategoryDTO;
use App\Entity\Category;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    private EntityManagerInterface $em;
    private CategoryRepository $categoryRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, CategoryRepository $categoryRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    //----CREATE----
    #[Route('/categories', name: 'create_category', methods: 'POST')]
    public function addCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $category = $this->categoryRepository->findOneBy(['name' => $data['name']]);

        if (!$category) {
            $category = new Category();
            $category->setName($data['name']);
            $this->em->persist($category);
            $this->em->flush();
        } else {
            return new JsonResponse(['error' => 'The category already exists!'], JsonResponse::HTTP_NOT_FOUND);
        }

        $categoryDTO = new CategoryDTO($category->getId(), $category->getName(), $this->validator);

        return new jsonResponse($categoryDTO->jsonConverter());
    }

    //----READ----
    #[Route('/categories', name: 'get_all_categories', methods: 'GET')]
    public function getAllCategories(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        if ($name) {
            $entries = $this->categoryRepository->findBy(['name' => $name]);
        } else {
            $entries = $this->categoryRepository->findAll();
        }

        $arr = [];
        //pentru dto nu avem nevoie de serializer
        foreach ($entries as $entry) {
            $categoryDTO = new CategoryDTO($entry->getId(), $entry->getName(), $this->validator);
            $arr[] = $categoryDTO->jsonConverter();
        }

        return new JsonResponse([
            'data' => $arr
        ]);
    }

    //----READ ONE----
    #[Route('/categories/{entry}', name: 'get_one_category', methods: 'GET')]
    public function getOneCategory(Category $entry): JsonResponse
    {
        if (!$entry) {
            return new JsonResponse(['error' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $categoryDTO = new CategoryDTO($entry->getId(), $entry->getName(), $this->validator);

        return new JsonResponse($categoryDTO->jsonConverter());
    }

    //----DELETE----
    #[Route('/categories/{entry}', name: 'delete_category', methods: 'DELETE')]
    public function deleteCategory(Category $entry): JsonResponse
    {
        if (!$entry) {
            return new JsonResponse(['error' => 'No category found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->em->remove($entry);
        $this->em->flush();

        return new JsonResponse(['message' => 'Category deleted successfully']);
    }

    //----UPDATE----
    #[Route('/categories/{entry}', name: 'update_category', methods: 'POST')]
    public function modifyCategory(Category $entry, Request $request): JsonResponse
    {
        if (!$entry) {
            return new JsonResponse(['error' => 'No category found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $entry->setName($data['name']);
        }

        $this->em->persist($entry);
        $this->em->flush();

        //$categoryDTO = new CategoryDTO($entry->getId(), $entry->getName(), $this->validator);

        return new JsonResponse(['message' => 'Category updated successfully']);
    }
}
