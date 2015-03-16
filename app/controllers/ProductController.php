<?php

use Repositories\Platform\PlatformRepositoryInterface as PlatformRepositoryInterface;
use Repositories\Category\CategoryRepositoryInterface as CategoryRepositoryInterface;
use Repositories\Product\ProductRepositoryInterface as ProductRepositoryInterface;

class ProductController extends \BaseController {

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

        //Cuando el checkbox del producto destacado está desmarcado su valor es null y se necesita un false
        Input::get('highlighted') !== null ? : Input::merge(['highlighted' => false]);

        //Campos del formulario
        $data = Input::only([
                    'game_id', 'platform_id', 'publisher_id', 'price', 'discount',
                    'stock', 'launch_date', 'highlighted', 'singleplayer', 'multiplayer',
                    'cooperative',
        ]);

        //Reglas de validación
        $rules = [
            'game_id' => 'exists:games,id|unique_with:products,platform_id',
            'platform_id' => 'exists:platforms,id',
            'publisher_id' => 'exists:publishers,id',
            'price' => 'numeric|min:0.01',
            'discount' => 'numeric|min:0|max:100',
            'stock' => 'integer|min:0|max:100',
            'launch_date' => 'date',
            'highlighted' => 'boolean',
            'singleplayer' => 'boolean',
            'multiplayer' => 'boolean',
            'cooperative' => 'boolean',
        ];

        //Validación de los campos del formulario
        $validator = Validator::make($data, $rules);

        //Los campos no son válidos
        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator, 'create')
                            ->withInput($data);
        }


        if ($data['launch_date'] > 0) {
            $data['launch_date'] = new DateTime($data['launch_date']);
        }

        $this->product->create($data);

        return Redirect::back()->with('save_success', 'Producto creado correctamente.');
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id) {

        $validator = Validator::make(['id' => $id], ['id' => 'exists:products']);

        //El id no existe
        if ($validator->fails()) {
            return Redirect::back();
        }

        $product = $this->product->getById($id);

        //Para el formato de la fecha
        $product->launch_date = $product->launch_date > 0 ? date_format(new dateTime($product->launch_date), 'd-m-Y') : '';

        //Miga de pan
        Breadcrumb::addBreadcrumb('Edición de productos', URL::route('admin.product.index'));
        Breadcrumb::addBreadcrumb("ID: $product->id");

        return View::make('admin.pages.edit')
                        ->with('restful', 'product')
                        ->with('model', $product)
                        ->with('header_title', "Editar producto (id: {$product->id})")
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

    /**
     * 
     *
     * @param  int  $platformId
     * @return Response
     */
    public function show($platformId, $categoryId, $productId) {

        //Producto no existente para ese id, plataforma y categoría
        if (!$this->product->exists($productId, $platformId, $categoryId)) {
            return Redirect::route('index');
        }

        //Se añaden los ids de la plataforma, la categoría y el producto  al input
        Input::replace(array_merge(['platform_id' => $platformId, 'category_id' => $categoryId, 'product_id' => $productId], Input::all()));

        //Producto
        $product = $this->product->getById($productId);

        //Miga de pan
        Breadcrumb::addBreadcrumb('Inicio', URL::route('index'));
        Breadcrumb::addBreadcrumb($product->platform->name, URL::route('platform.show', ['platform_id' => $platformId]));
        Breadcrumb::addBreadcrumb($product->game->category->name, URL::route('platform.category.show', ['platform_id' => $platformId, 'category_id' => $categoryId]));
        Breadcrumb::addBreadcrumb($product->game->name);


        return View::make('client.pages.product')
                        ->with('product', $product)
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id) {

        //Cuando el checkbox del producto destacado está desmarcado su valor es null y se necesita un false
        Input::get('highlighted') !== null ? : Input::merge(['highlighted' => false]);

        //Campos del formulario
        $data = Input::only([
                    'game_id', 'platform_id', 'publisher_id', 'price', 'discount',
                    'stock', 'launch_date', 'highlighted', 'singleplayer', 'multiplayer',
                    'cooperative',
        ]);

        //Reglas de validación
        $rules = [
            'game_id' => "exists:games,id|unique_with:products,platform_id,$id",
            'platform_id' => 'exists:platforms,id',
            'publisher_id' => 'exists:publishers,id',
            'price' => 'numeric|min:0.01',
            'discount' => 'numeric|min:0|max:100',
            'stock' => 'integer|min:0',
            'launch_date' => 'date',
            'highlighted' => 'boolean',
            'singleplayer' => 'boolean',
            'multiplayer' => 'boolean',
            'cooperative' => 'boolean',
        ];

        //Validación de los campos del formulario
        $validator = Validator::make($data, $rules);

        //Los campos no son válidos
        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator, 'update')
                            ->withInput($data);
        }

        if ($data['launch_date'] > 0) {
            $data['launch_date'] = new DateTime($data['launch_date']);
        }

        $this->product->updateById($id, $data);

        return Redirect::back()->with('save_success', 'Producto modificado correctamente.');
    }

    /**
     * 
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {

        $validator = Validator::make(['id' => $id], ['id' => 'exists:products']);

        //El id no existe
        if ($validator->fails()) {
            return Response::json([
                        'success' => false,
                        'errors' => $validator->getMessageBag()->toArray()
                            ], 400); // 400 being the HTTP code for an invalid request.
        }

        $this->product->deleteById($id);

        return Response::json(['success' => true], 200);
    }

    public function index() {

        $products = $this->product->paginateForIndexTable('games.name', 'asc', 20, Input::get('page'));

        if (Request::ajax()) {
            return Response::json(View::make('admin.includes.index_table')
                                    ->with([
                                        'data' => $products,
                                        'header' => ['ID', 'Juego', 'Plataforma', 'Categoría', 'Distribuidora'],
                                        'restful' => 'product'])->render(), 200);
        }

        //Miga de pan
        Breadcrumb::addBreadcrumb('Edición');

        return View::make('admin.pages.index')
                        ->with([
                            'data' => $products,
                            'header' => ['ID', 'Juego', 'Plataforma', 'Categoría', 'Distribuidora'],
                            'restful' => 'product'])
                        ->with('breadcrumbs', Breadcrumb::generate());
    }

}
