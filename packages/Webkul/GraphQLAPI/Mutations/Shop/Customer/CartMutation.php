<?php

namespace Webkul\GraphQLAPI\Mutations\Shop\Customer;

use Exception;
use Illuminate\Http\JsonResponse;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Http\Controllers\Controller;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Product\Repositories\ProductRepository;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CartMutation extends Controller
{
    /**
     * Contains current guard
     *
     * @var array
     */
    protected $guard;

    /**
     * CartRepository object
     *
     * @var \Webkul\Checkout\Repositories\CartRepository;
     */
    protected $cartRepository;

    /**
     * ProductRepository object
     *
     * @var \Webkul\Product\Repositories\ProductRepository;
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Checkout\Repositories\CartRepository  $cartRepository
     * @param  \Webkul\Product\Repositories\ProductRepository  $productRepository
     * @return void
     */
    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository
    )
    {
        $this->guard = 'api';

        auth()->setDefaultDriver($this->guard);
        
        $this->middleware('auth:' . $this->guard);
        
        $this->cartRepository = $cartRepository;
        
        $this->productRepository = $productRepository;
    }

    /**
     * Returns a current cart detail.
     *
     * @return \Illuminate\Http\Response
     */
    public function cart($rootValue, array $args , GraphQLContext $context)
    {
        try {
            $cart = Cart::getCart();

            return $cart;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Returns a current cart's detail.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartItems($rootValue, array $args , GraphQLContext $context)
    {
        try {
            $cart = Cart::getCart();

            return $cart->items;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($rootValue, array $args, GraphQLContext $context)
    {
        if (! isset($args['input']) || (isset($args['input']) && !$args['input'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }

        $data = $args['input'];

        if (! isset($data['product_id']) || (isset($data['product_id']) && !$data['product_id'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }
        
        try {
            $product = $this->productRepository->findOrFail($data['product_id']);

            $data = bagisto_graphql()->manageInputForCart($product, $data);
            
            $cart = Cart::addProduct($data['product_id'], $data);
            
            if ( isset($cart->id)) {
                return [
                    'success'   => trans('bagisto_graphql::app.shop.response.success-add-to-cart'),
                    'cart'      => $cart,
                ];
            } else {
                return $cart;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($rootValue, array $args, GraphQLContext $context)
    {
        if (! isset($args['input']) || (isset($args['input']) && !$args['input'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }

        $data = $args['input'];

        if (! isset($data['qty']) || (isset($data['qty']) && !$data['qty'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }
        
        try {
            $qty = [];
            foreach ($data['qty'] as $item) {
                if (isset($item['cart_item_id']) && $item['quantity']) {
                    $qty[$item['cart_item_id']] = $item['quantity'];
                }
            }
            $data['qty'] = $qty;

            $cart = Cart::updateItems($data);
            
            if ( $cart == true) {
                return [
                    'success'   => trans('bagisto_graphql::app.shop.response.success-update-to-cart'),
                    'cart'      => Cart::getCart(),
                ];
            } else {
                return $cart;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($rootValue, array $args, GraphQLContext $context)
    {
        if (! isset($args['id']) || (isset($args['id']) && !$args['id'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }

        $cartItemId = $args['id'];
        
        try {
            $cart = Cart::removeItem($cartItemId);
            
            if ( $cart == true) {
                return [
                    'success'   => trans('bagisto_graphql::app.shop.response.success-delete-cart-item'),
                    'cart'      => Cart::getCart(),
                ];
            } else {
                return $cart;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Move the specified resource to Wishlist.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function move($rootValue, array $args, GraphQLContext $context)
    {
        if (! isset($args['id']) || (isset($args['id']) && !$args['id'])) {
            throw new Exception(trans('bagisto_graphql::app.admin.response.error-invalid-parameter'));
        }

        $cartItemId = $args['id'];
        
        try {
            $cart = Cart::moveToWishlist($cartItemId);
            
            if ( $cart == true) {
                return [
                    'success'   => trans('bagisto_graphql::app.shop.response.success-moved-cart-item'),
                    'cart'      => Cart::getCart(),
                ];
            } else {
                return $cart;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}