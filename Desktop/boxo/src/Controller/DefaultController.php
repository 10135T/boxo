<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\GamesRepository;
use App\DTO\GamesDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Games;
use Ramsey\Uuid\Uuid;

class DefaultController extends AbstractController
{
    private $em;
    private $gamesRepository;

    public function __construct(EntityManagerInterface $em, GamesRepository $gamesRepository, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->gamesRepository = $gamesRepository;
    }

    #[Route('/create', name: 'app_create_room', methods: ['POST'])]
    public function createRoom(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //uuid
        $uuid = Uuid::uuid4();
        
        $game = new Games();
        $game->setName($data['name']);
        $game->setUuid($uuid);
        $this->em->persist($game);
        $this->em->flush();
        
        return $this->json([
            'message' => 'Room created successfully!',
            'url' => 'http://127.0.0.1:8000/play/' . $uuid
        ]);
    }
    
    #[Route('/play', name: 'all_rooms', methods: ['GET'])]
    public function getAllRooms(Request $request): JsonResponse
    {
        $uuid = $request->query->get('uuid');
        
        if ($uuid) {
            $entries = $this->gamesRepository->findBy(['uuid' => $uuid]);
        } else {
            $entries = $this->gamesRepository->findAll();
        }

        $arr = [];

        foreach ($entries as $entry) {
            $gamesDTO = new GamesDTO($entry->getId(), $entry->getUuid(), $entry->getName());
            $arr[] = $gamesDTO->jsonConverter();
        }

        return new JsonResponse([
            'data' => $arr
        ]);
    }

    #[Route('/play/{uuid}', name: 'app_connect_room', methods: ['GET'])]
    public function connectToRoom(string $uuid, Request $request): JsonResponse
    {
        // Retrieve session from request
        $session = $request->getSession();
        if (!$session) {
            return $this->json([
                'message' => 'Session is not available.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Retrieve existing player sessions for the room from session storage
        $rooms = $session->get('rooms', []);
        if (!isset($rooms[$uuid])) {
            $rooms[$uuid] = [];
        }

        // Check if the player is already in the room
        $sessionId = $session->getId();
        if (in_array($sessionId, $rooms[$uuid])) {
            return $this->json([
                'message' => 'You are already in the room ' . $uuid,
                'player_count' => count($rooms[$uuid]),
            ]);
        }

        // Check the number of players in the room
        if (count($rooms[$uuid]) >= 2) {
            return $this->json([
                'message' => 'Room is full',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Add the current session to the room
        $rooms[$uuid][] = $sessionId;
        $session->set('rooms', $rooms);

        return $this->json([
            'message' => 'You are in the room ' . $uuid,
            'player_count' => count($rooms[$uuid]),
        ]);
    }
    //asdad
    #[Route('/leave/{uuid}', name: 'app_leave_room', methods: ['GET'])]
    public function leaveRoom(string $uuid, Request $request): JsonResponse
    {
        // Retrieve session from request
        $session = $request->getSession();
        if (!$session) {
            return $this->json([
                'message' => 'Session is not available.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $rooms = $session->get('rooms', []);
        if (isset($rooms[$uuid])) {
            $sessionId = $session->getId();
            // Remove the session from the room
            $rooms[$uuid] = array_filter($rooms[$uuid], fn($id) => $id !== $sessionId);
            $session->set('rooms', $rooms);
        }

        return $this->json([
            'message' => 'You have left the room ' . $uuid,
            'player_count' => isset($rooms[$uuid]) ? count($rooms[$uuid]) : 0,
        ]);
    }
}


