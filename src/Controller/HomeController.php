<?php

namespace App\Controller;

use App\Service\ToDoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @var ToDoService
     */
    protected $todoService;

    /**
     * HomeController constructor.
     *
     * @param ToDoService $toDoService
     */
    public function __construct(ToDoService $toDoService)
    {
        $this->todoService = $toDoService;
    }

    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        $developerTasks = $this->todoService->getAssignedTasks();


        return $this->render('home/index.html.twig', [
            'developerTasks' => $developerTasks,
        ]);
    }
}
