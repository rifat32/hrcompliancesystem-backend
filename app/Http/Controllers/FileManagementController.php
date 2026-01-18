<?php

namespace App\Http\Controllers;


use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Requests\MultipleFileUploadRequestV2;
use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\SingleFileUploadRequestV2;
use App\Http\Utils\ErrorUtil;

use App\Models\UploadedFile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class FileManagementController extends Controller
{
    use ErrorUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/files/single-file-upload",
     *      operationId="createFileSingle",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store files",
     *      description="This method is to store files",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"file"},
     *         @OA\Property(
     *             description="file to upload",
     *             property="file",
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

    public function createFileSingle(SingleFileUploadRequest $request)
    {
        try {



            $request_data = $request->validated();

            $location =  config("setup-config.temporary_files_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["file"]->getClientOriginalName());

            $request_data["file"]->move(public_path($location), $new_file_name);




            return response()->json([

                "file" => $new_file_name,
                "location" => $location,
                "full_location" => ("/" . $location . "/" . $new_file_name)
            ], 200);


        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/files/multiple-file-upload",
     *      operationId="createFileMultiple",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store multiple leave files",
     *      description="This method is to store multiple leave files",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"files[]"},
     *         @OA\Property(
     *             description="array of files to upload",
     *             property="files[]",
     *             type="array",
     *             @OA\Items(
     *                 type="file"
     *             ),
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

    public function createFileMultiple(MultipleFileUploadRequest $request)
    {
        try {


            $request_data = $request->validated();

            $location =  config("setup-config.temporary_files_location");

            $files = [];
            if (!empty($request_data["files"])) {
                foreach ($request_data["files"] as $file) {
                    $new_file_name = time() . '_' . $file->getClientOriginalName();
                    $new_file_name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $file->move(public_path($location), $new_file_name);

                    array_push($files, ("/" . $location . "/" . $new_file_name));
                }
            }

            return response()->json(["files" => $files], 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v2.0/files/single-file-upload",
     *      operationId="createFileSingleV2",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store files",
     *      description="This method is to store files",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"file"},
     *         @OA\Property(
     *             description="file to upload",
     *             property="file",
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

    public function createFileSingleV2(SingleFileUploadRequestV2 $request)
    {
        try {



            $request_data = $request->validated();



            $folder = $request_data['folder_location'];

            $locations =  config("setup-config.folder_locations");

            // Check if the folder is a valid location
            if (!in_array($folder, $locations)) {
                $valid_locations = implode(", ", $locations);
                $hint_message = "Invalid Folder Location. Please choose one of the following valid locations: $valid_locations";
                throw new Exception($hint_message, 403);
            }



            // Ensure the folder exists or create it
            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder);
            }

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            // Generate a unique file name
            $createdBy = auth()->user()->id;
            $employeeId = !empty($request_data['user_id']) ? $request_data['user_id'] : 0;

            // Replace spaces in the original file name with underscores
            $fileNameWithoutSpaces = str_replace(' ', '_', $originalName);

            // Generate the new file name using timestamp, user_id, employee_id, and modified original file name
            $newFileName = time() . '_' . $createdBy . '_' . $employeeId . '_' . $fileNameWithoutSpaces . "_" . $request_data["is_public"];

            // Store the file in the specified folder
            $storedFilePath = $file->storeAs($folder, $newFileName . '.' . $extension);


            UploadedFile::create([
                "file_name" => $storedFilePath
            ]);

            return response()->json(['stored_file_path' => $storedFilePath], 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v2.0/files/multiple-file-upload",
     *      operationId="createFileMultipleV2",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store multiple leave files",
     *      description="This method is to store multiple leave files",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"files[]"},
     *         @OA\Property(
     *             description="array of files to upload",
     *             property="files[]",
     *             type="array",
     *             @OA\Items(
     *                 type="file"
     *             ),
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

    public function createFileMultipleV2(MultipleFileUploadRequestV2 $request)
    {
        try {


            $request_data = $request->validated();

            $folder = $request_data["folder_location"];


            $locations =  config("setup-config.folder_locations");

            // Check if the folder is a valid location
            if (!in_array($folder, $locations)) {
                $valid_locations = implode(", ", $locations);
                $hint_message = "Invalid Folder Location. Please choose one of the following valid locations: $valid_locations";
                throw new Exception($hint_message, 403);
            }


            $createdBy = auth()->user()->id;
            $employeeId = !empty($request_data['user_id']) ? $request_data['user_id'] : 0;

            // Ensure the folder exists or create it
            if (!Storage::exists($folder)) {
                Storage::makeDirectory($folder);
            }

            $files = [];

            foreach ($request_data["files"] as $file) {
                // Generate a unique file name
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Replace spaces in the original file name with underscores
                $fileNameWithoutSpaces = str_replace(' ', '_', $originalName);


                $newFileName = time() . '_' . $createdBy . '_' . $employeeId . '_' . $fileNameWithoutSpaces . "_" . $request_data["is_public"];

                // Store the file in the specified folder
                $storedFilePath = $file->storeAs($folder, $newFileName . '.' . $extension);

                // Save the file path to the database if needed
                UploadedFile::create([
                    'file_name' => $storedFilePath,

                ]);

                // Optionally, you can store the full path if needed
                // $storedFilePath = Storage::url($storedFilePath); // Example to get full URL

                // Push stored file path to array
                $files[] = $storedFilePath;
            }

            return response()->json(['files' => $files], 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/file/{filename}",
     *      operationId="getFile",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         description="filename",
     *         required=true,
     *  example="filename"
     *      ),

     *      summary="This method is to get  user ",
     *      description="This method is to get user",
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


    public function getFile($filename, Request $request)
    {
        try {


            $filenames = explode(',', $filename);


            $filenames = explode(',', $filename);
            $fileResponses = [];

            foreach ($filenames as $filename) {
                $filenameParts = explode('_', $filename);
                if (count($filenameParts) < 5) {
                    $fileResponses[] = [
                        "filename" => $filename,
                        "message" => "File not found",
                        "status" => 404
                    ];
                    continue;
                }

                $is_public = $filenameParts[4];

                if ($is_public == 1) {
                    $path = storage_path($filename);
                    $fileResponses[] = [
                        "filename" => $filename,
                        "file" => $path,
                        "status" => 200
                    ];
                    continue;
                }

                $createdBy = $filenameParts[1];
                $employeeId = $filenameParts[2];

                if ($employeeId == 0) {
                    if ($createdBy != auth()->user()->id) {
                        $fileResponses[] = [
                            "filename" => $filename,
                            "message" => "You don't have access to this file",
                            "status" => 404
                        ];
                        continue;
                    }
                } else {
                    $all_manager_department_ids = $this->get_all_departments_of_manager();

                    $employee = User::where([
                        "id" => $employeeId
                    ])
                        ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                        ->first();

                    if (empty($employee)) {
                        $fileResponses[] = [
                            "filename" => $filename,
                            "message" => "You don't have access to this file",
                            "status" => 404
                        ];
                        continue;
                    }
                }

                $path = storage_path($filename);
                $fileResponses[] = [
                    "filename" => $filename,
                    "file" => $path,
                    "status" => 200
                ];
            }

            $filesToReturn = [];
            foreach ($fileResponses as $fileResponse) {
                if ($fileResponse['status'] == 200) {
                    $filesToReturn[] = response()->file($fileResponse['file']);
                } else {
                    $filesToReturn[] = response()->json(
                        [
                            "filename" => $fileResponse['filename'],
                            "message" => $fileResponse['message']
                        ],
                        $fileResponse['status']
                    );
                }
            }

            return $filesToReturn;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }







}
