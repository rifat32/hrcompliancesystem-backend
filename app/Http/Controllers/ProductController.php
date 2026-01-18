<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductLinkRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Utils\ErrorUtil;

use App\Http\Utils\UserActivityUtil;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\ProductVariation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ErrorUtil,UserActivityUtil;
     /**
     *
  * @OA\Post(
  *      path="/v1.0/products",
  *      operationId="createProduct",
  *      tags={"shop_section.product_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to store product",
  *      description="This method is to store product",
  *
  *  @OA\RequestBody(
  *           description=" product type be single or variable",
  *         required=true,
  *         @OA\JsonContent(
  *            required={"type","name","description","shop_id","sku","image","images","sku","price","quantity","product_variations","product_category_id"},
  *    @OA\Property(property="type", type="string", format="string",example="single"),
  *  *    @OA\Property(property="name", type="string", format="string",example="gear"),
  *    @OA\Property(property="description", type="string", format="string",example="car description"),
   *    @OA\Property(property="shop_id", type="number", format="number",example="1"),
   *   *    @OA\Property(property="product_category_id", type="number", format="number",example="1"),
   *
   *    *   *    @OA\Property(property="sku", type="string", format="string",example="car 123"),
   *  *    @OA\Property(property="image", type="string", format="string",example="/abcd/efgh"),
   *  *    @OA\Property(property="images", type="string", format="array",example={"/f.png","/g.jpeg"}),
   *  *    @OA\Property(property="price", type="number", format="number",example="10"),
   *  *    @OA\Property(property="quantity", type="number", format="number",example="20"),
   *
   *    *  *    @OA\Property(property="product_variations", type="string", format="array",example={
   *
   * {
   * "automobile_make_id":1,
   * "price":10,
   * "quantity":30
   * },
   *  * {
   * "automobile_make_id":2,
   * "price":20,
   * "quantity":30
   * },
   *
   *
   *
   * }),


  *
  *         ),
  *      ),
  *      @OA\Response(
  *          response=200,
  *          description="Successful operation",
  *       @OA\JsonContent(),
  *       ),
  *      @OA\Response(
  *          response=401,
  *          description="Unauthenticated",
  * @OA\JsonContent(),
  *      ),
  *        @OA\Response(
  *          response=422,
  *          description="Unprocesseble Content",
  *    @OA\JsonContent(),
  *      ),
  *      @OA\Response(
  *          response=403,
  *          description="Forbidden",
  *   @OA\JsonContent()
  * ),
  *  * @OA\Response(
  *      response=400,
  *      description="Bad Request",
  *   *@OA\JsonContent()
  *   ),
  * @OA\Response(
  *      response=404,
  *      description="not found",
  *   *@OA\JsonContent()
  *   )
  *      )
  *     )
  */

 public function createProduct(ProductCreateRequest $request)
 {
     try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        return DB::transaction(function () use ($request) {
            if(!$request->user()->hasPermissionTo('product_create')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           $insertableData = $request->validated();
           $sku_prefix = "";
  // shop id is required if user is not super admin od data collector. and shop id means it is spacific to shop. so it is not default
           if(!empty($insertableData["shop_id"])) {
              $insertableData["is_default"] = false;
          $shop =   $this->shopOwnerCheck($insertableData["shop_id"]);
              if (!$shop) {
                  return response()->json([
                      "message" => "you are not the owner of the shop or the requested shop does not exist."
                  ], 401);
              }
              $sku_prefix = $shop->sku_prefix;
           } else {
              $insertableData["is_default"] = true;
           }


           $product =  Product::create($insertableData);

           if(empty($product->sku)){
              $product->sku = $sku_prefix .  str_pad($product->id, 4, '0', STR_PAD_LEFT);
          }
          $product->save();

           if($product->type == "single"){

              $product->product_variations()->create([

                      "sub_sku" => $product->sku,
                      "quantity" => $insertableData["quantity"],
                      "price" => $insertableData["price"],
                      "automobile_make_id" => NULL,


              ]);
           } else {
              foreach($insertableData["product_variations"] as $product_variation) {
                  $c = ProductVariation::
                  where('product_id', $product->id)
                  ->count() + 1;

                  $product->product_variations()->create([

                      "sub_sku" => $product->sku . "-" . $c,
                      "quantity" => $product_variation["quantity"],
                      "price" => $product_variation["price"],
                      "automobile_make_id" => $product_variation["automobile_make_id"],


              ]);

              }

           }

           if(!empty($insertableData["images"])) {
            foreach($insertableData["images"] as $product_image){
                ProductGallery::create([
                    "image" => $product_image,
                    "product_id" =>$product->id,
                ]);
            }
           }

           return response($product, 201);
        });


     } catch(Exception $e){
         error_log($e->getMessage());
     return $this->sendError($e,500,$request);
     }
 }

