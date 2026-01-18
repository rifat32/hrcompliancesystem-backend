<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Utils\ErrorUtil;

use App\Models\Business;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BusinessBackgroundImageController extends Controller
{
    use ErrorUtil;
       /**
        *
     * @OA\Post(
     *      path="/v1.0/business-background-image",
     *      operationId="updateBusinessBackgroundImage",
     *      tags={"business_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business bakground image ",
     *      description="This method is to business bakground user image",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function updateBusinessBackgroundImage(ImageUploadRequest $request)
     {
         try{

             if (!$request->user()->hasPermissionTo('global_business_background_image_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

             $insertableData = $request->validated();

             $location =  config("setup-config.business_background_image_location");

             $new_file_name = time() . "_" ."business_background_image.jpeg";

             $insertableData["image"]->move(public_path($location), $new_file_name);


// Update the desired key in the configuration
config(["setup-config.business_background_image_location_full" => $location . "/" . $new_file_name]);

// Save the updated configuration to the file
File::put(config_path('setup-config.php'), '<?php return ' . var_export(config('setup-config'), true) . ';');









             return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


         } catch(Exception $e){
         return $this->sendError($e);
         }
     }


          /**
        *
     * @OA\Post(
     *      path="/v1.0/business-background-image/by-user",
     *      operationId="updateBusinessBackgroundImageByUser",
     *      tags={"business_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store business bakground image by owner ",
     *      description="This method is to business bakground user image by owner",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function updateBusinessBackgroundImageByUser(ImageUploadRequest $request)
     {
         try{




             $insertableData = $request->validated();

             $location =  config("setup-config.business_background_image_location");


             $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());
             $insertableData["image"]->move(public_path($location), $new_file_name);


             User::where([
                "id" => $request->user()->id
             ])
             ->update([
                "background_image" => ("/".$location."/".$new_file_name)
             ]);










             return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


         } catch(Exception $e){

         return $this->sendError($e);
         }
     }



      /**
        *
     * @OA\Get(
     *      path="/v1.0/business-background-image",
     *      operationId="getBusinessBackgroundImage",
     *      tags={"business_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get business background image",
     *      description="This method is to get business background image",
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

    public function getBusinessBackgroundImage(Request $request) {

        try{


            if (!$request->user()->hasPermissionTo('global_business_background_image_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $image = ("/".  config("setup-config.business_background_image_location_full"));




            return response()->json($image, 200);
        } catch(Exception $e){

        return $this->sendError($e);
        }

    }


}
