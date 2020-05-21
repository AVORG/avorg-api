<?php
namespace App\Api\V1\Controllers\Admin;

use App\Agreement;
use App\License;
use App\Owner;
use App\Api\V1\Requests\AgreementRequest;
use App\Transformers\Admin\AgreementTransformer;
use App\Transformers\Admin\RecordingTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
/**
 * @group Legal Agreements
 *
 * Endpoints for manipulating distribution agreements.
 */
class AgreementController extends BaseController 
{
   /**
    * Get all agreements
    * 
    * @authenticated
    * @queryParam lang required string Example: en
    */
   public function all() {

      $agreement = Agreement::where($this->where)
            ->orderBy('created', 'desc')
            ->paginate(config('avorg.page_size'));

      if ($agreement->count() == 0) {
         return $this->response->errorNotFound("Agreements not found.");
      }

      return $this->response->paginator($agreement, new AgreementTransformer);
   }

   /**
    * Get one agreement
    *
    * @authenticated
    * @urlParam id required id of the agreement. Example: 1
    * @queryParam lang required string Example: en
    */
   public function one($agreementId) {

      try {
         $item = Agreement::where(['active' => 1])->findOrFail($agreementId);
         return $this->response->item($item, new AgreementTransformer);
      } catch( ModelNotFoundException $e) {
         return $this->response->errorNotFound("Agreement {$agreementId} not found.");
      }
   }

   /**
    * Get all recordings an agreements
    * 
    * @authenticated
    * @urlParam id required id of the agreement. Example: 1
    * @queryParam lang required string Example: en
    */
   public function recordings($agreementId) {

      try {

         $agreement = Agreement::where(['active' => 1])->findOrFail($agreementId);
         $recordings = $agreement->recordings();
         
         if ( $recordings->count() == 0 ) {
            return $this->response->errorNotFound("Recordings for agreement {$agreementId} not found.");
         }

         return $this->response->paginator($recordings->paginate(), new RecordingTransformer);

      } catch( ModelNotFoundException $e) {
         return $this->response->errorNotFound("Agreement {$agreementId} not found.");
      }

   }

   /**
	 * Create agreement
	 *
    * @authenticated
    * @queryParam title required string
    * @queryParam summary required string
    * @queryParam ownerId required int
    * @queryParam licenseId required int
    * @queryParam lang required string
    * @queryParam hidden required int
    * @queryParam notes required string
    * @queryParam retired required int
    */
   public function create(AgreementRequest $request) 
   {
      try {
         $agreement = new Agreement();
         $this->setFields($request, $agreement);
         $agreement->save();

         return response()->json([
            'message' => 'Agreement added.',
            'status_code' => 201
         ], 201);
      } catch (ModelNotFoundException $e) {
         return $this->response->errorNotFound($e->getMessage());
      }
   }

   /**
	 * Update agreement
	 *
    * @authenticated
    * @queryParam id required int
    * @queryParam title required string
    * @queryParam summary required string
    * @queryParam ownerId required int
    * @queryParam licenseId required int
    * @queryParam lang required string
    * @queryParam hidden required int
    * @queryParam notes required string
    * @queryParam retired required int
    */
   public function update(AgreementRequest $request) {

      try {
         $agreement = Agreement::where(['active' => 1])->findOrFail($request->id);
         $this->setFields($request, $agreement);
         $agreement->update();

         return response()->json([
            'message' => "Agreement {$request->id} updated.",
            'status_code' => 201
         ], 201);

      } catch (ModelNotFoundException $e) {
         return $this->response->errorNotFound("Agreement term {$request->id} not found.");
      }
   }
   /**
	 * Delete agreement
	 *
    * @authenticated
    * @queryParam id required int
    */
   public function delete(AgreementRequest $request) {

      try {
         $agreement = Agreement::where(['active' => 1])->findOrFail($request->id);

         if (!$agreement->releases()->exists()) {

            $agreement->active = 0;
            $agreement->save();

            return response()->json([
               'message' => "Agreement term {$request->id} deleted.",
               'status_code' => 201
            ], 201);

         } else {
            return $this->response->errorNotFound("Agreement term {$request->id} is referenced in another table thus can not be deleted.");
         }
      } catch (ModelNotFoundException $e) {
         return $this->response->errorNotFound("Agreement term {$request->id} not found.");
      }
   }

   private function setFields(AgreementRequest $request, Agreement $agreement) {
      
      // Verify if ownerId and licenseId exists
      try {
         $item = Owner::where([
            'active' => 1
         ])->findOrFail($request->ownerId);
      } 
      catch (ModelNotFoundException $e) {
         $request->ownerId = 0;
      }

      try {
         $item = License::where([
            'active' => 1
         ])->findOrFail($request->licenseId);
      } 
      catch (ModelNotFoundException $e) {
         $request->licenseId = 0;
      }

      $agreement->title = $request->title;
      $agreement->summary = $request->summary;
      $agreement->ownerId = $request->ownerId;
      $agreement->licenseId = $request->licenseId;
      $agreement->lang = $request->lang;
      $agreement->hiddenBySelf = $request->hidden;
      $agreement->hidden = $request->hidden;
      $agreement->notes = $request->notes;
      $agreement->retired = $request->retired;
      $agreement->active = 1;
   }
}