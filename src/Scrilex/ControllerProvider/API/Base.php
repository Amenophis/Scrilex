<?php

namespace Scrilex\ControllerProvider\API;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Response;

abstract class Base implements ControllerProviderInterface {

    protected $entityClass;
    protected $routePrefix;
    
    public function __construct($entityClass, $routePrefix)
    {
        $this->entityClass = $entityClass;
        $this->routePrefix = $routePrefix;
    }
    
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $this->app = $app;
        
        $that = $this;
        
        $controllers->get("/", function () use ($that){ return $that->all(); })->bind("{$this->routePrefix}_list");
        $controllers->get("/{id}", function ($id) use ($that){ return $that->one($id); })->bind("{$this->routePrefix}_one");
        $controllers->post("/", function () use ($that){ return $that->create(); })->bind("{$this->routePrefix}_create");
        $controllers->put("/{id}", function ($id) use ($that){ return $that->update($id); })->bind("{$this->routePrefix}_update");
        $controllers->delete("/{id}", function ($id) use ($that){ return $that->delete($id); })->bind("{$this->routePrefix}_delete");
        
        return $controllers;
    }
    
    protected function response($response)
    {
        return new Response(json_encode($response), 200, array(
            "Content-Type" => $this->app['request']->getMimeType('json')
        ));
    }
    
    public function all()
    {
        $items = $this->app['db.orm.em']->getRepository($this->entityClass)->findAll();
        
        $arrays = array();
        foreach($items as $item)
        {
            $arrays[] = $item->toArray();
        }
        
        return $this->response($arrays);
    }
    
    public function one($id)
    {
        $item = $this->app['db.orm.em']->getRepository($this->entityClass)->find($id);
        if(!$item) $this->app->abort ('404', 'Item not found');
        return $this->response($item->toArray());
    }
    
    public function create()
    {
        $item = $this->createFromJSON($json);
        
        $this->app['db.orm.em']->persist($item);
        $this->app['db.orm.em']->flush();
        
        return $this->app->json($this->app['serializer']->serialize($item, 'json'));
    }
    
    public function update($id)
    {
        $item = $this->app['db.orm.em']->getRepository($this->entityClass)->find($id);
        if(!$item) $this->app->abort ('404', 'Item not found');
        $item = $this->updateFromJSON($item, $json);
        $this->app['db.orm.em']->flush();
        
        return $this->app->json($this->app['serializer']->serialize($item, 'json'));
    }
    
    public function delete($id)
    {
        $item = $this->app['db.orm.em']->getRepository($this->entityClass)->find($id);
        if(!$item) $this->app->abort ('404', 'Item not found');
        
        $this->app['db.orm.em']->remove($item);
    }    
}