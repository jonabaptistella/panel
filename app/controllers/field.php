<?php

class FieldController extends Kirby\Panel\Controllers\Base {

  public function forFile($pageId, $filename, $fieldName, $fieldType, $path) {

    $page = $this->page($pageId);
    $file = $page->file(rawurldecode($filename));
    $form = $file->form('edit', function() {});

    return $this->route($file, $form, $fieldName, $fieldType, $path);

  }

  public function forPage($pageId, $fieldName, $fieldType, $path) {

    $page  = $this->page($pageId);
    $form  = $page->form('edit', function() {});

    return $this->route($page, $form, $fieldName, $fieldType, $path);

  }

  public function forUser($username, $fieldName, $fieldType, $path) {

    $user = panel()->user($username);
    $form = $user->form('user', function() {});

    return $this->route($user, $form, $fieldName, $fieldType, $path);

  }

  public function route($model, $form, $fieldName, $fieldType, $path) {

    $field = $form->fields()->$fieldName;

    if(!$field or $field->type() !== $fieldType) {
      throw new Exception('Invalid field');
    }

    $routes = $field->routes();
    $router = new Router($routes);
    
    if($route = $router->run($path)) {

      if(is_callable($route->action()) and is_a($route->action(), 'Closure')) {
        return call($route->action(), $route->arguments());
      } else {
 
        $controllerFile = $field->root() . DS . 'controller.php';
        $controllerName = $fieldType . 'FieldController';

        if(!file_exists($controllerFile)) {
          throw new Exception('The field controller file is missing');
        }

        require_once($controllerFile);

        if(!class_exists($controllerName)) {
          throw new Exception('The field controller class is missing');
        }

        $controller = new $controllerName($model, $field);

        return call(array($controller, $route->action()), $route->arguments());

      }
 
    } else {
      throw new Exception('Invalid field route');
    }

  }

}