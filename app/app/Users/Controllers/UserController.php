<?php

namespace App\Users\Controllers;

use Core\BaseController;
use Core\Request;
use Core\Authenticate;
use App\Users\Services\PermissionRoleService;
use App\Users\Services\UserService;
use App\Users\Repositories\PermissionRoleRepository;
use App\Users\Repositories\UserRepository;
use App\Users\Exceptions\UserCreateException;

class UserController extends BaseController
{
    use Authenticate;
    protected $container;

    public function __construct(\Core\Container $container)
    {
        $this->userService = new UserService(new UserRepository($container->get('connection')));
        $this->auth = $container->get('auth');
        $this->container = $container;
        $this->setupFlashMessages($this->container->get('session'));
    }

    public function create()
    {

        $messages = ['errors'=>$this->errors,
                    'success'=>$this->success,
                    'inputs'=>$this->inputs,
                    'roles'=>$this->getAllRoles(),
                    ];
        return  $this->get('template')
                ->setup($messages, 'user/create', 'Novo usuario')
                ->setUseLayout(true)
                ->render();
    }

    public function listUsers()
    {
        $messages = ['errors'=>$this->errors,
                    'success'=>$this->success,
                    'inputs'=>$this->inputs,
                    'users'=>$this->userService->listAll(),
                    ];
        return  $this->get('template')
                ->setup($messages, 'user/list', 'Listar Usuarios')
                ->setUseLayout(true)
                ->render();
    }

    public function store(Request $request)
    {
        $getPost = $request->getPost()->post;
        $this->get('session')->set('inputs', $getPost);
        try {
            $this->container->get('connection')->beginTransaction();
            $this->userService->create((array)$getPost);
            
            $this->container->get('connection')->commit();
            return $this->get('redirect')->route('/users', [
                'success' => ["Usuário criado com sucesso!"]
            ]);
        } catch (UserCreateException $error) {
            $this->container->get('connection')->rollback();
            return $this->get('redirect')->route('/user/create', [
                'errors' => [$error->getMessage()]
            ]);
        }
    }

    public function edit(Request $request)
    {
        $idUser = $request->getRouteParams('id');
        $messages = ['errors'=>$this->errors,
                    'success'=>$this->success,
                    'inputs'=>$this->inputs,
                    'user'=>$this->userService->getById($idUser),
                    'roles'=>$this->getAllRoles(),
                    ];
        return  $this->get('template')
                ->setup($messages, 'user/edit', 'Editar usuario')
                ->setUseLayout(true)
                ->render();
    }

    public function update(Request $request)
    {
        $getPost = $request->getPost()->post;
        $this->get('session')->set('inputs', $getPost);
        try {
            $this->container->get('connection')->beginTransaction();
            
            $this->userService->update((array)$getPost);
            
            $this->container->get('connection')->commit();

            return $this->get('redirect')->route('/users', [
                'success' => ["Usuário editado com sucesso!"]
            ]);
        } catch (UserCreateException $error) {
            $this->container->get('connection')->rollback();
            return $this->get('redirect')->route('/user/'.$getPost->id.'/edit', [
                'errors' => [$error->getMessage()]
            ]);
        }
    }

    private function getAllRoles()
    {
        $roleService = new PermissionRoleService(new PermissionRoleRepository($this->get('connection')));
        return $roleService->listAll();
    }
}
