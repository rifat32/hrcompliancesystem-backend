<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\EmailLogUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Business;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportImportController extends Controller
{
    use ErrorUtil, BusinessUtil, DiscountUtil, BasicUtil, EmailLogUtil;
   /**
 * @OA\Post(
 *      path="/v1.0/businesses-import",
 *      operationId="importBusiness",
 *      tags={"business_management"},
 *      security={
 *          {"bearerAuth": {}}
 *      },
 *      summary="This method is to import business data",
 *      description="This method is to import business data from a JSON file",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\MediaType(
 *              mediaType="multipart/form-data",
 *              @OA\Schema(
 *                  @OA\Property(
 *                      property="file",
 *                      description="JSON file to import",
 *                      type="string",
 *                      format="binary"
 *                  )
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Unprocessable Content",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *          @OA\JsonContent()
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Bad Request",
 *          @OA\JsonContent()
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Not Found",
 *          @OA\JsonContent()
 *      )
 * )
 */
public function importBusiness(Request $request)
{
    try {
        // Permission check
        if (!$request->user()->hasPermissionTo('business_import')) {
            return response()->json([
                "message" => "You cannot perform this action."
            ], 401);
        }

        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        // Get the uploaded file
        $file = $request->file('file');
        $data = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON file format", 400);
        }

        DB::beginTransaction();

        // Insert the main business record first
        $newBusiness = Business::create($data['businesses'][0]);

        // Assuming the JSON has a "businesses" key with an array

        // Loop through each table data
        foreach ($data as $table => $rows) {
            if ($table == 'businesses') {
                continue; // Skip the 'businesses' table as it's already inserted
            }

            foreach ($rows as $row) {
                $row['business_id'] = $newBusiness->id; // Set the new business ID
                DB::table($table)->insert($row);
            }
        }

        DB::commit();

        return response()->json([
            "message" => "Business data imported successfully.",
            "business_id" => $newBusiness->id
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();
        return $this->sendError($e);
    }
}


        /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses-download/{id}",
     *      operationId="downloadBusinessById",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
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

     public function downloadBusinessById($id, Request $request)
     {
         try {


             if (!$request->user()->hasPermissionTo('business_view')) {
                 return response()->json([
                     "message" => "You cannot perform this action."
                 ], 401);
             }

             // Fetch the business details with access control
             $business = $this->businessOwnerCheck($id,FALSE);




             // Fetch all related data for export
             $databaseName = env('DB_DATABASE');
             $tables = DB::select('SHOW TABLES');
             $businessData = [];

             foreach ($tables as $table) {
                 $tableName = $table->{"Tables_in_$databaseName"};

                 // Fetch rows related to this business
                 $rows = DB::table($tableName)->where('business_id', $id)->get();

                 if ($rows->isNotEmpty()) {
                     $businessData[$tableName] = $rows;
                 }
             }

             // Convert data to JSON format
             $jsonData = json_encode($businessData);

             // Create a temporary file for download
             $fileName = 'business_' . $id . '_data.json';
             $filePath = storage_path('app/' . $fileName);
             File::put($filePath, $jsonData);

             // Return the file as a response
             return response()->download($filePath, $fileName)->deleteFileAfterSend(true);

         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }


}
