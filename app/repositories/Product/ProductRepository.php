<?php

namespace Repositories\Product;

use KyleNoland\LaravelBaseRepository\BaseRepository as BaseRepository;

use \Product as Product;

/**
 * Description of ProductRepository
 *
 * @author Ivan
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface {

    public function __construct(Product $model) {
        $this->model = $model;
    }
    
    public function addDeveloper($id, $developerId) {
        return Product::find($id)->developers()->attach($developerId);
    }

    public function addLanguage($id, $languageId, $type) {
        return Product::find($id)->languages()->attach($languageId, ['type' => $type]);
    }
    
    public function removeDevelopers($id) {
        return Product::find($id)->developers()->detach();
    }

    public function removeLanguages($id) {
        return Product::find($id)->languages()->detach();
    }
    
    public function getByGameAndPlatform($gameId, $platformId) {

        return Product::where('game_id', '=', $gameId)->where('platform_id', '=', $platformId)->first();
    }

    public function exists($productId, $platformId = null, $categoryId = null) {

        $product = Product::where('id', '=', $productId);

        if ($platformId) {
            $product = $product->where('platform_id', '=', $platformId);
        }

        if ($categoryId) {
            $product = $product->whereHas('game', function ($query) use ($categoryId) {
                $query->where('category_id', '=', $categoryId);
            });
        }


        return !$product->get()->isEmpty();
    }

    public function paginateForIndexTable($sort = 'name', $sortDir = 'asc', $pagination = 16, $page = 1) {

        $products = Product::
                        leftJoin('games', 'products.game_id', '=', 'games.id')
                        ->leftJoin('categories', function ($query) {
                            $query->on('categories.id', '=', 'games.category_id');
                        })
                        ->leftJoin('platforms', 'products.platform_id', '=', 'platforms.id')
                        ->leftJoin('publishers', 'products.publisher_id', '=', 'publishers.id')
                        ->select(['products.id as id',
                            'games.name as game_name',
                            'platforms.name as platform_name',
                            'categories.name as category_name',
                            'publishers.name as punlisher_name']);
        
        \Paginator::setCurrentPage(($products->count() / $pagination) < $page ? ceil($products->count() / $pagination) : $page);

        return $products->orderBy($sort, $sortDir)->paginate($pagination);
    }

    public function paginateHighlighted($platformId = null, $discounted = null, $sort = 'name', $sortDir = 'asc', $pagination = 16) {

        $products = Product::join('games', 'products.game_id', '=', 'games.id')
                ->where('highlighted', '=', true)
                ->select('products.*');

        if ($platformId) {
            $products = $products->where('platform_id', '=', $platformId);
        }

        if ($discounted) {
            $products = $products->where('discount', ">", "0");
        } else if ($discounted === false) {
            $products = $products->Where('discount', "=", "0");
        }
//dd($products->toSql());
        return $products->orderBy($sort, $sortDir)->paginate($pagination);
    }

    public function paginateSimpleSearch($name = null, $sort = 'name', $sortDir = 'asc', $pagination = 16) {

        return Product::join('games', 'products.game_id', '=', 'games.id')
                        ->where('name', 'like', "%$name%")
                        ->orderBy($sort, $sortDir)
                        ->paginate($pagination);
    }

    public function paginateAdvancedSearch($data = [], $sort = 'name', $sortDir = 'asc', $pagination = 16) {

        $products = Product::
                join('games', 'products.game_id', '=', 'games.id')
                ->join('categories', function ($query) {
                    $query->on('categories.id', '=', 'games.category_id');
                })
                ->join('agerates', function ($query) {
                    $query->on('agerates.id', '=', 'games.agerate_id');
                })
                ->join('platforms', 'products.platform_id', '=', 'platforms.id')
                ->join('publishers', 'products.publisher_id', '=', 'publishers.id');

        //Buscar por nombre
        if (array_key_exists('name', $data) && $data['name'] !== '') {
            $products = $products->where('games.name', 'like', '%' . $data['name'] . '%');
        }

        //Buscar hasta un precio
        if (array_key_exists('price', $data) && $data['price'] !== '') {
            $products = $products->where('price', '<=', $data['price']);
        }

        //Buscar productos sin stock
        if (!array_key_exists('stock', $data) || $data['stock'] === '') {
            $products = $products->where('stock', '>', '0');
        }

        //Valores cerrados de las listas
        $closedValues = [
            'platforms' => 'platform',
            'categories' => 'category',
            'publishers' => 'publisher',
            'agerates' => 'agerate'
        ];

        //Buscar por una platadorma, una categoría, un desarrollador, una 
        //distribuidora o una calificación por edad
        foreach ($closedValues as $table => $input) {
            if (array_key_exists($input, $data) && $data[$input] !== '') {
                $products = $products->where("$table.id", '=', $data[$input]);
            }
        }

        //Tipos de juegos según el número de jugadores
        $playersMode = ['singleplayer', 'multiplayer', 'cooperative'];

        //Buscar por juegos de un jugador, multijugador o cooperativos
        foreach ($playersMode as $input) {
            if (array_key_exists($input, $data) && $data[$input] !== '') {
                $products = $products->where($input, '=', $data[$input]);
            }
        }

        //Buscar a partir de una fecha
        if (array_key_exists('launch_date', $data) && $data['launch_date'] !== '') {
            $products = $products->where('launch_date', '>=', new \DateTime($data['launch_date']));
        }
        
        return $products->orderBy($sort, $sortDir)->paginate($pagination);
    }

    public function paginateByPlatformAndCategory($platformId, $categoryId, $sort = 'name', $sortDir = 'asc', $pagination = 16) {

        $products = Product::join('games', 'products.game_id', '=', 'games.id')
                ->where('category_id', '=', $categoryId)
                ->where('platform_id', '=', $platformId)
                ->select('products.*');


        return $products->orderBy($sort, $sortDir)->paginate($pagination);
    }

    public function all($discounted = null, $sort = 'price', $sortDir = 'asc') {
        
        if ($discounted) {
            $products = Product::where('discount', ">", "0");
        } else if ($discounted === false) {
            $products = Product::where('discount', "=", "0");
        } else {
            $products = Product::all();
        }

        return $products->orderBy($sort, $sortDir)->get();
    }

}
