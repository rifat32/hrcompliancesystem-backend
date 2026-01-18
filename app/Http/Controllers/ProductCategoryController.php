<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCategoryCreateRequest;
use App\Http\Requests\ProductCategoryUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use ErrorUtil,UserActivityUtil;
    /**
     *
  * @OA\Post(
  *      path="/v1.0/product-categories",
  *      operationId="createProductCategory",
  *      tags={"shop_section.product_category_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to store product category",
  *      description="This method is to store product category",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={"name","icon","description"},
  *    @OA\Property(property="name", type="string", format="string",example="car"),
  *  *    @OA\Property(property="icon", type="string", format="string",example="fa fa tui halua kha"),
  *    @OA\Property(property="description", type="string", format="string",example="car"),


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

 public function createProductCategory(ProductCategoryCreateRequest $request)
 {
     try{

        $this->storeActivity($request, "DUMMY activity","DUMMY description");

         if(!$request->user()->hasPermissionTo('product_category_create')){
              return response()->json([
                 "message" => "You can not perform this action"
              ],401);
         }

         $insertableData = $request->validated();

         $product_category =  ProductCategory::create($insertableData);



         return response($product_category, 201);
     } catch(Exception $e){
         error_log($e->getMessage());
     return $this->sendError($e,500,$request);
     }
 }
/**
     *
  * @OA\Put(
  *      path="/v1.0/product-categories",
  *      operationId="updateProductCategory",
  *      tags={"shop_section.product_category_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to update Product Category",
  *      description="This method is to update Product Category",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={"id","name","icon","description"},
  *             @OA\Property(property="id", type="number", format="number",example="1"),
  *             @OA\Property(property="name", type="string", format="string",example="car"),
  *   *  *    @OA\Property(property="icon", type="string", format="string",example="fa fa-- tui halua kha"),
  *             @OA\Property(property="description", type="string", format="string",example="description"),

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

 public function updateProductCategory(ProductCategoryUpdateRequest $request)
 {

     try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
         if(!$request->user()->hasPermissionTo('product_category_update')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }
         $updatableData = $request->validated();



             $product_category  =  tap(ProductCategory::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                 'name',
                 'image',
                 'icon',
                 "description",

             ])->toArray()
             )
                 // ->with("somthing")

                 ->first();
                 if(!$product_category) {

                    return response()->json([
                        "message" => "no product category found"
                    ],404);
                }

         return response($product_category, 201);
     } catch(Exception $e){
         error_log($e->getMessage());
     return $this->sendError($e,500,$request);
     }
 }
/**
     *
  * @OA\Get(
  *      path="/v1.0/product-categories/{perPage}",
  *      operationId="getProductCategories",
  *      tags={"shop_section.product_category_management"},
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


  *      summary="This method is to get  Product Categories ",
  *      description="This method is to get Product Categories",
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

 public function getProductCategories($perPage,Request $request) {
     try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
         if(!$request->user()->hasPermissionTo('product_category_view')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }



         $productCategoriesQuery = new ProductCategory();

         if(!empty($request->search_key)) {
             $productCategoriesQuery = $productCategoriesQuery->where(function($query) use ($request){
                 $term = $request->search_key;
                 $query->where("name", "like", "%" . $term . "%");
             });

         }

         if (!empty($request->start_date)) {
             $productCategoriesQuery = $productCategoriesQuery->where('created_at', ">=", $request->start_date);
         }

         if (!empty($request->end_date)) {
             $productCategoriesQuery = $productCategoriesQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
         }


         $product_categories = $productCategoriesQuery->orderByDesc("id")->paginate($perPage);

         return response()->json($product_categories, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }
 }
  /**
     *
  * @OA\Get(
  *      path="/v1.0/product-categories/single/get/{id}",
  *      operationId="getProductCategoryById",
  *      tags={"shop_section.product_category_management"},
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
  *      summary="This method is to get Product Category by id",
  *      description="This method is to get Product Category by id",
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


 public function getProductCategoryById($id,Request $request) {
     try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
         if(!$request->user()->hasPermissionTo('product_category_view')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

         $product_category =  ProductCategory::where([
             "id" => $id
         ])
         ->first()
         ;
         if(!$product_category) {
         
return response()->json([
    "message" => "no product category found"
],404);
         }

         return response()->json($product_category, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }
 }




   /**
     *
  * @OA\Get(
  *      path="/v1.0/product-categories/get/all",
  *      operationId="getAllProductCategory",
  *      tags={"basics"},
 *       security={
  *           {"bearerAuth": {}}
  *       },


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
  *      summary="This method is to get all product categories ",
  *      description="This method is to get all product categories",
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

 public function getAllProductCategory(Request $request) {
     try{

        $this->storeActivity($request, "DUMMY activity","DUMMY description");
         $productCategoriesQuery = new ProductCategory();

         if(!empty($request->search_key)) {
             $productCategoriesQuery = $productCategoriesQuery->where(function($query) use ($request){
                 $term = $request->search_key;
                 $query->where("name", "like", "%" . $term . "%");
             });

         }

         if (!empty($request->start_date)) {
             $productCategoriesQuery = $productCategoriesQuery->where('created_at', ">=", $request->start_date);
         }
         if (!empty($request->end_date)) {
             $productCategoriesQuery = $productCategoriesQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
         }

         $product_categories = $productCategoriesQuery->orderByDesc("name")->get();
         return response()->json($product_categories, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }

/**
        *
     *     @OA\Delete(
     *      path="/v1.0/product-categories/{id}",
     *      operationId="deleteProductCategoryById",
     *      tags={"shop_section.product_category_management"},
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
     *      summary="This method is to delete product category by id",
     *      description="This method is to delete product category by id",
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

    public function deleteProductCategoryById($id,Request $request) {

        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$request->user()->hasPermissionTo('product_category_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           ProductCategory::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

}