/**
     *
  * @OA\Put(
  *      path="/v1.0/products",
  *      operationId="updateProduct",
  *      tags={"shop_section.product_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to update Product ",
  *      description="This method is to update Product",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={"id","name","description","shop_id","sku","image","images","sku","price","quantity","product_variations","product_category_id"},
    *    @OA\Property(property="id", type="number", format="number",example="1"),
    *    @OA\Property(property="type", type="string", format="string",example="single"),
  *  *    @OA\Property(property="name", type="string", format="string",example="gear"),
  *    @OA\Property(property="description", type="string", format="string",example="car description"),
   *    @OA\Property(property="shop_id", type="number", format="number",example="1"),
   * *   *    @OA\Property(property="product_category_id", type="number", format="number",example="1"),
   *   *    @OA\Property(property="sku", type="string", format="string",example="car 123"),
   *  *    @OA\Property(property="image", type="string", format="string",example="/abcd/efgh"),
   *  *    @OA\Property(property="images", type="string", format="array",example={"/f.png","/g.jpeg"}),
   *  *    @OA\Property(property="price", type="number", format="number",example="10"),
   *  *    @OA\Property(property="quantity", type="number", format="number",example="20"),
   *
   *    *  *    @OA\Property(property="product_variations", type="string", format="array",example={
   *
   * {
   * "id":1,
   * "automobile_make_id":1,
   * "price":10,
   * "quantity":30
   * },
   *  * {
   * * "id":2,
   * "automobile_make_id":2,
   * "price":20,
   * "quantity":30
   * },
   **  * {
   * *
   * "automobile_make_id":3,
   * "price":30,
   * "quantity":30
   * }
   *
   *
   * }),

  *
  *         ),
  *      ),
  *      @OA\Response(
  *          response=200,
  *          description="Successful operation",
  *       @OA\JsonContent(),
  *       ),
  *      @OA\Response(
  *          response=401,
  *          description="Unauthenticated",
  * @OA\JsonContent(),
  *      ),
  *        @OA\Response(
  *          response=422,
  *          description="Unprocesseble Content",
  *    @OA\JsonContent(),
  *      ),
  *      @OA\Response(
  *          response=403,
  *          description="Forbidden",
  *   @OA\JsonContent()
  * ),
  *  * @OA\Response(
  *      response=400,
  *      description="Bad Request",
  *   *@OA\JsonContent()
  *   ),
  * @OA\Response(
  *      response=404,
  *      description="not found",
  *   *@OA\JsonContent()
  *   )
  *      )
  *     )
  */

  public function updateProduct(ProductUpdateRequest $request)
  {

      try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        return DB::transaction(function () use ($request) {
            if(!$request->user()->hasPermissionTo('product_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



        $product_prev = Product::where(
            [
"id" => $updatableData["id"],
            ]
        )
        ->first();
        if(!$product_prev) {

            return response()->json([
                "message" => "no product found"
            ],404);
        }

        if(!empty($product_prev->shop_id)) {
            $shop =   $this->shopOwnerCheck($product_prev->shop_id);
            if (!$shop) {
                return response()->json([
                    "message" => "you are not the owner of the shop or the requested shop does not exist."
                ], 401);
            }
        }

                $product  =  tap(Product::where(
                    [
                        "id" => $product_prev->id,
                        "shop_id" => $product_prev->shop_id
                                    ]
                ))->update(collect($updatableData)->only([
                  "name",
                  "sku",
                  "description",
                  "image",
                  // "is_active",
                  "is_default",
                  "product_category_id",
                //   "shop_id"

                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$product) {

                       return response()->json([
                           "message" => "no product found"
                       ],404);
                   }



           if($product->type == "single"){

              $product->product_variations()
              ->where([
                   "product_vairations.product_id" => $product->id
              ])
              ->update([

                      "sub_sku" => $product->sku,
                      "quantity" => $updatableData["quantity"],
                      "price" => $updatableData["price"],
                      "automobile_make_id" => NULL,
              ]);
           } else {
              foreach($updatableData["product_variations"] as $product_variation) {

                  if(empty($product_variation["id"])) {
                      $c = ProductVariation::
                      where('product_id', $product->id)
                      ->count() + 1;

                      $product->product_variations()->create([

                          "sub_sku" => $product->sku . "-" . $c,
                          "quantity" => $product_variation["quantity"],
                          "price" => $product_variation["price"],
                          "automobile_make_id" => $product_variation["automobile_make_id"],

                  ]);

                  } else {
                      $product->product_variations()
                      ->where([

                          "product_variations.id" => $product_variation["id"]
                      ])
                      ->update([

                          // "sub_sku" => $product->sku . "-" . $c,
                          "quantity" => $product_variation["quantity"],
                          "price" => $product_variation["price"],
                          "automobile_make_id" => $product_variation["automobile_make_id"],

                  ]);
                  }

              }
              }

              if(!empty($updatableData["images"])) {
                ProductGallery::where([
                    "product_id" =>$product->id,
                ])
                ->delete();
              }
              if(!empty($updatableData["images"])) {
                foreach($updatableData["images"] as $product_image){
                    ProductGallery::create([
                        "image" => $product_image,
                        "product_id" =>$product->id,
                    ]);
                }

              }


            return response($product, 201);
        });


      } catch(Exception $e){
          error_log($e->getMessage());
      return $this->sendError($e,500,$request);
      }
  }


  /**
     *
  * @OA\Patch(
  *      path="/v1.0/products/link-product-to-shop",
  *      operationId="linkProductToShop",
  *      tags={"shop_section.product_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to link Product To Shop ",
  *      description="This method is to link Product To Shop",
  *
  *  @OA\RequestBody(
 *         required=true,
  *         @OA\JsonContent(
  *            required={"name","description","shop_id","sku","image","images","sku","price","quantity","product_variations","product_category_id"},
*    @OA\Property(property="type", type="string", format="string",example="single"),
  *  *    @OA\Property(property="name", type="string", format="string",example="gear"),
  *    @OA\Property(property="description", type="string", format="string",example="car description"),
   *    @OA\Property(property="shop_id", type="number", format="number",example="1"),
   * *   *    @OA\Property(property="product_category_id", type="number", format="number",example="1"),

   *  *    @OA\Property(property="image", type="string", format="string",example="/abcd/efgh"),
   *  *    @OA\Property(property="images", type="string", format="array",example={"/f.png","/g.jpeg"}),
   *  *    @OA\Property(property="price", type="number", format="number",example="10"),
   *  *    @OA\Property(property="quantity", type="number", format="number",example="20"),
   *
   *    *  *    @OA\Property(property="product_variations", type="string", format="array",example={
   *
   * {
   *
   * "automobile_make_id":1,
   * "price":10,
   * "quantity":30
   * },
   *  * {
   * *
   * "automobile_make_id":2,
   * "price":20,
   * "quantity":30
   * },
   **  * {
   * *
   * "automobile_make_id":3,
   * "price":30,
   * "quantity":30
   * }
   *
   *
   * }),


  *
  *         ),
  *      ),
  *      @OA\Response(
  *          response=200,
  *          description="Successful operation",
  *       @OA\JsonContent(),
  *       ),
  *      @OA\Response(
  *          response=401,
  *          description="Unauthenticated",
  * @OA\JsonContent(),
  *      ),
  *        @OA\Response(
  *          response=422,
  *          description="Unprocesseble Content",
  *    @OA\JsonContent(),
  *      ),
  *      @OA\Response(
  *          response=403,
  *          description="Forbidden",
  *   @OA\JsonContent()
  * ),
  *  * @OA\Response(
  *      response=400,
  *      description="Bad Request",
  *   *@OA\JsonContent()
  *   ),
  * @OA\Response(
  *      response=404,
  *      description="not found",
  *   *@OA\JsonContent()
  *   )
  *      )
  *     )
  */

  public function linkProductToShop(ProductLinkRequest $request)
  {

      try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        return DB::transaction(function () use ($request) {
            if(!$request->user()->hasPermissionTo('product_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();

            if (!$this->shopOwnerCheck($updatableData["shop_id"])) {
                return response()->json([
                    "message" => "you are not the owner of the shop or the requested shop does not exist."
                ], 401);
            }
            $shop =   $this->shopOwnerCheck($updatableData["shop_id"]);
            if (!$shop) {
                return response()->json([
                    "message" => "you are not the owner of the shop or the requested shop does not exist."
                ], 401);
            }

            $sku_prefix = $shop->sku_prefix;
            $updatableData["is_default"] = false;


                     $product =  Product::create($updatableData);

                     if(empty($product->sku)){
                        $product->sku = $sku_prefix .  str_pad($product->id, 4, '0', STR_PAD_LEFT);
                    }
                    $product->save();

                     if($product->type == "single"){

                        $product->product_variations()->create([

                                "sub_sku" => $product->sku,
                                "quantity" => $updatableData["quantity"],
                                "price" => $updatableData["price"],
                                "automobile_make_id" => NULL,


                        ]);
                     } else {
                        foreach($updatableData["product_variations"] as $product_variation) {
                            $c = ProductVariation::
                              where('product_id', $product->id)
                            ->count() + 1;

                            $product->product_variations()->create([

                                "sub_sku" => $product->sku . "-" . $c,
                                "quantity" => $product_variation["quantity"],
                                "price" => $product_variation["price"],
                                "automobile_make_id" => $product_variation["automobile_make_id"],


                        ]);

                        }




                     }



                     return response($product, 201);






        });


      } catch(Exception $e){
          error_log($e->getMessage());
      return $this->sendError($e,500,$request);
      }
  }
/**
     *
  * @OA\Get(
  *      path="/v1.0/products/{perPage}",
  *      operationId="getProducts",
  *      tags={"shop_section.product_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },

  *              @OA\Parameter(
  *         name="perPage",
  *         in="path",
  *         description="perPage",
  *         required=true,
  *  example="6"
  *      ),
    *      * *  @OA\Parameter(
* name="product_category_id",
* in="query",
* description="product_category_id",
* required=true,
* example="1"
* ),
  *      * *  @OA\Parameter(
* name="start_date",
* in="query",
* description="start_date",
* required=true,
* example="2019-06-29"
* ),
  * *  @OA\Parameter(
* name="end_date",
* in="query",
* description="end_date",
* required=true,
* example="2019-06-29"
* ),
  * *  @OA\Parameter(
* name="search_key",
* in="query",
* description="search_key",
* required=true,
* example="search_key"
* ),


  *      summary="This method is to get  Product  ",
  *      description="This method is to get Product ",
  *

  *      @OA\Response(
  *          response=200,
  *          description="Successful operation",
  *       @OA\JsonContent(),
  *       ),
  *      @OA\Response(
  *          response=401,
  *          description="Unauthenticated",
  * @OA\JsonContent(),
  *      ),
  *        @OA\Response(
  *          response=422,
  *          description="Unprocesseble Content",
  *    @OA\JsonContent(),
  *      ),
  *      @OA\Response(
  *          response=403,
  *          description="Forbidden",
  *   @OA\JsonContent()
  * ),
  *  * @OA\Response(
  *      response=400,
  *      description="Bad Request",
  *   *@OA\JsonContent()
  *   ),
  * @OA\Response(
  *      response=404,
  *      description="not found",
  *   *@OA\JsonContent()
  *   )
  *      )
  *     )
  */

  public function getProducts($perPage,Request $request) {
    try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        if(!$request->user()->hasPermissionTo('product_view')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }



        $productsQuery =  Product::with("product_variations");

        if(!empty($request->search_key)) {
            $productsQuery = $productsQuery->where(function($query) use ($request){
                $term = $request->search_key;
                $query->where("name", "like", "%" . $term . "%");
            });

        }

        if (!empty($request->product_category_id)) {
            $productsQuery = $productsQuery->where('product_category_id', $request->product_category_id);
        }

        if (!empty($request->start_date)) {
            $productsQuery = $productsQuery->where('created_at', ">=", $request->start_date);
        }

        if (!empty($request->end_date)) {
            $productsQuery = $productsQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
        }


        $products = $productsQuery->orderByDesc("id")->paginate($perPage);

        return response()->json($products, 200);
    } catch(Exception $e){

    return $this->sendError($e,500,$request);
    }
}

 /**
     *
  * @OA\Get(
  *      path="/v1.0/products/single/get/{id}",
  *      operationId="getProductById",
  *      tags={"shop_section.product_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *              @OA\Parameter(
  *         name="id",
  *         in="path",
  *         description="id",
  *         required=true,
  *  example="6"
  *      ),
  *      summary="This method is to get Product by id",
  *      description="This method is to get Product by id",
  *

  *      @OA\Response(
  *          response=200,
  *          description="Successful operation",
  *       @OA\JsonContent(),
  *       ),
  *      @OA\Response(
  *          response=401,
  *          description="Unauthenticated",
  * @OA\JsonContent(),
  *      ),
  *        @OA\Response(
  *          response=422,
  *          description="Unprocesseble Content",
  *    @OA\JsonContent(),
  *      ),
  *      @OA\Response(
  *          response=403,
  *          description="Forbidden",
  *   @OA\JsonContent()
  * ),
  *  * @OA\Response(
  *      response=400,
  *      description="Bad Request",
  *   *@OA\JsonContent()
  *   ),
  * @OA\Response(
  *      response=404,
  *      description="not found",
  *   *@OA\JsonContent()
  *   )
  *      )
  *     )
  */


  public function getProductById($id,Request $request) {
    try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        if(!$request->user()->hasPermissionTo('product_view')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }

        $product =  Product::where([
            "id" => $id
        ])
        ->first()
        ;
        if(!$product) {

return response()->json([
   "message" => "no product found"
],404);
        }

        return response()->json($product, 200);
    } catch(Exception $e){

    return $this->sendError($e,500,$request);
    }
}



/**
        *
     *     @OA\Delete(
     *      path="/v1.0/products/{id}",
     *      operationId="deleteProductById",
     *      tags={"shop_section.product_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete product by id",
     *      description="This method is to delete product by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function deleteProductById($id,Request $request) {

        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$request->user()->hasPermissionTo('product_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           Product::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }




}
