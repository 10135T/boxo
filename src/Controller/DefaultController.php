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
        // Obținem sesiunea curentă
        $session = $request->getSession();
        
        // Verificăm dacă există deja un UUID pentru jucător în sesiune
        $playerToken = $session->get('player_token');
    
        // Dacă nu există un token, generăm unul nou și îl salvăm în sesiune
        if (!$playerToken) {
            $playerToken = Uuid::uuid4()->toString();  // Generăm un UUID nou
            $session->set('player_token', $playerToken);
        }
    
        // Obținem jocul din baza de date după UUID-ul camerei
        $game = $this->gamesRepository->findOneBy(['uuid' => $uuid]);
    
        // Verificăm dacă jocul există
        if (!$game) {
            return $this->json([
                'message' => 'Room not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    
        // Obținem camerele din sesiune
        $rooms = $session->get('rooms', []);
    
        // Inițializăm camera dacă nu există
        if (!isset($rooms[$uuid])) {
            $rooms[$uuid] = [];
        }
    
        // Verificăm dacă playerToken-ul este deja în cameră
        if (in_array($playerToken, $rooms[$uuid])) {
            return $this->json([
                'message' => 'You are already in the room ' . $uuid,
                'player_count' => $game->getPlayerCount(),
            ]);
        }
    
        // Verificăm dacă camera este plină (max 2 jucători)
        if ($game->getPlayerCount() >= 2) {
            return $this->json([
                'message' => 'Room is full',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Adăugăm token-ul jucătorului în cameră
        $rooms[$uuid][] = $playerToken;
        $session->set('rooms', $rooms);
    
        // Incrementăm numărul de jucători în baza de date
        $game->setPlayerCount($game->getPlayerCount() + 1);
        $this->em->persist($game);
        $this->em->flush();
    
        return $this->json([
            'message' => 'You are in the room ' . $uuid,
            'player_count' => $game->getPlayerCount(),
        ]);
    }
    
    #[Route('/leave/{uuid}', name: 'app_leave_room', methods: ['GET'])]
    public function leaveRoom(string $uuid, Request $request): JsonResponse
    {
        // Obținem sesiunea curentă
        $session = $request->getSession();
        $playerToken = $session->get('player_token');
    
        if (!$playerToken) {
            return $this->json([
                'message' => 'Player not found in session.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Obținem jocul din baza de date după UUID-ul camerei
        $game = $this->gamesRepository->findOneBy(['uuid' => $uuid]);
    
        if (!$game) {
            return $this->json([
                'message' => 'Room not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    
        // Obținem camerele din sesiune
        $rooms = $session->get('rooms', []);
        if (isset($rooms[$uuid])) {
            // Scoatem jucătorul din cameră
            $rooms[$uuid] = array_filter($rooms[$uuid], fn($id) => $id !== $playerToken);
            $session->set('rooms', $rooms);
    
            // Reducem numărul de jucători în baza de date
            if ($game->getPlayerCount() > 0) {
                $game->setPlayerCount($game->getPlayerCount() - 1);
                $this->em->persist($game);
                $this->em->flush();
            }
        }
    
        return $this->json([
            'message' => 'You have left the room ' . $uuid,
            'player_count' => $game->getPlayerCount(),
        ]);
    }
    
    
}


