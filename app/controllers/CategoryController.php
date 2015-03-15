<?php

use Repositories\Platform\PlatformRepositoryInterface as PlatformRepositoryInterface;
use Repositories\Category\CategoryRepositoryInterface as CategoryRepositoryInterface;
use Repositories\Product\ProductRepositoryInterface as ProductRepositoryInterface;

class CategoryController extends \BaseController {

    public function __construct(PlatformRepositoryInterface $platform, CategoryRepositoryInterface $category, ProductRepositoryInterface $product) {
        $this->platform = $platform;
        $this->category = $category;
        $this->product = $product;
    }

    /**
     * 
     *
     * @return Response
     */
    public function store() {
        //Campos del formulario
        $fields = Input::only(['name', 'description']);

        //Reglas de validación
        $rules = [
            'name' => 'required|unique:categories',
            'description' => 'string',
        ];

        //Validación de los campos del formulario
        $validator = Validator::make($fields, $rules);

        //Los campos no son válidos
        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator, 'create')
                            ->withInput($fields);
        }

        //Éxito al guardar
        if ($this->category->create($fields)) {

            return Redirect::back()->with('save_success', 'Categoría creada correctamente.');
        }

        //Error de SQL
        return Redirect::back()
                        ->withErrors(['error' => 'Error al intentar crear la categoría.'], 'create')
                        ->withInput(Input::all());
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id) {

        $validator = Validator::make(['id' => $id], ['id' => 'exists:categories']);

        //El id no existe
        if ($validator->fails()) {
            return Redirect::back();
        }

        $category = $this->category->find($id);

        //Miga de pan
        Breadcrumb::addBreadcrumb('Edición de categorías', URL::route('admin.category.index'));
        Breadcrumb::addBreadcrumb($category->name);

        return View::make('admin.pages.edit')
                        ->with('restful', 'category')
                        ->with('model', $category)
                        ->with('header_title', "Editar categoría (id: {$category->id})")
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

    /**
     * 
     *
     * @param  int  $platformId
     * @return Response
     */
    public function show($platformId, $categoryId) {

        //Categoría no existente para ese id y plataforma
        if (!$this->category->exists($categoryId, $platformId)) {
            return Redirect::route('index');
        }

        //Se añade el id de la plataforma y de la categoría al input
        Input::replace(array_merge(['platform_id' => $platformId, 'category_id' => $categoryId], Input::all()));


        //Plataforma
        $platform = $this->platform->find($platformId);

        //Categoría
        $category = $this->category->find($categoryId);

        //Productos para esa plataforma y categoría
        $products = $this->product->paginateByPlatformAndCategory($platformId, $categoryId, Input::get('sort', 'name'), Input::get('sort_dir', 'asc'));

        //Miga de pan
        Breadcrumb::addBreadcrumb('Inicio', URL::route('index'));
        Breadcrumb::addBreadcrumb($platform->name, URL::route('platform.show', ['platform_id' => $platformId]));
        Breadcrumb::addBreadcrumb($category->name);

        return View::make('client.pages.category')
                        ->with('platform', $platform)
                        ->with('category', $category)
                        ->with('products', $products)
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id) {

        //Campos del formulario
        $fields = Input::only(['name', 'description']);

        //Reglas de validación
        $rules = [
            'name' => "required|unique:categories,name,$id",
            'description' => 'string',
        ];

        //Validación de los campos del formulario
        $validator = Validator::make($fields, $rules);

        //Los campos no son válidos
        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator, 'update')
                            ->withInput($fields);
        }

        //Éxito al guardar
        if ($this->category->update($id, $fields)) {

            return Redirect::back()->with('save_success', 'Categoría modificada correctamente.');
        }

        //Error de SQL
        return Redirect::back()
                        ->withErrors(['error' => 'Error al intentar modificar la categoría.'], 'update')
                        ->withInput(Input::all());
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {
        $validator = Validator::make(['id' => $id], ['id' => 'exists:categories']);

        //El id no existe
        if ($validator->fails()) {
            return Response::json([
                        'success' => false,
                        'errors' => $validator->getMessageBag()->toArray()
                            ], 400); // 400 being the HTTP code for an invalid request.
        }

        //Éxito al eliminar
        if ($this->category->erase($id)) {
            return Response::json(['success' => true], 200);
        }

        //Error de SQL
        return Response::json([
                    'success' => false,
                    'errors' => ['error' => 'Error al intentar borrar la categoría.']
                        ], 400);

//        //El id no existe
//        if ($validator->fails()) {
//            return Redirect::back();
//        }
//
//        //Éxito al eliminar
//        if ($this->category->erase($id)) {
//            return Redirect::back();
//        }
//
//        //Error de SQL
//        return Redirect::back()
//                        ->withErrors(['error' => 'Error al intentar borrar la categoría.'], 'erase');
    }

    public function index() {

        $categories = $this->category->paginateForIndexTable('name', 'asc', 20, Input::get('page'));

        if (Request::ajax()) {
            return Response::json(View::make('admin.includes.index_table')
                                    ->with([
                                        'data' => $categories,
                                        'header' => ['ID', 'Categoría'],
                                        'restful' => 'category'])->render(), 200);
        }

        //Miga de pan
        Breadcrumb::addBreadcrumb('Edición');

        return View::make('admin.pages.index')
                        ->with([
                            'data' => $categories,
                            'header' => ['ID', 'Categoría'],
                            'restful' => 'category'])
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

}
